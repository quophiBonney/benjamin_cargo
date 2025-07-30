
<?php 
include_once 'includes/auth.php';
$allowed_roles = ['admin', 'manager', 'hr'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
?>
<?php include_once 'includes/sidebar.php'; ?>
<?php include_once 'includes/header.php'; ?>
<?php include_once 'includes/app-bar.php';?>
 <main class="flex-1 md:ml-64 px-4 transition-all">
  <div class="bg-white shadow-md rounded-md p-6 mt-24">
    <h3 class="text-2xl font-semibold mb-4">Add Employee</h3>

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
    <form id="addEmployeeForm">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <div>
        <label for="fullName" class="block text-gray-700">Full Name</label>
        <input type="text" id="fullName" name="fullName" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter full name" value="">
      </div>
        <div>
          <label for="position" class="block text-gray-700">Position</label>
          <input type="text" id="position" name="position" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter position">
        </div>
        <div>
          <label for="email" class="block text-gray-700">Email Address</label>
          <input type="email" id="email" name="email" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter email address">
        </div>
        <div>
          <label for="phoneNumber" class="block text-gray-700">Phone Number</label>
          <input type="text" id="phoneNumber" name="phoneNumber" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter phone number">
        </div>
         <div>
          <label for="dob" class="block text-gray-700">Date Of Birth</label>
          <input type="date" id="dob" name="dob" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        </div>
        <div>
          <label for="hire_date" class="block text-gray-700">Date Hired</label>
          <input type="date" id="hiredDate" name="hiredDate" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        </div>
        <div>
        <label for="residentialAddress" class="block text-gray-700">Residential Address</label>
        <input type="text" id="residentialAddress" name="residentialAddress" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter residential address" value="">
      </div>
      <div>
        <label for="ghanaCardNumber" class="block text-gray-700">Ghana Card Number</label>
        <input type="text" id="ghanaCardNumber" name="ghanaCardNumber" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter ghana card number" value="">
      </div>
      <div>
          <label for="role" class="block text-gray-700">Role</label>
         <select id="role" name="role" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
            <option value="" selected disabled>Select Role</option>
            <option value="admin">Admin</option>
            <option value="manager">Manager</option>
            <option value="receptionist">Receptionist</option>
            <option value="cleaner">Cleaner</option>
            <option value="electrician">Electrician</option>
          </select>
        </div>
        <div>
          <label for="password" class="block text-gray-700">Password</label>
          <input type="password" id="password" name="password" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter password">
        </div>
        <div>
          <label for="salary" class="block text-gray-700">Salary</label>
          <input type="number" id="salary" name="salary" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter salary">
        </div>
      </div>
      <div class="mt-5">
        <button type="submit" name="submit" id="submitBtn" class="bg-gray-800 text-white px-6 py-2 rounded hover:bg-gray-700 hover:cursor-pointer">Add Emloyee</button>
      </div>
    </form>
  </div>
</main>

<!-- Scripts -->
<?php include_once 'includes/footer.php'; ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const addEmployeeForm = document.getElementById('addEmployeeForm');
  const submitBtn = document.getElementById('submitBtn');

  addEmployeeForm.addEventListener('submit', async function (e) {
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

      const formData = new FormData(addEmployeeForm);
      formData.append('latitude', latitude);
      formData.append('longitude', longitude);

      try {
        const response = await fetch('functions/employee/insert-employee.php', {
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
  window.location.href ="all-employees.php";
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
</body>
</html>
