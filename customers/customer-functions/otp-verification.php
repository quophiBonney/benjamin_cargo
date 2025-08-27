<?php
session_start();
 include_once __DIR__ . '/../../includes/dbconnection.php';

header('Content-Type: application/json');

// Ensure the user came from login with OTP pending
if (!isset($_SESSION['otp_pending_customer'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please log in again.',
        'redirect' => 'login.php'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_input = trim($_POST['otp'] ?? '');

    if ($otp_input === '') {
        echo json_encode([
            'success' => false,
            'message' => 'OTP is required.'
        ]);
        exit;
    }

    $customer_id = $_SESSION['otp_pending_customer'];

    // Fetch OTP from DB
    $stmt = $dbh->prepare("SELECT otp_code FROM customers WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode([
            'success' => false,
            'message' => 'Customer not found.'
        ]);
        exit;
    }

    // Check OTP match
    if ($row['otp_code'] !== $otp_input) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid OTP. Please try again.'
        ]);
        exit;
    }

    // OTP is correct — log the user in
    unset($_SESSION['otp_pending_customer']);
    $_SESSION['casaid'] = $customer_id;

    // Optional: clear OTP in DB
    $stmt = $dbh->prepare("UPDATE customers SET otp_code = NULL WHERE customer_id = ?");
    $stmt->execute([$customer_id]);

    echo json_encode([
        'success' => true,
        'message' => 'OTP verified successfully.',
        'redirect' => 'shipping-details.php'
    ]);
    exit;
}

echo json_encode([
    'success' => false,
    'message' => 'Invalid request.'
]);
