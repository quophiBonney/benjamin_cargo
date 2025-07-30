<?php
session_start();
// $userID = $_SESSION['employee_id'] ?? null;
// if (!$userID) {
//     echo json_encode(['success' => false, 'errors' => ['Unauthorized request. Please log in.']]);
//     exit;
// }

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);
include('../../includes/dbconnection.php');

$response = ['success' => false];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $salary = trim($_POST['salary'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $hiredDate = trim($_POST['hiredDate'] ?? '');
    $residentialAddress = trim($_POST['residentialAddress'] ?? '');
    $latitude = trim($_POST['latitude'] ?? 0);
    $longitude = trim($_POST['longitude'] ?? 0);
    $ghanaCardNumber = trim($_POST['ghanaCardNumber'] ?? '');
    $password  = trim($_POST['password'] ?? '');
    $role      = trim($_POST['role'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    // Validation
    if (
        empty($fullName) || empty($email) || empty($salary) || empty($position) ||
        empty($ghanaCardNumber) || empty($residentialAddress) || empty($hiredDate) || empty($dob) || empty($phoneNumber) || empty($phoneNumber) || empty($role)
    ) {
        $errors[] = "All fields are required.";
    }

    // Check for duplicates
    if (empty($errors)) {
        $checkStmt = $dbh->prepare("SELECT employee_id FROM employees WHERE email = :email OR phone = :phoneNumber LIMIT 1");
        $checkStmt->execute([
            ':email' => $email,
            ':phoneNumber' => $phoneNumber
        ]);
        if ($checkStmt->rowCount() > 0) {
            $errors[] = "Employee with the same email or phone number already exists.";
        }
    }
 $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    // Insert into DB
    if (empty($errors)) {
        $stmt = $dbh->prepare("INSERT INTO employees 
            (full_name, position, email, phone, ghana_card_number, residential_address, date_hired, salary, longitude, latitude, role, password, dob)
            VALUES
            (:fullName, :position, :email, :phone, :ghanaCardNumber, :residentialAddress, :hiredDate, :salary, :longitude, :latitude, :role, :password, :dob)");
        $stmt->bindParam(':fullName', $fullName);
        $stmt->bindParam(':position', $position);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phoneNumber);
        $stmt->bindParam(':ghanaCardNumber', $ghanaCardNumber);
        $stmt->bindParam(':residentialAddress', $residentialAddress);
        $stmt->bindParam(':dob', $dob);
        $stmt->bindParam(':hiredDate', $hiredDate);
        $stmt->bindParam(':salary', $salary);
        $stmt->bindParam(':longitude', $longitude);
        $stmt->bindParam(':latitude', $latitude);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':password', $hashedPassword);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Employee added successfully.";
        } else {
            $errors[] = "Failed to save to database.";
        }
    }

    $response['errors'] = $errors;
    echo json_encode($response);
    exit;
} else {
    echo json_encode(['success' => false, 'errors' => ['Invalid request method.']]);
    exit;
}
