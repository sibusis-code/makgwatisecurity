<?php
/**
 * Makgwati Security CMS — Blog post create/update/delete
 * Separate from save.php because posts need cover-image upload handling.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/media.php';

// An oversized cover image empties $_POST entirely, taking the CSRF token with
// it. Check before require_login() so the user gets the real reason.
if (post_was_truncated()) {
    header('Location: index.php?tab=blog&err=' . urlencode(truncated_post_message()));
    exit;
}

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?tab=blog'); exit;
}

$csrf     = $_POST['csrf'] ?? '';
$redirect = $_POST['redirect'] ?? 'index.php?tab=blog';
$ok  = function (string $msg) use ($redirect) { header('Location: ' . $redirect . (str_contains($redirect, '?') ? '&' : '?') . 'ok=' . urlencode($msg)); exit; };
$err = function (string $msg) use ($redirect) { header('Location: ' . $redirect . (str_contains($redirect, '?') ? '&' : '?') . 'err=' . urlencode($msg)); exit; };

if (!csrf_verify($csrf)) {
    $err('Security check failed. Please try again.');
}

// ── Delete ──
if (($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) $err('Missing id.');
    try {
        db()->prepare('DELETE FROM blog_posts WHERE id = :id')->execute(['id' => $id]);
    } catch (Throwable $e) {
        $err('Could not delete post — database error.');
    }
    $ok('Post deleted.');
}

// ── Create / Update ──
$id      = (int)($_POST['id'] ?? 0);
$title   = trim(strip_tags($_POST['title'] ?? ''));
$slug    = trim($_POST['slug'] ?? '');
$excerpt = trim(strip_tags($_POST['excerpt'] ?? ''));
$body    = trim($_POST['body'] ?? '');
$status  = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
$published_at_raw = trim($_POST['published_at'] ?? '');

if ($title === '') {
    $err('Title is required.');
}
if ($slug === '') {
    $slug = $title;
}
$slug = strtolower($slug);
$slug = trim(preg_replace('/[^a-z0-9]+/', '-', $slug), '-');
if ($slug === '') {
    $err('Could not generate a URL slug from that title.');
}

$published_at = null;
if ($status === 'published') {
    $published_at = $published_at_raw !== '' ? $published_at_raw . ' 00:00:00' : date('Y-m-d H:i:s');
}

// ── Optional cover image upload ──
$cover_image = null; // null = leave unchanged on update
if (!empty($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $result = validate_upload($_FILES['cover_image'], 'image');
    if (!$result['ok']) $err($result['msg']);

    $quota = quota_check('blog/covers', (int) $_FILES['cover_image']['size']);
    if (!$quota['ok']) $err($quota['msg']);

    $blogDir = SITE_ROOT . 'blog' . DIRECTORY_SEPARATOR . 'covers';
    if (!is_dir($blogDir)) mkdir($blogDir, 0755, true);

    $safe_name = safe_filename($_FILES['cover_image']['name']);
    if (file_exists($blogDir . DIRECTORY_SEPARATOR . $safe_name)) {
        $safe_name = pathinfo($safe_name, PATHINFO_FILENAME) . '_' . time() . '.' . $result['ext'];
    }
    $cover_path = $blogDir . DIRECTORY_SEPARATOR . $safe_name;
    if (!move_uploaded_file($_FILES['cover_image']['tmp_name'], $cover_path)) {
        $err('Could not save cover image. Check folder permissions.');
    }
    compress_image($cover_path);
    $cover_image = 'blog/covers/' . $safe_name;
}

try {
    if ($id) {
        if ($cover_image !== null) {
            $stmt = db()->prepare(
                'UPDATE blog_posts SET title=:title, slug=:slug, excerpt=:excerpt, body=:body,
                 status=:status, published_at=:published_at, cover_image=:cover_image WHERE id=:id'
            );
            $stmt->execute(compact('title','slug','excerpt','body','status','published_at','cover_image','id'));
        } else {
            $stmt = db()->prepare(
                'UPDATE blog_posts SET title=:title, slug=:slug, excerpt=:excerpt, body=:body,
                 status=:status, published_at=:published_at WHERE id=:id'
            );
            $stmt->execute(compact('title','slug','excerpt','body','status','published_at','id'));
        }
        $ok('Post updated.');
    } else {
        $stmt = db()->prepare(
            'INSERT INTO blog_posts (title, slug, excerpt, body, status, published_at, cover_image)
             VALUES (:title, :slug, :excerpt, :body, :status, :published_at, :cover_image)'
        );
        $stmt->execute([
            'title' => $title, 'slug' => $slug, 'excerpt' => $excerpt, 'body' => $body,
            'status' => $status, 'published_at' => $published_at, 'cover_image' => $cover_image,
        ]);
        $ok('Post created.');
    }
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        $err('That URL slug is already used by another post — choose a different one.');
    }
    $err('Could not save post — database error.');
}
