<?php include './includes/header-two.php'; ?>
<body class="customer-landing-page-background min-h-screen bg-slate-100">
  <div class="h-screen flex justify-center items-center px-4">
    <div class="bg-white shadow-lg rounded-xl w-full md:w-[50%] lg:w-[40%] p-3 md:p-6">
      <div class="flex flex-col justify-center items-center text-slate-800 mb-6">
        <h1 class="text-2xl font-bold">Welcome Back</h1>
        <?php 
          // Escape env variable to prevent XSS if attacker injects markup
          $brand = getenv('BenjaminCargo') ?: '';
          echo htmlspecialchars($brand, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        ?>
        <p class="text-sm text-slate-600 text-center mt-1">
          Type either your email or phone number to receive an OTP.
        </p>
      </div>

      <form id="customerVerificationForm" class="space-y-4" autocomplete="off">
        <div>
          <label for="customerInput" class="block text-sm font-medium text-slate-700">Email or Phone Number</label>
          <input type="text" id="customerInput" name="customerInput" placeholder="Enter email or phone"
                 class="mt-1 w-full p-3 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-slate-400 bg-white"/>
        </div>
      <div class="gap-3 md:gap-0 w-full ">
          <button type="submit" id="submitBtn"
                class="w-full bg-indigo-700 text-white px-6 py-3 rounded-md hover:bg-indigo-800 disabled:opacity-60 disabled:cursor-not-allowed">
          Verify Your Account
        </button>
</div>
      
      </form>

      <p class="text-center text-xs text-slate-500 mt-4">
        You'll be redirected to OTP verification if successful.
      </p>
    </div>
  </div>
<?php include './includes/footer.php'; ?>
  <script>
    // Escape helper for safe insertion into HTML
    function escapeHTML(str) {
      return String(str).replace(/[&<>'"]/g, (tag) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#39;',
        '"': '&quot;'
      }[tag]));
    }

    const form = document.getElementById('customerVerificationForm');
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', async function (e) {
      e.preventDefault();

      // Basic front-end sanity check
      const inputVal = document.getElementById('customerInput').value.trim();
      if (!inputVal) {
        Swal.fire({ icon: 'error', title: 'Missing input', text: 'Please enter your email or phone number.' });
        return;
      }

      submitBtn.disabled = true;
      const originalBtnText = submitBtn.textContent;
      submitBtn.textContent = 'Sending OTP…';

      try {
        const formData = new FormData(this);
        const resp = await fetch('./customer-functions/verify-email.php', {
          method: 'POST',
          body: formData
        });

        const contentType = resp.headers.get('Content-Type') || '';
        let result;
        if (contentType.includes('application/json')) {
          result = await resp.json();
        } else {
          const raw = await resp.text();
          throw new Error('Invalid response from server (not JSON).\n\n' + escapeHTML(raw));
        }

        if (result && result.success) {
          await Swal.fire({
            icon: 'success',
            text: escapeHTML(result.message || 'Check your inbox or phone for the code.'),
            timer: 1800,
            showConfirmButton: false
          });
          if (result.redirect) {
            window.location.href = result.redirect;
          }
        } else {
          const errs = (result && result.errors) ? result.errors : ['Unknown error'];
          const safeErrs = errs.map(e => escapeHTML(e));
          Swal.fire({
            icon: 'error',
            title: 'Verification failed',
            html: safeErrs.join('<br>')
          });
        }
      } catch (err) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          html: escapeHTML(err.message).replace(/\n/g, '<br>')
        });
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText;
      }
    });
  </script>
</body>
</html>
