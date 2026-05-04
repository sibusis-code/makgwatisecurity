<?php
/**
 * Makgwati Security CMS — Configuration
 */

define('SITE_ROOT',  dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('DATA_DIR',   __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR);
define('AUTH_FILE',  DATA_DIR . 'auth.json');
define('SESSION_NAME', 'mgw_admin_sess');

// Max upload sizes
define('MAX_IMAGE_SIZE', 10 * 1024 * 1024);   // 10 MB
define('MAX_VIDEO_SIZE', 300 * 1024 * 1024);  // 300 MB

// Allowed extensions (lower-case)
define('ALLOWED_IMAGE_EXTS', ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_VIDEO_EXTS', ['mp4', 'mov', 'webm']);

// Gallery categories: label => path relative to site root
$GALLERY_CATEGORIES = [
    'Corporate Events'      => 'vip/CorporateEvents',
    'Private Clients'       => 'vip/gallery/private-clients',
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
