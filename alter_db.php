<?php
require_once __DIR__ . '/php/db.php';
$pdo = get_db_connection();
try {
    $pdo->exec("ALTER TABLE projects ADD COLUMN video_path VARCHAR(255) DEFAULT NULL AFTER presentation_path");
    echo "Column added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
