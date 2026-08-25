<?php
/**
 * Makgwati Security CMS — Export all leads as CSV
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_login();

try {
    $leads = db()->query('SELECT created_at, source_page, name, phone, email, service_interest, location_text, message, status FROM leads ORDER BY created_at DESC')->fetchAll();
} catch (Throwable $e) {
    header('Location: index.php?tab=leads&err=' . urlencode('Could not export — database error.')); exit;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="makgwati-leads-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Date', 'Source', 'Name', 'Phone', 'Email', 'Service Interest', 'Location', 'Message', 'Status']);
foreach ($leads as $lead) {
    fputcsv($out, [
        $lead['created_at'], $lead['source_page'], $lead['name'], $lead['phone'],
        $lead['email'], $lead['service_interest'], $lead['location_text'],
        $lead['message'], $lead['status'],
    ]);
}
fclose($out);
