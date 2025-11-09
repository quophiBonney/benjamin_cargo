<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
header('Content-Type: application/json');

include_once '../../includes/dbconnection.php';

$allowed_roles = ['admin', 'manager'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($session_role, $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$logId = $_POST['log_id'] ?? null;

if (!$logId || !is_numeric($logId)) {
    echo json_encode(['success' => false, 'message' => 'Invalid log ID.']);
    exit;
}

try {
    $stmt = $dbh->prepare("DELETE FROM security_logs WHERE log_id = ?");
    $stmt->execute([$logId]);

    echo json_encode(['success' => true, 'message' => 'Log deleted successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
