<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
ob_clean();
header('Content-Type: application/json');

include_once __DIR__ . '/../../../includes/dbconnection.php';

$allowed_roles = ['admin', 'manager', 'hr'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    header("Location: login.php");
    exit;
}

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
$receiptNumber = trim($_POST['receipt_number'] ?? '');
$shippingMark = trim($_POST['shippingMark'] ?? '');
$entryDate = trim($_POST['entryDate'] ?? '');
$email = trim($_POST['email'] ?? '');
$packageName = trim($_POST['packageName'] ?? '');
$eta = trim($_POST['eta'] ?? '');
$pieces = trim($_POST['pieces'] ?? ''); 
$cbm = trim($_POST['cbm'] ?? '');
$status = trim($_POST['status'] ?? '');
$departureDate = trim($_POST['departure_date'] ?? '');
$expressTrackingNumber = trim($_POST['expressTrackingNumber'] ?? '');
$eto = trim($_POST['eto'] ?? '');
$loadingDate = trim($_POST['loadingDate'] ?? '');
$note = trim($_POST['note'] ?? '');
$supplierNumber = trim($_POST['supplier_number'] ?? '');

// Required fields
$requiredFields = [
    'id' => $id,
    'shippingMark' => $shippingMark,
    'entryDate' => $entryDate,
    'packageName' => $packageName,
    'departureDate' => $departureDate,
    "eto" => $eto,
    "loadingDate" => $loadingDate,
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

// Build the SQL update query correctly with placeholders
$updateQuery = "UPDATE shipping_manifest SET 
    receipt_number = ?,
    shipping_mark = ?,
    entry_date = ?, 
    package_name = ?, 
    number_of_pieces = ?,
    volume_cbm = ?,
    express_tracking_no = ?,
    loading_date = ?,
    departure_date = ?,
    estimated_time_of_arrival = ?,
    estimated_time_of_offloading = ?,
    supplier_number = ?,
    note = ?,
    status = ?
    WHERE id = ?";

$params = [
    $receiptNumber,
    $shippingMark,
    $entryDate,
    $packageName,
    $pieces,
    $cbm,
    $expressTrackingNumber,
    $loadingDate,
    $departureDate,
    $eta,
    $eto,
    $supplierNumber,
    $note,
    $status,
    $id
];

// Prepare and execute
$stmt = $dbh->prepare($updateQuery);
$success = $stmt->execute($params);

// Response
if ($success) {
    respond(true, 'Packing list updated successfully.');
} else {
    respond(false, 'Failed to update packing list.');
}
