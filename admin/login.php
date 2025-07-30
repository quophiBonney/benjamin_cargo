<?php
session_start();
?>
<?php include_once 'includes/header-two.php';?>
<?php include_once 'includes/footer.php'; ?>
<div class="h-screen flex justify-center items-center login-form-bg px-4">
  <div class="px-4 bg-white/30 backdrop-blur-sm p-3 rounded-md w-full md:w-[50%] lg:w-[40%]">
    <div class="flex flex-col justify-center items-center text-white">
      <h4 class="text-2xl font-bold">Welcome, Back</h4>
      <p>Fill your credentials to login</p>
    </div>
 <form id="loginForm" class="space-y-4">
  <div>
    <label for="email" class="text-white">Email</label>
    <input type="email" placeholder="Enter email" id="email" name="email"  class="bg-gray-100 w-full p-2 border border-gray-300 rounded"/>
</div>
 <div>
   <label for="password" class="text-white">Password</label>
    <input type="password" placeholder="Enter password" id="password" name="password" class="bg-gray-100 w-full p-2 border border-gray-300 rounded"/>
</div>
<div>
    <button type="submit" id="submitBtn" placeholder="Enter username"class="w-full bg-gray-800 text-white px-6 py-2 rounded hover:cursor-pointer hover:bg-gray-700">Login</button>
</div>
</form>
</div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const submitBtn = document.getElementById('submitBtn');
  submitBtn.disabled = true;

  const formData = new FormData(this);

  try {
    const response = await fetch('./functions/employee/employee-login.php', {
      method: 'POST',
      body: formData
    });

    const contentType = response.headers.get("Content-Type");
    if (!contentType || !contentType.includes("application/json")) {
      const raw = await response.text();  // Log what went wrong
      throw new Error("Invalid response:\n" + raw);
    }

    const result = await response.json();

    if (result.success) {
      Swal.fire({
        icon: 'success',
        title: 'Success',
        text: result.message || 'Login successful!',
        timer: 2000,
        showConfirmButton: false
      }).then(() => {
         window.location.href = result.redirect;
      })
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Login Failed',
        html: (result.errors || ['Unknown error']).join('<br>')
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
});

</script>
