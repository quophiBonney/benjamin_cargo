<?php
header('Content-Type: application/json');
include_once '../../includes/dbconnection.php';

// Force PDO to throw exceptions
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$status = trim($_POST['status'] ?? '');
if ($status === '') {
    echo json_encode(['success' => false, 'message' => 'Status is required']);
    exit;
}

try {
    // Fetch all shipping marks
    $stmt = $dbh->query("SELECT id FROM shipping_manifest");
    $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($shipments)) {
        echo json_encode(['success' => false, 'message' => 'No shipments found']);
        exit;
    }

    // Prepare insert for tracking_history
    $insert = $dbh->prepare("
        INSERT INTO tracking_history (shipping_manifest_id, status, date)
        VALUES (:shipping_manifest_id, :status, NOW())
    ");

    // Prepare update for shipping_manifest
    $update = $dbh->prepare("
        UPDATE shipping_manifest
        SET status = :status
        WHERE id = :shipping_manifest_id
    ");

    $addedCount = 0;

    foreach ($shipments as $ship) {
        // Append new status to tracking_history
        $inserted = $insert->execute([
            ':shipping_manifest_id' => $ship['id'],
            ':status' => $status
        ]);

        if ($inserted) {
            $addedCount++;
        } else {
            throw new Exception("Failed to insert tracking history for shipment ID: " . $ship['id']);
        }

        // Update the latest status in shipping_manifest
        $update->execute([
            ':status' => $status,
            ':shipping_manifest_id' => $ship['id']
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => "All shipment statuses appended successfully. Rows added to tracking_history: $addedCount"
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
