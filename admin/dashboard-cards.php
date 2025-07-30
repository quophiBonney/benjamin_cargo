<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once 'includes/dbconnection.php';

// Total Employees
$employeeQuery = $dbh->prepare("SELECT COUNT(*) AS total_employees FROM employees");
$employeeQuery->execute();
$employeeResult = $employeeQuery->fetch(PDO::FETCH_ASSOC);
$totalEmployees = $employeeResult['total_employees'];

// Total Rooms
$roomQuery = $dbh->prepare("SELECT COUNT(*) AS total_rooms FROM rooms");
$roomQuery->execute();
$roomResult = $roomQuery->fetch(PDO::FETCH_ASSOC);
$totalRooms = $roomResult['total_rooms'];

// Total Bookings
$bookingQuery = $dbh->prepare("SELECT COUNT(*) AS total_bookings FROM bookings");
$bookingQuery->execute();
$bookingResult = $bookingQuery->fetch(PDO::FETCH_ASSOC);
$totalBookings = $bookingResult['total_bookings'];

try {
$totalBookingAmountQuery = $dbh->prepare("
    SELECT SUM(r.price_per_night * b.number_of_nights) AS total_revenue
    FROM bookings b
    JOIN rooms r ON b.room_number = r.room_number
    WHERE b.status = 'booked'
");
    $totalBookingAmountQuery->execute();
    $totalBookingAmountResult = $totalBookingAmountQuery->fetch(PDO::FETCH_ASSOC);
    $totalBookingAmount = $totalBookingAmountResult['total_revenue'] ?? 0;
} catch (PDOException $e) {
    echo "<pre>Query failed: " . $e->getMessage() . "</pre>";
    $totalBookingAmount = 0;
}

//total salaries 
$salaryQuery = $dbh->prepare("SELECT SUM(salary) AS total_salaries FROM employees");
$salaryQuery->execute();
$salaryResult = $salaryQuery->fetch(PDO::FETCH_ASSOC);
$totalSalaries = $salaryResult['total_salaries'] ?? 0;
?>

<div class="mt-24 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
  <div class="bg-gray-700 text-white p-4 rounded shadow">
    <h2 class="text-md">Total Employees</h2>
    <h3 class="text-2xl font-bold"><?php echo $totalEmployees; ?></h3>
  </div>
  <div class="bg-orange-500 text-white p-4 rounded shadow">
    <h2 class="text-md">Total Bookings</h2>
    <h3 class="text-2xl font-bold"><?php echo $totalBookings; ?></h3>
  </div>
  <div class="bg-yellow-500 text-white p-4 rounded shadow">
    <h2 class="text-md">Total Rooms</h2>
   <h3 class="text-xl font-bold"><?php echo $totalRooms; ?></h3>
  </div>
  <div class="bg-red-500 text-white p-4 rounded shadow">
    <h2 class="text-md">Booking Revenue</h2>
    <h3 class="text-2xl font-bold">₵<?php echo number_format($totalBookingAmount, 2); ?></h3>
  </div>
  <div class="bg-blue-500 text-white p-4 rounded shadow">
    <h2 class="text-md">Total Salaries</h2>
    <h3 class="text-2xl font-bold">₵<?php echo number_format($totalSalaries, 2); ?></h3>
  </div>
</div>
