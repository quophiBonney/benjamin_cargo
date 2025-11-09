<?php 
session_start();
if (!isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    die();
}
include_once '../../includes/dbconnection.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = $_POST['id'] ?? null;

    if ($employeeId) {
        $stmt = $dbh->prepare("DELETE FROM employees WHERE employee_id = :id");
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