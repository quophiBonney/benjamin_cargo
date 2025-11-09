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

// Check if already clocked in
$stmt = $dbh->prepare("SELECT * FROM clock_ins WHERE employee_id = ? AND DATE(clock_in_time) = ?");
$stmt->execute([$user_id, $today]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => false, 'message' => 'You already clocked in today.']);
    exit;
}

// Insert clock in record
$insert = $dbh->prepare("INSERT INTO clock_ins (employee_id, clock_in_time, latitude, longitude) VALUES (?, NOW(), ?, ?)");
$success = $insert->execute([$user_id, $lat, $lng]);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Clocked in successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to clock in.']);
}
