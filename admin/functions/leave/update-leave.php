<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
header('Content-Type: application/json');
include('../../includes/dbconnection.php');

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leaveId = $_POST['id'] ?? null;
    $leaveType = trim($_POST['leaveType'] ?? '');
    $startDate = trim($_POST['startDate'] ?? '');
    $endDate = trim($_POST['endDate'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    $status = trim($_POST['status'] ?? '');

    if (!$leaveId || !$leaveType || !$startDate || !$endDate || !$reason) {
        $response['message'] = 'All fields are required.';
        echo json_encode($response);
        exit;
    }

    try {
        // Step 1: Check current status from DB
        $checkStmt = $dbh->prepare("SELECT status FROM leave_submissions WHERE leave_id = :leaveId");
        $checkStmt->execute([':leaveId' => $leaveId]);
        $currentLeave = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentLeave) {
            $response['message'] = 'Leave record not found.';
        } elseif (strtolower($currentLeave['status']) === 'approved') {
            $response['message'] = 'Cannot update leave that has already been approved.';
        } else {
            // Step 2: Proceed with update
            $stmt = $dbh->prepare("
                UPDATE leave_submissions
                SET leave_type = :leaveType,
                    start_date = :startDate,
                    end_date = :endDate,
                    reason = :reason,
                    status = :status
                WHERE leave_id = :leaveId
            ");
            $stmt->execute([
                ':leaveType' => $leaveType,
                ':startDate' => $startDate,
                ':endDate' => $endDate,
                ':reason' => $reason,
                ':status' => $status,
                ':leaveId' => $leaveId
            ]);

            $response['success'] = true;
            $response['message'] = 'Leave updated successfully.';
        }

    } catch (Exception $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>
