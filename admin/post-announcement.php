<?php 
session_start();
$userID = $_SESSION['employee_id'] ?? null;
if (!$userID) {
    echo json_encode(['success' => false, 'errors' => ['Unauthorized request. Please log in.']]);
    exit;
}

include_once 'includes/auth.php';
$allowed_roles = ['admin', 'manager', 'hr'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    header("Location: staffs-dashboard.php");
    exit;
}
?>
<?php include_once 'includes/sidebar.php'; ?>
<?php include_once 'includes/header.php'; ?>
<?php include_once 'includes/app-bar.php';?>
 <main class="flex-1 md:ml-64 px-4 transition-all">
<div class="bg-white shadow-md rounded-md p-6 mt-24">
    <h3 class="text-2xl font-semibold mb-4">Post Announcement</h3>
    <?php if (!empty($errors)): ?>
    <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-700 rounded">
      <ul class="list-disc list-inside">
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <!-- <form method="post" action="add-room.php" enctype="multipart/form-data"> -->
    <form id="addAnnouncementForm">
      <div class="mb-3">
        <label for="headline" class="block text-gray-700">Headline</label>
        <input type="text" id="headline" name="headline" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Type announcement headline" value="">
      </div>
      <div class="mb-3">
        <label for="details" class="block text-gray-700">Details</label>
        <textarea id="details" name="details" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" rows="6" columns="6" placeholder="Write details of the announcement here!" value=""></textarea>
      </div>
   
      <div class="mt-5">
        <button type="submit" name="submit" id="submitBtn" class="bg-gray-800 text-white px-6 py-2 rounded hover:bg-gray-700 hover:cursor-pointer">Post Announcement</button>
      </div>
    </form>
  </div>
</main>

<!-- Scripts -->
<?php include_once 'includes/footer.php'; ?>
<script>
 

document.getElementById('addAnnouncementForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const submitBtn = document.getElementById('submitBtn');
  submitBtn.disabled = true;

  const formData = new FormData(addAnnouncementForm)
   console.log(formData)
  try {
    const response = await fetch('./functions/announcement/add-announcement.php', {
      method: 'POST',
      body: formData
    });

    let result;
    const contentType = response.headers.get("Content-Type");

    if (!response.ok) {
      // Try to extract the body if it’s still JSON error
      try {
        result = await response.json();
      } catch {
        throw new Error('Server error, invalid response');
      }
      throw new Error(result.errors ? result.errors.join('<br>') : 'Server error');
    }

    // Check if the response is JSON
    if (contentType && contentType.includes("application/json")) {
      result = await response.json();
    } else {
      throw new Error('Invalid response format');
    }

    if (result.success) {
      Swal.fire({
        icon: 'success',
        title: 'Annoucement Posting',
        text: 'Announcement posted successfully!',
        timer: 2000,
        showConfirmButton: false
      });
      this.reset();
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        html: (result.errors || ['Unknown error occurred.']).map(e => `<div>${e}</div>`).join('')
      });
      return;
    }

  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      html: err.message
    });
  } finally {
    submitBtn.disabled = false;
  }
});
</script>
</body>
</html>
