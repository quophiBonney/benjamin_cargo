<?php 
include_once 'includes/auth.php';
$allowed_roles = ['admin', 'manager', 'receptionist'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    header("Location: staffs-dashboard.php");
    exit;
}
include_once 'includes/dbconnection.php';
$query = "SELECT * FROM bookings ORDER BY booking_id ASC";
$stmt = $dbh->prepare($query);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalBookingAmountQuery = $dbh->prepare("
    SELECT (r.price_per_night * b.number_of_nights) AS total_price
    FROM bookings b
    JOIN rooms r ON b.room_number = r.room_number
    WHERE b.booking_id = :booking_id
");

$totalBookingAmountQuery->execute([':booking_id' => $query]);
$result = $totalBookingAmountQuery->fetch(PDO::FETCH_ASSOC);

$totalPrice = $result['total_price'] ?? 0;
?>
<?php include_once 'includes/sidebar.php'; ?>
<?php include_once 'includes/header.php'; ?>
<?php include_once 'includes/app-bar.php';?>

<!-- Main Content -->
  <main class="flex-1 md:ml-64 px-4 transition-all">
  <?php if (count($bookings) > 0): ?>
<div class="bg-white shadow-md rounded-md p-6 mt-24 md:mt-32">
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
 <div class="w-full md:col-span-2 flex flex-col md:flex-row gap-2 items-center">
  <div>
        <p>
          Showing <span id="startCount">1</span> to <span id="endCount">50</span> of
          <span id="totalCount">0</span> bookings
        </p>
</div>
<div class="flex items-center gap-2">
          <label for="rowsPerPage" class="">Rows per page:</label>
          <select id="rowsPerPage" class="border border-gray-200 rounded bg-white text-black px-2 py-1">
            <option selected>50</option>
            <option>100</option>
            <option>200</option>
            <option>500</option>
            <option>1000</option>
          </select>
        </div>
        <div class="flex justify-center" id="paginationControls"></div>
</div>
<div class="">
    <input type="search" id="searchInput" placeholder="Search by name or number..."
           class="w-full p-2 bg-gray-100 border border-gray-300 rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
  </div>
</div>
<div class="w-full mt-5 md:mt-2">
<button  class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white">
    <a href="new-booking.php">New Booking</a>
  </button>
  <button class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white" id="downloadCSV">
    Download CSV
  </button>
 <button onclick="printTable()" class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white">Print</button>
</div>
   <div class="print-area overflow-x-auto mt-6">
    <div class="watermark" style="display:none;">CONSOL HOTEL</div>
      <table id="bookingsTable" class="table-x-auto w-full text-sm text-left text-gray-700 border border-gray-200 rounded-lg overflow-hidden shadow-sm">
        <thead class="bg-gray-700 text-white uppercase text-xs font-semibold border-b border-gray-300">
          <tr>
            <th class="py-3 px-4">Room Number</th>
            <th class="py-3 px-4 min-w-[120px]">Guest Name</th>
            <th class="py-3 px-4">Contact</th>
            <th class="py-3 px-4 min-w-[120px]">Check-In</th>
            <th class="py-3 px-4 min-w-[120px]">Check-Out</th>
             <th class="py-3 px-4">No. Of Nights</th>
             <th class="py-3 px-4">Paid</th>
             <th class="py-3 px-4 min-w-[120px]">Created At</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 px-4 no-print">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
<?php foreach ($bookings as $booking): ?>
  <?php
    // Calculate total price for this booking
    $totalBookingAmountQuery = $dbh->prepare("
        SELECT (r.price_per_night * b.number_of_nights) AS total_price
        FROM bookings b
        JOIN rooms r ON b.room_number = r.room_number
        WHERE b.booking_id = :booking_id
    ");
    $totalBookingAmountQuery->execute([':booking_id' => $booking['booking_id']]);
    $result = $totalBookingAmountQuery->fetch(PDO::FETCH_ASSOC);
    $totalPrice = $result['total_price'] ?? 0;
  ?>
  <tr class="even:bg-gray-50 hover:bg-gray-100 transition duration-200">
    <td class="py-2 px-4"><?= htmlspecialchars($booking['room_number']) ?></td>
    <td class="py-2 px-4"><?= htmlspecialchars($booking['guest_name']) ?></td>
    <td class="py-2 px-4"><?= htmlspecialchars($booking['guest_phone']) ?></td>
    <td class="py-2 px-4"><?= htmlspecialchars($booking['checkin_date']) ?></td>
    <td class="py-2 px-4"><?= htmlspecialchars($booking['checkout_date']) ?></td>
    <td class="py-2 px-4"><?= htmlspecialchars($booking['number_of_nights']) ?></td>
    <td class="py-2 px-4">₵<?= number_format($totalPrice, 2) ?></td>
    <td class="py-2 px-4"><?= htmlspecialchars($booking['created_at']) ?></td>
    <td class="py-2 px-4"><?= htmlspecialchars($booking['status']) ?></td>
      <td class="py-2 w-full flex space-x-1 no-print">
              <button class="bg-gray-500 text-white p-2 rounded hover:bg-gray-600 hover:cursor-pointer transition edit-btn" data-booking='<?= json_encode($booking) ?>'><i class="fas fa-edit"></i></button>
               <button class="bg-green-500  text-white p-2 rounded hover:bg-green-600 hover:cursor-pointer view-btn" data-id="<?= $booking['booking_id'] ?>">
                <i class="fa fa-eye"></i>
              </button>
              <a href="fpdf/booking-invoice.php?booking_id=<?= $booking['booking_id'] ?>" target="_blank" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"><i class="fa-solid fa-file-invoice"></i></a>

              <button class="hover:cursor-pointer bg-red-500 text-white p-2 rounded hover:bg-red-600 transition delete-btn" data-id="<?= $booking['booking_id']; ?>"><i class="fa fa-trash" aria-hidden="true"></i></button>
            </td>
  </tr>
<?php endforeach; ?>

        </tbody>
      </table>
</div>
  <?php else: ?>
    <div class="bg-white shadow-md rounded-md p-6 mt-24 px-4 text-center text-gray-600">
      No booking found.
    </div>
  <?php endif; ?>
<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 hidden z-50 bg-gray-400/10 backdrop-blur-md bg-opacity-20 flex items-center justify-center">
  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-4xl relative max-h-screen overflow-y-auto">
    <button onclick="closeModal()" class="mt-3 absolute bg-red-500 w-8 h-8 rounded text-white top-2 right-2 hover:cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
    <h2 class="text-xl font-bold mb-4">Booking Update</h2>
    <form id="editBookingForm">
    <input type="hidden" id="editbookingId" name="id">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div>
        <label for="guestName" class="block text-gray-700">Guest Name</label>
        <input type="text" id="guestName" name="guestName" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter full name" value="">
      </div>
      <div>
        <label for="roomNumber" class="block text-gray-700">Room Number</label>
        <input type="text" id="roomNumber" name="roomNumber" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter room number" value="">
      </div>
        <div>
          <label for="guestPhone" class="block text-gray-700">Guest's Phone</label>
        <input type="text" id="guestPhone" name="guestPhone" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter guest phone" value="">
        </div>
        <div>
          <label for="" class="block text-gray-700">ID Card No.</label>
          <input type="text" id="idCardNumber" name="idCardNumber" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter ID card number">
        </div>
        <div>
          <label for="checkIn" class="block text-gray-700">Check-In</label>
          <input type="date" id="checkIn" name="checkIn" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        </div>
           <div>
          <label for="checkOut" class="block text-gray-700">Check-Out</label>
          <input type="date" id="checkOut" name="checkOut" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        </div>
        <div>
          <label for="numberOfGuests" class="block text-gray-700">No. Of Guests</label>
          <input type="number" id="numberOfGuests" name="numberOfGuests" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        </div>
        <div>
        <label for="status" class="block text-gray-700">Status</label>
        <select id="status" name="status" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
  <option value="" disabled>Select Status</option>
  <option value="booked">Booked</option>
  <option value="reserved">Reserved</option>
  <option value="checkedout">Checked Out</option>
  <option value="cancelled">Cancelled</option>
</select>
      </div>
      <div>
        <label for="contactPersonName" class="block text-gray-700">Contact Person Name</label>
        <input type="text" id="contactPersonName" name="contactPersonName" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter ghana card number" value="">
      </div>
      <div>
        <label for="contactPersonName" class="block text-gray-700">Contact Person No.</label>
        <input type="text" id="contactPersonNumber" name="contactPersonNumber" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter ghana card number" value="">
      </div>
       <div>
          <label for="numberOfNights" class="block text-gray-700">Number Of Nights</label>
          <input type="number" readonly id="numberOfNights" name="numberOfNights" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Number of nights" value=<?= htmlspecialchars($booking['number_of_nights'])?>>
        </div>
      </div>
      <div class="mt-5">
        <button type="submit" name="submit" id="submitBtn" class="bg-gray-800 text-white px-6 py-2 rounded hover:bg-gray-700 hover:cursor-pointer">Save Changes</button>
      </div>
    </form>
  </div>
</div>
<div id="viewModal" class="fixed inset-0 hidden z-50 bg-gray-400/10 backdrop-blur-md bg-opacity-20 flex items-center justify-center">
  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-3xl relative max-h-screen overflow-y-auto">
   <div class="mb-3 flex justify-between items-center">
    <h2 class="text-xl font-bold text-gray-800">Booking Details</h2>
     <div class="flex space-2">
      <button onclick="printBooking()" class=" bg-green-500 w-8 h-8 rounded text-white hover:cursor-pointer">
      <i class="fa-solid fa-print"></i>
    </button>
<button onclick="closeViewModal()" class="bg-red-500 w-8 h-8 rounded text-white hover:cursor-pointer">
      <i class="fa-solid fa-xmark"></i>
    </button>
  </div>
  </div>
    <table class="w-full text-sm text-left text-gray-700 border">
      <tbody id="viewModalContent" class="divide-y divide-gray-200">
        <!-- Rows will be inserted here dynamically -->
      </tbody>
    </table>
  </div>
</div>

</main>
 <?php include_once 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/dropzone@5/dist/min/dropzone.min.js"></script>
<script>
  function printBooking() {
    window.print();
  }
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
document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('searchInput').addEventListener('input', function () {
    const searchValue = this.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
      const guestName = row.children[0].textContent.toLowerCase();
      const IDCardNumber = row.children[1].textContent.toLowerCase();
      row.style.display = guestName.includes(searchValue) || IDCardNumber.includes(searchValue) ? '' : 'none';
    });
  });

  window.closeModal = function () {
    document.getElementById('editModal').classList.add('hidden');
  }

  window.openEditModal = function (booking) {
  document.getElementById('editModal').classList.remove('hidden');
  document.getElementById('editbookingId').value = booking.booking_id;
  document.getElementById('guestName').value = booking.guest_name;
  document.getElementById('roomNumber').value = booking.room_number;
  document.getElementById('guestPhone').value = booking.guest_phone;
    document.getElementById('numberOfGuests').value = booking.number_of_guests;
  document.getElementById('status').value = booking.status;
  document.getElementById('checkIn').value = booking.checkin_date;
  document.getElementById('checkOut').value = booking.checkout_date;
    document.getElementById('contactPersonName').value = booking.contact_person_name;
      document.getElementById('contactPersonNumber').value = booking.contact_person_phone;
  document.getElementById('idCardNumber').value = booking.id_card_number;
  document.getElementById('residentialAddress').value = booking.residential_address;
  document.getElementById('numberOfNights').value = booking.number_of_nights;
}
window.openViewModal = function (booking) {
  const modal = document.getElementById('viewModal');
  const content = document.getElementById('viewModalContent');
  modal.classList.remove('hidden');
  const fields = {
    "Room Number": booking.room_number,
    "Guest Name": booking.guest_name,
    "Guest Phone": booking.guest_phone,
    "ID Card Number": booking.id_card_number,
    "Check-In": booking.checkin_date,
    "Check-Out": booking.checkout_date,
    "No. of Guests": booking.number_of_guests,
    "Contact Person": booking.contact_person_name,
    "Contact Person No.": booking.contact_person_phone,
    "No. of Nights": booking.number_of_nights,
    "Created At": booking.created_at,
    "Status": booking.status
  };

  content.innerHTML = ''; // Clear previous content

  for (const key in fields) {
    content.innerHTML += `
      <tr>
        <th class="py-2 px-4 bg-gray-100 font-semibold w-1/3">${key}</th>
        <td class="py-2 px-4">${fields[key] ?? '-'}</td>
      </tr>
    `;
  }
};

  window.closeViewModal = function () {
    document.getElementById('viewModal').classList.add('hidden');
  }

document.querySelectorAll('.view-btn').forEach(button => {
  button.addEventListener('click', function () {
    const booking = JSON.parse(this.closest('tr').querySelector('.edit-btn').dataset.booking);
    window.openViewModal(booking);
  });
});


  document.querySelectorAll('.edit-btn').forEach(button => {
  button.addEventListener('click', function () {
    const booking = JSON.parse(this.dataset.booking);
    window.openEditModal(booking); // ✅ this will now work
  });
});


  document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function () {
      const bookingId = this.dataset.id;
      const row = this.closest('tr');
      Swal.fire({
        title: 'Are you sure?',
        text: 'This booking will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e3342f',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!'
      }).then(result => {
        if (result.isConfirmed) {
          fetch('./functions/booking/delete-booking.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + encodeURIComponent(bookingId)
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              row.remove();
              Swal.fire('Deleted!', 'The booking has been deleted.', 'success');
            } else {
              Swal.fire('Error!', data.message || 'Failed to delete booking.', 'error');
            }
          })
          .catch(() => {
            Swal.fire('Error!', 'Something went wrong.', 'error');
          });
        }
      });
    });
  });

  document.getElementById('editBookingForm').addEventListener('submit', async function (event) {
  event.preventDefault();
  const form = this;
  const formData = new FormData(form);

  try {
    const res = await fetch('./functions/booking/update-booking.php', {
      method: 'POST',
      body: formData
    });

    const result = await res.json();

    if (result.success) {
      Swal.fire({
        icon: 'success',
        title: 'Booking Updated',
        text: 'Booking updated successfully',
        timer: 2500,
        timerProgressBar: true
      }).then(() => {
        location.reload();
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        html: result.message || 'Failed to update booking.'
      });
    }
  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'Network Error',
      text: 'Something went wrong.'
    });
  }
});

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
document.addEventListener('DOMContentLoaded', () => {
  const tableBody = document.querySelector('#rbookingsTable tbody');
  const rows = Array.from(tableBody.querySelectorAll('tr'));
  const paginationControls = document.getElementById('paginationControls');
  const totalCountEl = document.getElementById('totalCount');
  const startCountEl = document.getElementById('startCount');
  const endCountEl = document.getElementById('endCount');
  const rowsPerPageSelect = document.getElementById('rowsPerPage');

  let currentPage = 1;
  let rowsPerPage = parseInt(rowsPerPageSelect.value);

  function paginate() {
    const total = rows.length;
    totalCountEl.textContent = total;
    const totalPages = Math.ceil(total / rowsPerPage);

    const start = (currentPage - 1) * rowsPerPage;
    const end = Math.min(start + rowsPerPage, total);

    rows.forEach((row, i) => {
      row.style.display = i >= start && i < end ? '' : 'none';
    });

    startCountEl.textContent = start + 1;
    endCountEl.textContent = end;

    // Generate pagination buttons
    paginationControls.innerHTML = '';
    for (let i = 1; i <= totalPages; i++) {
      const btn = document.createElement('button');
      btn.textContent = i;
      btn.className = `mx-1 px-3 py-1 rounded ${
        i === currentPage
          ? 'bg-gray-600 text-white'
          : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
      } text-sm transition`;
      btn.addEventListener('click', () => {
        currentPage = i;
        paginate();
      });
      paginationControls.appendChild(btn);
    }
  }

  rowsPerPageSelect.addEventListener('change', () => {
    rowsPerPage = parseInt(rowsPerPageSelect.value);
    currentPage = 1;
    paginate();
  });

  paginate();
});
document.addEventListener('DOMContentLoaded', () => {
  const tableBody = document.querySelector('#securityLogTable tbody');
  const rows = Array.from(tableBody.querySelectorAll('tr'));
  const paginationControls = document.getElementById('paginationControls');
  const totalCountEl = document.getElementById('totalCount');
  const startCountEl = document.getElementById('startCount');
  const endCountEl = document.getElementById('endCount');
  const rowsPerPageSelect = document.getElementById('rowsPerPage');

  let currentPage = 1;
  let rowsPerPage = parseInt(rowsPerPageSelect.value);

  function paginate() {
    const total = rows.length;
    totalCountEl.textContent = total;
    const totalPages = Math.ceil(total / rowsPerPage);

    const start = (currentPage - 1) * rowsPerPage;
    const end = Math.min(start + rowsPerPage, total);

    rows.forEach((row, i) => {
      row.style.display = i >= start && i < end ? '' : 'none';
    });

    startCountEl.textContent = start + 1;
    endCountEl.textContent = end;

    // Generate pagination buttons
    paginationControls.innerHTML = '';
    for (let i = 1; i <= totalPages; i++) {
      const btn = document.createElement('button');
      btn.textContent = i;
      btn.className = `mx-1 px-3 py-1 rounded ${
        i === currentPage
          ? 'bg-gray-600 text-white'
          : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
      } text-sm transition`;
      btn.addEventListener('click', () => {
        currentPage = i;
        paginate();
      });
      paginationControls.appendChild(btn);
    }
  }

  rowsPerPageSelect.addEventListener('change', () => {
    rowsPerPage = parseInt(rowsPerPageSelect.value);
    currentPage = 1;
    paginate();
  });

  paginate();
});
window.addEventListener('DOMContentLoaded', paginateRows);

//print logic
function printTable() {
  const printArea = document.querySelector('.print-area');
  const styles = `
    <style>
      @media print {
        body {
          font-family: sans-serif;
        }
        .no-print {
          display: none !important;
        }
        .print-area {
          padding: 30px;
        }
        .print-area table {
          width: 100%;
          border-collapse: collapse;
          font-size: 13px;
        }
        .print-area th, .print-area td {
          border: 1px solid #ddd;
          padding: 8px;
          text-align: left;
        }
        .print-area th {
          font-size: 12px;
        }
        .print-area tr:nth-child(even) {
          background-color: #f9fafb;
        }
        .watermark {
          position: fixed;
          top: 45%;
          left: 50%;
          transform: translate(-50%, -50%);
          font-size: 80px;
          color: black;
          opacity: 0.05;
          pointer-events: none;
          z-index: 0;
        }
        .page-number::after {
          content: "Page " counter(page);
        }
        footer {
          position: fixed;
          bottom: 0;
          left: 0;
          right: 0;
          text-align: center;
          font-size: 12px;
          color: #9ca3af;
        }
      }
    </style>
  `;

  const win = window.open('', '', 'height=800,width=1200');
  win.document.write('<html><head><title>Print Security Logs</title>');
  win.document.write(styles);
  win.document.write('</head><body>');
  win.document.write('<div class="print-area">');
  win.document.write('<div class="watermark">CONSOL HOTEL</div>');
  win.document.write('<img src="your-logo.png" style="height:60px;margin-bottom:10px;">');
  win.document.write('<h3 style="text-align:center;margin:5px 0;">Consol Hotel - All Bookings</h3>');
  win.document.write('<p style="text-align:center;font-size:12px;color:gray;">Printed on: ' + new Date().toLocaleString() + '</p>');
  win.document.write(printArea.innerHTML);
  win.document.write('</div><footer class="page-number"></footer></body></html>');
  win.document.close();
  win.focus();
  win.print();
  win.close();
}
document.getElementById('downloadCSV').addEventListener('click', function () {
  const table = document.getElementById('employeesTable');
  const rows = table.querySelectorAll('thead tr, tbody tr');
  let csvContent = "";

  // Get index(es) of "Action" columns by scanning the first header row
  const actionIndexes = [];
  const firstRowCols = rows[0].querySelectorAll('th');
  firstRowCols.forEach((th, i) => {
    const headerText = th.textContent.trim().toLowerCase();
    if (headerText === 'action' || headerText.includes('actions')) {
      actionIndexes.push(i);
    }
  });

  // Loop through rows and collect data
  rows.forEach(row => {
    const cols = row.querySelectorAll('th, td');
    const rowData = Array.from(cols).map((col, i) => {
      // Skip action columns
      if (actionIndexes.includes(i)) return null;

      let text = col.textContent.trim().replace(/"/g, '""');

      // Format datetime if it matches "YYYY-MM-DD HH:mm:ss"
      if (/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/.test(text)) {
        const date = new Date(text);
        if (!isNaN(date)) {
          text = date.toLocaleString(); // Adjust to .toLocaleDateString() if only date needed
        }
      }

      return `"${text}"`;
    }).filter(Boolean); // Remove nulls (excluded columns)

    csvContent += rowData.join(",") + "\n";
  });

  // Add UTF-8 BOM to fix ₵ symbol in Excel
  const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement("a");
  link.setAttribute("href", URL.createObjectURL(blob));
  link.setAttribute("download", "all-bookings.csv");
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
});
</script>
</body>
</html>
