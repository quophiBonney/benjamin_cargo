<?php
session_start();
if (!isset($_SESSION['casaid'])) {
    header("Location: login.php");
    exit();
}

include_once '../admin/includes/dbconnection.php';
header('Content-Type: application/json');

// ✅ Get tracking number from POST
$shipping_mark = $_POST['tracking_number'] ?? null;

if (!$shipping_mark) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid tracking number.']);
    exit;
}

// Fetch the shipment by shipping mark
$stmt = $dbh->prepare("SELECT * FROM shipping_manifest WHERE shipping_mark = :shipping_mark");
$stmt->bindParam(':shipping_mark', $shipping_mark);
$stmt->execute();
$shipment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shipment) {
    echo json_encode(['status' => 'error', 'message' => 'Shipment not found for this tracking number.']);
    exit;
}

// Fetch tracking history
$timeline_stmt = $dbh->prepare("
    SELECT status, date 
    FROM tracking_history 
    WHERE shipping_manifest_id = :shipping_manifest_id 
    ORDER BY date DESC
");

$timeline_stmt->bindParam(':shipping_manifest_id', $shipment['id']);
$timeline_stmt->execute();
$timeline = $timeline_stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'status' => 'success',
    'shipment' => $shipment,
    'timeline' => $timeline
]);