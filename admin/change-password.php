<?php
/**
 * Makgwati Security CMS — Change password handler
 */
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}

$csrf     = $_POST['csrf']     ?? '';
$redirect = $_POST['redirect'] ?? '?tab=password';

if (!csrf_verify($csrf)) {
    header('Location: index.php?tab=password&err=' . urlencode('Security check failed.')); exit;
}

$current  = $_POST['current']  ?? '';
$new_pass = $_POST['new_pass'] ?? '';
$confirm  = $_POST['confirm']  ?? '';

$err = function(string $msg) use ($redirect) {
    header('Location: ' . $redirect . '&err=' . urlencode($msg)); exit;
};
$ok  = function(string $msg) use ($redirect) {
    header('Location: ' . $redirect . '&ok=' . urlencode($msg)); exit;
};

if (!$current || !$new_pass || !$confirm) $err('All fields are required.');
if (strlen($new_pass) < 8)               $err('New password must be at least 8 characters.');
if ($new_pass !== $confirm)              $err('New passwords do not match.');

$auth = json_decode(file_get_contents(AUTH_FILE), true);
if (!password_verify($current, $auth['hash'] ?? '')) {
    $err('Current password is incorrect.');
}

$auth['hash'] = password_hash($new_pass, PASSWORD_BCRYPT);
file_put_contents(AUTH_FILE, json_encode($auth));

$ok('Password changed successfully!');
