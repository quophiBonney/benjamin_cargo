<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['employee_id'])) {
   header("Location: login.php");
    die();
}

include_once '../../includes/dbconnection.php';

$user_id = $_SESSION['employee_id'];
$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;

if (!$lat || !$lng) {
    echo json_encode(['success' => false, 'message' => 'Location is required.']);
    exit;
}

$today = date('Y-m-d');

// Check if already clocked out
$stmt = $dbh->prepare("SELECT * FROM clock_outs WHERE employee_id = ? AND date = ?");
$stmt->execute([$user_id, $today]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => false, 'message' => 'You already clocked out today.']);
    exit;
}

// Insert clock out record
$insert = $dbh->prepare("INSERT INTO clock_outs (employee_id, clock_out_time, latitude, longitude, date) VALUES (?, NOW(), ?, ?, ?)");
$success = $insert->execute([$user_id, $lat, $lng, $today]);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Clocked out successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to clock out.']);
}
