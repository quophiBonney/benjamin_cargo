<?php 
$allowed_roles = ['admin', 'manager', 'hr'];
if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    header("Location: staffs-dashboard.php");
    exit;
}
?>
<?php
session_start();

if (!isset($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit;
}
?>
<?php include_once 'includes/sidebar.php'; ?>
<?php include_once 'includes/header.php'; ?>
<?php include_once 'includes/app-bar.php';?>
 <main class="flex-1 md:ml-64 px-4 transition-all">
<div class="bg-white shadow-md rounded-md p-6 mt-24 md:mt-32">
    <h3 class="text-2xl font-semibold mb-4">Register User</h3>

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
    <form id="addNewUserForm" class="space-y-3">
            <div>
                <label for="username" class="block text-gray-700">Username</label>
                <input type="text" id="username" name="username" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter username">
            </div>
           <div class="grid grid-cols-1 grid-cols-2 gap-4 md:gap-0 md:space-x-3">
             <div>
                <label for="role" class="block text-gray-700">Role</label>
                <select id="role" name="role" class=" bg-gray-100 w-full p-2 border border-gray-300 rounded">
                    <option value="" disabled selected>Choose Role</option>
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                    <option value="receptionist">Receptionist</option>
                    <option value="electrician">Electrician</option>
                    <option value="carpenter">Carpenter</option>
                </select>
            </div>

            <div>
                <label for="password" class="block text-gray-700">Password</label>
                <input type="password" id="password" name="password" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter password">
            </div>

        </div>
            <div class="mt-5">
                <button type="submit" id="submitBtn" class="bg-gray-800 text-white px-6 py-2 rounded hover:bg-gray-700 hover:cursor-pointer">Add User</button>
            </div>
        </form>
  </div>
</main>

<!-- Scripts -->
<?php include_once 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const addNewUserForm = document.getElementById('addNewUserForm');
  const submitBtn = document.getElementById('submitBtn');

  addNewUserForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    submitBtn.disabled = true;

    if (!navigator.geolocation) {
      Swal.fire({
        icon: 'error',
        title: 'Geolocation not supported',
        text: 'Your browser does not support location access.'
      });
      submitBtn.disabled = false;
      return;
    }

    navigator.geolocation.getCurrentPosition(async (position) => {
      const latitude = position.coords.latitude;
      const longitude = position.coords.longitude;

      const formData = new FormData(addNewUserForm);
      formData.append('latitude', latitude);
      formData.append('longitude', longitude);

      try {
        const response = await fetch('functions/user/add-new-user.php', {
          method: 'POST',
          body: formData
        });

        let result;
        const contentType = response.headers.get("Content-Type");

        if (!response.ok) {
          try {
            result = await response.json();
          } catch {
            throw new Error('Server error, invalid response');
          }
          throw new Error(result.errors ? result.errors.join('<br>') : 'Server error');
        }

        if (contentType && contentType.includes("application/json")) {
          result = await response.json();
        } else {
          throw new Error('Invalid response format');
        }

        if (result.success) {
        Swal.fire({
  icon: 'success',
  title: 'Success',
  text: result.message || 'User created successfully',
  timer: 2000,
  showConfirmButton: false
}).then (() => {
  this.reset();
})
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: (result.errors || ['Unknown error occurred.']).map(e => `<div>${e}</div>`).join('')
          });
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

    }, () => {
      Swal.fire({
        icon: 'error',
        title: 'Location Error',
        text: 'Permission denied or failed to get location.'
      });
      submitBtn.disabled = false;
    });
  });
});
</script>

