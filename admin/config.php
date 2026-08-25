<?php
/**
 * Makgwati Security CMS — Configuration
 */

define('SITE_ROOT',  dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('DATA_DIR',   __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR);
define('AUTH_FILE',  DATA_DIR . 'auth.json');
define('SESSION_NAME', 'mgw_admin_sess');

// MySQL connection — loaded from the .env file at the site root (never
// commit real credentials into this PHP file). Copy .env.example to
// .env and fill in your values; db.php will throw a clear error if a
// page tries to connect before these are set.
require_once __DIR__ . '/env.php';
load_env(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');
define('DB_HOST', env('DB_HOST'));
define('DB_NAME', env('DB_NAME'));
define('DB_USER', env('DB_USER'));
define('DB_PASS', env('DB_PASS'));

// Max upload sizes. These are policy caps only — the server's own
// upload_max_filesize / post_max_size are usually lower on shared hosting, and
// effective_limit() in media.php enforces whichever is smaller. Videos are
// capped low on purpose: every play streams the whole file off the hosting
// bandwidth quota, and most visitors are on mobile data.
define('MAX_IMAGE_SIZE', 10 * 1024 * 1024);   // 10 MB before compression
define('MAX_VIDEO_SIZE', 40 * 1024 * 1024);   // 40 MB

// Allowed extensions (lower-case)
define('ALLOWED_IMAGE_EXTS', ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_VIDEO_EXTS', ['mp4', 'mov', 'webm']);

// Uploaded images are resized and re-encoded on arrival so a 10 MB phone photo
// is not served to every visitor at full resolution.
define('IMAGE_MAX_DIMENSION', 1920);          // longest edge, in pixels
define('IMAGE_QUALITY', 82);                  // JPEG/WEBP quality, 0-100

// Total disk allowance across all media folders, and the per-category file cap.
// Without these the account can be filled until the whole site returns errors.
define('STORAGE_QUOTA', 2 * 1024 * 1024 * 1024);  // 2 GB
define('MAX_FILES_PER_FOLDER', 60);

// Directories counted towards STORAGE_QUOTA (relative to the site root).
define('MEDIA_DIRS', ['vip', 'images', 'blog']);

// Gallery categories: label => path relative to site root
$GALLERY_CATEGORIES = [
    'Corporate Events'      => 'vip/CorporateEvents',
    'Private Clients'       => 'vip/gallery/private-clients',
    'Private Assignments'   => 'vip/private-assignments',
    'Secure Transportation' => 'vip/gallery/secure-transport',
    'VIP Transportation'    => 'vip/gallery/vip-transport',
    'Special Assignments'   => 'vip/gallery/special-assignments',
    'Executive Protection'  => 'vip/gallery/executive-protection',
    'Corporate Security'    => 'vip/gallery/corporate-security',
    'Event Security Teams'  => 'vip/gallery/event-security',
    'Protection Details'    => 'vip/gallery/protection-details',
    'On-Site Operations'    => 'vip/gallery/on-site-operations',
    'Shooting Range'        => 'vip/gallery/shooting-range',
];

// Video folders: label => path relative to site root
$VIDEO_FOLDERS = [
    'Corporate Event Videos'   => 'vip/CorporateEvents',
    'Private Assignment Videos'=> 'vip/private-assignments',
    'Private Escort Projects' => 'vip/PrivateEscort',
    'Shooting Range Videos'   => 'vip/ShootingRange',
];

// Ensure gallery subdirectories exist
foreach ($GALLERY_CATEGORIES as $folder) {
    $full = SITE_ROOT . $folder;
    if (!is_dir($full)) {
        mkdir($full, 0755, true);
    }
}
foreach ($VIDEO_FOLDERS as $folder) {
    $full = SITE_ROOT . $folder;
    if (!is_dir($full)) {
        mkdir($full, 0755, true);
    }
}
// Ensure data dir exists
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}
