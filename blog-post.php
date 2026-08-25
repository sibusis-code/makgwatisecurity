<?php
require_once __DIR__ . '/inc/site-data.php';
$current_page = 'blog';
$footer_mode  = 'services';

$slug = $_GET['slug'] ?? '';
$post = $slug !== '' ? get_post_by_slug($slug) : null;

if (!$post) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $post ? htmlspecialchars($post['title']) . ' — Makgwati Security' : 'Post Not Found — Makgwati Security' ?></title>
    <meta name="description" content="<?= $post ? htmlspecialchars($post['excerpt']) : 'This post could not be found.' ?>">
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/inc/nav.php'; ?>

    <?php if (!$post): ?>
        <section class="page-hero">
            <div class="page-hero-content">
                <div class="section-tag">Not Found</div>
                <h1>Post Not <span>Found</span></h1>
                <p>This article may have been unpublished or the link is incorrect.</p>
            </div>
        </section>
        <section class="blog-post-section">
            <div class="blog-post-container">
                <a href="blog.php" class="blog-post-back"><i class="fas fa-arrow-left"></i> Back to News</a>
            </div>
        </section>
    <?php else: ?>
        <section class="page-hero">
            <div class="page-hero-content">
                <div class="breadcrumb"><a href="index.php">Home</a><i class="fas fa-chevron-right"></i><a href="blog.php">News</a><i class="fas fa-chevron-right"></i><span><?= htmlspecialchars($post['title']) ?></span></div>
                <div class="section-tag">News &amp; Updates</div>
                <h1><?= htmlspecialchars($post['title']) ?></h1>
            </div>
        </section>
        <section class="blog-post-section">
            <div class="blog-post-container">
                <a href="blog.php" class="blog-post-back"><i class="fas fa-arrow-left"></i> Back to News</a>
                <?php if ($post['cover_image']): ?>
                    <img class="blog-post-cover" src="<?= htmlspecialchars($post['cover_image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>">
                <?php endif; ?>
                <div class="blog-post-meta"><?= htmlspecialchars(date('d F Y', strtotime($post['published_at']))) ?></div>
                <div class="blog-post-body">
                    <?php foreach (preg_split('/\n\s*\n/', trim($post['body'])) as $para): ?>
                        <p><?= nl2br(htmlspecialchars($para)) ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php include __DIR__ . '/inc/footer.php'; ?>
</body>
</html>
