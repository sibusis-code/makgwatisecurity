<?php
/**
 * Makgwati Security CMS — MySQL connection helper
 */

require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    if (DB_HOST === '' || DB_NAME === '' || DB_USER === '') {
        throw new RuntimeException('Database is not configured yet. Set DB_HOST, DB_NAME, DB_USER, DB_PASS in admin/config.php.');
    }
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}
