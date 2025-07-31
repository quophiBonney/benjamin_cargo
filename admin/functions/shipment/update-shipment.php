<?php
header('Content-Type: application/json');
include_once '../../includes/dbconnection.php';

// Get POST data
$shipment_id = $_POST['shipment_id'] ?? null;
$tracking_number = trim($_POST['tracking_number'] ?? '');
$sender_name = trim($_POST['sender_name'] ?? '');
$sender_city = trim($_POST['sender_city'] ?? '');
$sender_country = trim($_POST['sender_country'] ?? '');
$receiver_name = trim($_POST['receiver_name'] ?? '');
$receiver_city = trim($_POST['receiver_city'] ?? '');
$receiver_country = trim($_POST['receiver_country'] ?? '');
$receiver_phone = trim($_POST['receiver_phone'] ?? '');
$package_name = trim($_POST['package_name'] ?? '');
$package_weight = trim($_POST['package_weight'] ?? '');
$package_len = trim($_POST['package_len'] ?? '');
$package_height = trim($_POST['package_height'] ?? '');
$package_description = trim($_POST['package_description'] ?? '');
$package_quantity = trim($_POST['package_quantity'] ?? '');
$package_payment_method = trim($_POST['package_payment_method'] ?? '');
$package_pickup_date = trim($_POST['package_pickup_date'] ?? '');
$package_expected_delivery_date = trim($_POST['package_expected_delivery_date'] ?? '');
$package_carrier = trim($_POST['carrier'] ?? '');
$package_type_of_shipment = trim($_POST['package_type_of_shipment'] ?? '');
$origin = trim($_POST['origin'] ?? '');
$destination = trim($_POST['destination'] ?? '');
$status = trim($_POST['status'] ?? '');

// Validate required fields
if (!$tracking_number || !$receiver_name || !$receiver_phone || !$package_name || !$origin || !$destination) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

// Update shipment
$update = $dbh->prepare("
    UPDATE shipments SET
        tracking_number = :tracking_number,
        sender_name = :sender_name,
        sender_city = :sender_city,
        sender_country = :sender_country,
        receiver_name = :receiver_name,
        receiver_city = :receiver_city,
        receiver_country = :receiver_country,
        receiver_phone = :receiver_phone,
        package_name = :package_name,
        package_weight = :package_weight,
        package_len = :package_len,
        package_height = :package_height,
        package_description = :package_description,
        package_quantity = :package_quantity,
        package_payment_method = :package_payment_method,
        package_pickup_date = :package_pickup_date,
        package_expected_delivery_date = :package_expected_delivery_date,
        package_carrier = :package_carrier,
        package_type_of_shipment = :package_type_of_shipment,
        origin = :origin,
        destination = :destination,
        status = :status
    WHERE shipment_id = :shipment_id
");

// Bind parameters
$update->bindParam(':tracking_number', $tracking_number);
$update->bindParam(':sender_name', $sender_name);
$update->bindParam(':sender_city', $sender_city);
$update->bindParam(':sender_country', $sender_country);
$update->bindParam(':receiver_name', $receiver_name);
$update->bindParam(':receiver_city', $receiver_city);
$update->bindParam(':receiver_country', $receiver_country);
$update->bindParam(':receiver_phone', $receiver_phone);
$update->bindParam(':package_name', $package_name);
$update->bindParam(':package_weight', $package_weight);
$update->bindParam(':package_len', $package_len);
$update->bindParam(':package_height', $package_height);
$update->bindParam(':package_description', $package_description);
$update->bindParam(':package_quantity', $package_quantity);
$update->bindParam(':package_payment_method', $package_payment_method);
$update->bindParam(':package_pickup_date', $package_pickup_date);
$update->bindParam(':package_expected_delivery_date', $package_expected_delivery_date);
$update->bindParam(':package_carrier', $package_carrier);
$update->bindParam(':package_type_of_shipment', $package_type_of_shipment);
$update->bindParam(':origin', $origin);
$update->bindParam(':destination', $destination);
$update->bindParam(':status', $status);
$update->bindParam(':shipment_id', $shipment_id, PDO::PARAM_INT);

// Execute and return result
if ($update->execute()) {
    echo json_encode(['success' => true, 'message' => 'Shipment updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update shipment.']);
}
