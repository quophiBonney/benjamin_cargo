<?php
session_start();
?>
<?php include_once 'templates/header-two.php';?>
<?php include_once 'templates/footer.php'; ?>
<div class="h-screen flex justify-center items-center login-form-bg px-4">
  <div class="px-4 bg-white/30 backdrop-blur-sm p-3 rounded-md w-full md:w-[50%] lg:w-[40%]">
    <div class="flex flex-col justify-center items-center text-white">
      <h4 class="text-2xl font-bold">Welcome, Back</h4>
      <p>Fill your credentials to login</p>
    </div>
 <form id="loginForm" class="space-y-4" autocomplete="off">
  <div>
    <label for="email" class="text-white">Email</label>
    <input type="email" placeholder="Enter email" id="email" name="email"  
           class="bg-gray-100 w-full p-2 border border-gray-300 rounded" required/>
</div>
 <div>
   <label for="password" class="text-white">Password</label>
    <input type="password" placeholder="Enter password" id="password" name="password" 
           class="bg-gray-100 w-full p-2 border border-gray-300 rounded" required/>
</div>
<div>
    <button type="submit" id="submitBtn" 
            class="w-full bg-gray-800 text-white px-6 py-2 rounded hover:cursor-pointer hover:bg-gray-700">
      Login
    </button>
</div>
</form>
</div>
</div>

<script>
// Helper to safely escape any text before showing in HTML
function escapeHTML(str) {
  return str.replace(/[&<>'"]/g, (tag) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    "'": '&#39;',
    '"': '&quot;'
  }[tag]));
}

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
      const raw = await response.text();  
      throw new Error("Invalid response:\n" + escapeHTML(raw));
    }

    const result = await response.json();

    if (result.success) {
      Swal.fire({
        icon: 'success',
        title: 'Success',
        text: escapeHTML(result.message || 'Login successful!'),
        timer: 2000,
        showConfirmButton: false
      }).then(() => {
         window.location.href = result.redirect;
      })
    } else {
      // Sanitize all error messages before displaying
      const safeErrors = (result.errors || ['Unknown error']).map(err => escapeHTML(err));
      Swal.fire({
        icon: 'error',
        title: 'Login Failed',
        html: safeErrors.join('<br>')
      });
    }

  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: escapeHTML(err.message)
    });
  } finally {
    submitBtn.disabled = false;
  }
});
</script>
