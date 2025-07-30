<?php
session_start();
header('Content-Type: application/json');
include_once '../../includes/dbconnection.php';

function respond($success, $message = '', $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

$admin_id = $_SESSION['employee_id'] ?? null;
if (!$admin_id) {
    respond(false, 'Unauthorized access.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

$id = $_POST['id'] ?? null;
$resolution_note = $_POST['resolutionNote'] ?? '';
$status = $_POST['status'] ?? '';
$description = trim($_POST['description'] ?? '');

// if (!$id || !$resolution_note || !$status) {
//     respond(false, 'All fields are required.');
// }

$resolved_at = date('Y-m-d H:i:s');

try {
    $stmt = $dbh->prepare("
        UPDATE room_issues 
        SET  resolved_by = :resolved_by, 
            resolution_note = :resolution_note, 
            resolved_at = :resolved_at, 
            status = :status 
        WHERE id = :id
    ");
    $stmt->execute([
        ':resolved_by' => $admin_id,
        ':resolution_note' => $resolution_note,
        ':resolved_at' => $resolved_at,
        ':status' => $status,
        ':id' => $id
    ]);
    respond(true, 'Issue updated successfully.');
} catch (Exception $e) {
    respond(false, 'Database error: ' . $e->getMessage());
}
