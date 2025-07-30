<?php
session_start();
$userID = $_SESSION['employee_id'] ?? null;
if (!$userID) {
    $errors[] = "Unauthorized request. Please log in.";
}
include('../../includes/dbconnection.php');
header('Content-Type: application/json');

try {
    $stmt = $dbh->prepare("SELECT room_number FROM rooms WHERE status = 'available'");
    $stmt->execute();
    $rooms = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode($rooms);
} catch (Exception $e) {
    echo json_encode([]);
}
?>
