<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

include_once '../../includes/dbconnection.php';

// Utility response function
function respond($success, $message = '', $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

// Handle Dropzone upload
if (isset($_FILES['file']) && empty($_POST)) {
    $uploadDir = '../../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $tmpName = $_FILES['file']['tmp_name'];
    $fileName = time() . '_' . basename($_FILES['file']['name']);
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($tmpName, $targetPath)) {
        respond(true, 'Image uploaded.', ['filePath' => 'uploads/' . $fileName]);
    } else {
        respond(false, 'Image upload failed.');
    }
}

// Validate required fields
$id = $_POST['room_id'] ?? null;
$room_name = trim($_POST['roomName'] ?? '');
$room_number = trim($_POST['roomNumber'] ?? '');
$number_of_rooms = (int)($_POST['numberOfRooms'] ?? 0);
$number_of_ac = (int)($_POST['numberOfAC'] ?? 0);
$number_of_fan = (int)($_POST['numberOfFan'] ?? 0);
$number_of_bathroom = (int)($_POST['numberOfBathroom'] ?? 0);
$price_per_night = (float)($_POST['price'] ?? 0);
$status = trim($_POST['status'] ?? 'available');
$uploaded_image = trim($_POST['uploaded_image'] ?? '');

if (!$id || !$room_name || !$room_number) {
    respond(false, 'Room ID, name, and number are required.');
}

// Get current image from DB
$stmt = $dbh->prepare("SELECT room_image FROM rooms WHERE room_id = ?");
$stmt->execute([$id]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);
$room_image = $current['room_image'] ?? null;

// Handle file upload via standard form input (optional)
if (!empty($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../../uploads/';
    $fileName = time() . '_' . basename($_FILES['room_image']['name']);
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['room_image']['tmp_name'], $targetPath)) {
        $room_image = 'uploads/' . $fileName;
    } else {
        respond(false, 'Standard image upload failed.');
    }
} elseif (!empty($uploaded_image)) {
    $room_image = $uploaded_image;
}

// Run update
$query = "UPDATE rooms SET 
    room_name = ?, 
    room_number = ?, 
    number_of_rooms = ?, 
    number_of_ac = ?, 
    number_of_fan = ?, 
    status = ?, 
    number_of_bathroom = ?, 
    price_per_night = ?, 
    room_image = ?
WHERE room_id = ?";

$stmt = $dbh->prepare($query);
$success = $stmt->execute([
    $room_name,
    $room_number,
    $number_of_rooms,
    $number_of_ac,
    $number_of_fan,
    $status,
    $number_of_bathroom,
    $price_per_night,
    $room_image,
    $id
]);

if ($success) {
    respond(true, 'Room updated successfully.');
} else {
    respond(false, 'Failed to update room.');
}
