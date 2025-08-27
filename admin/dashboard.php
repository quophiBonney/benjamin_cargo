<?php
include_once 'templates/auth.php';
$allowed_roles = ['admin', 'manager'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));

if (!in_array($session_role, $allowed_roles)) {
    header("Location: staffs-dashboard.php");
    exit;
}
?>

<?php include_once 'templates/sidebar.php'; ?>
<?php include_once 'templates/header.php'; ?>

<!-- Main Content -->
  <main class="flex-1 md:ml-64 px-4 transition-all">
    <?php include_once 'templates/app-bar.php';?>
  <!-- Navbar -->

  <?php include_once 'dashboard-cards.php'; ?>
   <div class="grid grid-cols-1 mt-10">
  <?php include_once 'line-chart.php'?>
</div>

</main>
</div>

<!-- Scripts -->
 <?php include_once 'templates/footer.php'; ?>
</body>
</html>
