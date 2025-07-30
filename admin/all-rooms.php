<?php 
include_once 'includes/auth.php';
$allowed_roles = ['admin', 'manager', 'receptionist', 'cleaner'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    header("Location: staffs-dashboard.php");
    exit;
}
include_once 'includes/dbconnection.php';
$query = "SELECT sl.*, e.full_name 
          FROM rooms sl
          LEFT JOIN employees e ON sl.created_by = e.employee_id
          ORDER BY sl.room_id DESC";
$stmt = $dbh->prepare($query);
$stmt->execute();
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include_once 'includes/sidebar.php'; ?>
<?php include_once 'includes/header.php'; ?>
<?php include_once 'includes/app-bar.php';?>
  <main class="flex-1 md:ml-64 transition-all px-4">
<?php if (count($rooms) > 0): ?>
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
            <option>400</option>
            <option>500</option>
            <option>1000</option>
          </select>
        </div>
        <div class="flex justify-center" id="paginationControls"></div>
</div>
<div class="">
    <input type="search" id="searchInput"
           placeholder="Search by room name or number..."
           class="w-full p-2 bg-gray-100 border border-gray-300 rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
  </div>
</div>
<div class="w-full mt-5 md:mt-2">
  <button class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white">
    <a href="add-room.php">Add New Room</a>
</button>
  <button id="downloadCSV" class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white">Download CSV</button>
 <button onclick="printTable()" class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white hover:pointer-cursor">Print</button>
</div>
  <div class="print-area overflow-x-auto mt-6">
    <div class="watermark" style="display:none;">CONSOL HOTEL</div>
    <table id="roomsTable" class="table-x-auto w-full text-sm text-left text-gray-700 border border-gray-200 rounded-lg overflow-hidden shadow-sm">
      <thead class="bg-gray-700 text-white uppercase text-xs font-semibold border-b border-gray-300">
        <tr>
          <th class="py-3 px-4 min-w-[170px]">Room Name</th>
          <th class="py-3 px-4">Room Number</th>
          <th class="py-3 px-4">No. Of Rooms</th>
          <th class="py-3 px-4">AC</th>
          <th class="py-3 px-4">Fan</th>
          <th class="py-3 px-4">Bathroom</th>
          <th class="py-3 px-4">Price</th>
          <th class="py-3 px-4">Status</th>
          <th class="py-3 px-4">Created By</th>
          <th class="py-3 px-4 min-w-[190px]">Added At</th>
          <th class="py-3 px-4 no-print">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
        <?php foreach ($rooms as $room): ?>
        <tr class="even:bg-gray-50 hover:bg-gray-100 transition duration-200">
          <td class="py-2 px-4"><?= htmlspecialchars($room['room_name']) ?></td>
          <td class="py-2 px-4"><?= htmlspecialchars($room['room_number']) ?></td>
          <td class="py-2 px-4"><?= htmlspecialchars($room['number_of_rooms']) ?></td>
          <td class="py-2 px-4"><?= htmlspecialchars($room['number_of_ac']) ?></td>
          <td class="py-2 px-4"><?= htmlspecialchars($room['number_of_fan']) ?></td>
          <td class="py-2 px-4"><?= htmlspecialchars($room['number_of_bathroom']) ?></td>
          <td class="py-2 px-4">₵<?= htmlspecialchars($room['price_per_night']) ?></td>
          <td class="py-2 px-4">
          <?php if ($room['status'] == 'available'): ?>
  <span class="w-full bg-green-600 rounded-lg p-2 text-white font-semibold">Available</span>
<?php elseif ($room['status'] == 'booked'): ?>
  <span class="w-full bg-red-600 text-white rounded-lg p-2 font-semibold">Booked</span>
<?php elseif ($room['status'] == 'reserved'): ?>
  <span class="w-full bg-yellow-500 text-white rounded-lg p-2 font-semibold">Reserved</span>
<?php elseif ($room['status'] == 'unavailable'): ?>
  <span class="w-full bg-gray-500 text-white rounded-lg p-2 font-semibold">Maintenance</span>
<?php endif; ?>
            </td>
            <td class="py-2 px-4"><?= htmlspecialchars($room['full_name'])?></td>
              <td class="py-2 px-4"><?= htmlspecialchars($room['created_at']) ?></td>
          <td class="py-2 flex gap-1 px-4 space-x-1 no-print">
            <button class="bg-gray-500 text-white p-2 rounded hover:bg-gray-600 hover:cursor-pointer transition edit-btn" data-room='<?= json_encode($room) ?>'><i class="fas fa-edit"></i></button>
            <button class="hover:cursor-pointer bg-red-500 text-white p-2 rounded hover:bg-red-600 transition delete-btn" data-id="<?= $room['room_id']; ?>"><i class="fa fa-trash" aria-hidden="true"></i></button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    
  </div>
</div>
<?php else: ?>
  <div class="bg-white shadow-md rounded-md p-6 mt-24 px-4 text-center text-gray-600">
    No rooms found.
  </div>
<?php endif; ?>
<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 hidden z-50 bg-gray-400/10 backdrop-blur-md bg-opacity-20 flex items-center justify-center">
  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-3xl relative max-h-screen overflow-y-auto">
    <button onclick="closeModal()" class="mt-3 absolute bg-red-500 w-10 h-10 rounded text-white top-2 right-2 text-white hover:cursor-pointer">&times;</button>
    <h2 class="text-xl font-bold mb-4">Edit Room</h2>
    <form id="editRoomForm" enctype="multipart/form-data">
      <input type="hidden" id="editRoomId" name="room_id">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div class="">
        <label for="roomName" class="block text-gray-700">Room Name</label>
        <input type="text" id="roomName" name="roomName" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter room name">
      </div>
      <div class="">
        <label for="status" class="block text-gray-700">Status</label>
        <select id="status" name="status" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" data-current="<?= htmlspecialchars($status ?? '') ?>">
  <option value="" disabled>Select Status</option>
  <option value="available">Available</option>
  <option value="booked">Booked</option>
  <option value="reserved">Reserved</option>
  <option value="checkedout">Checked Out</option>
  <option value="cancelled">Cancelled</option>
</select>
      </div>
        <div>
          <label for="roomNumber" class="block text-gray-700">Room Number</label>
          <input type="number" id="roomNumber" name="roomNumber" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        </div>
        <div>
          <label for="numberOfRooms" class="block text-gray-700">Number of Rooms</label>
          <input type="number" id="numberOfRooms" name="numberOfRooms" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        </div>
        <div>
          <label for="numberOfAC" class="block text-gray-700">Number of AC</label>
          <input type="number" id="numberOfAC" name="numberOfAC" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        </div>
        <div>
          <label for="numberOfBathroom" class="block text-gray-700">Number of Bathroom</label>
          <input type="number" id="numberOfBathroom" name="numberOfBathroom" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        </div>
        <div>
          <label for="numberOfFan" class="block text-gray-700">Number of Fan</label>
          <input type="number" id="numberOfFan" name="numberOfFan" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        </div>
        <div>
          <label for="price" class="block text-gray-700">Price Per Night</label>
          <input type="number" id="price" name="price" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        </div>
      </div>
        <div class="">
        <label for="roomImage" class="block text-gray-700">Room Image</label>
          <div id="imageDropzone" class=" dropzone border border-dashed border-gray-300 rounded p-4 bg-gray-100" name="room_image"></div>
        </div>
      <div class="mt-4">
      <button type="button" id="submitBtn" class="bg-gray-800 text-white px-6 py-2 rounded hover:bg-gray-700 hover:cursor-pointer">Update Room</button>
      </div>
    </form>
  </div>
</main>
<?php include_once 'includes/footer.php'; ?>
<script>
document.getElementById('searchInput').addEventListener('input', function () {
  const searchValue = this.value.toLowerCase();
  const rows = document.querySelectorAll('tbody tr');
  rows.forEach(row => {
    const name = row.children[0].textContent.toLowerCase();
    const number = row.children[1].textContent.toLowerCase();
    row.style.display = name.includes(searchValue) || number.includes(searchValue) ? '' : 'none';
  });
});

let uploadedImagePath = null;
const dropzone = new Dropzone("#imageDropzone", {
  url: "./functions/room/update-room.php",
  paramName: "file",
  maxFiles: 5,
  acceptedFiles: "image/*",
  addRemoveLinks: true,
  init: function () {
    this.on("success", function (file, response) {
      if (response.success) {
        uploadedImagePath = response.filePath;
      }
    });
  }
});

function closeModal() {
  document.getElementById('editModal').classList.add('hidden');
}

function openEditModal(room) {
  document.getElementById('editModal').classList.remove('hidden');
  document.getElementById('editRoomId').value = room.room_id; // fixed key
  document.getElementById('roomName').value = room.room_name;
  document.getElementById('roomNumber').value = room.room_number;
  document.getElementById('numberOfRooms').value = room.number_of_rooms;
  document.getElementById('numberOfAC').value = room.number_of_ac;
  document.getElementById('numberOfFan').value = room.number_of_fan;
  document.getElementById('status').value = room.status || 'available';
  document.getElementById('numberOfBathroom').value = room.number_of_bathroom;
  document.getElementById('price').value = room.price_per_night;

  const statusSelect = document.getElementById('status');
  statusSelect.value = room.status || 'available';
  statusSelect.setAttribute('data-current', room.status);

  // Disable if status is 'booked' or 'reserved'
  if (room.status === 'booked' || room.status === 'reserved') {
    statusSelect.disabled = true;
  } else {
    statusSelect.disabled = false;
  }
}


document.querySelectorAll('.edit-btn').forEach(button => {
  button.addEventListener('click', () => {
    const room = JSON.parse(button.dataset.room);
    openEditModal(room);
  });
});

document.querySelectorAll('.delete-btn').forEach(button => {
  button.addEventListener('click', function () {
    const roomId = this.dataset.id;
    const row = this.closest('tr');
    Swal.fire({
      title: 'Are you sure?',
      text: 'This room will be permanently deleted.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#e3342f',
      cancelButtonColor: '#6b7280',
      confirmButtonText: 'Yes, delete it!'
    }).then(result => {
      if (result.isConfirmed) {
        fetch('./functions/room/delete-room.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'id=' + encodeURIComponent(roomId)
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            row.remove();
            Swal.fire('Deleted!', 'The room has been deleted.', 'success');
          } else {
            Swal.fire('Error!', data.message || 'Failed to delete room.', 'error');
          }
        })
        .catch(() => {
          Swal.fire('Error!', 'Something went wrong.', 'error');
        });
      }
    });
  });
});

document.getElementById('submitBtn').addEventListener('click', async function () {
  const form = document.getElementById('editRoomForm');
  const formData = new FormData(form);

  if (uploadedImagePath) {
    formData.append('uploaded_image', uploadedImagePath);
  }

  try {
    const res = await fetch('./functions/room/update-room.php', {
      method: 'POST',
      body: formData,
      credentials: 'include'
    });

    const result = await res.json();

    if (result.success) {
      Swal.fire({
  icon: 'success',
  title: 'Room Updated',
  text: 'Room updated successfully',
  timer: 2500,
  timerProgressBar: true
}).then(() => {
  location.reload()
})
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        html: result.message || 'Failed to update room.'
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
document.addEventListener('DOMContentLoaded', () => {
  const tableBody = document.querySelector('#roomsTable tbody');
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
document.getElementById('downloadCSV').addEventListener('click', function () {
  const table = document.getElementById('roomsTable');
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
  link.setAttribute("download", "rooms-data.csv");
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
});

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
          background-color: #1f2937;
          color: white;
          font-size: 11px;
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
  win.document.write('<h3 style="text-align:center;margin:5px 0;">Consol Hotel - All Rooms</h3>');
  win.document.write('<p style="text-align:center;font-size:12px;color:gray;">Printed on: ' + new Date().toLocaleString() + '</p>');
  win.document.write(printArea.innerHTML);
  win.document.write('</div><footer class="page-number"></footer></body></html>');
  win.document.close();
  win.focus();
  win.print();
  win.close();
}
</script>
</body>
</html>