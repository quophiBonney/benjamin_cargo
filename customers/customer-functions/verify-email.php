<?php
session_start();
header('Content-Type: application/json');

require __DIR__ . '/../vendor/autoload.php'; // PHPMailer autoload
include('../../admin/includes/dbconnection.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$response = ['success' => false, 'errors' => []];

try {
    $input = trim($_POST['customerInput'] ?? '');
    if (empty($input)) {
        throw new Exception('Email or phone number is required.');
    }

    // Check if input is email or phone
if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
    $sql = "SELECT customer_id, email_address FROM customers WHERE email_address = :input LIMIT 1";
} else {
    $sql = "SELECT customer_id, phone_number FROM customers WHERE phone_number = :input LIMIT 1";
}

    // 1️⃣ Find the customer
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':input', $input, PDO::PARAM_STR);
    $stmt->execute();
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        throw new Exception('No account found with that email or phone number.');
    }

    // 2️⃣ Generate OTP
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['customer_id'] = $customer['customer_id'];
    $_SESSION['otp_pending_customer'] = $customer['customer_id'];

    // 3️⃣ Store OTP in database (expiry removed)
    $update = $dbh->prepare("UPDATE customers SET otp_code = :otp WHERE customer_id = :id");
    $update->bindParam(':otp', $otp, PDO::PARAM_INT);
    $update->bindParam(':id', $customer['customer_id'], PDO::PARAM_INT);
    $update->execute();

    // 4️⃣ Send OTP via email
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'consoldigitalagency@gmail.com';
    $mail->Password = 'potk xgbg ksdh czwb';   // Gmail app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('consoldigitalagency@gmail.com', 'Benjamin Cargo & Logistics');
    $mail->addAddress($customer['email_address']);
    $mail->Subject = 'Your OTP Code';
    $mail->Body = "Your OTP code is: $otp.";

    $mail->send();

    // 5️⃣ Response to frontend
    $response['success'] = true;
    $response['redirect'] = '../customers/otp-verification.php';

} catch (Exception $e) {
    $response['errors'][] = $e->getMessage();
}

echo json_encode($response);
