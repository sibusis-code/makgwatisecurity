<?php
/**
 * Public lead-capture endpoint.
 * Called via fetch() from script.js alongside the existing WhatsApp
 * redirect — this just gives Makgwati a persistent record of every
 * enquiry so nothing is lost if a WhatsApp message is missed.
 * Never blocks or breaks the WhatsApp flow: always returns JSON,
 * swallows DB errors so a misconfigured DB can't break the page.
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Honeypot field — real users never fill this in (hidden via CSS in the form).
if (!empty($_POST['website'])) {
    echo json_encode(['ok' => true]); // pretend success, drop silently
    exit;
}

$name    = trim($_POST['name'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$email   = trim($_POST['email'] ?? '');
$service = trim($_POST['service'] ?? '');
$location= trim($_POST['location'] ?? '');
$message = trim($_POST['message'] ?? '');
$source  = trim($_POST['source'] ?? 'unknown');

$allowedSources = ['home', 'services', 'training', 'contact', 'vip', 'chatbot'];
if (!in_array($source, $allowedSources, true)) {
    $source = 'unknown';
}

if ($name === '' || $phone === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Name and phone are required']);
    exit;
}

try {
    require_once __DIR__ . '/admin/db.php';
    $stmt = db()->prepare(
        'INSERT INTO leads (source_page, name, phone, email, service_interest, location_text, message)
         VALUES (:source, :name, :phone, :email, :service, :location, :message)'
    );
    $stmt->execute([
        'source'   => $source,
        'name'     => mb_substr($name, 0, 150),
        'phone'    => mb_substr($phone, 0, 30),
        'email'    => $email !== '' ? mb_substr($email, 0, 150) : null,
        'service'  => $service !== '' ? mb_substr($service, 0, 150) : null,
        'location' => $location !== '' ? mb_substr($location, 0, 150) : null,
        'message'  => $message !== '' ? $message : null,
    ]);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    // Don't surface DB errors to the public form — WhatsApp is still the
    // primary path and must keep working even if the DB isn't set up yet.
    http_response_code(200);
    echo json_encode(['ok' => false, 'error' => 'not_recorded']);
}
