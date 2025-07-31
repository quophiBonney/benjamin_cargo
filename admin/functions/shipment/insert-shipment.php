<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

$response = ['success' => false, 'errors' => []];

try {
    include_once('../../includes/dbconnection.php'); // adjust the path

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Capture and sanitize form input
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

        // Basic required field validation
        if (!$tracking_number || !$receiver_name || !$receiver_phone || !$package_name || !$origin || !$destination) {
            $response['errors'][] = 'Please fill in all required fields.';
            echo json_encode($response);
            exit;
        }

        // Check for duplicate tracking number
        $check = $dbh->prepare("SELECT COUNT(*) FROM shipments WHERE tracking_number = :tracking_number");
        $check->execute([':tracking_number' => $tracking_number]);
        if ($check->fetchColumn() > 0) {
            $response['errors'][] = 'Tracking number already exists.';
            echo json_encode($response);
            exit;
        }

        // Insert shipment
        $sql = "INSERT INTO shipments (
            tracking_number, 
            sender_name, 
            sender_city, 
            sender_country,
            receiver_name, 
            receiver_city, 
            receiver_country, 
            receiver_phone,
            package_name, 
            package_weight, 
            package_len, 
            package_height,
            package_description, 
            package_quantity, package_payment_method,
            package_pickup_date,
            package_expected_delivery_date, package_carrier, package_type_of_shipment,
            origin, 
            destination
        ) VALUES (
            :tracking_number, 
            :sender_name, 
            :sender_city, 
            :sender_country,
            :receiver_name, 
            :receiver_city, 
            :receiver_country, 
            :receiver_phone,
            :package_name, 
            :package_weight, 
            :package_len, 
            :package_height,
            :package_description, :package_quantity, :package_payment_method,:package_pickup_date,
            :package_expected_delivery_date, :package_carrier, :package_type_of_shipment,
            :origin, 
            :destination
        )";

        $stmt = $dbh->prepare($sql);
        $stmt->execute([
            ':tracking_number' => $tracking_number,
            ':sender_name' => $sender_name,
            ':sender_city' => $sender_city,
            ':sender_country' => $sender_country,
            ':receiver_name' => $receiver_name,
            ':receiver_city' => $receiver_city,
            ':receiver_country' => $receiver_country,
            ':receiver_phone' => $receiver_phone,
            ':package_name' => $package_name,
            ':package_weight' => $package_weight,
            ':package_len' => $package_len,
            ':package_height' => $package_height,
            ':package_description' => $package_description,
            ':package_quantity' => $package_quantity,
            ':package_payment_method' => $package_payment_method,
            ':package_pickup_date' => $package_pickup_date,
            ':package_expected_delivery_date' => $package_expected_delivery_date,
            ':package_carrier' => $package_carrier,
            ':package_type_of_shipment' => $package_type_of_shipment,
            ':origin' => $origin,
            ':destination' => $destination
        ]);

        $response['success'] = true;
        $response['message'] = 'Shipment added successfully.';
    } else {
        $response['errors'][] = 'Invalid request method.';
    }
} catch (Exception $e) {
    $response['errors'][] = $e->getMessage();
}

echo json_encode($response);
?>
