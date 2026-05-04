<?php
/**
 * VIP Protection — Dynamic gallery & videos
 * Images/videos are read from their folders automatically.
 * Manage content via /admin/
 */

$SITE_ROOT = __DIR__ . DIRECTORY_SEPARATOR;

// Gallery categories: display label => folder path (relative to site root)
$gallery_categories = [
    'Corporate Events'      => 'vip/CorporateEvents',
    'Private Clients'       => 'vip/gallery/private-clients',
    'Private Assignments'   => 'vip/private-assignments',
    'VIP Portfolio'         => 'vip',
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

// Video folders: display label => folder path
$video_folders = [
    'Private Escort Projects' => 'vip/PrivateEscort',
    'Shooting Range Videos'   => 'vip/ShootingRange',
];

// Helper: get all images from a folder
function get_gallery_images(string $folder_rel, string $site_root): array {
    $dir = rtrim($site_root, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $folder_rel);
    if (!is_dir($dir)) return [];
    $imgs = [];
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,webp}', GLOB_BRACE) as $f) {
        $imgs[] = basename($f);
    }
    return $imgs;
}

// Helper: get video metadata
function get_video_meta(string $folder_path): array {
    $f = rtrim($folder_path, '/\\') . DIRECTORY_SEPARATOR . 'meta.json';
    if (!file_exists($f)) return [];
    $data = json_decode(file_get_contents($f), true);
    return is_array($data) ? $data : [];
}

// Helper: get all videos from a folder
function get_folder_videos(string $folder_rel, string $site_root): array {
    $dir = rtrim($site_root, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $folder_rel);
    if (!is_dir($dir)) return [];
    $vids = [];
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*.{mp4,mov,webm}', GLOB_BRACE) as $f) {
        $vids[] = basename($f);
    }
    return $vids;
}

// Build gallery output
$gallery_html = '';
foreach ($gallery_categories as $cat_label => $cat_folder) {
    $images = get_gallery_images($cat_folder, $SITE_ROOT);
    if (empty($images)) continue;  // skip empty categories
    $gallery_html .= '<div class="gallery-category">' . "\n";
    $gallery_html .= '<h3>' . htmlspecialchars($cat_label) . '</h3>' . "\n";
    $gallery_html .= '<div class="gallery-images">' . "\n";
    foreach ($images as $img) {
        $src = htmlspecialchars($cat_folder . '/' . rawurlencode($img));
        $alt = htmlspecialchars($cat_label . ' — ' . pathinfo($img, PATHINFO_FILENAME));
        $gallery_html .= '<img src="' . $src . '" alt="' . $alt . '" loading="lazy">' . "\n";
    }
    $gallery_html .= '</div></div>' . "\n";
}

// Build video output — all video folders combined
$video_html = '';
foreach ($video_folders as $folder_label => $vid_folder) {
    $folder_abs = rtrim($SITE_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $vid_folder);
    $video_files = get_folder_videos($vid_folder, $SITE_ROOT);
    if (empty($video_files)) continue;
    $meta     = get_video_meta($folder_abs);
    $meta_map = [];
    foreach ($meta as $m) $meta_map[$m['file'] ?? ''] = $m;

    foreach ($video_files as $vid_file) {
        $m     = $meta_map[$vid_file] ?? [];
        $title = htmlspecialchars($m['title'] ?? pathinfo($vid_file, PATHINFO_FILENAME));
        $date  = htmlspecialchars($m['date'] ?? '');
        $desc  = htmlspecialchars($m['description'] ?? 'Professional security assignment by the Makgwati Security team.');
        $src   = htmlspecialchars($vid_folder . '/' . rawurlencode($vid_file));
        $video_html .= <<<HTML
<div class="project-card">
    <div class="project-video">
        <video controls preload="none">
            <source src="{$src}" type="video/mp4">
            Your browser does not support video playback.
        </video>
    </div>
    <div class="project-details">
        <h3 class="project-title">{$title}</h3>
        <div class="project-meta">
            <span class="project-date"><i class="fas fa-calendar-check"></i> {$date}</span>
        </div>
        <p class="project-summary">{$desc}</p>
    </div>
</div>
HTML;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIP Protection — Makgwati Security | Elite Close Protection &amp; Gallery</title>
    <meta name="description" content="Makgwati Security VIP Protection — elite close protection, executive escort, and event security. View our gallery of real assignments.">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.html" class="nav-logo-link">
                <div class="nav-logo">
                    <div class="logo-icon"><img src="images/logo.png" alt="Makgwati Security"></div>
                    <div class="logo-text"><span class="logo-main">MAKGWATI</span><span class="logo-sub">SECURITY</span></div>
                </div>
            </a>
            <ul class="nav-menu">
                <li><a href="index.html" class="nav-link">Home</a></li>
                <li><a href="services.html" class="nav-link">Services</a></li>
                <li><a href="training.html" class="nav-link">Training</a></li>
                <li><a href="vipprotection.php" class="nav-link active">VIP Protection</a></li>
                <li><a href="contact.html" class="nav-link">Contact</a></li>
                <li><a href="contact.html" class="nav-link nav-cta">Get a Quote</a></li>
            </ul>
            <div class="nav-toggle"><span class="bar"></span><span class="bar"></span><span class="bar"></span></div>
        </div>
    </nav>

    <!-- Page Hero -->
    <section class="page-hero">
        <div class="page-hero-content">
            <div class="breadcrumb"><a href="index.html">Home</a><i class="fas fa-chevron-right"></i><span>VIP Protection</span></div>
            <div class="section-tag">Elite Close Protection</div>
            <h1>VIP <span>Protection &amp; Escort</span></h1>
            <p>Discreet, professional close protection for executives, public figures, and high-value clients. Gallery updated as new assignments are completed.</p>
        </div>
    </section>

    <!-- VIP Enquiry -->
    <section class="vip-enquiry">
        <div class="container">
            <div class="lead-cta-inner" style="margin:0 auto; max-width:860px;">
                <div class="lead-cta-header">
                    <div class="section-tag">Request VIP Protection</div>
                    <h2>Secure Your <span>VIP Assignment</span></h2>
                    <p>Contact us to arrange close protection, executive escort, or event security. We respond on WhatsApp within minutes.</p>
                </div>
                <form class="lead-form" id="vipLeadForm">
                    <input type="text" id="v_name" placeholder="Your Full Name *" required>
                    <input type="tel" id="v_phone" placeholder="Phone Number *" required>
                    <input type="email" id="v_email" placeholder="Email Address">
                    <select id="v_service">
                        <option value="">Type of VIP Protection *</option>
                        <option>Executive / Close Protection</option>
                        <option>Corporate Event Security</option>
                        <option>Secure Transportation</option>
                        <option>Private Client Escort</option>
                        <option>Multi-Day Assignment</option>
                        <option>International / High-Risk</option>
                        <option>General Enquiry</option>
                    </select>
                    <input type="text" id="v_date" placeholder="Event / Assignment Date (if known)">
                    <textarea id="v_message" placeholder="Describe your requirements — location, duration, number of officers needed, any specific threats or risks..." class="lead-form-full"></textarea>
                    <div class="lead-form-full">
                        <button type="button" class="btn-submit-lead" onclick="submitLeadForm('vip')">
                            <i class="fab fa-whatsapp"></i> Request VIP Quote via WhatsApp
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Photo Gallery (dynamic) -->
    <?php if ($gallery_html): ?>
    <section class="gallery-section">
        <div class="container">
            <div class="section-header">
                <div class="section-tag">Assignment Gallery</div>
                <h2>Our Team <span>In Action</span></h2>
                <p>Real assignments. Real professionalism. Gallery updated as new photos are added.</p>
                <div class="gold-line" style="margin-left:0;"></div>
            </div>
            <div class="gallery-grid">
                <?= $gallery_html ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Project Videos (dynamic) -->
    <?php if ($video_html): ?>
    <section class="projects-section">
        <div class="container">
            <div class="section-header">
                <div class="section-tag">Assignment Videos</div>
                <h2>Elite Private <span>Escort Projects</span></h2>
                <p>Recent high-profile assignments showcasing our team's expertise in private client protection.</p>
            </div>
            <div class="projects-grid">
                <?= $video_html ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Lead CTA -->
    <section class="lead-cta">
        <div class="container">
            <div class="lead-cta-inner">
                <div class="lead-cta-header">
                    <div class="section-tag">Book Now</div>
                    <h2>Need Elite <span>VIP Protection?</span></h2>
                    <p>Speak to our team today. We'll design a custom protection plan for your needs.</p>
                </div>
                <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
                    <a href="https://wa.me/27790260098?text=I%20need%20VIP%20Protection%20services%20from%20Makgwati%20Security" target="_blank" class="btn btn-whatsapp btn" style="font-size:1rem; padding:0.9rem 2rem;">
                        <i class="fab fa-whatsapp"></i> WhatsApp Us Now
                    </a>
                    <a href="contact.html" class="btn btn-primary" style="font-size:1rem; padding:0.9rem 2rem;">
                        <i class="fas fa-envelope"></i> Send an Enquiry
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="logo-icon"><img src="images/logo.png" alt="Makgwati Security"></div>
                    <div class="logo-text" style="margin-top:0.5rem;"><span class="logo-main">MAKGWATI</span><span class="logo-sub">SECURITY</span></div>
                    <p class="footer-desc">Elite security solutions for individuals, businesses, and organisations across South Africa. PSIRA registered and fully certified.</p>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="index.html"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="services.html"><i class="fas fa-chevron-right"></i> Our Services</a></li>
                        <li><a href="training.html"><i class="fas fa-chevron-right"></i> Training</a></li>
                        <li><a href="vipprotection.php"><i class="fas fa-chevron-right"></i> VIP Protection</a></li>
                        <li><a href="contact.html"><i class="fas fa-chevron-right"></i> Contact</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>VIP Services</h4>
                    <ul class="footer-links">
                        <li><a href="vipprotection.php"><i class="fas fa-chevron-right"></i> Close Protection</a></li>
                        <li><a href="vipprotection.php"><i class="fas fa-chevron-right"></i> Executive Escort</a></li>
                        <li><a href="vipprotection.php"><i class="fas fa-chevron-right"></i> Corporate Events</a></li>
                        <li><a href="vipprotection.php"><i class="fas fa-chevron-right"></i> Secure Transport</a></li>
                        <li><a href="vipprotection.php"><i class="fas fa-chevron-right"></i> Private Clients</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Head Office</h4>
                    <div class="footer-contact-item"><i class="fas fa-user"></i> Ally</div>
                    <div class="footer-contact-item"><i class="fas fa-phone"></i><a href="tel:0150012295">015 001 2295</a></div>
                    <div class="footer-contact-item"><i class="fab fa-whatsapp"></i><a href="https://wa.me/27790260098" target="_blank">079 026 0098</a></div>
                    <div class="footer-contact-item" style="margin-top:0.5rem;"><i class="fas fa-certificate"></i> PSIRA: 4464345</div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Makgwati Security. All rights reserved.</p>
                <p>Designed by <a href="https://www.mplai.co.za" target="_blank" rel="noopener">MPL AI</a></p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
    <script src="chatbot.js"></script>
</body>
</html>
