<?php
include_once __DIR__ . '/../includes/dbconnection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$tracking_number = trim($_POST['tracking_number'] ?? '');

if (empty($tracking_number)) {
    echo json_encode(['status' => 'error', 'message' => 'Tracking number is required.']);
    exit;
}

try {
    $stmt = $dbh->prepare("SELECT entry_date FROM shipping_manifest WHERE express_tracking_no = :tracking_number LIMIT 1");
    $stmt->execute([':tracking_number' => $tracking_number]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode(['status' => 'success', 'entry_date' => $result['entry_date']]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No record found for the provided tracking number.']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
}
?>
