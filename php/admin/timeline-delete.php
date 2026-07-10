<?php
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: timeline.php');
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    header('Location: timeline.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$pdo = get_db_connection();

if ($id > 0 && $pdo !== null) {
    try {
        $stmt = $pdo->prepare('DELETE FROM timeline_items WHERE id = :id');
        $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        error_log('Timeline delete failed: ' . $e->getMessage());
    }
}

header('Location: timeline.php?flash=deleted');
exit;
