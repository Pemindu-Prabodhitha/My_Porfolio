<?php
/**
 * db.php
 * Returns a PDO connection to the MySQL database, or null if the database
 * is disabled or unreachable. Callers should check for null and fall back
 * to the JSON file / text log accordingly - the site should never show a
 * broken page just because the database isn't set up yet.
 */

require_once __DIR__ . '/config.php';

function get_db_connection() {
    static $pdo = null;
    static $attempted = false;

    if (!DB_ENABLED) {
        return null;
    }

    // Only try to connect once per request, even if called multiple times
    if ($attempted) {
        return $pdo;
    }
    $attempted = true;

    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        // Log the error server-side for debugging, but never expose DB
        // details to visitors. Callers will fall back to JSON/file storage.
        error_log('Database connection failed: ' . $e->getMessage());
        $pdo = null;
    }

    return $pdo;
}
