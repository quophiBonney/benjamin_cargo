<?php 
session_start();
if (!isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    die();
}
 include_once __DIR__ . '/../../../includes/dbconnection.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = $_POST['id'] ?? null;

    if ($customerId) {
        $stmt = $dbh->prepare("DELETE FROM customers WHERE customer_id = :id");
        $stmt->bindParam(':id', $customerId, PDO::PARAM_INT);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Failed to delete customer']);
    exit;
}
?>