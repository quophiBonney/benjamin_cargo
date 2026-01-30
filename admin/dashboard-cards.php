<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

 include_once __DIR__ . '../../includes/dbconnection.php';
// Total Employees
$employeeQuery = $dbh->prepare("SELECT COUNT(*) AS total_employees FROM employees");
$employeeQuery->execute();
$employeeResult = $employeeQuery->fetch(PDO::FETCH_ASSOC);
$totalEmployees = $employeeResult['total_employees'];

// Total Rooms
$shipmentQuery = $dbh->prepare("SELECT COUNT(*) AS total_shipments FROM shipping_manifest");
$shipmentQuery->execute();
$shipmentResult = $shipmentQuery->fetch(PDO::FETCH_ASSOC);
$totalShipments = $shipmentResult['total_shipments'];

// Total Bookings
$customerQuery = $dbh->prepare("SELECT COUNT(*) AS total_customers FROM customers");
$customerQuery->execute();
$customerResult = $customerQuery->fetch(PDO::FETCH_ASSOC);
$totalCustomers = $customerResult['total_customers'];

$prospectQuery = $dbh->prepare("SELECT COUNT(*) AS total_prospects FROM prospects");
$prospectQuery->execute();
$prospectResult = $prospectQuery->fetch(PDO::FETCH_ASSOC);
$totalProspects = $prospectResult['total_prospects'];
?>

<div class="mt-24 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <div class="bg-gray-700 text-white p-4 rounded shadow">
    <h2 class="text-md mb-4">Total Packing List</h2>
    <h3 class="text-2xl font-bold"><?php echo $totalShipments; ?></h3>
  </div>
  <div class="bg-orange-500 text-white p-4 rounded shadow">
    <h2 class="text-md mb-4">Total Customers</h2>
    <h3 class="text-2xl font-bold"><?php echo $totalCustomers; ?></h3>
  </div>
  <div class="bg-yellow-500 text-white p-4 rounded shadow">
    <h2 class="text-md mb-4">Total Users</h2>
   <h3 class="text-2xl font-bold"><?php echo $totalEmployees; ?></h3>
  </div>
  <div class="bg-indigo-500 text-white p-4 rounded shadow">
    <h2 class="text-md mb-4">Total Prospects</h2>
   <h3 class="text-2xl font-bold"><?php echo $totalProspects; ?></h3>
  </div>
</div>
