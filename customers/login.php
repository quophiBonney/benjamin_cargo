<?php include './includes/header-two.php'; ?>
<body class="min-h-screen bg-slate-100">
  <div class="h-screen flex justify-center items-center px-4">
    <div class="bg-white shadow-lg rounded-xl w-full md:w-[50%] lg:w-[40%] p-6">
      <div class="flex flex-col justify-center items-center text-slate-800 mb-6">
        <h1 class="text-2xl font-bold">Welcome Back</h1>
        <p class="text-sm text-slate-600 text-center mt-1">Type either your email or phone number to receive an OTP.</p>
      </div>

      <form id="customerVerificationForm" class="space-y-4">
        <div>
          <label for="customerInput" class="block text-sm font-medium text-slate-700">Email or Phone Number</label>
          <input type="text" id="customerInput" name="customerInput" placeholder="Enter email or phone"
                 class="mt-1 w-full p-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-slate-400 bg-white" />
        </div>

        <button type="submit" id="submitBtn"
                class="w-full bg-slate-800 text-white px-6 py-2 rounded-lg hover:bg-slate-700 disabled:opacity-60 disabled:cursor-not-allowed">
          Verify Your Account
        </button>
      </form>

      <p class="text-center text-xs text-slate-500 mt-4">You'll be redirected to OTP verification if successful.</p>
    </div>
  </div>
<?php include './includes/footer.php'; ?>
  <script>
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

        // Try to parse JSON; if it fails, show the raw text for easier debugging
        const contentType = resp.headers.get('Content-Type') || '';
        let result;
        if (contentType.includes('application/json')) {
          result = await resp.json();
        } else {
          const raw = await resp.text();
          throw new Error('Invalid response from server (not JSON).\n\n' + raw);
        }

        if (result && result.success) {
          await Swal.fire({
            icon: 'success',
            title: 'OTP Sent',
            text: result.message || 'Check your inbox or phone for the code.',
            timer: 1800,
            showConfirmButton: false
          });
          if (result.redirect) {
            window.location.href = result.redirect;
          }
        } else {
          const errs = (result && result.errors) ? result.errors : ['Unknown error'];
          Swal.fire({ icon: 'error', title: 'Verification failed', html: errs.join('<br>') });
        }
      } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error', html: err.message.replace(/\n/g, '<br>') });
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText;
      }
    });
  </script>
</body>
</html>