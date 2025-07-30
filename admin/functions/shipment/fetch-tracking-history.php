<?php
header('Content-Type: application/json');
include_once '../../includes/dbconnection.php';

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
