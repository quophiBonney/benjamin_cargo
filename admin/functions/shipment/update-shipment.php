<?php
session_start();
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
$shippingMark = trim($_POST['shippingMark'] ?? '');
$entryDate = trim($_POST['entryDate'] ?? '');
$email = trim($_POST['email'] ?? '');
$packageName = trim($_POST['packageName'] ?? '');
$eta = trim($_POST['eta'] ?? '');
$pieces = trim($_POST['pieces'] ?? ''); 
$cbm = trim($_POST['cbm'] ?? '');
$status = trim($_POST['status'] ?? '');

$requiredFields = [
    'id' => $id,
    'shippingMark' => $shippingMark,
    'entryDate' => $entryDate,
    'packageName' => $packageName,
    'eta' => $eta,
    'pieces' => $pieces,
    'cbm' => $cbm,
    'status' => $status,
];

$missing = [];
foreach ($requiredFields as $field => $value) {
    if (empty($value)) $missing[] = $field;
}

if (!empty($missing)) {
    respond(false, 'Missing fields: ' . implode(', ', $missing));
}

// Build the SQL update query
$updateQuery = "UPDATE shipping_manifest SET 
    shipping_mark = ?,
    entry_date = ?, 
    package_name = ?, 
    eta = ?, 
    number_of_pieces = ?,
    volume_cbm = ?,
    status = ?";

$params = [
    $shippingMark,
    $entryDate,
    $packageName,
    $eta,
    $pieces,
    $cbm,
    $status
];

$updateQuery .= " WHERE id = ?";
$params[] = $id;

// Prepare and execute
$stmt = $dbh->prepare($updateQuery);
$success = $stmt->execute($params);

// Response
if ($success) {
    respond(true, 'Packing list updated successfully.');
} else {
    respond(false, 'Failed to update packing list.');
}
