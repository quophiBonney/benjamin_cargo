<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

 include_once __DIR__ . '/../../includes/dbconnection.php';

$response = ['success' => false, 'errors' => []];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        $response['errors'][] = 'Method not allowed';
        echo json_encode($response);
        exit;
    }

    $fullName    = trim($_POST['fullName'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $message     = trim($_POST['message'] ?? '');

    if (!$fullName)    $response['errors'][] = 'Full name is required.';
    if (!$phoneNumber) $response['errors'][] = 'Phone number is required.';
    if (!$message)     $response['errors'][] = 'Message is required.';

    if (!empty($response['errors'])) {
        echo json_encode($response);
        exit;
    }

    // Duplicate check
    $checkStmt = $dbh->prepare("
        SELECT id FROM prospects 
        WHERE (email = :email OR phone_number = :phone) 
          AND message = :message
    ");
    $checkStmt->execute([
        ':email'   => $email,
        ':phone'   => $phoneNumber,
        ':message' => $message
    ]);

    if ($checkStmt->fetch()) {
        $response['errors'][] = 'You have already contacted Benjamin Cargo Logistics with this message.';
        echo json_encode($response);
        exit;
    }

    // Insert new record
    $stmt = $dbh->prepare("
        INSERT INTO prospects (fullName, email, phone_number, message, sent_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $success = $stmt->execute([$fullName, $email, $phoneNumber, $message]);

    if ($success) {
        $response['success'] = true;
    } else {
        $response['errors'][] = 'Database error. Try again.';
    }

    echo json_encode($response);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'errors' => [$e->getMessage()]]);
}
