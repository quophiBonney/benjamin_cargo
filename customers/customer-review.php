<?php
session_start();
include_once __DIR__ . '../../includes/dbconnection.php';

if (!isset($_SESSION['casaid'])) {
    header("Location: login.php");
    exit;
}

$customer_id = $_SESSION['casaid'];
?>

<?php include_once 'includes/sidebar.php'; ?>
<?php include_once 'includes/header.php'; ?>

<!-- Main Content -->
<main class="flex-1 md:ml-64 px-4 transition-all">
    <?php include_once 'includes/app-bar.php'; ?>
    <div class="mt-32 bg-white shadow-md rounded-xl p-6 w-full">
        <h2 class="text-2xl font-bold mb-4 text-center">Leave a Review</h2>
        
        <form id="review-form" class="space-y-4">
            <!-- Rating -->
            <select name="rating" id="rating" class="w-full border p-3 rounded">
                <option value="">Select Rating</option>
                <option value="5">⭐⭐⭐⭐⭐ - Excellent</option>
                <option value="4">⭐⭐⭐⭐ - Good</option>
                <option value="3">⭐⭐⭐ - Average</option>
                <option value="2">⭐⭐ - Poor</option>
                <option value="1">⭐ - Terrible</option>
            </select>

            <!-- Comment -->
            <textarea name="comment" id="comment" rows="4" placeholder="Your Comment"
                class="w-full border p-3 rounded"></textarea>

            <!-- Submit -->
            <button type="submit" id="submitBtn"
                class="w-full bg-gray-800 text-white px-6 py-3 rounded-md hover:bg-slate-800 disabled:opacity-60 disabled:cursor-not-allowed">
                Submit Review
            </button>
        </form>
    </div>
</main>

<?php include_once 'includes/footer.php'; ?>
<script>
document.getElementById("review-form").addEventListener("submit", async function(e) {
    e.preventDefault();

    const form = this;
    const submitBtn = document.getElementById("submitBtn");
    const originalBtnText = submitBtn.textContent;

    submitBtn.disabled = true;
    submitBtn.textContent = "Submitting...";

    try {
        const formData = new FormData(form);
        const resp = await fetch('./customer-functions/submit-review.php', {
            method: 'POST',
            body: formData
        });

        const result = await resp.json();

        if (result.success) {
            await Swal.fire({
                icon: 'success',
                text: result.message,
                timer: 1500,
                showConfirmButton: false,
                scrollbarPadding: false
            });
            window.location.reload();
        } else {
            Swal.fire({ icon: 'error', title: 'Failed', text: result.message });
        }
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error', text: err.message });
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText;
    }
});
</script>
