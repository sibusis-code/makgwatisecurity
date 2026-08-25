<?php
require_once __DIR__ . '/inc/site-data.php';
$current_page = 'home';
$footer_mode  = 'services';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Makgwati Security — Elite Security Solutions | PSIRA Registered</title>
    <meta name="description" content="Makgwati Security — South Africa's elite security solutions provider. PSIRA registered. VIP protection, armed response, CCTV, training and more. Get a free quote today.">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/inc/nav.php'; ?>

    <!-- Hero -->
    <section class="hero">
        <div class="hero-grid"></div>
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-badge"><i class="fas fa-shield-alt"></i> <?= htmlspecialchars(setting('hero_badge')) ?></div>
                <h1 class="hero-title">
                    <?= setting('hero_title') ?>
                </h1>
                <p class="hero-subtitle">
                    <?= htmlspecialchars(setting('hero_subtitle')) ?>
                </p>
                <div class="hero-buttons">
                    <a href="contact.php" class="btn btn-primary"><i class="fas fa-shield-alt"></i> Get a Free Quote</a>
                    <a href="services.php" class="btn btn-secondary">Our Services</a>
                </div>
                <div class="hero-credentials">
                    <div class="credential"><i class="fas fa-certificate"></i> PSIRA No. <?= htmlspecialchars(setting('psira_number')) ?></div>
                    <div class="credential"><i class="fas fa-award"></i> SAPS No. <?= htmlspecialchars(setting('saps_number')) ?></div>
                    <div class="credential"><i class="fas fa-check-circle"></i> PFTC No. <?= htmlspecialchars(setting('pftc_number')) ?></div>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-image-frame">
                    <img src="images/img9.jpg" alt="Makgwati Elite Security">
                    <div class="hero-image-badge">
                        <span class="big"><?= htmlspecialchars(setting('professionals_trained')) ?></span>
                        Professionals Trained
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust Bar -->
    <div class="trust-bar">
        <div class="trust-bar-inner">
            <div class="trust-item"><i class="fas fa-certificate"></i><div><strong>PSIRA Certified</strong><span>Fully Registered Provider</span></div></div>
            <div class="trust-item"><i class="fas fa-map-marker-alt"></i><div><strong><?= htmlspecialchars(setting('branch_count')) ?> Locations</strong><span>Limpopo &amp; North West</span></div></div>
            <div class="trust-item"><i class="fas fa-users"></i><div><strong><?= htmlspecialchars(setting('professionals_trained')) ?> Trained</strong><span>Active Security Professionals</span></div></div>
            <div class="trust-item"><i class="fas fa-clock"></i><div><strong>24/7 Response</strong><span>Always On Standby</span></div></div>
            <div class="trust-item"><i class="fas fa-star"></i><div><strong><?= htmlspecialchars(setting('training_pass_rate')) ?> Pass Rate</strong><span>Proven Training Excellence</span></div></div>
        </div>
    </div>

    <!-- Services Overview -->
    <section class="services-overview">
        <div class="container">
            <div class="section-header">
                <div class="section-tag">What We Do</div>
                <h2>Comprehensive <span>Security Solutions</span></h2>
                <p>From physical guarding and VIP protection to CCTV and fire systems — one elite provider for all your security needs.</p>
                <div class="gold-line"></div>
            </div>
            <div class="services-grid-elite">
                <?php foreach (get_services() as $s): if (empty($s['show_on_home'])) continue; ?>
                <div class="service-card-elite">
                    <div class="service-card-icon"><i class="<?= htmlspecialchars($s['icon_class']) ?>"></i></div>
                    <h3><?= htmlspecialchars($s['title']) ?></h3>
                    <p><?= htmlspecialchars($s['summary']) ?></p>
                    <a href="<?= htmlspecialchars($s['link_href'] ?: 'services.php') ?>" class="service-card-link"><?= htmlspecialchars($s['link_text'] ?: 'Learn More') ?> <i class="fas fa-arrow-right"></i></a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="why-us">
        <div class="container">
            <div class="why-us-grid">
                <div class="why-us-content">
                    <div class="section-tag">Why Makgwati</div>
                    <h2>The <span>Elite Choice</span> for Security</h2>
                    <p>We don't just provide guards — we deliver peace of mind through rigorous training, certified professionals, and proven security protocols used by South Africa's leading organisations.</p>
                    <div class="why-points">
                        <div class="why-point">
                            <div class="why-point-icon"><i class="fas fa-check"></i></div>
                            <div class="why-point-text"><strong>Fully PSIRA &amp; SAPS Registered</strong><span>All credentials verified and up to date — legally compliant in every operation.</span></div>
                        </div>
                        <div class="why-point">
                            <div class="why-point-icon"><i class="fas fa-check"></i></div>
                            <div class="why-point-text"><strong>Experienced Field Officers</strong><span>Our team has real-world experience in close protection, CIT, and armed response.</span></div>
                        </div>
                        <div class="why-point">
                            <div class="why-point-icon"><i class="fas fa-check"></i></div>
                            <div class="why-point-text"><strong>Multi-Location Coverage</strong><span><?= htmlspecialchars(setting('branch_count')) ?> branches across Limpopo and North West, ensuring rapid local response.</span></div>
                        </div>
                        <div class="why-point">
                            <div class="why-point-icon"><i class="fas fa-check"></i></div>
                            <div class="why-point-text"><strong>Tailored Security Solutions</strong><span>Every client gets a custom security assessment — not a one-size-fits-all package.</span></div>
                        </div>
                    </div>
                </div>
                <div class="stats-grid">
                    <div class="stat-card"><span class="stat-number"><?= htmlspecialchars(setting('professionals_trained')) ?></span><div class="stat-label">Professionals Trained</div></div>
                    <div class="stat-card"><span class="stat-number"><?= htmlspecialchars(setting('training_pass_rate')) ?></span><div class="stat-label">Training Pass Rate</div></div>
                    <div class="stat-card"><span class="stat-number"><?= htmlspecialchars(setting('branch_count')) ?></span><div class="stat-label">Branch Locations</div></div>
                    <div class="stat-card"><span class="stat-number">24/7</span><div class="stat-label">Response Capability</div></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials">
        <div class="container">
            <div class="section-header">
                <div class="section-tag">Client Testimonials</div>
                <h2>What Our <span>Clients Say</span></h2>
                <p>Trusted by businesses, executives, and organisations across South Africa.</p>
                <div class="gold-line"></div>
            </div>
            <div class="testimonials-grid">
                <?php foreach (get_testimonials() as $t): ?>
                <div class="testimonial-card">
                    <div class="testimonial-stars"><?= str_repeat('★', max(0, min(5, (int)$t['rating']))) ?></div>
                    <p class="testimonial-text">"<?= htmlspecialchars($t['quote_text']) ?>"</p>
                    <div class="testimonial-author">
                        <div class="testimonial-icon"><i class="fas fa-user-tie"></i></div>
                        <div><strong><?= htmlspecialchars($t['author_name']) ?></strong><span><?= htmlspecialchars($t['author_role']) ?></span></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Latest Updates -->
    <?php $recentPosts = get_published_posts(3); ?>
    <?php if ($recentPosts): ?>
    <section class="home-blog-teaser">
        <div class="container">
            <div class="section-header">
                <div class="section-tag">Latest Updates</div>
                <h2>News <span>&amp; Safety Insights</span></h2>
                <p>Recent updates, safety tips, and stories from the field.</p>
                <div class="gold-line"></div>
            </div>
            <div class="blog-grid">
                <?php foreach ($recentPosts as $post): ?>
                <a href="blog-post.php?slug=<?= urlencode($post['slug']) ?>" class="blog-card">
                    <?php if ($post['cover_image']): ?>
                        <img class="blog-card-image" src="<?= htmlspecialchars($post['cover_image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="blog-card-image placeholder"><i class="fas fa-shield-halved"></i></div>
                    <?php endif; ?>
                    <div class="blog-card-body">
                        <div class="blog-card-date"><?= htmlspecialchars(date('d M Y', strtotime($post['published_at']))) ?></div>
                        <div class="blog-card-title"><?= htmlspecialchars($post['title']) ?></div>
                        <?php if ($post['excerpt']): ?><p class="blog-card-excerpt"><?= htmlspecialchars($post['excerpt']) ?></p><?php endif; ?>
                        <span class="blog-card-link">Read More <i class="fas fa-arrow-right"></i></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Lead CTA -->
    <section class="lead-cta">
        <div class="container">
            <div class="lead-cta-inner">
                <div class="lead-cta-header">
                    <div class="section-tag">Free Consultation</div>
                    <h2>Secure Your World — <span>Speak to an Expert Today</span></h2>
                    <p>Fill in your details and our team will contact you on WhatsApp within minutes.</p>
                </div>
                <form class="lead-form" id="homeLeadForm">
                    <input type="text" id="h_name" placeholder="Your Full Name *" required>
                    <input type="tel" id="h_phone" placeholder="Phone Number *" required>
                    <input type="email" id="h_email" placeholder="Email Address">
                    <select id="h_service">
                        <option value="">Select Service Required *</option>
                        <option>VIP / Close Protection</option>
                        <option>Building Security</option>
                        <option>Armed Response</option>
                        <option>Event Security</option>
                        <option>CCTV Installation</option>
                        <option>Access Control</option>
                        <option>Fire Alarm Systems</option>
                        <option>Security Training</option>
                        <option>Firearm License Training</option>
                        <option>General Enquiry</option>
                    </select>
                    <textarea id="h_message" placeholder="Additional details or message (optional)" class="lead-form-full"></textarea>
                    <div class="lead-form-full">
                        <button type="button" class="btn-submit-lead" onclick="submitLeadForm('home')">
                            <i class="fab fa-whatsapp"></i> Send to WhatsApp — Get a Free Quote
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
