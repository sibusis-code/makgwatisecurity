<?php
/**
 * Makgwati Security CMS — Admin user management (create / delete)
 * Separate from save.php because passwords need hashing + lockout guards.
 */
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?tab=admins'); exit;
}

$csrf     = $_POST['csrf'] ?? '';
$redirect = $_POST['redirect'] ?? 'index.php?tab=admins';
$ok  = function (string $msg) use ($redirect) { header('Location: ' . $redirect . (str_contains($redirect, '?') ? '&' : '?') . 'ok=' . urlencode($msg)); exit; };
$err = function (string $msg) use ($redirect) { header('Location: ' . $redirect . (str_contains($redirect, '?') ? '&' : '?') . 'err=' . urlencode($msg)); exit; };

if (!csrf_verify($csrf)) {
    $err('Security check failed. Please try again.');
}

$action = $_POST['action'] ?? 'create';

// ── Delete ──
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) $err('Missing id.');
    if ($id === (int)($_SESSION['mgw_user_id'] ?? 0)) {
        $err('You cannot delete your own account while logged in.');
    }
    try {
        $count = (int) db()->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
        if ($count <= 1) {
            $err('Cannot delete the last remaining admin account.');
        }
        db()->prepare('DELETE FROM admin_users WHERE id = :id')->execute(['id' => $id]);
    } catch (Throwable $e) {
        $err('Could not delete admin — database error.');
    }
    $ok('Admin account removed.');
}

// ── Create ──
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $err('Enter a valid email address.');
}
if (strlen($password) < 8) {
    $err('Password must be at least 8 characters.');
}

try {
    $stmt = db()->prepare('INSERT INTO admin_users (email, password_hash) VALUES (:email, :hash)');
    $stmt->execute(['email' => $email, 'hash' => password_hash($password, PASSWORD_BCRYPT)]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        $err('That email is already registered as an admin.');
    }
    $err('Could not create admin — database error.');
}

$ok('Admin account created for ' . $email . '.');
