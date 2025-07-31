<?php
header('Content-Type: application/json');
include_once '../../includes/dbconnection.php';

$id = $_POST['id'] ?? null;
$location = trim($_POST['location'] ?? '');
$description = trim($_POST['description'] ?? '');
$status = trim($_POST['status'] ?? '');

if (!$id || !$location || !$status) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit;
}

// Insert into tracking_history
$insert = $dbh->prepare("
    INSERT INTO tracking_history (shipment_id, location, description, status, date_time)
    VALUES (:id, :location, :description, :status, NOW())
");

$insert->bindParam(':id', $id, PDO::PARAM_INT);
$insert->bindParam(':location', $location);
$insert->bindParam(':description', $description);
$insert->bindParam(':status', $status);

if ($insert->execute()) {
    // ✅ Update shipment status in the shipments table
    $update = $dbh->prepare("
        UPDATE shipments 
        SET status = :status 
        WHERE shipment_id = :id
    ");
    $update->bindParam(':status', $status);
    $update->bindParam(':id', $id, PDO::PARAM_INT);
    $update->execute();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database operation failed']);
}
