<?php
/**
 * Makgwati Security CMS — Update a lead's status
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?tab=leads'); exit;
}

$csrf     = $_POST['csrf'] ?? '';
$redirect = $_POST['redirect'] ?? 'index.php?tab=leads';

if (!csrf_verify($csrf)) {
    header('Location: index.php?tab=leads&err=' . urlencode('Security check failed. Please try again.')); exit;
}

$id     = (int)($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';

if (!$id || !in_array($status, ['new', 'contacted', 'won', 'lost'], true)) {
    header('Location: ' . $redirect . '&err=' . urlencode('Invalid lead update.')); exit;
}

try {
    $stmt = db()->prepare('UPDATE leads SET status = :status WHERE id = :id');
    $stmt->execute(['status' => $status, 'id' => $id]);
} catch (Throwable $e) {
    header('Location: ' . $redirect . '&err=' . urlencode('Could not update lead — database error.')); exit;
}

header('Location: ' . $redirect); exit;
