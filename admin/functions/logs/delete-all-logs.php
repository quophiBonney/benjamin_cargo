<?php
session_start();
header('Content-Type: application/json');

include_once '../../includes/dbconnection.php';

$allowed_roles = ['admin', 'manager'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($session_role, $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $dbh->prepare("DELETE FROM security_logs");
    $stmt->execute();
    $dbh->exec("ALTER TABLE security_logs AUTO_INCREMENT = 1");

    echo json_encode(['success' => true, 'message' => 'All security logs deleted successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
