<?php
header('Content-Type: application/json');
include_once '../../includes/dbconnection.php';

// Get shipment ID from POST data
$shipment_id = $_POST['shipment_id'] ?? null;
$location = trim($_POST['location'] ?? '');
$description = trim($_POST['description'] ?? '');
$status = trim($_POST['status'] ?? '');

if (!$shipment_id || !$location || !$status) {
    echo json_encode([
        'success' => false, 
        'message' => 'Shipment ID, location and status are required'
    ]);
    exit;
}

// Always insert a new tracking history entry
$insert = $dbh->prepare("
    INSERT INTO tracking_history (shipment_id, location, description, status, date_time)
    VALUES (:shipment_id, :location, :description, :status, NOW())
");

$insert->bindParam(':shipment_id', $shipment_id, PDO::PARAM_INT);
$insert->bindParam(':location', $location);
$insert->bindParam(':description', $description);
$insert->bindParam(':status', $status);

if ($insert->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Tracking history updated successfully'
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Database operation failed: ' . implode(' ', $insert->errorInfo())
    ]);
}