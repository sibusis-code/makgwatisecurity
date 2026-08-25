<?php
/**
 * VIP Protection — Dynamic gallery & videos
 * Images/videos are read from their folders automatically.
 * Manage content via /admin/
 */

require_once __DIR__ . '/inc/site-data.php';
$current_page = 'vip';
$footer_mode  = 'vip';

// Encode only the filename portion of a relative path (folders never
// contain characters that need encoding; uploaded filenames often do).
function encode_media_path(string $rel): string {
    $parts = explode('/', $rel);
    $file = array_pop($parts);
    $parts[] = rawurlencode($file);
    return implode('/', $parts);
}

// Build gallery output — grouped by category, matches the admin CMS
$gallery_html = '';
try {
    $rows = db()->query("SELECT * FROM gallery_media WHERE media_type = 'image' ORDER BY category ASC, sort_order ASC, id ASC")->fetchAll();
    $byCategory = [];
    foreach ($rows as $r) $byCategory[$r['category']][] = $r;
    foreach ($byCategory as $cat_label => $images) {
        $gallery_html .= '<div class="gallery-category">' . "\n";
        $gallery_html .= '<h3><span>' . htmlspecialchars($cat_label) . '</span><span class="gallery-count">' . count($images) . '</span></h3>' . "\n";
        $gallery_html .= '<div class="gallery-images">' . "\n";
        foreach ($images as $img) {
            $src = htmlspecialchars(encode_media_path($img['file_path']));
            $alt = htmlspecialchars($img['title'] ?: ($cat_label . ' — ' . pathinfo($img['file_path'], PATHINFO_FILENAME)));
            $gallery_html .= '<img src="' . $src . '" alt="' . $alt . '" loading="lazy">' . "\n";
        }
        $gallery_html .= '</div></div>' . "\n";
    }
} catch (Throwable $e) { /* leave $gallery_html empty — section is hidden below */ }

// Build video output — all categories combined, matches the admin CMS
$video_html = '';
try {
    $rows = db()->query("SELECT * FROM gallery_media WHERE media_type = 'video' ORDER BY sort_order ASC, id ASC")->fetchAll();
    foreach ($rows as $r) {
        $title = htmlspecialchars($r['title'] ?: pathinfo($r['file_path'], PATHINFO_FILENAME));
        $date  = htmlspecialchars($r['event_date'] ?? '');
        $desc  = htmlspecialchars($r['description'] ?: 'Professional security assignment by the Makgwati Security team.');
        $src   = htmlspecialchars(encode_media_path($r['file_path']));
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
} catch (Throwable $e) { /* leave $video_html empty — section is hidden below */ }
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
    <?php include __DIR__ . '/inc/nav.php'; ?>

    <!-- Page Hero -->
    <section class="page-hero">
        <div class="page-hero-content">
            <div class="breadcrumb"><a href="index.php">Home</a><i class="fas fa-chevron-right"></i><span>VIP Protection</span></div>
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
                <h2>Operational <span>Video Highlights</span></h2>
                <p>A curated overview of escort, event, and field operations captured from recent assignments.</p>
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
                    <a href="<?= htmlspecialchars(wa_link(setting('whatsapp_number'), 'I need VIP Protection services from Makgwati Security')) ?>" target="_blank" class="btn btn-whatsapp btn" style="font-size:1rem; padding:0.9rem 2rem;">
                        <i class="fab fa-whatsapp"></i> WhatsApp Us Now
                    </a>
                    <a href="contact.php" class="btn btn-primary" style="font-size:1rem; padding:0.9rem 2rem;">
                        <i class="fas fa-envelope"></i> Send an Enquiry
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
