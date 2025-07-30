<?php 
include_once 'includes/auth.php';
$allowed_roles = ['admin', 'manager', 'hr', 'receptionist', 'staff', 'cleaner'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    header("Location: staffs-dashboard.php");
    exit;
}
include_once 'includes/dbconnection.php';
$allowed_roles = ['admin', 'manager', 'hr'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
$employee_id = $_SESSION['employee_id'] ?? null;

if (in_array($session_role, $allowed_roles)) {
    // Admins or Managers: See all leave submissions
    $query = "SELECT sl.*, e.full_name 
              FROM leave_submissions sl
              LEFT JOIN employees e ON sl.employee_id = e.employee_id 
              ORDER BY sl.leave_id DESC";
    $stmt = $dbh->prepare($query);
    $stmt->execute();
} else {
    // Normal staff: See only their own submissions
    $query = "SELECT sl.*, e.full_name 
              FROM leave_submissions sl
              LEFT JOIN employees e ON sl.employee_id = e.employee_id 
              WHERE sl.employee_id = :employee_id 
              ORDER BY sl.leave_id DESC";
    $stmt = $dbh->prepare($query);
    $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
    $stmt->execute();
}
$leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
$perPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$start = ($page - 1) * $perPage;

// Get total leave count
$totalStmt = $dbh->query("SELECT COUNT(*) FROM leave_submissions");
$totalLeaves = $totalStmt->fetchColumn();
$totalPages = ceil($totalLeaves / $perPage);

?>
<?php include_once 'includes/sidebar.php'; ?>
<?php include_once 'includes/header.php'; ?>
<?php include_once 'includes/app-bar.php';?>
 <main class="flex-1 md:ml-64 px-4 transition-all">
  <?php if (count($leaves) > 0): ?>
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
    <input type="search" id="searchInput" placeholder="Search by name or number..."
           class="w-full p-2 bg-gray-100 border border-gray-300 rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
  </div>
</div>
<div class="w-full mt-5 md:mt-2">
   <button class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white">
    <a href="employee-leave-submission.php">New Application</a>
</button>
  <button id="downloadCSV" class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white">Download CSV</button>
 <button onclick="printTable()" class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white hover:pointer-cursor">Print</button>
</div>
    <div class="overflow-x-auto mt-6">
      <table class="table-x-auto w-full text-sm text-left text-gray-700 border border-gray-200 rounded-lg overflow-hidden shadow-sm">
  <thead class="table-x-auto w-full text-sm text-left text-gray-700 border border-gray-200 rounded-lg overflow-hidden shadow-sm">
    <tr class="bg-gray-100 text-left text-sm font-semibold text-gray-700">
      <thead class="bg-gray-700 text-white uppercase text-xs font-semibold border-b border-gray-300">
        <tr>
           <th class="p-2 border">#</th>
           <th class="p-2 border">Employee Name</th>
      <th class="p-2 border">Leave Type</th>
      <th class="p-2 border">Start Date</th>
      <th class="p-2 border">End Date</th>
      <th class="p-2 border">Status</th>
      <th class="p-2 border">Actions</th>
        </tr>
      </thead>
    </tr>
  </thead>
  <tbody class="text-sm">
    <?php foreach ($leaves as $index => $leave): ?>
      <tr class="hover:bg-gray-50">
        <td class="p-2 border"><?= $start + $index + 1 ?></td>
        <td class="p-2 border"><?= $leave['full_name'] ?></td>
        <td class="p-2 border"><?= htmlspecialchars($leave['leave_type']) ?></td>
        <td class="p-2 border"><?= htmlspecialchars($leave['start_date']) ?></td>
        <td class="p-2 border"><?= htmlspecialchars($leave['end_date']) ?></td>
       <td class="p-2 border text-white">
    <?php if ($leave['status'] === 'pending'): ?>
        <span class="bg-yellow-500 p-2 px-2 py-1 rounded">Pending</span>
    <?php elseif ($leave['status'] === 'approved'): ?>
        <span class="bg-green-500 p-2 px-2 py-1 rounded">Approved</span>
    <?php elseif ($leave['status'] === 'rejected'): ?>
        <span class="bg-red-500 p-2 px-2 py-1 rounded">Rejected</span>
    <?php else: ?>
        <span class="bg-gray-500 p-2 px-2 py-1 rounded"><?= htmlspecialchars($leave['status']) ?></span>
    <?php endif; ?>
</td>

        </td>
        
        <td class="p-2 border">
         <button class="bg-gray-500 text-white px-2 py-1 rounded" 
        onclick='openEditModal(<?= json_encode($leave) ?>)'>Update</button>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php if ($totalPages > 1): ?>
<nav class="mt-4 flex justify-center items-center space-x-1 text-sm">
  <?php if ($page > 1): ?>
    <a href="?page=<?= $page - 1 ?>" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">&laquo; Prev</a>
  <?php endif; ?>

  <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="?page=<?= $i ?>" 
       class="px-3 py-1 rounded <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
      <?= $i ?>
    </a>
  <?php endfor; ?>

  <?php if ($page < $totalPages): ?>
    <a href="?page=<?= $page + 1 ?>" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">Next &raquo;</a>
  <?php endif; ?>
</nav>
<?php endif; ?>

    </div>
  </div>
  <?php else: ?>
    <div class="mt-24 bg-white shadow-md rounded-md p-6 px-4 text-center text-gray-600">
     You have no leave application.
    </div>
  <?php endif; ?>
<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 hidden z-50 bg-gray-400/10 backdrop-blur-md bg-opacity-20 flex items-center justify-center">
  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-2xl relative max-h-screen overflow-y-auto">
    <button onclick="closeModal()" class="mt-3 absolute bg-red-500 w-8 h-8 rounded text-white top-2 right-2 hover:cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
    <h2 class="text-xl font-bold mb-4">Leave Update</h2>
    <form id="editLeaveForm">
         <input type="hidden" id="editLeaveId" name="id">
      <div class="mb-4">
        <label for="leaveType" class="block text-gray-700">Leave Type</label>
       <select id="leaveType" name="leaveType" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        <option value="" disabled selected>Select Leave Type</option>
         <option value="annual">Annual</option>
        <option value="emergency">Emergency</option>
         <option value="maternal">Maternal</option>
          <option value="personal">Personal</option>
        </select>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="startDate" class="block text-gray-700">Start Date</label>
          <input type="date" id="startDate" name="startDate" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        </div>
       <div>
          <label for="endDate" class="block text-gray-700">End Date</label>
          <input type="date" id="endDate" name="endDate" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        </div>
      </div>
      <div class="mt-4">
        <label for="reason">Reason</label>
        <textarea id="reason" name="reason" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" rows="4" cols="50" placeholder="Please type your reason for leave"></textarea>
        </div>
        <!-- <div class="mt-4">
          <div id="imageDropzone" class="dropzone border border-dashed border-gray-300 rounded p-4 bg-gray-100"></div>
        </div> -->
      <?php 
$role = $_SESSION['role'] ?? '';
$allowedRoles = ['admin', 'manager', 'hr'];
$isDisabled = !in_array(strtolower($role), $allowedRoles);
?>
<div class="mb-4">
  <label for="status" class="block text-gray-700">Status</label>
  <select 
    id="status" 
    name="status" 
    class="bg-gray-100 w-full p-2 border border-gray-300 rounded"
    <?= $isDisabled ? 'disabled' : '' ?>
  >
    <option value="" disabled selected>Select Leave Type</option>
    <option value="approved">Approved</option>
    <option value="rejected">Rejected</option>
  </select>
</div>
      <div class="mt-3">
        <button type="submit" name="submit" id="submitBtn" class="bg-gray-800 text-white px-6 py-2 rounded hover:bg-gray-700 hover:cursor-pointer">Submit Application</button>
      </div>
    </form>
  </div>
</div>
<!-- Leave View Modal -->
<div id="leaveModal" class="fixed inset-0 hidden z-50 bg-gray-400/10 backdrop-blur-md bg-opacity-20 flex items-center justify-center">
  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-2xl relative max-h-screen overflow-y-auto">
    <button onclick="closeLeaveModal()" class="mt-3 absolute bg-red-500 w-8 h-8 rounded text-white top-2 right-2 hover:cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
    <h2 class="text-xl font-bold mb-4">Leave Application Details</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm text-gray-700">
    <div>
          <p class="text-lg font-semibold">Leave Type:</p>
        <p id="modalStatus" class="text-lg text-gray-600"></p>
  </div>
  <div>
          <p class="text-lg font-semibold">Status:</p>
        <p id="modalLeaveType" class="text-lg text-gray-600"></p>
  </div>
   <div>
          <p class="text-lg font-semibold">Start Date:</p>
        <p id="modalStartDate" class="text-lg text-gray-600"></p>
  </div>
   <div>
          <p class="text-lg font-semibold">End Date:</p>
        <p id="modalEndDate" class="text-lg text-gray-600"></p>
  </div>
   <div>
          <p class="text-lg font-semibold">Date Applied:</p>
        <p id="modalSubmissionDate" class="text-lg text-gray-600"></p>
  </div>
  </div>
  <div class="w-full">
    <p class="text-lg font-semibold">Reason:</p>
    <p id="modalReason" class="text-lg"></p>
  </div>
  </div>
</div>


</main>
<?php include_once 'includes/footer.php'; ?>
<!-- <script src="https://cdn.jsdelivr.net/npm/dropzone@5/dist/min/dropzone.min.js"></script> -->
<script>
    
    // View details modal logic
window.showLeaveDetails = function (leave) {
  document.getElementById('modalLeaveType').textContent = leave.leave_type || '';
  document.getElementById('modalReason').textContent = leave.reason || '';
  document.getElementById('modalStartDate').textContent = leave.start_date || '';
  document.getElementById('modalEndDate').textContent = leave.end_date || '';
  document.getElementById('modalStatus').textContent = leave.status || '';
  document.getElementById('modalSubmissionDate').textContent = leave.submission_date || '';

  const modal = document.getElementById('leaveModal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
};

window.closeLeaveModal = function () {
  const modal = document.getElementById('leaveModal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
};

document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('searchInput').addEventListener('input', function () {
    const searchValue = this.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
      const fullName = row.children[0].textContent.toLowerCase();
      const ghanaCardNumber = row.children[1].textContent.toLowerCase();
      row.style.display = fullName.includes(searchValue) || ghanaCardNumber.includes(searchValue) ? '' : 'none';
    });
  });

  window.closeModal = function () {
    document.getElementById('editModal').classList.add('hidden');
  }

  window.openEditModal = function (leave) {
  document.getElementById('editModal').classList.remove('hidden');
  document.getElementById('editLeaveId').value = leave.leave_id;
document.getElementById('leaveType').value = leave.leave_type;
document.getElementById('startDate').value = leave.start_date;
document.getElementById('endDate').value = leave.end_date;
document.getElementById('reason').value = leave.reason;
document.getElementById('status').value = leave.status;
}

  document.querySelectorAll('.edit-btn').forEach(button => {
  button.addEventListener('click', function () {
    const leave = JSON.parse(this.dataset.leave);
    window.openEditModal(leave); // ✅ this will now work
  });
});


  document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function () {
      const leaveId = this.dataset.id;
      const row = this.closest('tr');
      Swal.fire({
        title: 'Are you sure?',
        text: 'This leave will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e3342f',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!'
      }).then(result => {
        if (result.isConfirmed) {
          fetch('./functions/leave/delete-leave.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + encodeURIComponent(leaveId)
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              row.remove();
              Swal.fire('Deleted!', 'The leave application has been deleted.', 'success');
            } else {
              Swal.fire('Error!', data.message || 'Failed to delete leave application.', 'error');
            }
          })
          .catch(() => {
            Swal.fire('Error!', 'Something went wrong.', 'error');
          });
        }
      });
    });
  });

  document.getElementById('editLeaveForm').addEventListener('submit', async function (event) {
  event.preventDefault();
  const form = this;
  const formData = new FormData(form);

  try {
    const res = await fetch('./functions/leave/update-leave.php', {
      method: 'POST',
      body: formData
    });

    const result = await res.json();

    if (result.success) {
      Swal.fire({
        icon: 'success',
        title: 'Leave Application Update',
        text: 'Leave application updated successfully',
        timer: 2500,
        timerProgressBar: true
      }).then(() => {
        location.reload();
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        html: result.message || 'Failed to update leave application.'
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
  link.setAttribute("download", "all-leave-applications.csv");
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
  win.document.write('<h3 style="text-align:center;margin:5px 0;">Consol Hotel - All Leave Applications</h3>');
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
