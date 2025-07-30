<?php
include_once 'includes/auth.php';
$allowed_roles = ['admin', 'manager', 'hr'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    header("Location: staffs-dashboard.php"); 
    exit;
}

include_once 'includes/dbconnection.php'; // move this here if needed

// ✅ Only this query needed:
$query = "SELECT a.*, e.full_name 
          FROM announcements a 
          LEFT JOIN employees e ON a.created_by = e.employee_id 
          ORDER BY a.announcement_id ASC";
$stmt = $dbh->prepare($query);
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include_once 'includes/sidebar.php'; ?>
<?php include_once 'includes/header.php'; ?>
<?php include_once 'includes/app-bar.php';?>
 <main class="flex-1 md:ml-64 px-4 transition-all">
  <?php if (count($announcements) > 0): ?>
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
    <input type="search" id="searchInput" placeholder="Search by headline..."
           class="w-full p-2 bg-gray-100 border border-gray-300 rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
  </div>
</div>
<div class="w-full mt-5 md:mt-2">
<button class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white">
    <a href="add-announcement.php">New Announcement</a>
  </button>
<button id="downloadCSV" class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white">Download CSV</button>
 <button class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white" onclick="printTable()">
Print
  </button>
</div>
    <div class="print-area overflow-x-auto mt-6">
      <div class="watermark" style="display:none;">CONSOL HOTEL</div>
      <table id="announcementTable" class="table-x-auto w-full text-sm text-left text-gray-700 border border-gray-200 rounded-lg overflow-hidden shadow-sm">
        <thead class="bg-gray-700 text-white uppercase text-xs font-semibold border-b border-gray-300">
          <tr>
             <th class="py-3 px-4">#</th>
            <th class="py-3 px-4 min-w-[150px]">Headline</th>
            <th class="py-3 px-4 min-w-[150px]">Details</th>
            <th class="py-3 px-4 min-w-[150px]">Created By</th>
            <th class="py-3 px-4">Created At</th>
            <th class="py-3 px-4 no-print">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php foreach ($announcements as $announcement): ?>
          <tr class="even:bg-gray-50 hover:bg-gray-100 transition duration-200">
            <td class="py-2 px-4"><?= htmlspecialchars($announcement['announcement_id']) ?></td>
            <td class="py-2 px-4"><?= htmlspecialchars($announcement['headline']) ?></td>
            <td class="py-2 px-4"><?= htmlspecialchars($announcement['details']) ?></td>
           <td class="py-2 px-4"><?= htmlspecialchars($announcement['full_name'] ?? 'N/A') ?></td>
            <td class="py-2 px-4"><?= htmlspecialchars($announcement['created_at']) ?></td>
            <td class="py-2 flex gap-1 px-4 space-x-1 no-print">
              <button class="bg-gray-500 text-white p-2 rounded hover:bg-gray-600 hover:cursor-pointer transition edit-btn" data-announcement='<?= json_encode($announcement) ?>'><i class="fas fa-edit"></i></button>
              <button class="hover:cursor-pointer bg-red-500 text-white p-2 rounded hover:bg-red-600 transition delete-btn" data-id="<?= $announcement['announcement_id']; ?>"><i class="fa fa-trash" aria-hidden="true"></i></button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php else: ?>
    <div class="mt-20 bg-white shadow-md rounded-md p-6 mt-16 md:mt-10 px-4 text-center text-gray-600">
      No announcement found.
    </div>
  <?php endif; ?>
<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 hidden z-50 bg-gray-400/10 backdrop-blur-md bg-opacity-20 flex items-center justify-center">
  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-2xl relative max-h-screen overflow-y-auto">
    <button onclick="closeModal()" class="mt-3 absolute bg-red-500 w-8 h-8 rounded text-white top-2 right-2 hover:cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
    <h2 class="text-xl font-bold mb-4">Announcement Update</h2>
       <form id="editAnnouncementForm">
        <input type="hidden" id="editannouncementId" name="id">
      <div class="mb-3">
        <label for="headline" class="block text-gray-700">Headline</label>
        <input type="text"  id="editHeadline" name="headline" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Type announcement headline" value="">
      </div>
      <div class="mb-3">
        <label for="details" class="block text-gray-700">Details</label>
        <textarea id="editDetails" name="details" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" rows="6" columns="6" placeholder="Write details of the announcement here!" value=""></textarea>
      </div>
     <div class="mt-5">
        <button type="submit" name="submit" id="submitBtn" class="bg-gray-800 text-white px-6 py-2 rounded hover:bg-gray-700 hover:cursor-pointer">Save Changes</button>
      </div>
    </form>
  </div>
</div>
</main>
<?php include_once 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/dropzone@5/dist/min/dropzone.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.getElementById('searchInput').addEventListener('input', function () {
    const searchValue = this.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
      const headline = row.children[1].textContent.toLowerCase();
      row.style.display = headline.includes(searchValue) ? '' : 'none';
    });
  });

  window.closeModal = function () {
    document.getElementById('editModal').classList.add('hidden');
  }

  window.openEditModal = function (announcement) {
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editannouncementId').value = announcement.announcement_id;
    document.getElementById('editHeadline').value = announcement.headline;
    document.getElementById('editDetails').value = announcement.details;
  }

  document.querySelectorAll('.edit-btn').forEach(button => {
    button.addEventListener('click', function () {
      const announcement = JSON.parse(this.dataset.announcement);
      window.openEditModal(announcement);
    });
  });

  document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function () {
      const announcementId = this.dataset.id;
      const row = this.closest('tr');
      Swal.fire({
        title: 'Are you sure?',
        text: 'This announcement will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e3342f',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!'
      }).then(result => {
        if (result.isConfirmed) {
          fetch('./functions/announcement/delete-announcement.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + encodeURIComponent(announcementId)
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              row.remove();
              Swal.fire('Deleted!', 'The announcement has been deleted.', 'success');
            } else {
              Swal.fire('Error!', data.message || 'Failed to delete announcement.', 'error');
            }
          })
          .catch(() => {
            Swal.fire('Error!', 'Something went wrong.', 'error');
          });
        }
      });
    });
  });

  document.getElementById('editAnnouncementForm').addEventListener('submit', async function (event) {
    event.preventDefault();
    const form = this;
    const formData = new FormData(form);

    try {
      const res = await fetch('./functions/announcement/update-announcement.php', {
        method: 'POST',
        body: formData
      });

      const result = await res.json();

      if (result.success) {
        Swal.fire({
          icon: 'success',
          title: 'Announcement Updated',
          text: 'Announcement updated successfully',
          timer: 2500,
          timerProgressBar: true
        }).then(() => {
          location.reload();
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Validation Error',
          html: result.message || 'Failed to update announcement.'
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
  const table = document.getElementById('announcementTable');
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
  link.setAttribute("download", "announcements.csv");
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
});
document.addEventListener('DOMContentLoaded', () => {
  const tableBody = document.querySelector('#announcementTable tbody');
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
  win.document.write('<h3 style="text-align:center;margin:5px 0;">Consol Hotel - Announcements</h3>');
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
