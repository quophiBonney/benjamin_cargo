<?php
declare(strict_types=1);

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/../../includes/dbconnection.php';

$response = ['success' => false, 'errors' => []];

try {
    // Enforce POST method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        $response['errors'][] = 'Method not allowed';
        echo json_encode($response);
        exit;
    }

    // Sanitize inputs
    $fullName    = trim($_POST['fullName']    ?? '');
    $email       = trim($_POST['email']       ?? '');
    $phoneNumber = trim($_POST['phoneNumber'] ?? '');
    $message     = trim($_POST['message']     ?? '');

    // Basic validation
    if ($fullName === '') {
        $response['errors'][] = 'Full name is required.';
    }

    if ($phoneNumber === '') {
        $response['errors'][] = 'Phone number is required.';
    }

    if ($message === '') {
        $response['errors'][] = 'Message is required.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['errors'][] = 'Invalid email format.';
    }

    if ($response['errors']) {
        echo json_encode($response);
        exit;
    }

    // Duplicate check (email/phone + message)
    $checkStmt = $dbh->prepare("
        SELECT id 
        FROM prospects 
        WHERE (email = :email OR phone_number = :phone) 
          AND message = :message
        LIMIT 1
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

    // Insert new record safely
    $stmt = $dbh->prepare("
        INSERT INTO prospects (fullName, email, phone_number, message, sent_at) 
        VALUES (:fullName, :email, :phone, :message, NOW())
    ");
    $success = $stmt->execute([
        ':fullName' => $fullName,
        ':email'    => $email,
        ':phone'    => $phoneNumber,
        ':message'  => $message,
    ]);

    if ($success) {
        $response['success'] = true;
    } else {
        $response['errors'][] = 'Database error. Please try again later.';
    }

    echo json_encode($response);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'errors'  => ['Internal server error. Please contact support.'],
        'debug'   => getenv('APP_ENV') === 'dev' ? $e->getMessage() : null
    ]);
}
