<?php
require_once __DIR__ . '/inc/site-data.php';
$current_page = 'blog';
$footer_mode  = 'services';
$posts = get_published_posts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News &amp; Updates — Makgwati Security</title>
    <meta name="description" content="Safety tips, case studies, and updates from Makgwati Security — South Africa's elite PSIRA registered security provider.">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/inc/nav.php'; ?>

    <!-- Page Hero -->
    <section class="page-hero">
        <div class="page-hero-content">
            <div class="breadcrumb"><a href="index.php">Home</a><i class="fas fa-chevron-right"></i><span>News</span></div>
            <div class="section-tag">Latest Updates</div>
            <h1>News <span>&amp; Safety Insights</span></h1>
            <p>Updates, safety tips, and stories from the field — from South Africa's elite PSIRA registered security provider.</p>
        </div>
    </section>

    <section class="blog-section">
        <div class="container">
            <?php if (!$posts): ?>
                <div class="blog-empty"><i class="fas fa-newspaper" style="font-size:2rem;opacity:.3;display:block;margin-bottom:1rem;"></i>No posts published yet — check back soon.</div>
            <?php else: ?>
            <div class="blog-grid">
                <?php foreach ($posts as $post): ?>
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
            <?php endif; ?>
        </div>
    </section>

    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
