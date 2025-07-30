<?php 
include_once 'includes/auth.php';
$allowed_roles = ['admin', 'manager', 'receptionist', 'staff', 'hr', 'cleaner'];
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
    <h3 class="text-2xl font-semibold mb-4">Request For Leave</h3>
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
    <form id="addLeaveForm">
      <div class="mb-4">
        <label for="leaveType" class="block text-gray-700">Leave Type</label>
       <select id="leaveType" name="leaveType" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        <option value="" disabled selected>Select Leave Type</option>
         <option value="annual">Annual</option>
        <option value="emergency">Emergency</option>
         <option value="maternal">Maternal</option>
          <option value="personal">Personal</option>
        </select>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="startDate" class="block text-gray-700">Start Date</label>
          <input type="date" id="startDate" name="startDate" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        </div>
       <div>
          <label for="endDate" class="block text-gray-700">End Date</label>
          <input type="date" id="endDate" name="endDate" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        </div>
      </div>
      <div class="mt-4">
        <label for="reason">Reason</label>
        <textarea id="reason" name="reason" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" rows="4" cols="50" placeholder="Please type your reason for leave"></textarea>
        </div>
        <!-- <div class="mt-4">
          <div id="imageDropzone" class="dropzone border border-dashed border-gray-300 rounded p-4 bg-gray-100"></div>
        </div> -->
   
      <div class="mt-3">
        <button type="submit" name="submit" id="submitBtn" class="bg-gray-800 text-white px-6 py-2 rounded hover:bg-gray-700 hover:cursor-pointer">Submit Application</button>
      </div>
    </form>
  </div>
</main>

<!-- Scripts -->
<?php include_once 'includes/footer.php'; ?>
<script>
//   Dropzone.autoDiscover = false;
// const myDropzone = new Dropzone("#imageDropzone", {
//   url: "./functions/upload-room-image.php",
//   autoProcessQueue: false,
//   maxFiles: 5,
//   acceptedFiles: "image/*",
//   addRemoveLinks: true
// });

document.getElementById('addLeaveForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const submitBtn = document.getElementById('submitBtn');
  submitBtn.disabled = true;

  const formData = new FormData(this);
//   if (myDropzone.files.length > 0) {
//     formData.append('image', myDropzone.files[0]);
//   }

  try {
    const response = await fetch('./functions/leave/submit-leave.php', {
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
        title: 'Leave Request',
        text: 'Your leave application is submitted for processing.!',
        timer: 2000,
        showConfirmButton: false
      });
      this.reset();
      window.location.href = 'leave-applications.php';
    //   myDropzone.removeAllFiles();
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
