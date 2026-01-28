<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../../../includes/dbconnection.php';

if (!isset($_GET['tracking_number'])) {
    echo json_encode(['success' => false, 'message' => 'No tracking number provided']);
    exit;
}

$tracking_number = trim($_GET['tracking_number']);

$query = "SELECT * FROM tracking_numbers WHERE tracking_number = :tracking_number";
$stmt = $dbh->prepare($query);
$stmt->bindParam(':tracking_number', $tracking_number, PDO::PARAM_STR);
$stmt->execute();

$tracking = $stmt->fetch(PDO::FETCH_ASSOC);

if ($tracking) {
    echo json_encode(['success' => true, 'tracking' => $tracking]);
} else {
    echo json_encode(['success' => false, 'message' => 'Tracking number not found']);
}
?>
