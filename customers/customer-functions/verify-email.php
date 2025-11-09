<?php
session_start();
header('Content-Type: application/json');
require __DIR__ . '../../../vendor/autoload.php'; // PHPMailer autoload
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->load();
include_once __DIR__ . '/../../includes/dbconnection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$response = ['success' => false, 'errors' => [], 'message' => ''];

try {
    $raw = trim($_POST['customerInput'] ?? '');
    if ($raw === '') {
        throw new Exception('Email or phone number is required.');
    }

    // Detect whether input is an email or phone (basic)
    if (filter_var($raw, FILTER_VALIDATE_EMAIL)) {
        $sql = "SELECT customer_id, email_address, otp_verified FROM customers WHERE email_address = :input LIMIT 1";
        $stmt = $dbh->prepare($sql);
        $stmt->execute([':input' => $raw]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        $isEmail = true;
    } else {
        // normalize phone (digits only) and use LIKE to be tolerant of formatting
        $normalized = preg_replace('/\D+/', '', $raw);
        if ($normalized === '') {
            throw new Exception('Invalid phone number format.');
        }
        $sql = "SELECT customer_id, phone_number, otp_verified FROM customers WHERE REPLACE(phone_number, ' ', '') LIKE :phone LIMIT 1";
        $stmt = $dbh->prepare($sql);
        $stmt->execute([':phone' => "%$normalized%"]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        $isEmail = false;
    }

    if (!$customer) {
        throw new Exception('No customer found with that email or phone number.');
    }

    // --- If already verified: immediate login + redirect (stable absolute path) ---
    if (!empty($customer['otp_verified'])) {
        session_regenerate_id(true); // security
        $_SESSION['casaid'] = $customer['customer_id'];
        $_SESSION['otp_verified'] = true;

        $response['success'] = true;
        $response['message'] = 'Login Successful';
        // Use an absolute path so the front-end will always redirect correctly
        $response['redirect'] = '../customers/shipping-details.php';
        echo json_encode($response);
        exit;
    }

    // --- Not verified: generate & save OTP, store session metadata ---
    $otp = (string) random_int(100000, 999999);

    $update = $dbh->prepare("UPDATE customers SET otp_code = :otp WHERE customer_id = :id");
    $update->execute([':otp' => $otp, ':id' => $customer['customer_id']]);

    $_SESSION['otp_pending_customer'] = $customer['customer_id'];
    $_SESSION['otp_sent_at'] = time();
    $_SESSION['otp_expires'] = time() + 30 * 60; // 30 minutes expiry
    $_SESSION['otp_attempts'] = 0;

    // Send OTP via email or SMS
    if ($isEmail) {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USERNAME') ?: 'consoldigitalagency@gmail.com';
        $mail->Password = $_ENV['GMAIL_APP_PASSWORD'] ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('consoldigitalagency@gmail.com', 'Benjamin Cargo Logistics');
        $mail->addAddress($customer['email_address']);
        $mail->Subject = "OTP Verification - Benjamin Cargo Logistics";
        $mail->Body = "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'><title>OTP Verification</title></head>
<body style='margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background-color:#f4f4f4;'>
  <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color:#f4f4f4;padding:20px;'>
    <tr><td align='center'>
      <table width='600' cellpadding='0' cellspacing='0' border='0' style='background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.1);'>
        <tr>
          <td align='center' style='background-color:#1e3a8a;padding:20px;'>
            <h1 style='color:#ffffff;margin:0;font-size:24px;'>Benjamin Cargo Logistics</h1>
          </td>
        </tr>
        <tr>
          <td style='padding:30px;'>
            <h2 style='color:#333333;font-size:20px;'>Dear cherished customer,</h2>
            <p style='color:#555555;font-size:16px;line-height:1.6;'>
              To complete your login, use the One-Time Password (OTP) below:
            </p>
            <div style='text-align:center;margin:30px 0;'>
              <span style='display:inline-block;background-color:#1e3a8a;color:#ffffff;font-size:32px;letter-spacing:5px;padding:15px 30px;border-radius:8px;font-weight:bold;'>
                {$otp}
              </span>
            </div>
            <p style='color:#555555;font-size:14px;'>
              This code expires in <strong>5 minutes</strong>. Do not share it with anyone.
            </p>
            <p style='margin-top:30px;font-size:14px;color:#999999;'>
              Regards,<br><strong>Benjamin Cargo Logistics Team</strong>
            </p>
          </td>
        </tr>
        <tr>
          <td align='center' style='background-color:#f9fafb;padding:15px;font-size:12px;color:#888888;'>
            © ".date('Y')." Benjamin Cargo Logistics. All Rights Reserved.
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
";
    $mail->AltBody = "Dear cherished customer, your OTP is: {$otp}. It expires in 30 minutes. Do not share it.";
    $mail->send();
        } else {
        // --- Send OTP via Arkesel SMS API ---
        $apiKey   = $_ENV['ARKESEL_OTP_API_KEY'];
        $phone    = $customer['phone_number'];
        $senderId = "BCL"; 
        $smsText  = urlencode("Your Verification Pin is $otp. It expires in 30 minutes. - Benjamin Cargo Logistics");

        $url = "https://sms.arkesel.com/sms/api?action=send-sms&api_key={$apiKey}&to={$phone}&from={$senderId}&sms={$smsText}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('SMS sending failed: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("SMS sending failed. Response: $result");
        }
    }

    $response['success'] = true;
    $response['message'] = 'OTP sent. Please check your email or phone.';
    $response['redirect'] = '../customers/otp-verification.php';

} catch (Exception $e) {
    $response['errors'][] = $e->getMessage();
    $response['message'] = implode(' ', $response['errors']);
}

echo json_encode($response);
