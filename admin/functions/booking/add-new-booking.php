<?php
session_start();
$userID = $_SESSION['employee_id'] ?? null;
if (!$userID) {
    $errors[] = "Unauthorized request. Please log in.";
}
ob_start();
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

include('../../includes/dbconnection.php');

$response = ['success' => false];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guestName = trim($_POST['guestName'] ?? '');
    $numberOfGuests = intval($_POST['numberOfGuests'] ?? 0);
    $contactPersonName = trim($_POST['contactPersonName'] ?? '');
    $contactPersonPhone = trim($_POST['contactPersonPhone'] ?? '');
    $roomNumber = intval($_POST['roomNumber'] ?? 0);
    $checkIn = trim($_POST['checkIn'] ?? '');
    $checkOut = trim($_POST['checkOut'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $ghanaCardNumber = trim($_POST['ghanaCardNumber'] ?? '');
    $guestPhone = trim($_POST['guestPhone'] ?? '');
    $numberOfNights = intval($_POST['numberOfNights'] ?? 0);

    // Validate required fields
    if (
        !$guestName || !$roomNumber || !$checkIn || !$checkOut || !$status || !$guestPhone ||
        !$contactPersonName || !$contactPersonPhone || !$numberOfGuests
    ) {
        $response['errors'] = ['Fill all required fields'];
        echo json_encode($response);
        exit;
    }

    try {
        $dbh->beginTransaction();

        // Step 1: Check if the room exists and is available
        $stmt = $dbh->prepare("SELECT status FROM rooms WHERE room_number = :room_number FOR UPDATE");
        $stmt->execute([':room_number' => $roomNumber]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$room) {
            $response['errors'] = ['Room not found.'];
            $dbh->rollBack();
        } elseif (strtolower($room['status']) !== 'available') {
            $response['errors'] = ['Room is not available.'];
            $dbh->rollBack();
        } else {
            // Step 2: Insert booking
            $stmt = $dbh->prepare("
                INSERT INTO bookings (
                    room_number, checkin_date, checkout_date, number_of_guests, status,
                    guest_name, guest_phone, contact_person_name, contact_person_phone,
                    id_card_number, number_of_nights
                ) VALUES (
                    :room_number, :check_in, :check_out, :numberOfGuests, :status,
                    :guestName, :guestPhone, :contactPersonName, :contactPersonPhone,
                    :ghanaCardNumber, :numberOfNights
                )
            ");
            $stmt->execute([
                ':room_number' => $roomNumber,
                ':check_in' => $checkIn,
                ':check_out' => $checkOut,
                ':numberOfGuests' => $numberOfGuests,
                ':status' => $status,
                ':guestName' => $guestName,
                ':guestPhone' => $guestPhone,
                ':contactPersonName' => $contactPersonName,
                ':contactPersonPhone' => $contactPersonPhone,
                ':ghanaCardNumber' => $ghanaCardNumber,
                ':numberOfNights' => $numberOfNights

            ]);

            $dbh->commit();
            $response['success'] = true;
            $response['message'] = 'Booking successful!';
        }
    } catch (Exception $e) {
        $dbh->rollBack();
        $response['errors'] = ['Server error: ' . $e->getMessage()];
    }
}

echo json_encode($response);
?>
