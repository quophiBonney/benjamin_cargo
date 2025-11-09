<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);
include('../../includes/dbconnection.php');

$response = ['success' => false];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $password  = trim($_POST['password'] ?? '');
    $role      = trim($_POST['role'] ?? '');
    // Validation
    if (
        empty($fullName) || empty($email) || empty($phoneNumber) || empty($role) || empty($password)
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
            (full_name, email, phone, role, password)
            VALUES
            (:fullName, :email, :phone, :role, :password)");
        $stmt->bindParam(':fullName', $fullName);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phoneNumber);
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
