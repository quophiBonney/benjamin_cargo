<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include_once __DIR__ . '/../../includes/dbconnection.php';

$response = ['success' => false, 'message' => '', 'errors' => []];

// Must be in OTP flow
if (!isset($_SESSION['otp_pending_customer'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please start verification again.',
        'redirect' => '/login.php'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$otp_input = preg_replace('/\D+/', '', $_POST['otp'] ?? '');
if ($otp_input === '') {
    echo json_encode(['success' => false, 'message' => 'OTP is required.']);
    exit;
}

$customer_id = (int) $_SESSION['otp_pending_customer'];

// expiry check (session-based)
if (isset($_SESSION['otp_expires']) && time() > $_SESSION['otp_expires']) {
    // clear OTP in DB (optional safe cleanup)
    $stmt = $dbh->prepare("UPDATE customers SET otp_code = NULL WHERE customer_id = ?");
    $stmt->execute([$customer_id]);

    // clear session OTP flow
    unset($_SESSION['otp_pending_customer'], $_SESSION['otp_sent_at'], $_SESSION['otp_expires'], $_SESSION['otp_attempts']);
    echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new code.', 'redirect' => '/login.php']);
    exit;
}

// throttle attempts to prevent brute force
$_SESSION['otp_attempts'] = ($_SESSION['otp_attempts'] ?? 0) + 1;
if ($_SESSION['otp_attempts'] > 5) {
    unset($_SESSION['otp_pending_customer'], $_SESSION['otp_sent_at'], $_SESSION['otp_expires'], $_SESSION['otp_attempts']);
    echo json_encode(['success' => false, 'message' => 'Too many incorrect attempts. Please request a new OTP.', 'redirect' => '/login.php']);
    exit;
}

try {
    $stmt = $dbh->prepare("SELECT otp_code FROM customers WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception('Customer not found.');
    }

    $dbOtp = preg_replace('/\D+/', '', (string)($row['otp_code'] ?? ''));
    if ($dbOtp === '' || $dbOtp !== $otp_input) {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP.']);
        exit;
    }

    // OTP valid -> mark customer verified & create login session
    session_regenerate_id(true);
    unset($_SESSION['otp_pending_customer'], $_SESSION['otp_sent_at'], $_SESSION['otp_expires'], $_SESSION['otp_attempts']);

    $_SESSION['casaid'] = $customer_id;
    $_SESSION['otp_verified'] = true;

    $stmt = $dbh->prepare("UPDATE customers SET otp_code = NULL, otp_verified = 1 WHERE customer_id = ?");
    $stmt->execute([$customer_id]);

    echo json_encode([
        'success' => true,
        'message' => 'OTP verified successfully. Redirecting to your portal...',
        'redirect' => '../customers/shipping-details.php'
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again later.']);
    exit;
}
