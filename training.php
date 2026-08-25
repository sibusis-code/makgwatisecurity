<?php
require_once __DIR__ . '/inc/site-data.php';
$current_page = 'training';
$footer_mode  = 'courses';

$courses = get_training_courses();
$grouped = [];
foreach ($courses as $c) {
    $grouped[$c['category']][] = $c;
}
$category_icons = [
    'Security Grade Courses'      => 'fa-shield-alt',
    'Firearm Competency Training' => 'fa-crosshairs',
];
function course_wa_text(string $name): string {
    return 'I want to enroll for ' . $name . ' at Makgwati Security';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PSIRA Training &amp; Courses — Makgwati Security | Firearm &amp; Security Grades</title>
    <meta name="description" content="PSIRA registered training programs: Security Grade A, B, EDC, CIT, Reaction Unit, and Firearm Licenses (Handgun, Shotgun, Rifle). Enroll today.">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/inc/nav.php'; ?>

    <!-- Page Hero -->
    <section class="page-hero">
        <div class="page-hero-content">
            <div class="breadcrumb"><a href="index.php">Home</a><i class="fas fa-chevron-right"></i><span>Training</span></div>
            <div class="section-tag">PSIRA Accredited</div>
            <h1>Security <span>Training &amp; Courses</span></h1>
            <p>Industry-leading PSIRA certified training programs. Equip yourself with the skills and credentials to excel in the security sector.</p>
        </div>
    </section>

    <!-- Training -->
    <section class="training-page">
        <div class="container">

            <!-- Registration Banner -->
            <div class="reg-banner">
                <div class="reg-banner-label"><i class="fas fa-certificate" style="margin-right:6px;"></i> Official Accreditation &amp; Registration Numbers</div>
                <div class="reg-items">
                    <div class="reg-item"><span class="reg-label">PSIRA Registration</span><span class="reg-number"><?= htmlspecialchars(setting('psira_number')) ?></span></div>
                    <div class="reg-item"><span class="reg-label">Training Number</span><span class="reg-number"><?= htmlspecialchars(setting('training_number')) ?></span></div>
                    <div class="reg-item"><span class="reg-label">SAPS Number</span><span class="reg-number"><?= htmlspecialchars(setting('saps_number')) ?></span></div>
                    <div class="reg-item"><span class="reg-label">PFTC Number</span><span class="reg-number"><?= htmlspecialchars(setting('pftc_number')) ?></span></div>
                </div>
            </div>

            <?php foreach ($grouped as $category => $categoryCourses): ?>
            <div class="training-category">
                <div class="training-cat-header">
                    <i class="fas <?= htmlspecialchars($category_icons[$category] ?? 'fa-graduation-cap') ?>"></i>
                    <h3><?= htmlspecialchars($category) ?></h3>
                </div>
                <div class="training-cards-grid">
                    <?php foreach ($categoryCourses as $c): ?>
                    <div class="training-card">
                        <h4><?= htmlspecialchars($c['name']) ?></h4>
                        <p><?= htmlspecialchars($c['description']) ?></p>
                        <div class="training-card-footer">
                            <span class="training-price">R<?= number_format((float)$c['price'], 0) ?></span>
                            <a href="<?= htmlspecialchars(wa_link(setting('whatsapp_number'), course_wa_text($c['name']))) ?>" target="_blank" class="enroll-btn"><i class="fab fa-whatsapp"></i> Enroll Now</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
    </section>

    <!-- Enrollment CTA -->
    <section class="lead-cta">
        <div class="container">
            <div class="lead-cta-inner">
                <div class="lead-cta-header">
                    <div class="section-tag">Enroll Today</div>
                    <h2>Ready to Start Your <span>Security Career?</span></h2>
                    <p>Fill in your details and our training coordinators will contact you on WhatsApp to confirm your enrollment.</p>
                </div>
                <form class="lead-form" id="trainingLeadForm">
                    <input type="text" id="t_name" placeholder="Your Full Name *" required>
                    <input type="tel" id="t_phone" placeholder="Phone Number *" required>
                    <input type="email" id="t_email" placeholder="Email Address">
                    <select id="t_service">
                        <option value="">Select Course *</option>
                        <?php foreach ($courses as $c): ?>
                            <option><?= htmlspecialchars($c['name']) ?> — R<?= number_format((float)$c['price'], 0) ?></option>
                        <?php endforeach; ?>
                        <option>Multiple Courses — Enquire</option>
                    </select>
                    <input type="text" id="t_location" placeholder="Nearest Location (e.g. Jane Furse, Driekop, Mogwase)">
                    <textarea id="t_message" placeholder="Any additional questions or requirements" class="lead-form-full"></textarea>
                    <div class="lead-form-full">
                        <button type="button" class="btn-submit-lead" onclick="submitLeadForm('training')">
                            <i class="fab fa-whatsapp"></i> Send Enrollment Request via WhatsApp
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
