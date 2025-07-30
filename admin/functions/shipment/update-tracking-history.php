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

// Check if tracking already exists
$stmt = $dbh->prepare("SELECT id FROM tracking_history WHERE shipment_id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    // Update
    $update = $dbh->prepare("UPDATE tracking_history SET location = :location, description = :description, status = :status, date_time = NOW() WHERE shipment_id = :id");
} else {
    // Insert
    $update = $dbh->prepare("INSERT INTO tracking_history (shipment_id, location, description, status, date_time) VALUES (:id, :location, :description, :status, NOW())");
}

$update->bindParam(':id', $id, PDO::PARAM_INT);
$update->bindParam(':location', $location);
$update->bindParam(':description', $description);
$update->bindParam(':status', $status);

if ($update->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database operation failed']);
}
