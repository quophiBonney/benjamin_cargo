<?php
// Authentication and DB connection
include_once 'includes/auth.php';
include_once 'includes/dbconnection.php';

// Define allowed roles
$allowed_roles = ['admin', 'manager', 'hr', 'staff', 'receptionist', 'cleaner'];
$limited_roles = ['staff', 'receptionist', 'cleaner'];

// Get session role and employee ID
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
$employee_id = $_SESSION['employee_id'] ?? null;

// Redirect if role is not allowed
if (!in_array($session_role, $allowed_roles)) {
    header("Location: staffs-dashboard.php");
    exit;
}

try {
    // Prepare query based on role
    if (in_array($session_role, $limited_roles)) {
        // Limited roles: view only their submitted issues
        $query = "
            SELECT 
                ex.*, 
                r.room_number, 
                rep.full_name AS reporter_name, 
                res.full_name AS resolver_name
            FROM room_issues ex
            LEFT JOIN rooms r ON ex.room_id = r.room_id
            LEFT JOIN employees rep ON ex.reported_by = rep.employee_id
            LEFT JOIN employees res ON ex.resolved_by = res.employee_id
            WHERE ex.reported_by = :employee_id
            ORDER BY ex.id DESC
        ";
        $stmt = $dbh->prepare($query);
        $stmt->bindParam(':employee_id', $employee_id, PDO::PARAM_INT);
    } else {
        // Allowed roles: view all issues
        $query = "
            SELECT 
                ex.*, 
                r.room_number, 
                rep.full_name AS reporter_name, 
                res.full_name AS resolver_name
            FROM room_issues ex
            LEFT JOIN rooms r ON ex.room_id = r.room_id
            LEFT JOIN employees rep ON ex.reported_by = rep.employee_id
            LEFT JOIN employees res ON ex.resolved_by = res.employee_id
            ORDER BY ex.id DESC
        ";
        $stmt = $dbh->prepare($query);
    }

    $stmt->execute();
    $issues = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching issues: " . $e->getMessage());
}
?>
<?php include_once 'includes/sidebar.php'; ?>
<?php include_once 'includes/header.php'; ?>
<?php include_once 'includes/app-bar.php';?>

<!-- Main Content -->
  <main class="flex-1 md:ml-64 px-4 transition-all">
  <?php if (count($issues) > 0): ?>
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
    <a href="report-room-issue.php">New Report</a>
  </button>
  <button class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white">
    <a target="_blank" href="prints/print-rooms.php">Download CSV</a>
  </button>
 <button onclick="printReports()" class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white">Print</button>
</div>
   <div class="print-area overflow-x-auto mt-6">
    <div class="watermark" style="display:none;">CONSOL HOTEL</div>
      <table id="reportsTable" class="table-x-auto w-full text-sm text-left text-gray-700 border border-gray-200 rounded-lg overflow-hidden shadow-sm">
        <thead class="bg-gray-700 text-white uppercase text-xs font-semibold border-b border-gray-300">
          <tr>
            <th class="py-3 px-3 min-w-[100px]">Room No.</th>
            <th class="py-3 px-3 min-w-[170px]">Issue</th>
            <th class="py-3 px-3 min-w-[180px]">Reported By</th>
            <th class="py-3 px-3">Reported At</th>
            <th class="py-3 px-4 min-w-[120px]">Resolved By</th>
             <th class="py-3 px-3">Resolved At</th>
              <th class="py-3 px-3">Status</th>
              <th class="py-3 px-3 no-print">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
<?php foreach ($issues as $issue): ?>
  <tr class="even:bg-gray-50 hover:bg-gray-100 transition duration-200">
    <td class="py-2 px-3"><?= htmlspecialchars($issue['room_number']) ?></td>
  <td class="px-3 py-2">
        <?= strlen($issue['issue_description']) > 30 
            ? htmlspecialchars(substr($issue['issue_description'], 0, 30)) . '...'
            : htmlspecialchars($issue['issue_description']) ?>
    </td>
    <td>
    <?= strlen($issue['reporter_name']) > 30 
            ? htmlspecialchars(substr($issue['reporter_name'], 0, 30)) . '...'
            : htmlspecialchars($issue['reporter_name']) ?>
    </td>
    <td class="py-2 px-3"><?= htmlspecialchars($issue['reported_at']) ?></td>
    <td>
    <?= strlen($issue['resolver_name']) > 30 
            ? htmlspecialchars(substr($issue['resolver_name'], 0, 30)) . '...'
            : htmlspecialchars($issue['resolver_name']) ?>
    </td>
     <td class="py-2 px-3"><?= htmlspecialchars($issue['resolved_at']) ?></td>
    <td class="py-2 px-3"> <?php if ($issue['status'] === 'reported'): ?>
            <span class="bg-red-600 text-white p-2 rounded">Reported</span>
        <?php elseif ($issue['status'] === 'in progress'): ?>
            <span class="bg-yellow-500 text-white p-2 rounded">Progress</span>
        <?php elseif ($issue['status'] === 'resolved'): ?>
            <span class="bg-green-600 text-white p-2 rounded">Resolved</span>
        <?php endif; ?>
</td>
      <td class="py-2 w-full flex space-x-1 no-print">
              <button class="bg-gray-500 text-white p-2 rounded hover:bg-gray-600 hover:cursor-pointer transition edit-btn" data-issue='<?= json_encode($issue) ?>'><i class="fas fa-edit"></i></button>
              
               <button class="bg-green-500  text-white p-2 rounded hover:bg-green-600 hover:cursor-pointer view-btn" data-id="<?= $issue['id'] ?>">
                <i class="fa fa-eye"></i>
</button>
              <button class="hover:cursor-pointer bg-red-500 text-white p-2 rounded hover:bg-red-600 transition delete-btn" data-id="<?= $issue['id']; ?>"><i class="fa fa-trash" aria-hidden="true"></i></button>
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
  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-2xl relative max-h-screen overflow-y-auto">
    <button onclick="closeModal()" class="mt-3 absolute bg-red-500 w-8 h-8 rounded text-white top-2 right-2 hover:cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
    <h2 class="text-xl font-bold mb-4">Report Update</h2>
    <form id="updateReportForm" class="space-y-3">
    <input type="hidden" id="editIssueId" name="id">
    <?php
  $session_role = strtolower(trim($_SESSION['role'] ?? ''));
  $editable_roles = ['admin', 'manager', 'hr'];
  $note_disabled = in_array($session_role, $editable_roles) ? '' : 'disabled';
?>
<div>
  <label for="resolutionNote">Resolution Note</label>
  <textarea id="resolutionNote" name="resolutionNote"  <?= $note_disabled ?> class="bg-gray-100 w-full p-2 border border-gray-300 rounded" rows="6"></textarea>
  </div>
       <?php
  $session_role = strtolower(trim($_SESSION['role'] ?? ''));
  $editable_roles = ['admin', 'manager', 'hr'];
  $status_disabled = in_array($session_role, $editable_roles) ? '' : 'disabled';
?>
<div>
  <label for="status">Status</label>
  <select id="status" name="status" <?= $status_disabled ?> class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
    <option selected disabled>Select Status</option>
    <option value="in progress">In Progress</option>
    <option value="resolved">Resolved</option>
  </select>
</div>

      <div class="">
        <button type="submit" name="submit" id="submitBtn" class="bg-gray-800 text-white px-6 py-2 rounded hover:bg-gray-700 hover:cursor-pointer">Save Changes</button>
      </div>
    </form>
  </div>
  </div>
<div id="viewModal" class="fixed inset-0 hidden z-50 bg-gray-400/10 backdrop-blur-md bg-opacity-20 flex items-center justify-center">
  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-3xl relative max-h-screen overflow-y-auto">
   <div class="mb-3 flex justify-between items-center">
    <h2 class="text-xl font-bold text-gray-800">Report Details</h2>
     <div class="flex space-2">
      <button onclick="printReports()" class="bg-green-500 w-8 h-8 rounded text-white hover:cursor-pointer">
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
</main>
 <?php include_once 'includes/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  // ✅ SEARCH FILTER
  document.getElementById('searchInput').addEventListener('input', function () {
    const searchValue = this.value.toLowerCase();
    const rows = document.querySelectorAll('#reportsTable tbody tr');
    rows.forEach(row => {
      const roomNumber = row.children[0].textContent.toLowerCase();
      const issueDescription = row.children[1].textContent.toLowerCase();
      row.style.display = roomNumber.includes(searchValue) || issueDescription.includes(searchValue) ? '' : 'none';
    });
  });

  // ✅ EDIT MODAL LOGIC
  window.closeModal = function () {
    document.getElementById('editModal').classList.add('hidden');
  };

  window.openEditModal = function (issue) {
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editIssueId').value = issue.id;
    document.getElementById('resolutionNote').value = issue.resolution_note;
    document.getElementById('status').value = issue.status;
  };

  // ✅ VIEW MODAL LOGIC
  window.openViewModal = function (issue) {
    const modal = document.getElementById('viewModal');
    const content = document.getElementById('viewModalContent');
    modal.classList.remove('hidden');

    const fields = {
      "Room Number": issue.room_number,
      "Issue Description": issue.issue_description,
      "Reported By": issue.reporter_name,
      "Reported At": issue.reported_at,
      "Resolved By": issue.resolver_name,
      "Resolved At": issue.resolved_at,
      "Resolution Note": issue.resolution_note,
      "Status": issue.status
    };

    content.innerHTML = '';
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
  };

  // ✅ BUTTON HANDLERS
  document.querySelectorAll('.edit-btn').forEach(button => {
    button.addEventListener('click', function () {
      const issue = JSON.parse(this.dataset.issue);
      window.openEditModal(issue);
    });
  });

  document.querySelectorAll('.view-btn').forEach(button => {
    button.addEventListener('click', function () {
      const row = this.closest('tr');
      const editBtn = row.querySelector('.edit-btn');
      if (editBtn && editBtn.dataset.issue) {
        const issue = JSON.parse(editBtn.dataset.issue);
        window.openViewModal(issue);
      }
    });
  });

  document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function () {
      const issueId = this.dataset.id;
      const row = this.closest('tr');
      Swal.fire({
        title: 'Are you sure?',
        text: 'This report will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e3342f',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!'
      }).then(result => {
        if (result.isConfirmed) {
          fetch('./functions/room/delete-room-issue.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + encodeURIComponent(issueId)
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              row.remove();
              Swal.fire('Deleted!', 'The issue has been deleted.', 'success');
            } else {
              Swal.fire('Error!', data.message || 'Failed to delete issue.', 'error');
            }
          })
          .catch(() => {
            Swal.fire('Error!', 'Something went wrong.', 'error');
          });
        }
      });
    });
  });

  // ✅ FORM SUBMIT HANDLER
  document.getElementById('updateReportForm').addEventListener('submit', async function (event) {
    event.preventDefault();
    const formData = new FormData(this);
    try {
      const res = await fetch('./functions/room/update-room-issue.php', {
        method: 'POST',
        body: formData
      });
      const result = await res.json();
      if (result.success) {
        Swal.fire({
          icon: 'success',
          title: 'Report Update',
          text: 'Report updated successfully',
          timer: 2500,
          timerProgressBar: true
        }).then(() => location.reload());
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Validation Error',
          html: result.message || 'Failed to update report.'
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

  // ✅ PAGINATION
  const tableBody = document.querySelector('#reportsTable tbody');
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

    paginationControls.innerHTML = '';
    for (let i = 1; i <= totalPages; i++) {
      const btn = document.createElement('button');
      btn.textContent = i;
      btn.className = `mx-1 px-3 py-1 rounded ${
        i === currentPage ? 'bg-gray-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
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

window.printReports = function () {
  const printArea = document.querySelector('.print-area').innerHTML;
  const printWindow = window.open('', '', 'height=600,width=800');

  printWindow.document.write(`
    <html>
    <head>
      <title>Room Issues Report</title>
      <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border: 1px solid #ccc; text-align: left; }
        th { background-color: #f4f4f4; }
        .no-print { display: none !important; }
      </style>
    </head>
    <body>
      <h2>Consol Hotel - Room Issues Report</h2>
      ${printArea}
    </body>
    </html>
  `);

  printWindow.document.close();
  printWindow.focus();
  printWindow.print();
  printWindow.close();
};


</script>
</body>
</html>
