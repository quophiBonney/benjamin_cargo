<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    header("Location: login.php");
    die();
    exit;
}
header('Content-Type: application/json');
include_once __DIR__ . '/../../../includes/dbconnection.php';

$allowed_roles = ['admin', 'manager', 'hr'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    header("Location: login.php");
    exit;
}

require __DIR__ . '/../../../vendor/autoload.php';
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 3));
$dotenv->load();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$status = trim($_POST['status'] ?? '');
$dateSelected = trim($_POST['dateSelected'] ?? '');
$trackingMessage = trim($_POST['trackingMessage'] ?? '');

if ($status === '' || $dateSelected === '' || $trackingMessage === '') {
    echo json_encode(['success' => false, 'message' => 'Kindly fill all fields']);
    exit;
}

try {
    $stmt = $dbh->prepare("SELECT id, customer_id FROM shipping_manifest WHERE estimated_time_of_arrival = :dateSelected");
    $stmt->execute([':dateSelected' => $dateSelected]);
    $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($shipments)) {
        echo json_encode(['success' => false, 'message' => 'No shipments found']);
        exit;
    }

    $customerIds = array_column($shipments, 'customer_id');
    $placeholders = implode(',', array_fill(0, count($customerIds), '?'));
    $custStmt = $dbh->prepare("SELECT customer_id, phone_number, client_name FROM customers WHERE customer_id IN ($placeholders)");
    $custStmt->execute($customerIds);
    $customers = $custStmt->fetchAll(PDO::FETCH_ASSOC);

    // map phones and names by customer_id
    $customerPhones = array_column($customers, 'phone_number', 'customer_id');
    $customerNames = array_column($customers, 'client_name', 'customer_id');

    $insert = $dbh->prepare("
        INSERT INTO tracking_history (shipping_manifest_id, status, tracking_message, date)
        VALUES (:shipping_manifest_id, :status, :tracking_message, NOW())
    ");

    $update = $dbh->prepare("
        UPDATE shipping_manifest 
        SET status = :status 
        WHERE id = :shipping_manifest_id
    ");

    $addedCount = 0;
    $smsSentCount = 0;

    foreach ($shipments as $ship) {
        $inserted = $insert->execute([
            ':shipping_manifest_id' => $ship['id'],
            ':status' => $status,
            ':tracking_message' => $trackingMessage
        ]);

        if ($inserted) {
            $addedCount++;
        } else {
            throw new Exception("Failed to insert tracking history for shipment ID: " . $ship['id']);
        }
        $update->execute([
            ':status' => $status,
            ':shipping_manifest_id' => $ship['id']
        ]);
        if ($status !== "shipments picked up" &&  !empty($customerPhones[$ship['customer_id']])) {
            $phoneNumber = formatPhoneNumber($customerPhones[$ship['customer_id']]);
            $clientName = $customerNames[$ship['customer_id']] ?? 'Customer';
            if (sendArkeselSMS($phoneNumber, $status, $trackingMessage, $clientName)) {
                $smsSentCount++;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Tracking history appended successfully. Rows added: $addedCount. SMS sent: $smsSentCount"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function formatPhoneNumber($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) === '0') {
        return '233' . substr($phone, 1);
    } elseif (substr($phone, 0, 3) !== '233') {
        return '233' . $phone;
    }
    return $phone;
}

function sendArkeselSMS($to, $status, $trackingMessage, $clientName) {
    $apiKey = $_ENV['ARKESEL_TRACKING_API_KEY']; 
    $url = "www.benjamincargo.com/customers/shipping-details.php";
    $senderId = "BCL";
    $smsText = "Dear $clientName, your $status visit $url to find out more about your package - $trackingMessage";
    $smsText = urlencode($smsText);
    $url = "https://sms.arkesel.com/sms/api?action=send-sms&api_key=$apiKey&to=$to&from=$senderId&sms=$smsText";

    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => true,
        ]);
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            error_log("SMS cURL Error for $to: " . curl_error($ch));
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        $result = json_decode($response, true);
        error_log("SMS API Response for $to: " . $response);
        
        return (isset($result['code']) && strtolower($result['code']) === "ok");
    } catch (Exception $e) {
        error_log("SMS Exception for $to: " . $e->getMessage());
        return false;
    }
}
?>
