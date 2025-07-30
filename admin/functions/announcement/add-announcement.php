<?php
header('Content-Type: application/json');
session_start();

$response = ['success' => false, 'errors' => []];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['errors'][] = 'Method not allowed';
    echo json_encode($response);
    exit;
}

require_once '../../includes/dbconnection.php';

$headline = trim($_POST['headline'] ?? '');
$details = trim($_POST['details'] ?? '');
$created_by = $_SESSION['employee_id'] ?? null;

if (!$headline) $response['errors'][] = 'Headline is required.';
if (!$details) $response['errors'][] = 'Details are required.';
if (!$created_by) $response['errors'][] = 'Invalid session. Please login again.';

if (!empty($response['errors'])) {
    echo json_encode($response);
    exit;
}

// Insert announcement
$stmt = $dbh->prepare("INSERT INTO announcements (headline, details, created_by) VALUES (?, ?, ?)");
$success = $stmt->execute([$headline, $details, $created_by]);

if ($success) {
    $response['success'] = true;
} else {
    $response['errors'][] = 'Database error. Try again.';
}

echo json_encode($response);
