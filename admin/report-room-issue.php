<?php 
include_once 'includes/auth.php';
$allowed_roles = ['admin', 'manager', 'hr','receptionist', 'cleaner'];
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
<div class="mt-24 bg-white shadow-md rounded-md p-6">
    <h3 class="text-2xl font-semibold mb-4">Send Report</h3>
    <?php if (!empty($errors)): ?>
    <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-700 rounded">
      <ul class="list-disc list-inside">
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <form id="roomIssueForm">
        <div class="grid grid-cols-1 gap-4">
      <div class="">
        <label for="room_number" class="block text-gray-700">Room Number</label>
         <input type="number" id="room_number" name="room_number" placeholder="Type room number that has issue" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" value="">
      </div>
        <div class="">
        <label for="description" class="block text-gray-700">Description</label>
         <textarea id="description" name="description" placeholder="What is the issue?" 
         rows="6" columns="4" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" value=""></textarea>
      </div>
      <div class="">
        <button type="submit" name="submit" id="submitBtn" class="bg-gray-800 text-white px-6 py-2 rounded hover:bg-gray-700 hover:cursor-pointer">Send Report</button>
      </div>
    </form>
  </div>
</main>

<!-- Scripts -->
<?php include_once 'includes/footer.php'; ?>
<script>
 

document.getElementById('roomIssueForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const submitBtn = document.getElementById('submitBtn');
  submitBtn.disabled = true;

  const formData = new FormData(roomIssueForm)
   console.log(formData)
  try {
    const response = await fetch('./functions/room/add-new-issue.php', {
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
        title: 'Room Issue Reporting',
        text: 'Room issue sent successfully!',
        timer: 2000,
        showConfirmButton: false
      });
      this.reset();
      window.location.href = "room-reports.php";
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
