<?php
include_once 'dbconnection.php';

if (!isset($_SESSION['casaid'])) {
    $client_name = 'Guest';
} else {
    $customer_id = $_SESSION['casaid'];

    $stmt = $dbh->prepare("SELECT client_name FROM customers WHERE customer_id = :id LIMIT 1");
    $stmt->bindParam(':id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $client_name = $row ? $row['client_name'] : 'Guest';
}
?>
  <button id="menu-toggle" class="fixed top-2 right-4 z-50 md:hidden bg-gray-500 text-white p-2 rounded-md shadow-lg text-xl w-8 h-10">
  <i class="fa-solid fa-bars"></i>
</button>
<header class="backdrop-blur-md md:mx-4 fixed top-0 left-0 md:left-64 right-0 z-30 bg-white/40 shadow-md p-4 md:p-2 flex justify-between items-center md:mt-2 md:rounded-lg">
  <div>
    <h1 class="text-xl font-semibold">Hello, <span class="text-gray-600"><?php echo $client_name?></span> </h1>
  </div>
  <div class="hidden md:flex gap-2">
    <form method="POST" action="includes/logout.php">
      <button type="submit" class="mr-2 mt-2 bg-red-500 text-white p-2 rounded hover:bg-gray-600 animate-pulse">
        Sign Out
      </button>
    </form>
</div>
  </div>
</header>



