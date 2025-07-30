<?php 
session_start();
$userID = $_SESSION['employee_id'] ?? null;
if (!$userID) {
    $errors[] = "Unauthorized request. Please log in.";
}
include_once '../../includes/dbconnection.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userID = $_POST['id'] ?? null;

    if ($userID) {
        $stmt = $dbh->prepare("DELETE FROM bookings WHERE booking_id = :id");
        $stmt->bindParam(':id', $userID, PDO::PARAM_INT);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Failed to delete booking']);
    exit;
}
?>