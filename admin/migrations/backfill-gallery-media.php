<?php
/**
 * One-time backfill: populate gallery_media from the existing vip/
 * folders + meta.json files, so switching vipprotection.php from
 * filesystem-scanning to a DB query doesn't drop any existing photo
 * or video. Idempotent — safe to re-run (skips file_paths already
 * present). CLI only; not reachable over HTTP.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../db.php';

global $GALLERY_CATEGORIES, $VIDEO_FOLDERS;
$pdo = db();

$existing = $pdo->query('SELECT file_path FROM gallery_media')->fetchAll(PDO::FETCH_COLUMN);
$existing = array_flip($existing);

$insert = $pdo->prepare(
    'INSERT INTO gallery_media (category, file_path, media_type, title, description, event_date, sort_order)
     VALUES (:category, :file_path, :media_type, :title, :description, :event_date, :sort_order)'
);

$inserted = 0;

// Images
foreach ($GALLERY_CATEGORIES as $label => $folder) {
    $dir = SITE_ROOT . $folder;
    if (!is_dir($dir)) continue;
    $order = 0;
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,webp}', GLOB_BRACE) as $f) {
        $rel = $folder . '/' . basename($f);
        $order++;
        if (isset($existing[$rel])) continue;
        $insert->execute([
            'category' => $label, 'file_path' => $rel, 'media_type' => 'image',
            'title' => null, 'description' => null, 'event_date' => null, 'sort_order' => $order,
        ]);
        $inserted++;
    }
}

// Videos (pick up any existing meta.json captions, mostly empty today)
foreach ($VIDEO_FOLDERS as $label => $folder) {
    $dir = SITE_ROOT . $folder;
    if (!is_dir($dir)) continue;
    $meta = read_video_meta($dir);
    $metaMap = [];
    foreach ($meta as $m) $metaMap[$m['file'] ?? ''] = $m;

    $order = 0;
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*.{mp4,mov,webm}', GLOB_BRACE) as $f) {
        $file = basename($f);
        $rel = $folder . '/' . $file;
        $order++;
        if (isset($existing[$rel])) continue;
        $m = $metaMap[$file] ?? [];
        $insert->execute([
            'category' => $label, 'file_path' => $rel, 'media_type' => 'video',
            'title' => $m['title'] ?? null, 'description' => $m['description'] ?? null,
            'event_date' => $m['date'] ?? null, 'sort_order' => $order,
        ]);
        $inserted++;
    }
}

echo "Backfill complete. Inserted $inserted new gallery_media row(s).\n";
