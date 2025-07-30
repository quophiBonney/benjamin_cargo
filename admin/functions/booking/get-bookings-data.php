<?php
include_once '../../includes/dbconnection.php';

$filter = $_GET['filter'] ?? 'monthly';
$filter = strtolower($filter);

switch ($filter) {
  case 'daily':
    $query = "
      SELECT DATE(created_at) AS day, COUNT(*) AS total
      FROM bookings
      GROUP BY day
      ORDER BY day
    ";
    $label_format = fn($day) => date('M j', strtotime($day)); // e.g., Jul 26
    break;

  case 'weekly':
    $query = "
      SELECT YEAR(created_at) AS y, WEEK(created_at, 1) AS w, COUNT(*) AS total
      FROM bookings
      GROUP BY y, w
      ORDER BY y, w
    ";
    $label_format = fn($y, $w) => "Week $w - $y";
    break;

  case 'yearly':
    $query = "
      SELECT YEAR(created_at) AS year, COUNT(*) AS total
      FROM bookings
      GROUP BY year
      ORDER BY year
    ";
    $label_format = fn($year) => (string)$year;
    break;

  default: // monthly
    $query = "
      SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS total
      FROM bookings
      GROUP BY month
      ORDER BY month
    ";
    $label_format = fn($month) => $month;
    break;
}


$stmt = $dbh->prepare($query);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format labels and values
$labels = [];
$values = [];

foreach ($data as $row) {
  if ($filter === 'daily') {
    $labels[] = $label_format($row['day']);
  } elseif ($filter === 'weekly') {
    $labels[] = $label_format($row['y'], $row['w']);
  } elseif ($filter === 'yearly') {
    $labels[] = $label_format($row['year']);
  } else {
    $labels[] = $label_format($row['month']);
  }
  $values[] = (int)$row['total'];
}


echo json_encode([
  'labels' => $labels,
  'values' => $values
]);
