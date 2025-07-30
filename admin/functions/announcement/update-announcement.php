<?php
session_start();
ob_clean();
header('Content-Type: application/json');

include_once '../../includes/dbconnection.php';

// Utility function to return JSON and exit
function respond($success, $message = '', $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

// Check session for logged-in employee
$userID = $_SESSION['employee_id'] ?? null;
if (!$userID) {
    respond(false, 'Unauthorized access. Please log in.');
}

// Ensure POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

// Collect input safely
$id = $_POST['id'] ?? null;
$headline = trim($_POST['headline'] ?? '');
$details = trim($_POST['details'] ?? '');

// Validate required fields
$missing = [];
if (empty($id)) $missing[] = 'id';
if (empty($headline)) $missing[] = 'headline';
if (empty($details)) $missing[] = 'details';

if (!empty($missing)) {
    respond(false, 'Missing fields: ' . implode(', ', $missing));
}

// Build the SQL update query
$updateQuery = "UPDATE announcements SET headline = ?, details = ? WHERE announcement_id = ?";

// Prepare and execute
$stmt = $dbh->prepare($updateQuery);
$success = $stmt->execute([$headline, $details, $id]);

// Response
if ($success) {
    respond(true, 'Announcement updated successfully.');
} else {
    respond(false, 'Failed to update announcement.');
}
