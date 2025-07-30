<?php
session_start();
ob_start();
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

include('../../includes/dbconnection.php');

$response = ['success' => false];
$errors = [];

$userID = $_SESSION['employee_id'] ?? null;
if (!$userID) {
    $response['errors'] = ['Unauthorized request. Please log in.'];
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leaveType = trim($_POST['leaveType'] ?? '');
    $startDate = trim($_POST['startDate'] ?? '');
    $endDate = trim($_POST['endDate'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    // Validate required fields
    if (!$leaveType || !$startDate || !$endDate || !$reason) {
        $response['errors'] = ['All fields are required.'];
        echo json_encode($response);
        exit;
    }

    try {
        $dbh->beginTransaction();

        // Check if user already has a pending leave application
        $stmt = $dbh->prepare("
            SELECT 1 FROM leave_submissions 
            WHERE employee_id = :employee_id AND status = 'pending'
        ");
        $stmt->execute([':employee_id' => $userID]);
        $leaveExists = $stmt->fetchColumn();

        if ($leaveExists) {
            $dbh->rollBack();
            $response['errors'] = ['You already have a pending leave application.'];
        } else {
            // Insert new leave request
            $stmt = $dbh->prepare("
                INSERT INTO leave_submissions (
                    employee_id, leave_type, start_date, end_date, reason, status
                ) VALUES (
                    :employee_id, :leave_type, :start_date, :end_date, :reason, 'pending'
                )
            ");
            $stmt->execute([
                ':employee_id' => $userID,
                ':leave_type' => $leaveType,
                ':start_date' => $startDate,
                ':end_date' => $endDate,
                ':reason' => $reason
            ]);

            $dbh->commit();
            $response['success'] = true;
            $response['message'] = 'Leave application submitted successfully.';
        }
    } catch (Exception $e) {
        $dbh->rollBack();
        $response['errors'] = ['Server error: ' . $e->getMessage()];
    }
}

echo json_encode($response);
?>
