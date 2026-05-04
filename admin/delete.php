<?php
/**
 * Makgwati Security CMS — Delete handler
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
    header('Location: index.php?err=' . urlencode('Security check failed.')); exit;
}

$type   = $_POST['type']   ?? '';
$folder = $_POST['folder'] ?? '';
$file   = basename($_POST['file'] ?? '');  // basename prevents path traversal

$ok  = function(string $msg) use ($redirect) {
    header('Location: ' . $redirect . '&ok=' . urlencode($msg)); exit;
};
$err = function(string $msg) use ($redirect) {
    header('Location: ' . $redirect . '&err=' . urlencode($msg)); exit;
};

if (!$file) $err('No file specified.');

// ── Delete gallery image ──
if ($type === 'image') {
    if (!in_array($folder, $GALLERY_CATEGORIES, true)) $err('Invalid folder.');
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_EXTS, true)) $err('Invalid file type.');
    $path = SITE_ROOT . $folder . DIRECTORY_SEPARATOR . $file;
    $real = realpath($path);
    $base = realpath(SITE_ROOT . $folder);
    // Ensure resolved path is within the allowed folder
    if (!$real || !$base || strpos($real, $base) !== 0) $err('File not found or access denied.');
    unlink($real);
    $ok('Photo "' . $file . '" deleted.');
}

// ── Delete video ──
if ($type === 'video') {
    if (!in_array($folder, $VIDEO_FOLDERS, true)) $err('Invalid folder.');
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_VIDEO_EXTS, true)) $err('Invalid file type.');
    $path = SITE_ROOT . $folder . DIRECTORY_SEPARATOR . $file;
    $real = realpath($path);
    $base = realpath(SITE_ROOT . $folder);
    if (!$real || !$base || strpos($real, $base) !== 0) $err('File not found or access denied.');
    unlink($real);
    // Remove from metadata
    $meta = read_video_meta(SITE_ROOT . $folder);
    $meta = array_filter($meta, fn($m) => ($m['file'] ?? '') !== $file);
    write_video_meta(SITE_ROOT . $folder, array_values($meta));
    $ok('Video "' . $file . '" deleted.');
}

$err('Unknown delete type.');
