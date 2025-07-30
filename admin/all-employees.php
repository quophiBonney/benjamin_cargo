<?php 
include_once 'includes/auth.php';
$allowed_roles = ['admin', 'manager', 'hr'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    header("Location: staffs-dashboard.php");
    exit;
}
?>
<?php 
include_once 'includes/dbconnection.php';
$query = "SELECT * FROM employees ORDER BY employee_id ASC";
$stmt = $dbh->prepare($query);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include_once 'includes/sidebar.php'; ?>
<?php include_once 'includes/header.php'; ?>
<?php include_once 'includes/app-bar.php';?>
 <main class="flex-1 md:ml-64 px-4 transition-all">
  <?php if (count($employees) > 0): ?>
  <div class="bg-white shadow-md rounded-md p-6 mt-24 md:mt-32">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
 <div class="w-full md:col-span-2 flex flex-col md:flex-row gap-2 items-center">
  <div>
        <p>
          Showing <span id="startCount">1</span> to <span id="endCount">10</span> of
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
    <a href="add-employee.php">Add New employee</a>
  </button>
 <button class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white" id="downloadCSV">
  Download CSV
  </button>
  <button onclick="printTable()" class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white">
Print
 <button>
</div>
    <div class="print-area overflow-x-auto mt-6">
      <div class="watermark" style="display:none;">CONSOL HOTEL</div>
      <table id="employeesTable" class="table-x-auto w-full text-sm text-left text-gray-700 border border-gray-200 rounded-lg overflow-hidden shadow-sm">
        <thead class="bg-gray-700 text-white uppercase text-xs font-semibold border-b border-gray-300">
          <tr>
             <th class="py-3 px-4">#</th>
            <th class="py-3 px-4 min-w-[150px]">Full Name</th>
            <th class="py-3 px-4">Phone</th>
            <th class="py-3 px-4 min-w-[150px]">Res. Address</th>
            <th class="py-3 px-4">Position</th>
            <!-- <th class="py-3 px-4">Gh Card No.</th> -->
            <th class="py-3 px-4 min-w-[130px]">Date Hired</th>
            <!-- <th class="py-3 px-4 min-w-[130px]">Location</th> -->
            <th class="py-3 px-4">Salary</th>
            <th class="py-3 px-4">DOB</th>
            <th class="py-3 px-4">Status</th>
            <th class="py-3 px-4">Role</th>
            <th class="py-3 px-4 no-print">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php foreach ($employees as $employee): ?>
          <tr class="even:bg-gray-50 hover:bg-gray-100 transition duration-200">
            <td class="py-2 px-4"><?= htmlspecialchars($employee['employee_id']) ?></td>
            <td class="py-2 px-4"><?= htmlspecialchars($employee['full_name']) ?></td>
            <td class="py-2 px-4"><?= htmlspecialchars($employee['phone']) ?></td>
            <td class="py-2 px-4"><?= htmlspecialchars($employee['residential_address']) ?></td>
            <td class="py-2 px-4"><?= htmlspecialchars($employee['position']) ?></td>
            <!-- <td class="py-2 px-4"><?= htmlspecialchars($employee['ghana_card_number']) ?></td> -->
            <td class="py-2 px-4"><?= htmlspecialchars($employee['date_hired']) ?></td>

             <!-- <td class="py-2 px-4"> <a 
                        href="location.php?lat=<?= urlencode($employee['latitude']) ?>&lng=<?= urlencode($employee['longitude']) ?>" 
                        target="_blank" 
                        class="bg-green-600 p-1 rounded inline-block mt-1 text-white hover:underline text-xs"
                      >View Location</a></td> -->
            <td class="py-2 px-4">₵<?= htmlspecialchars($employee['salary']) ?></td>
            <td class="py-2 px-4"><?= htmlspecialchars($employee['dob']) ?></td>
            <td class="py-2 px-4"><?= htmlspecialchars($employee['status'])?></td>
            <td class="py-2 px-4"><?= htmlspecialchars($employee['role'])?></td>
            <td class="py-2 flex gap-1 px-4 space-x-1 no-print">
              <button class="bg-gray-500 text-white p-2 rounded hover:bg-gray-600 hover:cursor-pointer transition edit-btn" data-employee='<?= json_encode($employee) ?>'><i class="fas fa-edit"></i></button>
              <button class="hover:cursor-pointer bg-red-500 text-white p-2 rounded hover:bg-red-600 transition delete-btn" data-id="<?= $employee['employee_id']; ?>"><i class="fa fa-trash" aria-hidden="true"></i></button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php else: ?>
    <div class="mt-24 bg-white shadow-md rounded-md p-6 px-4 text-center text-gray-600">
      No employee found.
    </div>
  <?php endif; ?>
<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 hidden z-50 bg-gray-400/10 backdrop-blur-md bg-opacity-20 flex items-center justify-center">
  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-2xl relative max-h-screen overflow-y-auto">
    <button onclick="closeModal()" class="mt-3 absolute bg-red-500 w-8 h-8 rounded text-white top-2 right-2 hover:cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
    <h2 class="text-xl font-bold mb-4">Employee Update</h2>
    <form id="editemployeeForm">
    <input type="hidden" id="editemployeeId" name="id">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <div>
        <label for="fullName" class="block text-gray-700">Full Name</label>
        <input type="text" id="fullName" name="fullName" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter full name" value="">
      </div>
      <div>
        <label for="position" class="block text-gray-700">Position</label>
        <input type="text" id="position" name="position" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter position" value="">
      </div>
        <div>
        <label for="dob" class="block text-gray-700">Date Of Birth</label>
        <input type="date" id="dob" name="dob" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" value="">
      </div>
        <div>
          <label for="role" class="block text-gray-700">Role</label>
         <select id="role" name="role" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
              <option value="" selected disabled>Select Position</option>
            <option value="">Admin</option>
            <option value="manager">Manager</option>
            <option value="hr">HR</option>
            <option value="receptionist">Receptionist</option>
            <option value="cleaner">Cleaner</option>
            <option value="staff">Staff</option>
          </select>
        </div>
        <div>
          <label for="email" class="block text-gray-700">Email Address</label>
          <input type="email" id="email" name="email" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        </div>
          <div>
          <label for="password" class="block text-gray-700">Password</label>
          <input type="password" id="password" name="password" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter password">
        </div>
        <div>
          <label for="phoneNumber" class="block text-gray-700">Phone Number</label>
          <input type="text" id="phoneNumber" name="phoneNumber" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        </div>
        <div>
          <label for="hire_date" class="block text-gray-700">Date Hired</label>
          <input type="date" id="hiredDate" name="hiredDate" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        </div>
        <div>
        <label for="residentialAddress" class="block text-gray-700">Residential Address</label>
        <input type="text" id="residentialAddress" name="residentialAddress" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter residential address" value="">
      </div>
      <div>
        <label for="ghanaCardNumber" class="block text-gray-700">Ghana Card Number</label>
        <input type="text" id="ghanaCardNumber" name="ghanaCardNumber" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter ghana card number" value="">
      </div>
          <div>
          <label for="status" class="block text-gray-700">Active Status</label>
         <select id="status" name="status" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
            <option value="" selected disabled>Select Status</option>
            <option value="active">Active</option>
            <option value="on-leave">On Leave</option>
            <option value="sacked">Sacked</option>
            <option value="quit">Quit</option>
          </select>
        </div>
        <div>
          <label for="salary" class="block text-gray-700">Salary</label>
          <input type="number" id="salary" name="salary" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
        </div>
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
      const fullName = row.children[0].textContent.toLowerCase();
      const ghanaCardNumber = row.children[1].textContent.toLowerCase();
      row.style.display = fullName.includes(searchValue) || ghanaCardNumber.includes(searchValue) ? '' : 'none';
    });
  });

  window.closeModal = function () {
    document.getElementById('editModal').classList.add('hidden');
  }

  window.openEditModal = function (employee) {
  document.getElementById('editModal').classList.remove('hidden');
  document.getElementById('editemployeeId').value = employee.employee_id;
  document.getElementById('fullName').value = employee.full_name;
  document.getElementById('hiredDate').value = employee.date_hired;
  document.getElementById('email').value = employee.email;
  document.getElementById('role').value = employee.role;
  document.getElementById('dob').value = employee.dob;
  document.getElementById('password').value = employee.password;
  document.getElementById('status').value = employee.status;
  document.getElementById('phoneNumber').value = employee.phone;
  document.getElementById('residentialAddress').value = employee.residential_address;
  document.getElementById('position').value = employee.position;
  document.getElementById('salary').value = employee.salary;
  document.getElementById('ghanaCardNumber').value = employee.ghana_card_number;
}

  document.querySelectorAll('.edit-btn').forEach(button => {
  button.addEventListener('click', function () {
    const employee = JSON.parse(this.dataset.employee);
    window.openEditModal(employee); // ✅ this will now work
  });
});


  document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function () {
      const employeeId = this.dataset.id;
      const row = this.closest('tr');
      Swal.fire({
        title: 'Are you sure?',
        text: 'This employee will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e3342f',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!'
      }).then(result => {
        if (result.isConfirmed) {
          fetch('./functions/employee/delete-employee.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + encodeURIComponent(employeeId)
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              row.remove();
              Swal.fire('Deleted!', 'The employee has been deleted.', 'success');
            } else {
              Swal.fire('Error!', data.message || 'Failed to delete employee.', 'error');
            }
          })
          .catch(() => {
            Swal.fire('Error!', 'Something went wrong.', 'error');
          });
        }
      });
    });
  });

  document.getElementById('editemployeeForm').addEventListener('submit', async function (event) {
  event.preventDefault();
  const form = this;
  const formData = new FormData(form);

  try {
    const res = await fetch('./functions/employee/update-employee.php', {
      method: 'POST',
      body: formData
    });

    const result = await res.json();

    if (result.success) {
      Swal.fire({
        icon: 'success',
        title: 'Employee Updated',
        text: 'Employee updated successfully',
        timer: 2500,
        timerProgressBar: true
      }).then(() => {
        location.reload();
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        html: result.message || 'Failed to update employee.'
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
  win.document.write('<h3 style="text-align:center;margin:5px 0;">Consol Hotel - Employees List</h3>');
  win.document.write('<p style="text-align:center;font-size:12px;color:gray;">Printed on: ' + new Date().toLocaleString() + '</p>');
  win.document.write(printArea.innerHTML);
  win.document.write('</div><footer class="page-number"></footer></body></html>');
  win.document.close();
  win.focus();
  win.print();
  win.close();
}

//csv download
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
  link.setAttribute("download", "all-employees.csv");
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
});
</script>
</body>
</html>
