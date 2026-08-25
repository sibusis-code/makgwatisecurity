<?php
/**
 * Makgwati Security CMS — Upload handler
 * Handles: image (gallery), video (project), logo, hero
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/media.php';

// A file larger than post_max_size makes PHP discard the entire request body,
// CSRF token included. Catch that BEFORE require_login() and csrf_verify(),
// otherwise a too-big upload is reported as a security failure or bounces the
// user to the login screen. Both are wrong and both waste a support call.
if (post_was_truncated()) {
    header('Location: index.php?tab=' . truncated_post_tab('gallery')
         . '&err=' . urlencode(truncated_post_message()));
    exit;
}

require_login();
global $GALLERY_CATEGORIES, $VIDEO_FOLDERS;

function insert_gallery_media(string $category, string $file_path, string $media_type, ?string $title = null, ?string $description = null, ?string $event_date = null): void {
    try {
        $stmt = db()->prepare(
            'INSERT INTO gallery_media (category, file_path, media_type, title, description, event_date, sort_order)
             VALUES (:category, :file_path, :media_type, :title, :description, :event_date,
                     (SELECT next_order FROM (SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM gallery_media WHERE category = :category2) t))'
        );
        $stmt->execute([
            'category' => $category, 'file_path' => $file_path, 'media_type' => $media_type,
            'title' => $title, 'description' => $description, 'event_date' => $event_date,
            'category2' => $category,
        ]);
    } catch (Throwable $e) {
        // DB not configured yet — the file upload still succeeds. Log it so an
        // orphaned file (on disk but invisible in the gallery) can be traced.
        error_log('[mgw-cms] gallery_media insert failed for ' . $file_path . ': ' . $e->getMessage());
    }
}

/**
 * Keep only the most recent $keep backups matching a glob, so replacing the
 * logo or hero image a hundred times does not quietly fill the disk.
 */
function prune_backups(string $glob_pattern, int $keep = 3): void {
    $files = glob($glob_pattern);
    if (!$files || count($files) <= $keep) return;
    usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
    foreach (array_slice($files, $keep) as $old) {
        @unlink($old);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}

$csrf     = $_POST['csrf'] ?? '';
$redirect = $_POST['redirect'] ?? 'index.php';

if (!csrf_verify($csrf)) {
    header('Location: index.php?err=' . urlencode('Security check failed. Please try again.')); exit;
}

$type = $_POST['type'] ?? '';
// $redirect is normally '?tab=gallery' etc, but defaults to a bare 'index.php'
// — join with the right separator so the flash message is never lost to a
// malformed URL.
$sep  = str_contains($redirect, '?') ? '&' : '?';
$ok   = function(string $msg) use ($redirect, $sep) {
    header('Location: ' . $redirect . $sep . 'ok=' . urlencode($msg)); exit;
};
$err  = function(string $msg) use ($redirect, $sep) {
    header('Location: ' . $redirect . $sep . 'err=' . urlencode($msg)); exit;
};

if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
    $err('No file was selected.');
}

// ── Image gallery upload ──
if ($type === 'image') {
    $folder = $_POST['folder'] ?? '';
    // Validate folder is in allowed list
    if (!in_array($folder, $GALLERY_CATEGORIES, true)) {
        $err('Invalid gallery category.');
    }
    $result = validate_upload($_FILES['file'], 'image');
    if (!$result['ok']) $err($result['msg']);

    $quota = quota_check($folder, (int) $_FILES['file']['size']);
    if (!$quota['ok']) $err($quota['msg']);

    $dest_dir  = SITE_ROOT . $folder;
    $safe_name = safe_filename($_FILES['file']['name']);
    // Prevent overwrite
    if (file_exists($dest_dir . DIRECTORY_SEPARATOR . $safe_name)) {
        $safe_name = pathinfo($safe_name, PATHINFO_FILENAME) . '_' . time() . '.' . $result['ext'];
    }
    $dest = $dest_dir . DIRECTORY_SEPARATOR . $safe_name;
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        $err('Could not save file. Check folder permissions.');
    }
    $shrunk   = compress_image($dest);
    $category = array_search($folder, $GALLERY_CATEGORIES, true) ?: $folder;
    insert_gallery_media($category, $folder . '/' . $safe_name, 'image');
    $ok('Photo "' . $safe_name . '" uploaded successfully!' . compression_note($shrunk));
}

// ── Video upload ──
if ($type === 'video') {
    $folder = $_POST['folder'] ?? '';
    if (!in_array($folder, $VIDEO_FOLDERS, true)) {
        $err('Invalid video folder.');
    }
    $result = validate_upload($_FILES['file'], 'video');
    if (!$result['ok']) $err($result['msg']);

    $quota = quota_check($folder, (int) $_FILES['file']['size']);
    if (!$quota['ok']) $err($quota['msg']);

    $dest_dir  = SITE_ROOT . $folder;
    $safe_name = safe_filename($_FILES['file']['name']);
    if (file_exists($dest_dir . DIRECTORY_SEPARATOR . $safe_name)) {
        $safe_name = pathinfo($safe_name, PATHINFO_FILENAME) . '_' . time() . '.' . $result['ext'];
    }
    $dest = $dest_dir . DIRECTORY_SEPARATOR . $safe_name;
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        $err('Could not save video. Check folder permissions or PHP upload_max_filesize setting.');
    }

    $category = array_search($folder, $VIDEO_FOLDERS, true) ?: $folder;
    $title       = strip_tags(trim($_POST['title'] ?? $safe_name));
    $date        = strip_tags(trim($_POST['date'] ?? ''));
    $description = strip_tags(trim($_POST['description'] ?? ''));
    insert_gallery_media($category, $folder . '/' . $safe_name, 'video', $title ?: null, $description ?: null, $date ?: null);

    $ok('Video "' . $safe_name . '" uploaded successfully!');
}

// ── Logo upload ──
if ($type === 'logo') {
    $result = validate_upload($_FILES['file'], 'image');
    if (!$result['ok']) $err($result['msg']);

    $quota = quota_check('images', (int) $_FILES['file']['size']);
    if (!$quota['ok']) $err($quota['msg']);

    $dest = SITE_ROOT . 'images' . DIRECTORY_SEPARATOR . 'logo.png';
    // Keep backup
    if (file_exists($dest)) {
        copy($dest, SITE_ROOT . 'images' . DIRECTORY_SEPARATOR . 'logo_backup_' . date('Ymd_His') . '.png');
        prune_backups(SITE_ROOT . 'images' . DIRECTORY_SEPARATOR . 'logo_backup_*.png');
    }
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        $err('Could not save logo. Check folder permissions.');
    }
    // The logo renders at 80px in the nav — 600px is ample and keeps it sharp
    // on high-DPI screens without shipping a multi-megabyte file on every page.
    $shrunk = compress_image($dest, 600);
    $ok('Logo updated successfully!' . compression_note($shrunk));
}

// ── Hero image upload ──
if ($type === 'hero') {
    $result = validate_upload($_FILES['file'], 'image');
    if (!$result['ok']) $err($result['msg']);

    $quota = quota_check('images', (int) $_FILES['file']['size']);
    if (!$quota['ok']) $err($quota['msg']);

    $dest = SITE_ROOT . 'images' . DIRECTORY_SEPARATOR . 'img9.jpg';
    if (file_exists($dest)) {
        copy($dest, SITE_ROOT . 'images' . DIRECTORY_SEPARATOR . 'img9_backup_' . date('Ymd_His') . '.jpg');
        prune_backups(SITE_ROOT . 'images' . DIRECTORY_SEPARATOR . 'img9_backup_*.jpg');
    }
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        $err('Could not save hero image. Check folder permissions.');
    }
    $shrunk = compress_image($dest);
    $ok('Hero image updated successfully!' . compression_note($shrunk));
}

$err('Unknown upload type.');
