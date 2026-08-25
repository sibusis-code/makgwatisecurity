<?php
/**
 * Makgwati Security CMS — Auth helpers
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/media.php';

function mgw_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_samesite', 'Lax');
        session_start();
    }
}

/**
 * True once at least one admin_users row exists. Multi-admin, DB-backed
 * (replaces the old single-file admin/data/auth.json).
 */
function is_setup_done(): bool {
    try {
        return (int) db()->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function is_logged_in(): bool {
    mgw_session_start();
    return !empty($_SESSION['mgw_auth']) && $_SESSION['mgw_auth'] === true;
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function csrf_token(): string {
    mgw_session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_verify(string $token): bool {
    mgw_session_start();
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/**
 * Sanitise filename: keep only safe characters, strip path traversal.
 */
function safe_filename(string $name): string {
    $name = basename($name);
    $name = preg_replace('/[^\w\s.\-]/u', '', $name);
    $name = preg_replace('/\s+/', '_', $name);
    return $name ?: 'upload_' . time();
}

/**
 * Validate an uploaded file is a real image or video (by MIME + extension).
 */
function validate_upload(array $file, string $type = 'image'): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'msg' => upload_error_message((int) $file['error'], $type)];
    }
    $ext   = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $limit = effective_limit($type);
    if ($type === 'image') {
        if (!in_array($ext, ALLOWED_IMAGE_EXTS, true)) {
            return ['ok' => false, 'msg' => 'Only JPG, PNG, or WEBP images are allowed.'];
        }
        if ($file['size'] > $limit) {
            return ['ok' => false, 'msg' =>
                'That image is ' . format_bytes((int) $file['size']) . '. The maximum is '
                . format_bytes($limit) . '.'];
        }
        // Verify it is actually an image
        if (!@getimagesize($file['tmp_name'])) {
            return ['ok' => false, 'msg' => 'File is not a valid image.'];
        }
    } else {
        if (!in_array($ext, ALLOWED_VIDEO_EXTS, true)) {
            return ['ok' => false, 'msg' => 'Only MP4, MOV, or WEBM videos are allowed.'];
        }
        if ($file['size'] > $limit) {
            return ['ok' => false, 'msg' =>
                'That video is ' . format_bytes((int) $file['size']) . '. The maximum is '
                . format_bytes($limit) . '. Please compress it first — a free tool like '
                . 'HandBrake or the "Compress Video" option on your phone will shrink it '
                . 'without a visible loss in quality.'];
        }
        // Basic MIME check for video
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowed_mimes = ['video/mp4', 'video/quicktime', 'video/webm', 'application/octet-stream'];
        if (!in_array($mime, $allowed_mimes, true)) {
            return ['ok' => false, 'msg' => 'File MIME type not allowed: ' . htmlspecialchars($mime)];
        }
    }
    return ['ok' => true, 'msg' => '', 'ext' => $ext];
}

/**
 * Read video metadata JSON for a folder.
 */
function read_video_meta(string $folder_path): array {
    $f = rtrim($folder_path, '/\\') . DIRECTORY_SEPARATOR . 'meta.json';
    if (!file_exists($f)) return [];
    $data = json_decode(file_get_contents($f), true);
    return is_array($data) ? $data : [];
}

/**
 * Write video metadata JSON for a folder.
 */
function write_video_meta(string $folder_path, array $meta): void {
    $f = rtrim($folder_path, '/\\') . DIRECTORY_SEPARATOR . 'meta.json';
    file_put_contents($f, json_encode(array_values($meta), JSON_PRETTY_PRINT));
}
