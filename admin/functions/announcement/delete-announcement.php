<?php 
session_start();
$userID = $_SESSION['employee_id'] ?? null;
if (!$userID) {
  header("Location: login.php");
    die();
}
include_once '../../includes/dbconnection.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $announcementId = $_POST['id'] ?? null;

    if ($announcementId) {
        $stmt = $dbh->prepare("DELETE FROM announcements WHERE employee_id = :id");
        $stmt->bindParam(':id', $announcementId, PDO::PARAM_INT);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Failed to delete announcement']);
    exit;
}
?>