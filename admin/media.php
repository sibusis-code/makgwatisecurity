<?php
/**
 * Makgwati Security CMS — Media limits, storage quota, and image compression.
 *
 * Shared hosting (cPanel) caps uploads at the PHP level via upload_max_filesize
 * and post_max_size. Those caps are almost always LOWER than the limits we set
 * in config.php, and when a file exceeds post_max_size PHP silently discards
 * the whole request body — including the CSRF token — which makes a legitimate
 * upload look like a security failure. Everything in this file exists to make
 * the real limits visible and the failures honest.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// ─────────────────────────────────────────────────────────────────────
// PHP limit detection
// ─────────────────────────────────────────────────────────────────────

/**
 * Convert a php.ini shorthand size ("8M", "512K", "1G") to bytes.
 * Returns PHP_INT_MAX for unlimited (-1 / 0).
 */
function ini_bytes(string $value): int {
    $value = trim($value);
    if ($value === '' || $value === '-1' || $value === '0') {
        return PHP_INT_MAX;
    }
    $unit = strtolower($value[strlen($value) - 1]);
    $num  = (int) $value;
    switch ($unit) {
        case 'g': return $num * 1024 * 1024 * 1024;
        case 'm': return $num * 1024 * 1024;
        case 'k': return $num * 1024;
        default:  return (int) $value;
    }
}

/**
 * The largest single file this server will actually accept, regardless of what
 * config.php says. post_max_size must hold the file PLUS the other form fields,
 * so we reserve a small margin for those.
 */
function php_upload_ceiling(): int {
    static $ceiling = null;
    if ($ceiling !== null) {
        return $ceiling;
    }
    $upload = ini_bytes((string) ini_get('upload_max_filesize'));
    $post   = ini_bytes((string) ini_get('post_max_size'));
    // Reserve 512 KB of the POST budget for text fields, CSRF token, headers.
    if ($post !== PHP_INT_MAX) {
        $post = max(0, $post - (512 * 1024));
    }
    $ceiling = min($upload, $post);
    return $ceiling;
}

/**
 * The limit we actually enforce for a given media type: whichever is smaller,
 * our own policy cap or what the server can physically accept.
 */
function effective_limit(string $type): int {
    $configured = ($type === 'video') ? MAX_VIDEO_SIZE : MAX_IMAGE_SIZE;
    return min($configured, php_upload_ceiling());
}

/**
 * True when the server's own limits are stricter than our policy — meaning the
 * admin UI should show the server number, not the policy number.
 */
function server_limits_are_binding(string $type): bool {
    $configured = ($type === 'video') ? MAX_VIDEO_SIZE : MAX_IMAGE_SIZE;
    return php_upload_ceiling() < $configured;
}

// ─────────────────────────────────────────────────────────────────────
// Truncated POST detection
// ─────────────────────────────────────────────────────────────────────

/**
 * Detect the "file exceeded post_max_size" case.
 *
 * PHP throws away the entire request body when this happens, so $_POST and
 * $_FILES arrive empty while Content-Length shows a large payload was sent.
 * Must be called BEFORE any CSRF check, otherwise the user sees a misleading
 * "Security check failed" message after a long upload.
 */
function post_was_truncated(): bool {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return false;
    }
    $length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    return $length > 0 && empty($_POST) && empty($_FILES);
}

/**
 * Human-readable explanation for a truncated POST, including the size the user
 * actually tried to send and the ceiling they need to stay under.
 */
function truncated_post_message(): string {
    $sent = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    return 'That file is too large for this server. You sent about '
         . format_bytes($sent) . ', but the maximum upload size is '
         . format_bytes(php_upload_ceiling())
         . '. Please compress the file and try again.';
}

/**
 * Work out which dashboard tab to bounce a truncated upload back to. The POST
 * body is gone, so the only clue left is the referring page — fall back to
 * $default when there isn't a usable one.
 */
function truncated_post_tab(string $default = 'gallery'): string {
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref === '') return $default;
    $query = parse_url($ref, PHP_URL_QUERY);
    if (!$query) return $default;
    parse_str($query, $params);
    $tab = $params['tab'] ?? '';
    // Only echo back a tab we recognise — never reflect arbitrary input.
    $known = ['gallery', 'videos', 'logo', 'blog'];
    return in_array($tab, $known, true) ? $tab : $default;
}

/**
 * Turn a PHP upload error code into something the client can act on, instead of
 * the bare "Upload error code: 1" they used to see.
 */
function upload_error_message(int $code, string $type = 'image'): string {
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'That file is larger than this server allows. The maximum '
                 . ($type === 'video' ? 'video' : 'image') . ' size is '
                 . format_bytes(effective_limit($type))
                 . '. Please compress it and try again.';
        case UPLOAD_ERR_PARTIAL:
            return 'The upload was interrupted before it finished — this usually '
                 . 'means the connection dropped. Please try again.';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was selected.';
        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
            return 'The server could not save the file. Please contact your web '
                 . 'developer — this is a hosting configuration problem, not a '
                 . 'problem with your file.';
        case UPLOAD_ERR_EXTENSION:
            return 'The upload was blocked by the server. Please contact your web developer.';
        default:
            return 'Upload failed (error code ' . $code . '). Please try again.';
    }
}

// ─────────────────────────────────────────────────────────────────────
// Formatting
// ─────────────────────────────────────────────────────────────────────

function format_bytes(int $bytes, int $precision = 1): string {
    if ($bytes >= PHP_INT_MAX) return 'unlimited';
    if ($bytes < 1024)         return $bytes . ' B';
    $units = ['KB', 'MB', 'GB', 'TB'];
    $i   = -1;
    $val = $bytes;
    do {
        $val /= 1024;
        $i++;
    } while ($val >= 1024 && $i < count($units) - 1);
    return round($val, $precision) . ' ' . $units[$i];
}

// ─────────────────────────────────────────────────────────────────────
// Storage usage + quota
// ─────────────────────────────────────────────────────────────────────

/**
 * Total bytes used by all uploadable media directories.
 */
function storage_used(): int {
    static $total = null;
    if ($total !== null) {
        return $total;
    }
    $total = 0;
    foreach (MEDIA_DIRS as $rel) {
        $dir = SITE_ROOT . $rel;
        if (!is_dir($dir)) continue;
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($it as $file) {
            if ($file->isFile()) {
                $total += $file->getSize();
            }
        }
    }
    return $total;
}

function storage_remaining(): int {
    return max(0, STORAGE_QUOTA - storage_used());
}

function storage_percent(): float {
    if (STORAGE_QUOTA <= 0) return 0.0;
    return min(100.0, (storage_used() / STORAGE_QUOTA) * 100);
}

/**
 * Number of media files already in a folder (images + videos, ignores meta.json).
 */
function folder_file_count(string $folder_rel): int {
    $dir = SITE_ROOT . $folder_rel;
    if (!is_dir($dir)) return 0;
    $exts  = array_merge(ALLOWED_IMAGE_EXTS, ALLOWED_VIDEO_EXTS);
    $files = glob($dir . DIRECTORY_SEPARATOR . '*.{' . implode(',', $exts) . '}', GLOB_BRACE);
    return $files ? count($files) : 0;
}

/**
 * Gate an upload against the total storage quota and the per-folder file cap.
 * Returns ['ok' => bool, 'msg' => string].
 */
function quota_check(string $folder_rel, int $incoming_size): array {
    if (storage_used() + $incoming_size > STORAGE_QUOTA) {
        return ['ok' => false, 'msg' =>
            'Storage is full. This website is using ' . format_bytes(storage_used())
            . ' of its ' . format_bytes(STORAGE_QUOTA) . ' allowance, so there is only '
            . format_bytes(storage_remaining()) . ' left. Delete some old photos or videos, '
            . 'then try again.'];
    }
    $count = folder_file_count($folder_rel);
    if ($count >= MAX_FILES_PER_FOLDER) {
        return ['ok' => false, 'msg' =>
            'This category already has the maximum of ' . MAX_FILES_PER_FOLDER
            . ' files. Delete one before adding another.'];
    }
    return ['ok' => true, 'msg' => ''];
}

// ─────────────────────────────────────────────────────────────────────
// FTP sync — reconciling files on disk with gallery_media rows
// ─────────────────────────────────────────────────────────────────────

/**
 * Files copied up by FTP never pass through upload.php, so they get no
 * gallery_media row and stay invisible on the public site. This walks the
 * known gallery/video folders and reports both directions of drift:
 *
 *   'new'     — files on disk that no row points at (need importing)
 *   'missing' — rows whose file has been deleted (render as broken images)
 *
 * Category is resolved by extension, because a folder such as
 * vip/CorporateEvents is registered as both an image category and a video
 * folder under different labels.
 *
 * Returns ['new' => [], 'missing' => [], 'error' => string].
 */
function scan_unregistered_media(): array {
    global $GALLERY_CATEGORIES, $VIDEO_FOLDERS;
    $out = ['new' => [], 'missing' => [], 'error' => ''];

    try {
        $rows = db()->query('SELECT id, category, file_path, media_type FROM gallery_media')->fetchAll();
    } catch (Throwable $e) {
        $out['error'] = 'Database unavailable: ' . $e->getMessage();
        return $out;
    }

    $known = [];
    foreach ($rows as $r) {
        $known[strtolower($r['file_path'])] = true;
        $abs = SITE_ROOT . str_replace('/', DIRECTORY_SEPARATOR, $r['file_path']);
        if (!file_exists($abs)) {
            $out['missing'][] = $r;
        }
    }

    // folder path => label, split by media type
    $image_folders = array_flip($GALLERY_CATEGORIES);
    $video_folders = array_flip($VIDEO_FOLDERS);
    $folders = array_unique(array_merge(array_values($GALLERY_CATEGORIES), array_values($VIDEO_FOLDERS)));

    foreach ($folders as $folder) {
        $dir = SITE_ROOT . $folder;
        if (!is_dir($dir)) continue;
        $exts = array_merge(ALLOWED_IMAGE_EXTS, ALLOWED_VIDEO_EXTS);
        $glob = glob($dir . DIRECTORY_SEPARATOR . '*.{' . implode(',', $exts) . '}', GLOB_BRACE);
        foreach ($glob ?: [] as $f) {
            $rel = $folder . '/' . basename($f);
            if (isset($known[strtolower($rel)])) continue;

            $ext  = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            $type = in_array($ext, ALLOWED_VIDEO_EXTS, true) ? 'video' : 'image';
            $label = $type === 'video'
                ? ($video_folders[$folder] ?? null)
                : ($image_folders[$folder] ?? null);
            // A video sitting in an image-only folder (or vice versa) has no
            // label to file it under — skip rather than guess a wrong category.
            if ($label === null) continue;

            $out['new'][] = [
                'path'     => $rel,
                'abs'      => $f,
                'category' => $label,
                'type'     => $type,
                'size'     => filesize($f) ?: 0,
            ];
        }
    }

    return $out;
}

/**
 * Import scanned files into gallery_media, compressing images on the way in so
 * FTP'd media gets the same optimisation as a CMS upload.
 *
 * Files already on disk are always imported, even when they push past the
 * quota or the per-video size policy — refusing would leave them consuming
 * space while staying invisible, which is the worst of both. Breaches are
 * reported back as warnings for the operator to act on instead.
 *
 * Returns a summary: imported, compressed count, bytes saved, warnings.
 */
function import_scanned_media(array $found): array {
    $summary = ['imported' => 0, 'compressed' => 0, 'saved' => 0, 'warnings' => [], 'failed' => 0];
    if (!$found) return $summary;

    try {
        $pdo = db();
        $insert = $pdo->prepare(
            'INSERT INTO gallery_media (category, file_path, media_type, title, description, event_date, sort_order)
             VALUES (:category, :file_path, :media_type, NULL, NULL, NULL, :sort_order)'
        );
        $next_order = $pdo->prepare(
            'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM gallery_media WHERE category = :category'
        );
    } catch (Throwable $e) {
        $summary['warnings'][] = 'Database unavailable: ' . $e->getMessage();
        return $summary;
    }

    foreach ($found as $item) {
        // Compress before recording the row, so the size we report is final.
        if ($item['type'] === 'image') {
            $shrunk = compress_image($item['abs']);
            if (!empty($shrunk['ok'])) {
                $summary['compressed']++;
                $summary['saved'] += $shrunk['saved'];
            }
        } elseif ($item['size'] > MAX_VIDEO_SIZE) {
            $summary['warnings'][] = basename($item['path']) . ' is '
                . format_bytes($item['size']) . ' — over the '
                . format_bytes(MAX_VIDEO_SIZE) . ' video policy. Consider compressing it.';
        }

        try {
            $next_order->execute(['category' => $item['category']]);
            $order = (int) $next_order->fetchColumn();
            $insert->execute([
                'category'   => $item['category'],
                'file_path'  => $item['path'],
                'media_type' => $item['type'],
                'sort_order' => $order,
            ]);
            $summary['imported']++;
        } catch (Throwable $e) {
            $summary['failed']++;
            error_log('[mgw-cms] media import failed for ' . $item['path'] . ': ' . $e->getMessage());
        }
    }

    // storage_used() memoises, so re-read the total fresh after compression.
    $used = 0;
    foreach (MEDIA_DIRS as $rel) {
        $dir = SITE_ROOT . $rel;
        if (!is_dir($dir)) continue;
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($it as $f) {
            if ($f->isFile()) $used += $f->getSize();
        }
    }
    if ($used > STORAGE_QUOTA) {
        $summary['warnings'][] = 'Storage is now ' . format_bytes($used) . ', over the '
            . format_bytes(STORAGE_QUOTA) . ' allowance. Delete some media or raise STORAGE_QUOTA.';
    }

    return $summary;
}

// ─────────────────────────────────────────────────────────────────────
// Image compression
// ─────────────────────────────────────────────────────────────────────

function image_processing_available(): bool {
    return extension_loaded('gd') || extension_loaded('imagick');
}

/**
 * Resize + re-encode an image in place so a 10 MB phone photo does not get
 * served to every visitor at full resolution. Preserves the original format
 * and PNG/WEBP transparency.
 *
 * Never fatal: if no image extension is loaded, or the file cannot be decoded,
 * the original is left untouched and ['ok' => false] is returned. The upload
 * itself still succeeds — the size limits already capped it.
 */
function compress_image(string $path, int $max_dim = IMAGE_MAX_DIMENSION, int $quality = IMAGE_QUALITY): array {
    $before = @filesize($path) ?: 0;
    $fail   = ['ok' => false, 'before' => $before, 'after' => $before, 'saved' => 0];

    if (!extension_loaded('gd')) {
        return compress_image_imagick($path, $max_dim, $quality, $before) ?? $fail;
    }

    $info = @getimagesize($path);
    if (!$info) return $fail;

    [$width, $height] = $info;
    $mime = $info['mime'] ?? '';

    switch ($mime) {
        case 'image/jpeg': $src = @imagecreatefromjpeg($path); break;
        case 'image/png':  $src = @imagecreatefrompng($path);  break;
        case 'image/webp': $src = @imagecreatefromwebp($path); break;
        default: return $fail;
    }
    if (!$src) return $fail;

    // Scale down only when the image is larger than the target; never upscale.
    $scale      = min(1.0, $max_dim / max($width, $height));
    $new_width  = max(1, (int) round($width * $scale));
    $new_height = max(1, (int) round($height * $scale));

    $dst = imagecreatetruecolor($new_width, $new_height);
    if ($mime === 'image/png' || $mime === 'image/webp') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $new_width, $new_height, $transparent);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    // Write to a temp file first so a failed encode cannot destroy the upload.
    $tmp = $path . '.tmp';
    switch ($mime) {
        case 'image/jpeg': $written = @imagejpeg($dst, $tmp, $quality); break;
        // PNG takes a 0-9 compression level, not a 0-100 quality.
        case 'image/png':  $written = @imagepng($dst, $tmp, 8); break;
        case 'image/webp': $written = @imagewebp($dst, $tmp, $quality); break;
        default: $written = false;
    }
    imagedestroy($src);
    imagedestroy($dst);

    if (!$written || !file_exists($tmp)) {
        @unlink($tmp);
        return $fail;
    }

    // Keep whichever is smaller — re-encoding an already-optimised image can
    // make it bigger, and there is no point shipping the larger of the two.
    $after = filesize($tmp);
    if ($after > 0 && $after < $before) {
        @rename($tmp, $path);
        return ['ok' => true, 'before' => $before, 'after' => $after, 'saved' => $before - $after];
    }
    @unlink($tmp);
    return ['ok' => false, 'before' => $before, 'after' => $before, 'saved' => 0];
}

/**
 * Imagick fallback for hosts that ship it instead of GD.
 * Returns null when Imagick is unavailable or the file cannot be processed.
 */
function compress_image_imagick(string $path, int $max_dim, int $quality, int $before): ?array {
    if (!extension_loaded('imagick')) {
        return null;
    }
    try {
        $img = new Imagick($path);
        $img->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
        $w = $img->getImageWidth();
        $h = $img->getImageHeight();
        if (max($w, $h) > $max_dim) {
            $img->resizeImage(
                $w >= $h ? $max_dim : 0,
                $h >  $w ? $max_dim : 0,
                Imagick::FILTER_LANCZOS, 1
            );
        }
        $img->setImageCompressionQuality($quality);
        $img->stripImage(); // drop EXIF/GPS — smaller, and no location leak
        $tmp = $path . '.tmp';
        $img->writeImage($tmp);
        $img->clear();
        $img->destroy();

        $after = @filesize($tmp) ?: 0;
        if ($after > 0 && $after < $before) {
            @rename($tmp, $path);
            return ['ok' => true, 'before' => $before, 'after' => $after, 'saved' => $before - $after];
        }
        @unlink($tmp);
    } catch (Throwable $e) {
        // Leave the original in place; the upload is still valid.
    }
    return null;
}

/**
 * Append an "Optimised for web" note to a success message when the saving is
 * large enough to be worth telling the user about.
 */
function compression_note(array $result): string {
    if (empty($result['ok']) || $result['saved'] < 50 * 1024) {
        return '';
    }
    return ' Optimised for web: ' . format_bytes($result['before'])
         . ' → ' . format_bytes($result['after']) . '.';
}
