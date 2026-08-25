<?php
/**
 * Shared footer + chatbot widget + script includes.
 * Expects (optionally) $footer_mode = 'services' | 'courses' | 'branches'
 * to control the third footer column, and $current_page for nav links.
 */
$footer_mode = $footer_mode ?? 'services';
$head_office = get_head_office();
$whatsapp    = setting('whatsapp_number');
?>
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="logo-icon"><img src="images/logo.png" alt="Makgwati Security"></div>
                    <div class="logo-text" style="margin-top:0.5rem;">
                        <span class="logo-main">MAKGWATI</span>
                        <span class="logo-sub">SECURITY</span>
                    </div>
                    <p class="footer-desc"><?= htmlspecialchars(setting('footer_description')) ?></p>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="services.php"><i class="fas fa-chevron-right"></i> Our Services</a></li>
                        <li><a href="training.php"><i class="fas fa-chevron-right"></i> Training &amp; Courses</a></li>
                        <li><a href="vipprotection.php"><i class="fas fa-chevron-right"></i> VIP Protection</a></li>
                        <li><a href="contact.php"><i class="fas fa-chevron-right"></i> Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <?php if ($footer_mode === 'vip'): ?>
                        <h4>VIP Services</h4>
                        <ul class="footer-links">
                            <li><a href="vipprotection.php"><i class="fas fa-chevron-right"></i> Close Protection</a></li>
                            <li><a href="vipprotection.php"><i class="fas fa-chevron-right"></i> Executive Escort</a></li>
                            <li><a href="vipprotection.php"><i class="fas fa-chevron-right"></i> Corporate Events</a></li>
                            <li><a href="vipprotection.php"><i class="fas fa-chevron-right"></i> Secure Transport</a></li>
                            <li><a href="vipprotection.php"><i class="fas fa-chevron-right"></i> Private Clients</a></li>
                        </ul>
                    <?php elseif ($footer_mode === 'branches'): ?>
                        <h4>Our Branches</h4>
                        <ul class="footer-links">
                            <?php foreach (get_branches() as $b): ?>
                                <li><a href="contact.php"><i class="fas fa-chevron-right"></i> <?= htmlspecialchars($b['name']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php elseif ($footer_mode === 'courses'): ?>
                        <h4>Courses</h4>
                        <ul class="footer-links">
                            <?php foreach (array_slice(get_training_courses(), 0, 5) as $c): ?>
                                <li><a href="training.php"><i class="fas fa-chevron-right"></i> <?= htmlspecialchars($c['name']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <h4>Services</h4>
                        <ul class="footer-links">
                            <?php foreach (array_slice(get_services(), 0, 5) as $s): ?>
                                <li><a href="services.php"><i class="fas fa-chevron-right"></i> <?= htmlspecialchars($s['title']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="footer-col">
                    <h4>Head Office</h4>
                    <?php if ($head_office): ?>
                        <div class="footer-contact-item"><i class="fas fa-user"></i> <?= htmlspecialchars($head_office['contact_person']) ?></div>
                        <?php if ($head_office['phone']): ?><div class="footer-contact-item"><i class="fas fa-phone"></i><a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $head_office['phone'])) ?>"><?= htmlspecialchars($head_office['phone']) ?></a></div><?php endif; ?>
                        <?php if ($head_office['whatsapp']): ?><div class="footer-contact-item"><i class="fab fa-whatsapp"></i><a href="<?= htmlspecialchars(wa_link($head_office['whatsapp'])) ?>" target="_blank"><?= htmlspecialchars(format_phone($head_office['whatsapp'])) ?></a></div><?php endif; ?>
                    <?php endif; ?>
                    <div class="footer-contact-item" style="margin-top:0.5rem;"><i class="fas fa-certificate"></i> PSIRA: <?= htmlspecialchars(setting('psira_number')) ?></div>
                    <div class="footer-contact-item"><i class="fas fa-award"></i> SAPS: <?= htmlspecialchars(setting('saps_number')) ?></div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= htmlspecialchars(setting('copyright_name')) ?>. All rights reserved. PSIRA Registered Provider.</p>
                <p>Designed by <a href="https://www.mplai.co.za" target="_blank" rel="noopener">MPL AI</a></p>
            </div>
        </div>
    </footer>

    <!-- Chatbot -->
    <div class="chatbot-widget">
        <div class="chatbot-window" id="chatbotWindow">
            <div class="chatbot-header">
                <div class="chatbot-header-info">
                    <div class="chatbot-avatar"><i class="fas fa-robot"></i></div>
                    <div><strong>Makgwati AI Assistant</strong><span class="chatbot-status">● Online — Ask me anything</span></div>
                </div>
                <button class="chatbot-close" id="chatbotClose">&times;</button>
            </div>
            <div class="chatbot-messages" id="chatbotMessages"></div>
            <div class="chatbot-quick-btns">
                <button class="quick-btn" data-query="What services do you offer?">Our Services</button>
                <button class="quick-btn" data-query="What are the training prices?">Prices</button>
                <button class="quick-btn" data-query="Where are your locations?">Locations</button>
                <button class="quick-btn" data-query="Tell me about VIP protection">VIP Protection</button>
                <button class="quick-btn" data-query="I want to enroll in training">Enroll Now</button>
                <button class="quick-btn" data-query="Firearm license training information">Firearm License</button>
            </div>
            <div class="chatbot-input-area">
                <input type="text" id="chatbotTextInput" placeholder="Ask a question..." autocomplete="off">
                <button class="chatbot-send-btn" id="chatbotSendBtn"><i class="fas fa-paper-plane"></i></button>
            </div>
            <div class="chatbot-lead-form" id="chatbotLeadForm" style="display:none;">
                <input type="text" id="cbLeadName" placeholder="Your Name *">
                <input type="tel" id="cbLeadPhone" placeholder="Your Phone Number *">
                <select id="cbLeadService">
                    <option value="">Select Service *</option>
                    <option>VIP / Close Protection</option>
                    <option>Building Security</option>
                    <option>Armed Response</option>
                    <option>Event Security</option>
                    <option>CCTV Installation</option>
                    <option>Access Control</option>
                    <option>Security Training (Grade A/B)</option>
                    <option>Firearm License Training</option>
                    <option>CIT Training</option>
                    <option>General Enquiry</option>
                </select>
                <button type="button" id="cbSubmit" class="chatbot-submit-btn">
                    <i class="fab fa-whatsapp"></i> Send to WhatsApp
                </button>
            </div>
        </div>
        <button class="chatbot-trigger" id="chatbotTrigger">
            <i class="fas fa-comments"></i>
            <span class="chatbot-badge">1</span>
        </button>
    </div>

    <script>
    window.__FAQS__ = <?= get_faqs_json() ?>;
    // The WhatsApp destination for every enquiry form and the chatbot. Comes
    // from the CMS (Site Settings -> whatsapp_number) so the client can change
    // where leads land without a code deploy.
    window.__WA_NUMBER__ = <?= json_encode(preg_replace('/\D/', '', $whatsapp)) ?>;
    </script>
    <script src="script.js"></script>
    <script src="chatbot.js"></script>
