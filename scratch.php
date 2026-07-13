<?php
require_once __DIR__ . '/php/db.php';
$pdo = get_db_connection();
$rows = $pdo->query('SELECT content_key, content_value FROM site_content WHERE content_key = "hero_heading"')->fetchAll();
print_r($rows);
