<?php
session_start();
$userID = $_SESSION['employee_id'] ?? null;
if (!$userID) {
    echo json_encode(['success' => false, 'errors' => ['Unauthorized request. Please log in.']]);
    exit;
}

include_once 'includes/dbconnection.php'; // Ensure you include your DB connection

$query = "SELECT 
    employee_id,
    full_name,
    DATE_FORMAT(dob, '%M %d') AS birth_date,
    '🎉 Happy Birthday!' AS birthday_message
FROM 
    employees
WHERE 
    MONTH(dob) = MONTH(CURDATE()) 
    AND DAY(dob) = DAY(CURDATE())";

$stmt = $dbh->prepare($query);
$stmt->execute();
$birthdays = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="h-full bg-white rounded shadow p-4 mb-6">
    <h2 class="text-2xl font-bold mb-4">Today's Birthday 🎂</h2>
    
    <?php if (count($birthdays) > 0): ?>
        <ul class="space-y-2">
            <?php foreach ($birthdays as $employee): ?>
                <li class="animate-bounce p-3 bg-green-100 rounded text-green-800 font-medium">
                    <?= $employee['birthday_message'] ?> <?= htmlspecialchars($employee['full_name']) ?> (Born on <?= $employee['birth_date'] ?>)
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="text-gray-500">No birthday today.</p>
    <?php endif; ?>
</div>
