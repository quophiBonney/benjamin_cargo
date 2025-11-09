<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
include_once __DIR__ . '/../../includes/dbconnection.php';

$response = ['success' => false, 'message' => '', 'errors' => []];

// Check if user is logged in
if (!isset($_SESSION['casaid'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please login again.',
        'redirect' => '/login.php'
    ]);
    exit;
}

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "POST") {
    $rating  = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    $customer_id = $_SESSION['casaid'];

    if ($rating < 1 || $rating > 5) {
        $response['errors'][] = 'Invalid rating value.';
    }

    if (empty($comment)) {
        $response['errors'][] = 'Comment cannot be empty.';
    }

    if (empty($response['errors'])) {
        try {
            $stmt = $dbh->prepare("
                INSERT INTO customer_reviews (customer_id, rating, comment) 
                VALUES (:customer_id, :rating, :comment)
            ");
            $stmt->bindParam(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
            $stmt->bindParam(':comment', $comment, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "✅ Review submitted successfully!";
            } else {
                $response['message'] = "❌ Database error.";
            }
        } catch (PDOException $e) {
            $response['message'] = "❌ PDO Exception: " . $e->getMessage();
        }
    } else {
        $response['message'] = 'Validation failed.';
    }
} elseif ($method === "GET") {
    // Optional: return something when testing in Postman
    $response['success'] = true;
    $response['message'] = "✅ API is working. Use POST with rating & comment to submit a review.";
    $response['sample'] = [
        "rating" => 5,
        "comment" => "Great service!"
    ];
} else {
    http_response_code(405);
    $response['message'] = "❌ Method not allowed. Use POST.";
}

echo json_encode($response);
