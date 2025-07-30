<?php
include_once '../admin/includes/dbconnection.php';

header('Content-Type: application/json');

$tracking_number = $_POST['tracking_number'] ?? '';

if (!$tracking_number) {
    echo json_encode(['status' => 'error', 'message' => 'Tracking number is required']);
    exit;
}

$stmt = $dbh->prepare("SELECT * FROM shipments WHERE tracking_number = :tracking_number");
$stmt->bindParam(':tracking_number', $tracking_number);
$stmt->execute();
$shipment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shipment) {
    echo json_encode(['status' => 'error', 'message' => 'Shipment not found for this tracking number.']);
    exit;
}

$timeline_stmt = $dbh->prepare("SELECT * FROM tracking_history WHERE shipment_id = :shipment_id ORDER BY date_time DESC");
$timeline_stmt->bindParam(':shipment_id', $shipment['id']);
$timeline_stmt->execute();
$timeline = $timeline_stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'status' => 'success',
    'data' => $shipment,
    'timeline' => $timeline
]);
