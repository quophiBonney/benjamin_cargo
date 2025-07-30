<?php 
include_once 'includes/auth.php';
$allowed_roles = ['admin', 'manager', 'receptionist'];
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
    <h3 class="text-2xl font-semibold mb-4">New Booking</h3>
    <?php if (!empty($errors)): ?>
    <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-700 rounded">
      <ul class="list-disc list-inside">
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

     <form id="addBookingForm">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <div>
          <label for="roomNumber" class="block text-gray-700">Room Number<sup class="text-red-500">*</sup></label>
          <select id="roomNumber" name="roomNumber" placeholder="Select a room..." class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
  <option value="" disabled selected>Select room number</option>
</select>
        </div>
        <div>
          <label for="guestName" class="block text-gray-700">Guest Name<sup class="text-red-500">*</sup></label>
          <input type="text" id="guestName" name="guestName" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Guest name">
        </div>
        <div>
          <label for="guestPhone" class="block text-gray-700">Guest Phone No:<sup class="text-red-500">*</sup></label>
          <input type="number" id="guestPhoneC" name="guestPhone" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Guest phone no.">
        </div>
        <div>
          <label for="numberOfGuests" class="block text-gray-700">Number of Guests<sup class="text-red-500">*</sup></label>
          <input type="number" id="numberOfGuests" name="numberOfGuests" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Number of guests">
        </div>
        <div>
          <label for="ghanaCardNumber" class="block text-gray-700">Ghana Card No.</label>
          <input type="number" id="ghanaCardNumber" name="ghanaCardNumber" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Ghana card no.">
        </div>
        <div>
          <label for="checkIn" class="block text-gray-700">Check-In Date<sup class="text-red-500">*</sup></label>
          <input type="date" id="checkIn" name="checkIn" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        </div>
        <div>
          <label for="checkOut" class="block text-gray-700">Check-Out Date<sup class="text-red-500">*</sup></label>
          <input type="date" id="checkOut" name="checkOut" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        </div>
        <div>
          <label for="status" class="block text-gray-700">Status</label>
          <select id="status" name="status" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
            <option value="" selected disabled>Select Status</option>
            <option value="booked">Booked</option>
            <option value="cancelled">Cancelled</option>
             <option value="checkedout">Check-Out</option>
            <option value="Reserved">Reserved</option>
        </select>
        </div>
        <div>
          <label for="contactPersonName" class="block text-gray-700">Contact Person Name<sup class="text-red-500">*</sup></label>
          <input type="text" id="contactPersonName" name="contactPersonName" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter contact person name">
        </div>
        <div>
          <label for="contactPersonPhone" class="block text-gray-700">Contact Person Number<sup class="text-red-500">*</sup></label>
          <input type="text" id="contactPersonPhone" name="contactPersonPhone" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter contact person number">
        </div>
        <div>
          <label for="numberOfNights" class="block text-gray-700">Number Of Nights</label>
          <input type="number" readonly id="numberOfNights" name="numberOfNights" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Number of nights">
        </div>
      </div>
      <div class="mt-5">
        <button type="submit" name="submit" id="submitBtn" class="bg-gray-800 text-white px-6 py-2 rounded hover:bg-gray-700 hover:cursor-pointer">Book Now</button>
      </div>
    </form>
  </div>
</main>


<!-- Scripts -->
<?php include_once 'includes/footer.php'; ?>
<script>
  document.getElementById('roomNumber').addEventListener('input', function(e) {
  this.value = this.value.replace(/[^0-9]/g, '');
});
  const today = new Date().toISOString().split("T")[0];
document.getElementById('checkIn').setAttribute('min', today);
document.getElementById('checkOut').setAttribute('min', today);

// Ensure checkOut cannot be before checkIn
document.getElementById('checkIn').addEventListener('change', function() {
  const checkInDate = this.value;
  document.getElementById('checkOut').setAttribute('min', checkInDate);
  calculateNights(); // update nights on change
});

document.getElementById('checkOut').addEventListener('change', function() {
  calculateNights(); // update nights on change
});

// Existing function
function calculateNights() {
  const checkIn = document.getElementById('checkIn').value;
  const checkOut = document.getElementById('checkOut').value;
  const nightsField = document.getElementById('numberOfNights');

  if (checkIn && checkOut) {
    const inDate = new Date(checkIn);
    const outDate = new Date(checkOut);

    const timeDiff = outDate - inDate;
    const nights = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));

    if (nights > 0) {
      nightsField.value = nights;
    } else {
      nightsField.value = '';
    }
  } else {
    nightsField.value = '';
  }
}

document.getElementById('addBookingForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const submitBtn = document.getElementById('submitBtn');
  submitBtn.disabled = true;

  const formData = new FormData(this);


  try {
    const response = await fetch('./functions/booking/add-new-booking.php', {
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
        title: 'Room Booking',
        text: 'Room booked successfully!',
        timer: 2000,
        showConfirmButton: false
      }).then(() => {
        window.location.href = 'all-bookings.php';
      })
      
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
async function loadAvailableRooms() {
  try {
    const res = await fetch('./functions/room/get-available-rooms.php');
    const roomNumbers = await res.json();

    const select = document.getElementById('roomNumber');
    select.innerHTML = '<option value="" disabled selected>Select room number</option>';

    roomNumbers.forEach(room => {
      const option = document.createElement('option');
      option.value = room;
      option.textContent = room;
      select.appendChild(option);
    });

    // Initialize Tom Select
    new TomSelect('#roomNumber', {
      create: false,
      sortField: {
        field: "text",
        direction: "asc"
      },
      placeholder: "Select an available room",
    });

  } catch (err) {
    console.error('Failed to load available rooms:', err);
  }
}

document.addEventListener('DOMContentLoaded', loadAvailableRooms);
</script>
</body>
</html>
