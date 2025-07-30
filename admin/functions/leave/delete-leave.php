<?php
session_start();
header('Content-Type: application/json');
include_once '../../includes/dbconnection.php';

$response = ['success' => false];

// Authentication check
if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized request. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leaveId = $_POST['id'] ?? null;

    if ($leaveId) {
        try {
            $stmt = $dbh->prepare("DELETE FROM leave_submissions WHERE leave_id = :id");
            $stmt->bindParam(':id', $leaveId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
                exit;
            }
        } catch (Exception $e) {
            $response['message'] = 'Error: ' . $e->getMessage();
            echo json_encode($response);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Invalid leave ID.']);
    exit;
}
?>
