<?php include_once 'includes/header-two.php'; ?>

<div class="h-screen flex justify-center items-center login-form-bg px-4">
  <div class="px-4 bg-white/30 backdrop-blur-sm p-3 rounded-md w-full md:w-[50%] lg:w-[40%]">
    <div class="flex flex-col justify-center items-center text-white">
      <h4 class="text-2xl font-bold">Create An Account</h4>
      <p>Fill in with your credentials to complete your signup</p>
    </div>
<form id="addEmployeeForm" method="post">
  <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

    <div>
      <label for="full_name" class="block text-gray-700">Full Name</label>
      <input type="text" id="fullName" name="fullName" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter full name" required>
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
      <label for="phone" class="block text-gray-700">Phone Number</label>
      <input type="text" id="phoneNumber" name="phoneNumber" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter phone number" required>
    </div>

    <div>
      <label for="dob" class="block text-gray-700">Date of Birth</label>
      <input type="date" id="dob" name="dob" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
    </div>

    <div>
      <label for="date_hired" class="block text-gray-700">Date Hired</label>
      <input type="date" id="hiredDate" name="hiredDate" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" required>
    </div>

    <div>
      <label for="residential_address" class="block text-gray-700">Residential Address</label>
      <input type="text" id="residentialAddress" name="residentialAddress" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter residential address" required>
    </div>

    <div>
      <label for="ghana_card_number" class="block text-gray-700">Ghana Card Number</label>
      <input type="text" id="ghanaCardNumber" name="ghanaCardNumber" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter Ghana Card number" required>
    </div>

    <div>
      <label for="role" class="block text-gray-700">Role</label>
      <select id="role" name="role" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" required>
        <option value="" selected disabled>Select Role</option>
        <option value="admin">Admin</option>
        <option value="manager">Manager</option>
        <option value="sales">Sales & Marketing</option>
      </select>
    </div>

    <div>
      <label for="salary" class="block text-gray-700">Salary</label>
      <input type="number" id="salary" name="salary" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter salary" required>
    </div>

    <div>
      <label for="password" class="block text-gray-700">Password</label>
      <input type="password" id="password" name="password" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter password">
    </div>

  </div>

  <div class="mt-6">
    <button type="submit" name="submit" id="submitBtn" class="bg-gray-800 text-white px-6 py-2 rounded hover:bg-gray-700">Add Employee</button>
  </div>
</form>
</div>
</div>

<?php include_once 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const signupForm = document.getElementById('signupForm');
  const submitBtn = document.getElementById('submitBtn');

  signupForm.addEventListener('submit', async function (e) {
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

      const formData = new FormData(signupForm);
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
}).then(() => {
  window.location.href = 'login.php'; 
});
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
