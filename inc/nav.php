<?php
/**
 * Shared site navigation. Expects $current_page to be set by the
 * including page: 'home' | 'services' | 'training' | 'vip' | 'contact'.
 */
$current_page = $current_page ?? '';
function nav_active(string $page, string $current): string {
    return $page === $current ? ' active' : '';
}
?>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo-link">
                <div class="nav-logo">
                    <div class="logo-icon"><img src="images/logo.png" alt="Makgwati Security"></div>
                    <div class="logo-text">
                        <span class="logo-main">MAKGWATI</span>
                        <span class="logo-sub">SECURITY</span>
                    </div>
                </div>
            </a>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link<?= nav_active('home', $current_page) ?>">Home</a></li>
                <li><a href="services.php" class="nav-link<?= nav_active('services', $current_page) ?>">Services</a></li>
                <li><a href="training.php" class="nav-link<?= nav_active('training', $current_page) ?>">Training</a></li>
                <li><a href="vipprotection.php" class="nav-link<?= nav_active('vip', $current_page) ?>">VIP Protection</a></li>
                <li><a href="blog.php" class="nav-link<?= nav_active('blog', $current_page) ?>">News</a></li>
                <li><a href="contact.php" class="nav-link<?= nav_active('contact', $current_page) ?>">Contact</a></li>
                <li><a href="contact.php" class="nav-link nav-cta">Get a Quote</a></li>
            </ul>
            <div class="nav-toggle"><span class="bar"></span><span class="bar"></span><span class="bar"></span></div>
        </div>
    </nav>
