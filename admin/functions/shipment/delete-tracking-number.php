<?php 
session_start();
$userID = $_SESSION['employee_id'] ?? null;
if (!$userID) {
    $errors[] = "Unauthorized request. Please log in.";
    header("Location: login.php");
    die();
}
 include_once __DIR__ . '/../../../includes/dbconnection.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shippingId = $_POST['id'] ?? null;

    if ($shippingId) {
        $stmt = $dbh->prepare("DELETE FROM tracking_numbers WHERE tracking_id = :id");
        $stmt->bindParam(':id', $shippingId, PDO::PARAM_INT);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Failed to delete tracking number']);
    exit;
}
?>