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
$fullName = trim($_POST['fullName'] ?? '');
$email = trim($_POST['email'] ?? '');
$position = trim($_POST['position'] ?? '');
$salary = trim($_POST['salary'] ?? '');
$ghanaCardNumber = trim($_POST['ghanaCardNumber'] ?? '');
$hiredDate = trim($_POST['hiredDate'] ?? '');
$residentialAddress = trim($_POST['residentialAddress'] ?? '');
$status = trim($_POST['status'] ?? '');
$phoneNumber = trim($_POST['phoneNumber'] ?? '');
$role = trim($_POST['role'] ?? '');
$password = trim($_POST['password'] ?? '');
$dob = trim($_POST['dob'] ?? '');

// Validation: Required fields except password (which is optional during update)
$requiredFields = [
    'id' => $id,
    'fullName' => $fullName,
    'email' => $email,
    'position' => $position,
    'salary' => $salary,
    'ghanaCardNumber' => $ghanaCardNumber,
    'hiredDate' => $hiredDate,
    'residentialAddress' => $residentialAddress,
    'status' => $status,
    'phoneNumber' => $phoneNumber,
    'role' => $role
];

$missing = [];
foreach ($requiredFields as $field => $value) {
    if (empty($value)) $missing[] = $field;
}

if (!empty($missing)) {
    respond(false, 'Missing fields: ' . implode(', ', $missing));
}

// Build the SQL update query
$updateQuery = "UPDATE employees SET 
    full_name = ?, 
    position = ?, 
    email = ?, 
    phone = ?, 
    ghana_card_number = ?, 
    residential_address = ?,  
    date_hired = ?, 
    salary = ?,
    role = ?,
    dob = ?,
    status = ?";

// Add password only if provided
$params = [
    $fullName,
    $position,
    $email,
    $phoneNumber,
    $ghanaCardNumber,
    $residentialAddress,
    $hiredDate,
    $salary,
    $role,
    $dob,
    $status
];

if (!empty($password)) {
    $updateQuery .= ", password = ?";
    $params[] = password_hash($password, PASSWORD_DEFAULT);
}

$updateQuery .= " WHERE employee_id = ?";
$params[] = $id;

// Prepare and execute
$stmt = $dbh->prepare($updateQuery);
$success = $stmt->execute($params);

// Response
if ($success) {
    respond(true, 'Employee updated successfully.');
} else {
    respond(false, 'Failed to update employee.');
}
