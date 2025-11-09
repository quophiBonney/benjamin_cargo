<?php
session_start();
$userID = $_SESSION['employee_id'] ?? null;
if (!$userID) {
    header("Location: login.php");
    die();
}

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);
include('../../includes/dbconnection.php');

$response = ['success' => false];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $amount = trim($_POST['amount'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $expenseDate = trim($_POST['expenseDate'] ?? '');
    $recorded_by = trim($_SESSION['employee_id'] ?? '');

    if (empty($title) || empty($description) || empty($category) || empty($amount) || empty($expenseDate)) {
        $errors[] = "All fields are required.";
    }

    if (empty($errors)) {
        $checkStmt = $dbh->prepare("SELECT expense_date FROM expenses WHERE title = :title AND expense_date = :expenseDate LIMIT 1");
        $checkStmt->execute([
            ':title' => $title,
            ':expenseDate' => $expenseDate
        ]);
        if ($checkStmt->rowCount() > 0) {
            $errors[] = "Expense with the same title and date already exists.";
        }
    }

    if (empty($errors)) {
        $stmt = $dbh->prepare("INSERT INTO expenses (title, description, amount, category, expense_date, recorded_by) 
                               VALUES (:title, :description, :amount, :category, :expenseDate, :recorded_by)");
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':expenseDate', $expenseDate);
        $stmt->bindParam(':recorded_by', $recorded_by);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Expense added successfully.";
        } else {
            $errors[] = "Failed to save to database.";
        }
    }

    $response['errors'] = $errors;
    echo json_encode($response);
    exit;
}



