<?php
/**
 * Makgwati Security CMS — Upload handler
 * Handles: image (gallery), video (project), logo, hero
 */
require_once __DIR__ . '/auth.php';
require_login();
global $GALLERY_CATEGORIES, $VIDEO_FOLDERS;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}

$csrf     = $_POST['csrf'] ?? '';
$redirect = $_POST['redirect'] ?? 'index.php';

if (!csrf_verify($csrf)) {
    header('Location: index.php?err=' . urlencode('Security check failed. Please try again.')); exit;
}

$type = $_POST['type'] ?? '';
$ok   = function(string $msg) use ($redirect) {
    header('Location: ' . $redirect . '&ok=' . urlencode($msg)); exit;
};
$err  = function(string $msg) use ($redirect) {
    header('Location: ' . $redirect . '&err=' . urlencode($msg)); exit;
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
    $ok('Photo "' . $safe_name . '" uploaded successfully!');
}

// ── Video upload ──
if ($type === 'video') {
    $folder = $_POST['folder'] ?? '';
    if (!in_array($folder, $VIDEO_FOLDERS, true)) {
        $err('Invalid video folder.');
    }
    $result = validate_upload($_FILES['file'], 'video');
    if (!$result['ok']) $err($result['msg']);

    $dest_dir  = SITE_ROOT . $folder;
    $safe_name = safe_filename($_FILES['file']['name']);
    if (file_exists($dest_dir . DIRECTORY_SEPARATOR . $safe_name)) {
        $safe_name = pathinfo($safe_name, PATHINFO_FILENAME) . '_' . time() . '.' . $result['ext'];
    }
    $dest = $dest_dir . DIRECTORY_SEPARATOR . $safe_name;
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        $err('Could not save video. Check folder permissions or PHP upload_max_filesize setting.');
    }

    // Save metadata
    $meta    = read_video_meta($dest_dir);
    $meta[]  = [
        'file'        => $safe_name,
        'title'       => strip_tags(trim($_POST['title'] ?? $safe_name)),
        'date'        => strip_tags(trim($_POST['date'] ?? '')),
        'description' => strip_tags(trim($_POST['description'] ?? '')),
    ];
    write_video_meta($dest_dir, $meta);

    $ok('Video "' . $safe_name . '" uploaded successfully!');
}

// ── Logo upload ──
if ($type === 'logo') {
    $result = validate_upload($_FILES['file'], 'image');
    if (!$result['ok']) $err($result['msg']);
    $dest = SITE_ROOT . 'images' . DIRECTORY_SEPARATOR . 'logo.png';
    // Keep backup
    if (file_exists($dest)) {
        copy($dest, SITE_ROOT . 'images' . DIRECTORY_SEPARATOR . 'logo_backup_' . date('Ymd_His') . '.png');
    }
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        $err('Could not save logo. Check folder permissions.');
    }
    $ok('Logo updated successfully!');
}

// ── Hero image upload ──
if ($type === 'hero') {
    $result = validate_upload($_FILES['file'], 'image');
    if (!$result['ok']) $err($result['msg']);
    $ext  = $result['ext'];
    $dest = SITE_ROOT . 'images' . DIRECTORY_SEPARATOR . 'img9.jpg';
    if (file_exists($dest)) {
        copy($dest, SITE_ROOT . 'images' . DIRECTORY_SEPARATOR . 'img9_backup_' . date('Ymd_His') . '.jpg');
    }
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        $err('Could not save hero image. Check folder permissions.');
    }
    $ok('Hero image updated successfully!');
}

$err('Unknown upload type.');
