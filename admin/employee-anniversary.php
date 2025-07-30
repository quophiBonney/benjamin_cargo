<?php
include_once 'includes/dbconnection.php';

$query = "SELECT 
    employee_id,
    full_name,
    date_hired,
    TIMESTAMPDIFF(YEAR, date_hired, CURDATE()) AS years_completed,
    CONCAT('🎊 Happy ', TIMESTAMPDIFF(YEAR, date_hired, CURDATE()), ' Year Work Anniversary!') AS anniversary_message
FROM 
    employees
WHERE 
    date_hired IS NOT NULL
    AND MONTH(date_hired) = MONTH(CURDATE())
    AND DAY(date_hired) = DAY(CURDATE())
    AND TIMESTAMPDIFF(YEAR, date_hired, CURDATE()) >= 1
";

$stmt = $dbh->prepare($query);
$stmt->execute();
$anniversaries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="h-full bg-white rounded shadow p-4 mb-6">
    <h2 class="text-2xl font-bold mb-4">Today's Anniversaries 🎂</h2>
    
    <?php if (count($anniversaries) > 0): ?>
        <ul class="space-y-2">
            <?php foreach ($anniversaries as $employee): ?>
                <li class="p-3 bg-green-100 rounded text-green-800 font-medium">
                    <?= htmlspecialchars($employee['anniversary_message']) ?> - <?= htmlspecialchars($employee['full_name']) ?>
                    (Hired on <?= htmlspecialchars($employee['date_hired']) ?>)
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="text-gray-500">No anniversaries today.</p>
    <?php endif; ?>
</div>
