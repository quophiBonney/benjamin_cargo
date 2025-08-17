<?php
header('Content-Type: application/json');
include_once '../../includes/dbconnection.php';

$mark = $_POST['shippingMarkNumber'] ?? '';

if (empty($mark)) {
    echo json_encode([
        'success' => false,
        'errors' => ['Shipping mark number is required']
    ]);
    exit;
}

$stmt = $dbh->prepare("SELECT * FROM shipping_manifest WHERE shipping_mark = :mark");
$stmt->execute([':mark' => $mark]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($data && count($data) > 0) {
    echo json_encode([
        'success' => true,
        'message' => 'Shipping mark found.',
        'shippingMarkNumber' => $mark, // ✅ Send mark for redirect
        'records' => $data // ✅ Send all matching rows
    ]);
} else {
    echo json_encode([
        'success' => false,
        'errors' => ['Shipping mark not found']
    ]);
}
