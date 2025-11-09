<?php 
include_once __DIR__ . '/../includes/dbconnection.php';

$query = "SELECT r.id, r.rating, r.comment, r.created_at, c.client_name AS customer_name
          FROM customer_reviews r
          JOIN customers c ON r.customer_id = c.customer_id
          ORDER BY r.created_at DESC
          LIMIT 3"; // Fetch only the latest 4 reviews
$stmt = $dbh->prepare($query);
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
  <div class="px-5 mt-32 mb-5 md:mb-8 text-left md:text-center">
      <h3 class="text-2xl md:text-4xl lg:text-6xl font-bold">
       What Customers Says About Us.
      </h3>
    </div>
<div class="px-5 md:px-16 mb-10 lg:mb-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php foreach ($reviews as $review): ?>
        <div class="flex flex-col bg-gray-300/20 shadow-md rounded-md p-4">
            
            <!-- Rating -->
            <div class="text-yellow-500 text-lg mb-4">
                <?= str_repeat('⭐', intval($review['rating'])) . str_repeat('☆', 5 - intval($review['rating'])) ?>
            </div>
            
            <!-- Comment -->
            <p class="text-gray-700 italic mb-6 flex-grow">
                “<?= htmlspecialchars($review['comment']) ?>”
            </p>
            
            <!-- User Info -->
            <div class="mt-auto">
                <h4 class="text-lg font-semibold text-gray-900">
                    <?= htmlspecialchars($review['customer_name']) ?>
                </h4>
                <small class="text-gray-500 text-sm">
                    <?= date("F j, Y", strtotime($review['created_at'])) ?>
                </small>
            </div>
        </div>
    <?php endforeach; ?>
</div>
