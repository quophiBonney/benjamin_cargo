<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    header("Location: login.php");
    die();
    exit;
}
header('Content-Type: application/json');
 include_once __DIR__ . '/../../../includes/dbconnection.php'; 

$allowed_roles = ['admin', 'manager', 'hr'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'No shipment ID provided']);
    exit;
}

$shipment_id = $_GET['id'];

$query = "SELECT * FROM tracking_history WHERE shipment_id = :id ORDER BY date_time DESC LIMIT 1";
$stmt = $dbh->prepare($query);
$stmt->bindParam(':id', $shipment_id, PDO::PARAM_INT);
$stmt->execute();

$tracking = $stmt->fetch(PDO::FETCH_ASSOC);

if ($tracking) {
    echo json_encode(['success' => true, 'tracking' => $tracking]);
} else {
    echo json_encode(['success' => false, 'message' => 'Tracking not found']);
}