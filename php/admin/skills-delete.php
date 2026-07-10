<?php
require_once __DIR__ . '/auth.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: skills.php');
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    header('Location: skills.php');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
$pdo = get_db_connection();

if ($id > 0 && $pdo !== null) {
    try {
        $stmt = $pdo->prepare('DELETE FROM skills WHERE id = :id');
        $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        error_log('Skill delete failed: ' . $e->getMessage());
    }
}

header('Location: skills.php?flash=deleted');
exit;
