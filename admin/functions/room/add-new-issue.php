<?php
session_start();
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

include('../../includes/dbconnection.php');

$userID = $_SESSION['employee_id'] ?? null;
$response = ['success' => false];
$errors = [];

if (!$userID) {
    echo json_encode(['success' => false, 'errors' => ['Unauthorized request. Please log in.']]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_number = trim($_POST['room_number'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($room_number) || empty($description)) {
        $errors[] = "Room number and issue description are required.";
    }

    if (empty($errors)) {
        // Check if room exists
        $roomStmt = $dbh->prepare("SELECT room_id FROM rooms WHERE room_number = :room_number LIMIT 1");
        $roomStmt->execute([':room_number' => $room_number]);
        $room = $roomStmt->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            $errors[] = "Room number '{$room_number}' does not exist.";
        } else {
            $room_id = $room['room_id'];

            // Insert into room_issues
            $insertStmt = $dbh->prepare("INSERT INTO room_issues (room_id, reported_by, issue_description) 
                                         VALUES (:room_id, :reported_by, :issue_description)");
            $insertStmt->bindParam(':room_id', $room_id);
            $insertStmt->bindParam(':reported_by', $userID);
            $insertStmt->bindParam(':issue_description', $description);

            if ($insertStmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Room issue reported successfully.";
            } else {
                $errors[] = "Failed to save room issue. Please try again.";
            }
        }
    }

    $response['errors'] = $errors;
    echo json_encode($response);
    exit;
}
?>
