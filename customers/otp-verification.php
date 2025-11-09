<?php
session_start();
if (!isset($_SESSION['otp_pending_customer'])) {
    header("Location: login.php");
    exit();
}

// ✅ Calculate expiry (default: 30 min if not set)
$expiresAt = $_SESSION['otp_expires'] ?? (time() + 30 * 60);
$remaining = max(0, $expiresAt - time());
?>
<?php include './includes/header-two.php'; ?>
<body>
  <div class="otp-verification-bg h-screen flex justify-center items-center px-4 bg-slate-50">
    <div class="bg-white shadow-lg rounded-xl w-full max-w-md p-2 md:p-6">
      
      <!-- Title -->
      <div class="flex flex-col justify-center items-center text-slate-800 mb-6">
        <h1 class="text-2xl font-bold">Enter OTP</h1>
        <p class="text-sm text-slate-600 text-center mt-1">
          Please enter the 6-digit code sent to your email or phone to verify your account.
        </p>
      </div>

      <!-- OTP Form -->
      <form id="otpForm" class="space-y-6">
        <div class="flex justify-between space-x-1 md:space-x-0">
          <?php for ($i = 0; $i < 6; $i++): ?>
            <input type="text" maxlength="1" class="otp-input w-10 h-10 md:w-12 md:h-12 border border-slate-300 rounded-lg text-center text-lg font-bold focus:outline-none focus:ring-2 focus:ring-slate-400" />
          <?php endfor; ?>
        </div>

        <!-- Hidden field for combined OTP -->
        <input type="hidden" name="otp" id="otpFull" />

        <!-- Expiry timer -->
        <p id="timerText" class="text-center text-sm text-slate-600 mt-2"></p>

        <!-- Submit Button -->
        <button type="submit" id="submitOtpBtn"
          class="w-full bg-indigo-700 text-white px-6 py-3 rounded-md hover:bg-slate-800 disabled:opacity-60 disabled:cursor-not-allowed">
          Verify OTP
        </button>

        <!-- Resend Button (hidden initially) -->
        <button type="button" id="resendBtn"
          class="w-full mt-2 bg-gray-600 text-white px-6 py-3 rounded-md hover:bg-gray-800 disabled:opacity-60 disabled:cursor-not-allowed hidden">
          Resend OTP
        </button>
      </form>
    </div>
  </div>

<?php include './includes/footer.php'; ?>
<script>
  const form = document.getElementById('otpForm');
  const otpInputs = document.querySelectorAll('.otp-input');
  const otpFull = document.getElementById('otpFull');
  const submitBtn = document.getElementById('submitOtpBtn');
  const resendBtn = document.getElementById('resendBtn');
  const timerText = document.getElementById('timerText');

  // PHP gives remaining seconds
  let remainingSeconds = <?php echo $remaining; ?>;

  // Countdown logic
  function startCountdown() {
    const interval = setInterval(() => {
      if (remainingSeconds <= 0) {
        clearInterval(interval);
        timerText.textContent = "OTP expired. Please request a new one.";
        submitBtn.disabled = true;
        resendBtn.classList.remove('hidden');
        return;
      }
      const m = String(Math.floor(remainingSeconds / 60)).padStart(2, '0');
      const s = String(remainingSeconds % 60).padStart(2, '0');
      timerText.textContent = `Code expires in ${m}:${s}`;
      remainingSeconds--;
    }, 1000);
  }
  startCountdown();

  // Auto focus next field
  otpInputs.forEach((input, index) => {
    input.addEventListener('input', () => {
      if (input.value.length === 1 && index < otpInputs.length - 1) {
        otpInputs[index + 1].focus();
      }
    });
  });

  // OTP form submit
  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    // Combine OTP values
    let otpValue = '';
    otpInputs.forEach(input => otpValue += input.value);
    otpFull.value = otpValue;

    if (otpValue.length !== 6) {
      Swal.fire({ 
        icon: 'error', 
        title: 'Invalid OTP', 
        text: 'Please enter all 6 digits.',
        scrollbarPadding: false
      });
      return;
    }

    submitBtn.disabled = true;
    const originalBtnText = submitBtn.textContent;
    submitBtn.textContent = 'Verifying…';

    try {
      const formData = new FormData(form);
      const resp = await fetch('./customer-functions/otp-verification.php', {
        method: 'POST',
        body: formData
      });

      const result = await resp.json();

      if (result.success) {
        await Swal.fire({
          icon: 'success',
          title: 'Verified',
          text: result.message,
          timer: 1500,
          showConfirmButton: false,
          scrollbarPadding: false
        });
        window.location.href = result.redirect;
      } else {
        Swal.fire({ icon: 'error', title: 'Failed', text: result.message });
      }
    } catch (err) {
      Swal.fire({ icon: 'error', title: 'Error', text: err.message });
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = originalBtnText;
    }
  });

  // Resend OTP
  resendBtn.addEventListener('click', async () => {
    resendBtn.disabled = true;
    resendBtn.textContent = "Sending new OTP…";
    try {
      // Hit verify-email.php again, using stored session
      const resp = await fetch('./customer-functions/verify-email.php', {
        method: 'POST',
        body: new URLSearchParams({ customerInput: "resend" }) // dummy, backend will use session customer
      });
      const result = await resp.json();
      if (result.success) {
        Swal.fire({ icon: 'success', title: 'OTP Resent', text: result.message });
        // Reset timer
        remainingSeconds = 1800; // 30 mins
        submitBtn.disabled = false;
        resendBtn.classList.add('hidden');
        resendBtn.textContent = "Resend OTP";
        startCountdown();
      } else {
        Swal.fire({ icon: 'error', title: 'Error', text: result.message || 'Failed to resend.' });
      }
    } catch (err) {
      Swal.fire({ icon: 'error', title: 'Error', text: err.message });
    } finally {
      resendBtn.disabled = false;
    }
  });
</script>
</body>
</html>
