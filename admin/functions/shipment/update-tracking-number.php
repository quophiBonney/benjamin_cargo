<?php
session_start();

if (!isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit;
}

include_once __DIR__ . '/../../../includes/dbconnection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $trackingNumber = $_POST['trackingNumber'] ?? null;
    $dateReceived = $_POST['dateReceived'] ?? null;

    if ($id && $trackingNumber && $dateReceived) {
        $stmt = $dbh->prepare("UPDATE tracking_numbers SET tracking_number = ?, date_received = ? WHERE tracking_id = ?");
        if ($stmt->execute([$trackingNumber, $dateReceived, $id])) {
            echo json_encode(['success' => true]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Failed to update tracking number']);
    exit;
}
?>
