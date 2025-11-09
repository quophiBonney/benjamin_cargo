<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    die();
}
ob_clean();
header('Content-Type: application/json');

include_once __DIR__ . '/../../../includes/dbconnection.php';

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
$sn = trim($_POST['sn'] ?? '');
$clientName = trim($_POST['clientName'] ?? '');
$email = trim($_POST['email'] ?? '');
$code = trim($_POST['code'] ?? '');
$location = trim($_POST['location'] ?? '');
$phoneNumber = trim($_POST['phoneNumber'] ?? '');
$sea = trim($_POST['sea']);
$air = trim($_POST['air']);

// Validation: Required fields except password
$requiredFields = [
    'id' => $id,
    'sn' => $sn,
    'clientName' => $clientName,
    'email' => $email,
    'location' => $location,
    'code' => $code,
    'phoneNumber' => $phoneNumber,
    'sea' => $sea,
    'air' => $air
];

$missing = [];
foreach ($requiredFields as $field => $value) {
    if (empty($value)) $missing[] = $field;
}

if (!empty($missing)) {
    respond(false, 'Missing fields: ' . implode(', ', $missing));
}

// Build the SQL update query
$updateQuery = "UPDATE customers SET 
    sn = ?,
    client_name = ?, 
    email_address = ?, 
    phone_number = ?,
    location = ?, 
    code = ?,
    sea = ?,
    air = ?";

$params = [
    $sn,
    $clientName,
    $email,
    $phoneNumber,
    $location,
    $code,
    $sea,
    $air
];

$updateQuery .= " WHERE customer_id = ?";
$params[] = $id;

// Prepare and execute
$stmt = $dbh->prepare($updateQuery);
$success = $stmt->execute($params);

// Response
if ($success) {
    respond(true, 'Customer updated successfully.');
} else {
    respond(false, 'Failed to update customer.');
}
