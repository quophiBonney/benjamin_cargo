<?php
include_once '../includes/dbconnection.php';

$filter_name = $_GET['name'] ?? '';
$filter_start = $_GET['start_date'] ?? '';
$filter_end = $_GET['end_date'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 5;
$offset = ($page - 1) * $perPage;

$year = date('Y');
$month = date('m');
$startDate = $filter_start ?: "$year-$month-01";
$endDate = $filter_end ?: date("Y-m-t", strtotime($startDate));

// Build list of dates
$dates = [];
for ($d = strtotime($startDate); $d <= strtotime($endDate); $d = strtotime("+1 day", $d)) {
    $dates[] = date("Y-m-d", $d);
}

// Filter employees
$params = [];
$query = "SELECT SQL_CALC_FOUND_ROWS employee_id, full_name FROM employees";
if (!empty($filter_name)) {
    $query .= " WHERE full_name = ?";
    $params[] = $filter_name;
}
$query .= " LIMIT $perPage OFFSET $offset";

$stmt = $dbh->prepare($query);
$stmt->execute($params);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count
$total = $dbh->query("SELECT FOUND_ROWS()")->fetchColumn();
$totalPages = ceil($total / $perPage);

// Fetch clock-in/out for each employee/date
foreach ($employees as &$emp) {
    $emp['days'] = [];
    foreach ($dates as $d) {
        $stmtIn = $dbh->prepare("SELECT TIME(clock_in_time) AS clock_in, latitude, longitude FROM clock_ins WHERE employee_id = ? AND DATE(clock_in_time) = ?");
        $stmtIn->execute([$emp['employee_id'], $d]);
        $in = $stmtIn->fetch(PDO::FETCH_ASSOC);

        $stmtOut = $dbh->prepare("SELECT TIME(clock_out_time) AS clock_out FROM clock_outs WHERE employee_id = ? AND DATE(clock_out_time) = ?");
        $stmtOut->execute([$emp['employee_id'], $d]);
        $out = $stmtOut->fetch(PDO::FETCH_ASSOC);

        $emp['days'][$d] = [
            'in' => $in['clock_in'] ?? null,
            'out' => $out['clock_out'] ?? null,
            'lat' => $in['latitude'] ?? null,
            'lng' => $in['longitude'] ?? null
        ];
    }
}

echo json_encode([
    'employees' => $employees,
    'dates' => $dates,
    'current_page' => $page,
    'total_pages' => $totalPages
]);
