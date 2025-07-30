<?php 
include_once 'includes/auth.php';
$allowed_roles = ['admin', 'manager', 'hr'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    header("Location: staffs-dashboard.php");
    exit;
}
?>
<?php
$lat = $_GET['lat'] ?? '';
$lng = $_GET['lng'] ?? '';

if (!is_numeric($lat) || !is_numeric($lng)) {
    die("Invalid coordinates.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GPS Location Viewer</title>
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        iframe {
            width: 100%;
            height: 100vh;
            border: none;
        }
    </style>
</head>
<body>
    <iframe 
        src="https://maps.google.com/maps?q=<?= htmlspecialchars($lat) ?>,<?= htmlspecialchars($lng) ?>&hl=es&z=15&output=embed" 
        allowfullscreen>
    </iframe>
</body>
</html>
