<?php
include_once '../admin/includes/dbconnection.php';
header('Content-Type: application/json');

$tracking_number = $_POST['tracking_number'] ?? '';

if (!$tracking_number) {
    echo json_encode(['status' => 'error', 'message' => 'Tracking number is required']);
    exit;
}

// Fetch the shipment by tracking number
$stmt = $dbh->prepare("SELECT * FROM shipments WHERE tracking_number = :tracking_number");
$stmt->bindParam(':tracking_number', $tracking_number);
$stmt->execute();
$shipment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shipment) {
    echo json_encode(['status' => 'error', 'message' => 'Shipment not found for this tracking number.']);
    exit;
}

// Fetch tracking history based on shipment_id (not id)
$timeline_stmt = $dbh->prepare("
    SELECT location, description, status, date_time 
    FROM tracking_history 
    WHERE shipment_id = :shipment_id 
    ORDER BY date_time DESC
");
$timeline_stmt->bindParam(':shipment_id', $shipment['shipment_id']);
$timeline_stmt->execute();
$timeline = $timeline_stmt->fetchAll(PDO::FETCH_ASSOC);

// Return response
echo json_encode([
    'status' => 'success',
    'data' => $shipment,
    'timeline' => $timeline
]);
