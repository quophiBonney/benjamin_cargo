<?php
session_start();
$userID = $_SESSION['employee_id'] ?? null;
if (!$userID) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized request.']);
    exit;
}

ob_clean();
header('Content-Type: application/json');
include_once '../../includes/dbconnection.php';

function respond($success, $message = '', $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

// Collect and sanitize input
$id = $_POST['id'] ?? null;
$guestName = trim($_POST['guestName'] ?? '');
$guestPhone = trim($_POST['guestPhone'] ?? '');
$numberOfGuests = trim($_POST['numberOfGuests'] ?? '');
$idCardNumber = trim($_POST['idCardNumber'] ?? '');
$checkIn = trim($_POST['checkIn'] ?? '');
$checkOut = trim($_POST['checkOut'] ?? '');
$contactPersonName = trim($_POST['contactPersonName'] ?? '');
$contactPersonNumber = trim($_POST['contactPersonNumber'] ?? '');
$roomNumber = trim($_POST['roomNumber'] ?? '');
$numberOfNights = intval($_POST['numberOfNights'] ?? 0);
$status = trim($_POST['status'] ?? '');

// Basic validation
if (!$id || !$guestName || !$roomNumber || !$status) {
    respond(false, 'Required fields are missing.');
}

// Perform the update (room status is handled by trigger)
$updateQuery = "UPDATE bookings SET 
    guest_name = ?, 
    guest_phone = ?, 
    number_of_guests = ?, 
    id_card_number = ?, 
    checkin_date = ?, 
    checkout_date = ?, 
    contact_person_name = ?,
    contact_person_phone = ?,
    room_number = ?,
    number_of_nights = ?,
    status = ?,
    employee_id = ?
WHERE booking_id = ?";

try {
    $stmt = $dbh->prepare($updateQuery);
    $success = $stmt->execute([
         $guestName,
    $guestPhone,
    $numberOfGuests,
    $idCardNumber,
    $checkIn,
    $checkOut,
    $contactPersonName,
    $contactPersonNumber,
    $roomNumber,
    $numberOfNights,
    $status,
    $userID,  // 👈 This is the employee_id being saved
    $id
    ]);

    if ($success && $stmt->rowCount() > 0) {
        respond(true, 'Booking updated successfully.');
    } elseif ($success) {
        respond(true, 'No changes made.');
    } else {
        respond(false, 'Failed to update booking.');
    }
} catch (PDOException $e) {
    respond(false, 'Database error: ' . $e->getMessage());
}
?>
