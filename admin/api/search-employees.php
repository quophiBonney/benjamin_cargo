<?php
include_once '../includes/dbconnection.php';
header('Content-Type: application/json');

$q = $_GET['q'] ?? '';
$results = [];

if (!empty($q)) {
    $stmt = $dbh->prepare("SELECT full_name FROM employees WHERE full_name LIKE ? ORDER BY full_name LIMIT 10");
    $stmt->execute(["%$q%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode($results);
