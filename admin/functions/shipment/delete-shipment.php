<?php 
session_start();
$userID = $_SESSION['employee_id'] ?? null;
if (!$userID) {
    $errors[] = "Unauthorized request. Please log in.";
}
include_once '../../includes/dbconnection.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = $_POST['id'] ?? null;

    if ($employeeId) {
        $stmt = $dbh->prepare("DELETE FROM shipments WHERE shipment_id = :id");
        $stmt->bindParam(':id', $employeeId, PDO::PARAM_INT);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Failed to delete employee']);
    exit;
}
?>