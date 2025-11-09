<?php
include_once __DIR__ . '/includes/dbconnection.php';

$reviewId = $_GET['id'] ?? null;
$review = null;

if ($reviewId) {
    $query = "SELECT r.id, r.rating, r.comment, r.created_at, c.client_name AS customer_name
              FROM customer_reviews r
              JOIN customers c ON r.customer_id = c.customer_id
              WHERE r.id = :id LIMIT 1";
    $stmt = $dbh->prepare($query);
    $stmt->execute([':id' => $reviewId]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Prepare OG/Twitter meta content
if ($review) {
    $title = "⭐ " . intval($review['rating']) . "/5 from " . htmlspecialchars($review['customer_name']);
    $description = $review['comment'] ?: "Customer left a review on Benjamin Cargo Logistics.";
    $url = "https://www.benjamincargo.com/reviews.php?id=" . urlencode($review['id']);
    $image = "https://www.benjamincargo.com/assets/logo.png";
} else {
    $title = "Customer Reviews - Benjamin Cargo Logistics";
    $description = "Read what our customers are saying about Benjamin Cargo Logistics.";
    $url = "https://www.benjamincargo.com/reviews.php";
    $image = "https://www.benjamincargo.com/assets/ship1.png";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($title) ?></title>

  <!-- Open Graph -->
  <meta property="og:title" content="<?= htmlspecialchars($title) ?>" />
  <meta property="og:description" content="<?= htmlspecialchars($description) ?>" />
  <meta property="og:image" content="<?= $image ?>" />
  <meta property="og:url" content="<?= $url ?>" />
  <meta property="og:type" content="article" />
  <meta property="og:site_name" content="Benjamin Cargo Logistics" />

  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="<?= htmlspecialchars($title) ?>" />
  <meta name="twitter:description" content="<?= htmlspecialchars($description) ?>" />
  <meta name="twitter:image" content="<?= $image ?>" />

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/@tailwindcss/forms"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
</head>
<body class="bg-gradient-to-br from-slate-100 to-slate-200 min-h-screen flex justify-center items-center font-sans">

  <div class="w-full max-w-4xl mx-auto p-6">
    <!-- Logo -->
    <div class="flex justify-center mb-8">
      <img src="https://www.benjamincargo.com/assets/logo.png" class="w-32 h-32 object-contain animate-fadeIn" alt="Benjamin Cargo Logistics Logo"/>
    </div>

    <!-- Page Title -->
    <h3 class="text-center text-4xl md:text-5xl font-extrabold tracking-tight bg-gradient-to-r from-indigo-600 to-indigo-500 bg-clip-text text-transparent mb-10 animate-slideUp">
      Customer Review
    </h3>

    <!-- Review Card -->
    <div class="bg-white shadow-2xl rounded-2xl p-10 relative overflow-hidden animate-fadeUp">
      <?php if ($review): ?>
        <!-- Decorative Accent -->
        <div class="absolute inset-x-0 top-0 h-2 bg-gradient-to-r from-indigo-500 to-indigo-400 rounded-t-2xl"></div>

        <!-- Rating Stars -->
        <div class="flex items-center justify-center mb-6">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <?php if ($i <= intval($review['rating'])): ?>
              <svg class="w-8 h-8 md:w-10 md:h-10 text-yellow-400 drop-shadow-sm" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.18 3.642a1 1 0 00.95.69h3.825c.969 0 1.371 1.24.588 1.81l-3.092 2.245a1 1 0 00-.364 1.118l1.18 3.642c.3.921-.755 1.688-1.54 1.118l-3.092-2.245a1 1 0 00-1.176 0l-3.092 2.245c-.784.57-1.838-.197-1.539-1.118l1.18-3.642a1 1 0 00-.364-1.118L2.51 9.07c-.783-.57-.38-1.81.588-1.81h3.825a1 1 0 00.95-.69l1.176-3.642z"/>
              </svg>
            <?php else: ?>
              <svg class="w-8 h-8 md:w-10 md:h-10 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.18 3.642a1 1 0 00.95.69h3.825c.969 0 1.371 1.24.588 1.81l-3.092 2.245a1 1 0 00-.364 1.118l1.18 3.642c.3.921-.755 1.688-1.54 1.118l-3.092-2.245a1 1 0 00-1.176 0l-3.092 2.245c-.784.57-1.838-.197-1.539-1.118l1.18-3.642a1 1 0 00-.364-1.118L2.51 9.07c-.783-.57-.38-1.81.588-1.81h3.825a1 1 0 00.95-.69l1.176-3.642z"/>
              </svg>
            <?php endif; ?>
          <?php endfor; ?>
        </div>

        <!-- Comment -->
        <blockquote class="italic text-gray-700 bg-gradient-to-r from-slate-50 to-slate-100 rounded-xl p-6 border-l-4 border-indigo-500 text-xl md:text-2xl leading-relaxed shadow-inner">
          “<?= htmlspecialchars($review['comment']) ?>”
        </blockquote>

        <!-- Reviewer -->
        <h4 class="text-2xl md:text-3xl font-bold text-gray-900 mt-6 text-center">
          — <?= htmlspecialchars($review['customer_name']) ?>
        </h4>

        <!-- Date -->
        <p class="text-gray-500 text-center mt-2 text-lg">
          Posted on <?= date("F j, Y", strtotime($review['created_at'])) ?>
        </p>
      <?php else: ?>
        <div class="text-center py-12">
          <h1 class="text-3xl md:text-4xl font-extrabold text-gray-800 mb-4">Review Not Found</h1>
          <p class="text-gray-600 text-lg">Sorry, this review does not exist or may have been removed.</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Back Home -->
    <div class="flex justify-center mt-10">
      <a href="https://www.benjamincargo.com" 
         class="bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-700 hover:to-indigo-600 transition-all shadow-lg px-6 py-3 rounded-full text-white font-semibold tracking-wide">
        Go Back Home
      </a>
    </div>
  </div>

  <script>
    // Subtle entrance animations
    gsap.from(".animate-slideUp", { y: 40, opacity: 0, duration: 1, ease: "power4.out" });
    gsap.from(".animate-fadeUp", { y: 60, opacity: 0, duration: 1.2, ease: "power4.out", delay: 0.3 });
    gsap.from(".animate-fadeIn", { opacity: 0, scale: 0.9, duration: 1.2, ease: "back.out(1.7)" });
  </script>
</body>
</html>
