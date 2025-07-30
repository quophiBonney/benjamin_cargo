<?php 
include_once 'includes/dbconnection.php';
$query = "SELECT * FROM announcements";
$stmt = $dbh->prepare($query);
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include_once 'includes/sidebar.php'; ?>
<?php include_once 'includes/header.php'; ?>

<!-- Main Content -->
<main class="flex-1 md:ml-64 px-4 transition-all">
    <?php include_once 'includes/app-bar.php'; ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-24">
        <?php include_once 'employee-birthday.php'; ?>
        <?php include_once 'employee-anniversary.php'?>
        <div class="">
  <div class="swiper">
    <div class="swiper-wrapper">
      <?php foreach ($announcements as $announcement): ?>
        <div class="swiper-slide">
          <div class="bg-white/80 backdrop-blur-lg border border-gray-200 p-6 rounded-md shadow transition-all duration-300 hover:shadow-lg h-full">
            <h2 class="text-xl font-bold text-gray-800 mb-6 text-center">📢 Latest Announcements</h2>
            <h3 class="text-xl font-semibold text-gray-900 mb-3"><?= htmlspecialchars($announcement['headline']) ?></h3>
            <p class="text-gray-700 leading-relaxed mb-4"><?= htmlspecialchars($announcement['details']) ?></p>
            <p class="text-sm text-gray-500 font-medium">🗓️ <?= date('F j, Y', strtotime($announcement['created_at'])) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
    </div>



</main>

<?php include_once 'includes/footer.php'; ?>
<script>
  const swiper = new Swiper('.swiper', {
    loop: true,
    slidesPerView: 1,
    spaceBetween: 30,
    autoplay: {
      delay: 6000,
      disableOnInteraction: false
    },
    allowTouchMove: false, // optional: disables swipe on touch
  });
</script>

</body>
</html>
