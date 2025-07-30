<?php
session_start();
header('Content-Type: application/json');
include('../../includes/dbconnection.php');

$response = ['success' => false];
$errors = [];

if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'errors' => ['Unauthorized access. Please log in.']]);
    exit;
}

$userID = $_SESSION['employee_id'];

// Validate inputs
$roomName = trim($_POST['roomName'] ?? '');
$roomNumber = trim($_POST['roomNumber'] ?? '');
$numberOfRooms = trim($_POST['numberOfRooms'] ?? '');
$numberOfAC = trim($_POST['numberOfAC'] ?? '');
$numberOfBathroom = trim($_POST['numberOfBathroom'] ?? '');
$numberOfFan = trim($_POST['numberOfFan'] ?? '');
$price = trim($_POST['price'] ?? '');
$imagePath = '';

if ($roomName === '') $errors[] = 'Room name is required.';
if ($roomNumber === '') $errors[] = 'Room number is required.';
if (!is_numeric($roomNumber)) $errors[] = 'Room number must be a number.';
if ($numberOfRooms === '' || !is_numeric($numberOfRooms)) $errors[] = 'Number of rooms must be numeric.';
if ($numberOfAC === '' || !is_numeric($numberOfAC)) $errors[] = 'Number of AC must be numeric.';
if ($numberOfBathroom === '' || !is_numeric($numberOfBathroom)) $errors[] = 'Number of bathrooms must be numeric.';
if ($numberOfFan === '' || !is_numeric($numberOfFan)) $errors[] = 'Number of fans must be numeric.';
if ($price === '' || !is_numeric($price)) $errors[] = 'Price must be numeric.';

// Handle image upload if available
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $imageTmp = $_FILES['image']['tmp_name'];
    $imageName = basename($_FILES['image']['name']);
    $imageExt = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($imageExt, $allowedExts)) {
        $errors[] = 'Invalid image format. Only JPG, PNG, or WEBP allowed.';
    } else {
        $uploadDir = '../../uploads/rooms/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $uniqueName = uniqid('room_', true) . '.' . $imageExt;
        $targetPath = $uploadDir . $uniqueName;

        if (move_uploaded_file($imageTmp, $targetPath)) {
            $imagePath = 'uploads/rooms/' . $uniqueName;
        } else {
            $errors[] = 'Failed to upload image.';
        }
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Insert into database
try {
    $stmt = $dbh->prepare("
        INSERT INTO rooms (
            room_name, room_number, number_of_rooms, number_of_ac,
            number_of_bathroom, number_of_fan, price_per_night, room_image, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $roomName,
        $roomNumber,
        $numberOfRooms,
        $numberOfAC,
        $numberOfBathroom,
        $numberOfFan,
        $price,
        $imagePath,
        $userID
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => ['Database error: ' . $e->getMessage()]]);
}
