<?php
/**
 * Makgwati Security — deployment script.
 *
 * Uploads the site over SFTP to either the dev subdomain or the live domain.
 * Credentials come from deploy.env (git-ignored); nothing secret lives here.
 *
 *   php deploy.php                    # code only -> dev   (the usual case)
 *   php deploy.php --media            # media only -> dev  (one-time, ~222 MB)
 *   php deploy.php --all              # code + media -> dev
 *   php deploy.php --live             # code only -> live domain
 *   php deploy.php --dry-run          # list what would upload, send nothing
 *   php deploy.php --with-diagnostic  # also upload server-check.php
 *
 * Code and media are separated deliberately: media is ~222 MB and changes
 * rarely, code is ~1 MB and changes constantly. Pushing them together would
 * make every deploy take minutes for no reason.
 *
 * CLI only — never reachable over the web.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

$root = __DIR__;
chdir($root);

// ── Options ───────────────────────────────────────────────────────────
$args       = array_slice($argv, 1);
$dryRun     = in_array('--dry-run', $args, true);
$toLive     = in_array('--live', $args, true);
$wantMedia  = in_array('--media', $args, true) || in_array('--all', $args, true);
$wantCode   = !in_array('--media', $args, true);   // --media alone means media only
$withDiag   = in_array('--with-diagnostic', $args, true);

// ── Credentials ───────────────────────────────────────────────────────
$envFile = $root . '/deploy.env';
if (!file_exists($envFile)) {
    exit("deploy.env not found. Copy the template and fill in your FTP details.\n");
}
$cfg = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $cfg[trim($k)] = trim($v);
}
foreach (['FTP_HOST', 'FTP_USER', 'FTP_PASS'] as $need) {
    if (empty($cfg[$need])) exit("deploy.env is missing $need.\n");
}

$remoteDir = $toLive
    ? ($cfg['FTP_REMOTE_DIR_LIVE'] ?? 'public_html')
    : ($cfg['FTP_REMOTE_DIR'] ?? '');
if ($remoteDir === '') {
    exit("deploy.env is missing the remote directory for this target.\n");
}
$remoteDir = trim($remoteDir, '/');

// ── What never goes up ────────────────────────────────────────────────
// deploy.env holds the FTP password. server-check.php is opt-in. .git is
// hundreds of megabytes of history the web server has no use for.
$excludeDirs = ['.git', '.vscode', '.idea', 'node_modules'];
$excludeFiles = ['deploy.env', 'deploy.php', '.gitignore', '.gitattributes',
                 'Thumbs.db', 'Desktop.ini', '.DS_Store',
                 // Legacy single-admin credential file. AUTH_FILE is still
                 // defined in config.php but nothing reads it — auth is the
                 // admin_users table now. No reason to put a password hash
                 // on a public web server.
                 'auth.json'];
if (!$withDiag) {
    $excludeFiles[] = 'server-check.php';
}

// Media lives in these trees. Split out so code deploys stay fast.
$mediaDirs = ['vip', 'images', 'blog/covers'];

function is_media(string $rel, array $mediaDirs): bool {
    foreach ($mediaDirs as $m) {
        if ($rel === $m || str_starts_with($rel, $m . '/')) return true;
    }
    return false;
}

// ── Build the file list ───────────────────────────────────────────────
$files = [];
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);
foreach ($it as $path => $info) {
    $rel = str_replace('\\', '/', substr($path, strlen($root) + 1));

    // Skip excluded directories wholesale.
    $skip = false;
    foreach ($excludeDirs as $d) {
        if ($rel === $d || str_starts_with($rel, $d . '/')) { $skip = true; break; }
    }
    if ($skip || !$info->isFile()) continue;
    if (in_array(basename($rel), $excludeFiles, true)) continue;
    if (str_ends_with($rel, '.tmp')) continue;

    // Generated backups belong on the server only if they were made there.
    if (preg_match('#^images/(logo|img9)_backup_#', $rel)) continue;

    $media = is_media($rel, $mediaDirs);
    if ($media && !$wantMedia) continue;
    if (!$media && !$wantCode) continue;

    $files[$rel] = $info->getSize();
}

// .env is git-ignored but the site cannot connect without it, so it is
// uploaded explicitly rather than being picked up by the walk above.
if ($wantCode && file_exists($root . '/.env')) {
    $files['.env'] = filesize($root . '/.env');
}

ksort($files);
$total = array_sum($files);

// ── Report ────────────────────────────────────────────────────────────
$target = $toLive ? 'LIVE  ' . $cfg['FTP_HOST'] : 'DEV   ' . $cfg['FTP_HOST'];
echo "\n";
echo "  Target      : $target\n";
echo "  Remote dir  : /$remoteDir\n";
echo "  Uploading   : ", $wantCode ? 'code' : '', ($wantCode && $wantMedia ? ' + ' : ''), $wantMedia ? 'media' : '', "\n";
echo "  Files       : ", count($files), "\n";
echo "  Total size  : ", number_format($total / 1048576, 1), " MB\n";
if ($dryRun) echo "  MODE        : DRY RUN — nothing will be sent\n";
echo "\n";

if ($toLive && !$dryRun) {
    echo "  This targets the LIVE domain. Type 'deploy live' to continue: ";
    $answer = trim(fgets(STDIN));
    if ($answer !== 'deploy live') exit("  Aborted.\n");
    echo "\n";
}

if (!$files) exit("  Nothing to upload.\n");

if ($dryRun) {
    foreach ($files as $rel => $size) {
        printf("    %9s  %s\n", number_format($size / 1024, 1) . ' KB', $rel);
    }
    echo "\n  Dry run complete.\n";
    exit(0);
}

// ── Upload ────────────────────────────────────────────────────────────
// One curl invocation per batch: curl reuses the SFTP connection across all
// URLs in a single run, so batching turns hundreds of handshakes into a few.
$tmpDir = sys_get_temp_dir() . '/mgw-deploy';
if (!is_dir($tmpDir)) mkdir($tmpDir, 0700, true);

$host = $cfg['FTP_HOST'];
$user = $cfg['FTP_USER'];
$pass = $cfg['FTP_PASS'];

$batches = array_chunk($files, 40, true);
$done = 0;
$failed = [];

foreach ($batches as $i => $batch) {
    $confPath = $tmpDir . '/batch.conf';
    $conf = "--user \"$user:$pass\"\n--insecure\n--ftp-create-dirs\n--silent\n--show-error\n--fail\n";
    foreach ($batch as $rel => $size) {
        // curl needs the remote path URL-encoded per segment; filenames here
        // contain spaces and parentheses.
        $encoded = implode('/', array_map('rawurlencode', explode('/', $rel)));
        // Forward slashes only: curl treats a backslash inside a quoted config
        // value as an escape, so a Windows path silently loses its separators.
        $localPath = str_replace('\\', '/', $root) . '/' . $rel;
        $conf .= "--upload-file \"" . $localPath . "\"\n";
        $conf .= "--url \"sftp://$host/$remoteDir/$encoded\"\n";
    }
    file_put_contents($confPath, $conf);

    $out = [];
    $code = 0;
    exec('curl --config ' . escapeshellarg($confPath) . ' 2>&1', $out, $code);

    if ($code === 0) {
        $done += count($batch);
    } else {
        foreach ($batch as $rel => $size) $failed[] = $rel;
        echo "  ! batch ", $i + 1, " failed (curl exit $code)\n";
        foreach (array_slice($out, 0, 4) as $line) echo "      $line\n";
    }
    printf("  batch %d/%d — %d/%d files\n", $i + 1, count($batches), $done, count($files));
}

@unlink($tmpDir . '/batch.conf');

echo "\n";
if ($failed) {
    echo "  Uploaded ", $done, " of ", count($files), " files. ", count($failed), " failed:\n";
    foreach (array_slice($failed, 0, 15) as $f) echo "    $f\n";
    exit(1);
}

echo "  Uploaded ", $done, " files (", number_format($total / 1048576, 1), " MB) to /$remoteDir\n\n";
echo "  Next:\n";
echo "    1. Load https://", $toLive ? $cfg['FTP_HOST'] : 'dev.' . $cfg['FTP_HOST'], "/\n";
echo "    2. Check /.env returns 403 or 404, not file contents\n";
if ($wantMedia) {
    echo "    3. Log into /admin/ and run the media health check\n";
}
echo "\n";
