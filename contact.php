<?php
require_once __DIR__ . '/inc/site-data.php';
$current_page = 'contact';
$footer_mode  = 'branches';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us — Makgwati Security | <?= htmlspecialchars(setting('branch_count')) ?> Branch Locations</title>
    <meta name="description" content="Contact Makgwati Security at any of our <?= htmlspecialchars(setting('branch_count')) ?> branch locations across Limpopo and North West. Get a free security quote today.">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/inc/nav.php'; ?>

    <!-- Page Hero -->
    <section class="page-hero">
        <div class="page-hero-content">
            <div class="breadcrumb"><a href="index.php">Home</a><i class="fas fa-chevron-right"></i><span>Contact</span></div>
            <div class="section-tag"><?= htmlspecialchars(setting('branch_count')) ?> Locations</div>
            <h1>Get in <span>Touch With Us</span></h1>
            <p>Speak to your nearest Makgwati Security branch or send us an enquiry and our team will respond via WhatsApp within minutes.</p>
        </div>
    </section>

    <!-- Contact Page -->
    <section class="contact-page">
        <div class="container">
            <div class="contact-layout">

                <!-- Branch Contacts -->
                <div>
                    <h3 class="contact-col-title">Our Branch Locations</h3>
                    <div class="contact-branches">
                        <?php foreach (get_branches() as $b): ?>
                            <?php
                                $isHQ = !empty($b['is_head_office']);
                                $cardClass = 'branch-card' . ($isHQ ? ' head-office' : '');
                                $icon = $isHQ ? 'fa-building' : 'fa-map-marker-alt';
                            ?>
                            <div class="<?= $cardClass ?>">
                                <div class="branch-header">
                                    <i class="fas <?= $icon ?>"></i>
                                    <h4><?= htmlspecialchars($b['name']) ?></h4>
                                    <?php if ($isHQ): ?>
                                        <span class="head-badge">HQ</span>
                                    <?php elseif (!empty($b['badge'])): ?>
                                        <span class="head-badge new-badge"><?= htmlspecialchars($b['badge']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="branch-details">
                                    <?php if ($b['contact_person']): ?><div class="branch-detail"><i class="fas fa-user"></i> <?= htmlspecialchars($b['contact_person']) ?></div><?php endif; ?>
                                    <?php if ($b['whatsapp']): ?><div class="branch-detail"><i class="fab fa-whatsapp"></i><a href="<?= htmlspecialchars(wa_link($b['whatsapp'])) ?>" target="_blank" rel="noopener"><?= htmlspecialchars(preg_replace('/^27/', '0', $b['whatsapp'])) ?></a></div><?php endif; ?>
                                    <?php if ($b['phone']): ?><div class="branch-detail"><i class="fas fa-phone-alt"></i><a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $b['phone'])) ?>"><?= htmlspecialchars($b['phone']) ?></a></div><?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Contact Form -->
                <div>
                    <h3 class="contact-col-title">Send Us an Enquiry</h3>
                    <div class="contact-form-box">
                        <form class="lead-form" id="contactLeadForm">
                            <input type="text" id="c_name" placeholder="Your Full Name *" required class="lead-form-full">
                            <input type="tel" id="c_phone" placeholder="Phone Number *" required>
                            <input type="email" id="c_email" placeholder="Email Address">
                            <select id="c_service" class="lead-form-full">
                                <option value="">What Can We Help You With? *</option>
                                <option>VIP / Close Protection</option>
                                <option>Building Security</option>
                                <option>Armed Response</option>
                                <option>Event Security Management</option>
                                <option>Riot Intervention</option>
                                <option>CCTV Installation</option>
                                <option>Access Control Systems</option>
                                <option>Fire Alarm Installation</option>
                                <option>Car Guarding</option>
                                <option>Cash In Transit</option>
                                <option>Security Grade Training (A/B)</option>
                                <option>Firearm License Training</option>
                                <option>CIT Training</option>
                                <option>EDC Training</option>
                                <option>Reaction Unit Training</option>
                                <option>General Enquiry</option>
                            </select>
                            <input type="text" id="c_location" placeholder="Your Location / Nearest Branch">
                            <textarea id="c_message" placeholder="Tell us more about your security requirements..." class="lead-form-full"></textarea>
                            <div class="lead-form-full">
                                <button type="button" class="btn-submit-lead" onclick="submitLeadForm('contact')">
                                    <i class="fab fa-whatsapp"></i> Send Enquiry via WhatsApp
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

            <!-- Registration Info -->
            <div class="reg-banner">
                <div class="reg-banner-label"><i class="fas fa-certificate" style="margin-right:6px;"></i> Official Registration Details</div>
                <div class="reg-items">
                    <div class="reg-item"><span class="reg-label">PSIRA Registration</span><span class="reg-number"><?= htmlspecialchars(setting('psira_number')) ?></span></div>
                    <div class="reg-item"><span class="reg-label">Training Number</span><span class="reg-number"><?= htmlspecialchars(setting('training_number')) ?></span></div>
                    <div class="reg-item"><span class="reg-label">SAPS Number</span><span class="reg-number"><?= htmlspecialchars(setting('saps_number')) ?></span></div>
                    <div class="reg-item"><span class="reg-label">PFTC Number</span><span class="reg-number"><?= htmlspecialchars(setting('pftc_number')) ?></span></div>
                </div>
            </div>

        </div>
    </section>

    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
