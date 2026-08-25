<?php
require_once __DIR__ . '/inc/site-data.php';
$current_page = 'services';
$footer_mode  = 'services';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Services — Makgwati Security | Elite Protection Solutions</title>
    <meta name="description" content="Full-spectrum security services: VIP protection, armed response, building security, CCTV, event security, access control and more. Get a free quote today.">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/inc/nav.php'; ?>

    <!-- Page Hero -->
    <section class="page-hero">
        <div class="page-hero-content">
            <div class="breadcrumb"><a href="index.php">Home</a><i class="fas fa-chevron-right"></i><span>Services</span></div>
            <div class="section-tag">What We Offer</div>
            <h1>Elite <span>Security Services</span></h1>
            <p>Full-spectrum security solutions tailored for your unique needs — protecting people, property, and assets 24/7.</p>
        </div>
    </section>

    <!-- Services Grid -->
    <section class="services-page">
        <div class="container">
            <div class="services-full-grid">
                <?php foreach (get_services() as $s): if (empty($s['show_on_services'])) continue; ?>
                <div class="service-full-card">
                    <div class="service-card-icon"><i class="<?= htmlspecialchars($s['icon_class']) ?>"></i></div>
                    <h3><?= htmlspecialchars($s['title']) ?></h3>
                    <p><?= htmlspecialchars($s['description'] ?: $s['summary']) ?></p>
                    <?php $features = $s['features'] ? json_decode($s['features'], true) : []; ?>
                    <?php if ($features): ?>
                    <ul class="service-features">
                        <?php foreach ($features as $f): ?>
                            <li><i class="fas fa-check-circle"></i> <?= htmlspecialchars($f) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <a href="<?= htmlspecialchars(wa_link(setting('whatsapp_number'), $s['whatsapp_text'])) ?>" target="_blank" class="service-quote-btn"><i class="fab fa-whatsapp"></i> Get a Quote</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Lead CTA -->
    <section class="lead-cta">
        <div class="container">
            <div class="lead-cta-inner">
                <div class="lead-cta-header">
                    <div class="section-tag">Free Quote</div>
                    <h2>Get a <span>Custom Security Quote</span></h2>
                    <p>Tell us what you need and we'll contact you on WhatsApp with a tailored solution.</p>
                </div>
                <form class="lead-form" id="servicesLeadForm">
                    <input type="text" id="s_name" placeholder="Your Full Name *" required>
                    <input type="tel" id="s_phone" placeholder="Phone Number *" required>
                    <input type="email" id="s_email" placeholder="Email Address">
                    <select id="s_service">
                        <option value="">Select Service *</option>
                        <option>VIP / Close Protection</option>
                        <option>Building Security</option>
                        <option>Armed Response</option>
                        <option>Event Security</option>
                        <option>Riot Intervention</option>
                        <option>CCTV Installation</option>
                        <option>Access Control</option>
                        <option>Fire Alarm Systems</option>
                        <option>Car Guarding</option>
                        <option>Cash In Transit</option>
                        <option>General Enquiry</option>
                    </select>
                    <textarea id="s_message" placeholder="Describe your security needs (location, size, requirements)" class="lead-form-full"></textarea>
                    <div class="lead-form-full">
                        <button type="button" class="btn-submit-lead" onclick="submitLeadForm('services')">
                            <i class="fab fa-whatsapp"></i> Send Enquiry to WhatsApp
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
