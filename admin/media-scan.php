<?php
/**
 * Makgwati Security CMS — Media health check.
 *
 * Reconciles the files actually present on the web server against the
 * gallery_media rows that reference them. Two jobs:
 *
 *   Import  — files on the server with no database row (invisible on the site)
 *             get a row, and images are compressed on the way in.
 *   Prune   — rows whose file is not on the server (broken images on the site)
 *             get removed.
 *
 * Mainly a deploy check: after uploading the site by FTP, run this to confirm
 * every photo and video the database expects actually arrived. It is safe to
 * run repeatedly — both operations are idempotent.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/media.php';

require_login();
global $GALLERY_CATEGORIES, $VIDEO_FOLDERS;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?tab=gallery'); exit;
}

$redirect = $_POST['redirect'] ?? '?tab=gallery';
$sep      = str_contains($redirect, '?') ? '&' : '?';
$ok  = function (string $msg) use ($redirect, $sep) {
    header('Location: ' . $redirect . $sep . 'ok=' . urlencode($msg)); exit;
};
$err = function (string $msg) use ($redirect, $sep) {
    header('Location: ' . $redirect . $sep . 'err=' . urlencode($msg)); exit;
};

if (!csrf_verify($_POST['csrf'] ?? '')) {
    $err('Security check failed. Please try again.');
}

$scan = scan_unregistered_media();
if ($scan['error'] !== '') {
    $err($scan['error']);
}

$action = $_POST['action'] ?? 'import';

// ── Remove rows whose file is not on this server ──
if ($action === 'prune') {
    if (!$scan['missing']) {
        $ok('Nothing to clean up — every gallery entry has a matching file on the server.');
    }
    $deleted = 0;
    try {
        $stmt = db()->prepare('DELETE FROM gallery_media WHERE id = :id');
        foreach ($scan['missing'] as $m) {
            $stmt->execute(['id' => (int) $m['id']]);
            $deleted++;
        }
    } catch (Throwable $e) {
        $err('Could not remove the broken entries: ' . $e->getMessage());
    }
    $ok('Removed ' . $deleted . ' gallery entr' . ($deleted === 1 ? 'y' : 'ies')
        . ' whose file is missing from the server.');
}

// ── Import files that are on the server but not in the database ──
if (!$scan['new']) {
    $msg = 'Scan complete — no new files found.';
    if ($scan['missing']) {
        $msg .= ' Warning: ' . count($scan['missing']) . ' gallery entr'
              . (count($scan['missing']) === 1 ? 'y refers' : 'ies refer')
              . ' to files that are not on this server. They will show as broken on the site.';
        $err($msg);
    }
    $ok($msg . ' Everything on the server is already published.');
}

$result = import_scanned_media($scan['new']);

$msg = 'Imported ' . $result['imported'] . ' file' . ($result['imported'] === 1 ? '' : 's') . '.';
if ($result['compressed'] > 0) {
    $msg .= ' Optimised ' . $result['compressed'] . ' image'
          . ($result['compressed'] === 1 ? '' : 's')
          . ', saving ' . format_bytes($result['saved']) . '.';
}
if ($result['failed'] > 0) {
    $msg .= ' ' . $result['failed'] . ' could not be added — see the server error log.';
}
foreach ($result['warnings'] as $w) {
    $msg .= ' ' . $w;
}

if ($result['failed'] > 0 || $result['warnings']) {
    $err($msg);
}
$ok($msg);
