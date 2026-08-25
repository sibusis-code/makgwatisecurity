<?php
/**
 * Makgwati Security CMS — Generic content save handler
 * Handles create/update/delete for the simple content tables
 * (services, training_courses, testimonials, branches, faqs) plus
 * upsert for site_settings (key/value, no numeric id).
 *
 * Security: table + column names are NEVER taken from user input —
 * only from the hardcoded $TABLES allow-list below. Only values are
 * bound as query parameters.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}

$csrf     = $_POST['csrf'] ?? '';
$redirect = $_POST['redirect'] ?? 'index.php';
$ok  = function (string $msg) use ($redirect) { header('Location: ' . $redirect . (str_contains($redirect, '?') ? '&' : '?') . 'ok=' . urlencode($msg)); exit; };
$err = function (string $msg) use ($redirect) { header('Location: ' . $redirect . (str_contains($redirect, '?') ? '&' : '?') . 'err=' . urlencode($msg)); exit; };

if (!csrf_verify($csrf)) {
    $err('Security check failed. Please try again.');
}

// Allow-listed tables + their editable columns and types.
// types: string | text | html | int | decimal | bool | features(json bullet list)
$TABLES = [
    'services' => [
        'columns' => [
            'title' => 'string', 'icon_class' => 'string', 'summary' => 'text',
            'description' => 'text', 'features' => 'features', 'whatsapp_text' => 'string',
            'link_href' => 'string', 'link_text' => 'string',
            'show_on_home' => 'bool', 'show_on_services' => 'bool',
            'sort_order' => 'int', 'active' => 'bool',
        ],
    ],
    'training_courses' => [
        'columns' => [
            'category' => 'string', 'name' => 'string', 'description' => 'text',
            'price' => 'decimal', 'sort_order' => 'int', 'active' => 'bool',
        ],
    ],
    'testimonials' => [
        'columns' => [
            'author_name' => 'string', 'author_role' => 'string', 'rating' => 'int',
            'quote_text' => 'text', 'sort_order' => 'int', 'active' => 'bool',
        ],
    ],
    'branches' => [
        'columns' => [
            'name' => 'string', 'contact_person' => 'string', 'phone' => 'string',
            'whatsapp' => 'string', 'badge' => 'string', 'is_head_office' => 'bool',
            'sort_order' => 'int',
        ],
    ],
    'faqs' => [
        'columns' => [
            'keywords' => 'text', 'answer_html' => 'html',
            'sort_order' => 'int', 'active' => 'bool',
        ],
    ],
];

$table  = $_POST['table'] ?? '';
$action = $_POST['action'] ?? 'save';

// ── site_settings is key/value, handled separately (no numeric id) ──
if ($table === 'site_settings') {
    $key   = trim($_POST['key'] ?? '');
    $value = $_POST['value'] ?? '';
    if ($key === '' || !preg_match('/^[a-z0-9_]+$/', $key)) {
        $err('Invalid setting key.');
    }
    try {
        $stmt = db()->prepare(
            'INSERT INTO site_settings (`key`, `value`) VALUES (:k, :v)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)'
        );
        $stmt->execute(['k' => $key, 'v' => $value]);
    } catch (Throwable $e) {
        $err('Could not save setting — database error.');
    }
    $ok('Setting "' . $key . '" updated.');
}

if (!isset($TABLES[$table])) {
    $err('Unknown content type.');
}
$columns = $TABLES[$table]['columns'];

// ── Delete ──
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) $err('Missing id.');
    try {
        $stmt = db()->prepare("DELETE FROM `$table` WHERE id = :id");
        $stmt->execute(['id' => $id]);
    } catch (Throwable $e) {
        $err('Could not delete — database error.');
    }
    $ok('Deleted successfully.');
}

// ── Create / Update ──
$id = (int)($_POST['id'] ?? 0);
$values = [];
foreach ($columns as $col => $type) {
    $raw = $_POST[$col] ?? null;
    switch ($type) {
        case 'bool':
            $values[$col] = !empty($raw) ? 1 : 0;
            break;
        case 'int':
            $values[$col] = $raw !== null && $raw !== '' ? (int)$raw : 0;
            break;
        case 'decimal':
            $values[$col] = $raw !== null && $raw !== '' ? (float)$raw : 0;
            break;
        case 'features':
            // Textarea, one bullet per line -> JSON array
            $lines = array_filter(array_map('trim', explode("\n", (string)$raw)), fn($l) => $l !== '');
            $values[$col] = $lines ? json_encode(array_values($lines)) : null;
            break;
        case 'html':
            // Trusted admin-authored HTML (chatbot answers use basic markup) — not stripped
            $values[$col] = trim((string)$raw);
            break;
        case 'text':
            $values[$col] = trim((string)$raw);
            break;
        default: // string
            $values[$col] = trim(strip_tags((string)$raw));
    }
}

try {
    if ($id) {
        $setSql = implode(', ', array_map(fn($c) => "`$c` = :$c", array_keys($columns)));
        $stmt = db()->prepare("UPDATE `$table` SET $setSql WHERE id = :id");
        $stmt->execute($values + ['id' => $id]);
        $ok('Updated successfully.');
    } else {
        $colSql = implode(', ', array_map(fn($c) => "`$c`", array_keys($columns)));
        $paramSql = implode(', ', array_map(fn($c) => ":$c", array_keys($columns)));
        $stmt = db()->prepare("INSERT INTO `$table` ($colSql) VALUES ($paramSql)");
        $stmt->execute($values);
        $ok('Added successfully.');
    }
} catch (Throwable $e) {
    $err('Could not save — database error.');
}
