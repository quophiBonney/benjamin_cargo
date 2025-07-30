<?php
include_once 'includes/auth.php';
include_once 'includes/dbconnection.php';

// Restrict to admin or manager
$allowed_roles = ['admin', 'manager', 'hr'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($session_role, $allowed_roles)) {
    header("Location: staffs-dashboard.php");
    exit;
}

// Fetch expenses
$query = "SELECT ex.*, e.full_name 
FROM expenses ex
LEFT JOIN employees e ON ex.recorded_by = e.employee_id
ORDER BY ex.expense_id DESC;
";
$stmt = $dbh->prepare($query);
$stmt->execute();
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>
<?php include_once 'includes/sidebar.php'; ?>
<?php include_once 'includes/header.php'; ?>
<?php include_once 'includes/app-bar.php'; ?>

<main class="flex-1 md:ml-64 px-4 transition-all mt-24 md:mt-32">
  <div class="bg-white shadow-md rounded-md p-6 mt-24 md:mt-32">
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
 <div class="w-full md:col-span-2 flex flex-col md:flex-row gap-2 items-center">
  <div>
        <p>
          Showing <span id="startCount">1</span> to <span id="endCount">50</span> of
          <span id="totalCount">0</span> expenses
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
    <input type="search" id="searchInput" placeholder="Search by email or status..." 
           class="w-full p-2 bg-gray-100 border border-gray-300 rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
  </div>
</div>
<div class="w-full mt-5 md:mt-2">
  <button id="downloadCSV" class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white">Download CSV</button>
 <button onclick="printTable()" class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white hover:pointer-cursor">Print</button>
 <button onclick="confirmDeleteAll()" class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white">
  Delete All
</button>
</button>
</div>
    <?php if (count($expenses) > 0): ?>
    <div class="print-area overflow-x-auto mt-2">
      <div class="watermark" style="display:none;">CONSOL HOTEL</div>
      <table id="securityexpensesTable" class=" table-x-auto w-full text-sm text-left text-gray-700 border border-gray-200 rounded-lg overflow-hidden shadow-sm">
        <thead class="bg-gray-700 text-white uppercase text-xs font-semibold border-b border-gray-300">
          <tr>
            <th class="px-4 py-3 min-w-[160px]">Employee Name</th>
            <th class="px-4 py-3">Title</th>
            <th class="px-4 py-3">Description</th>
            <th class="px-4 py-3">Category</th>
            <th class="px-4 py-3">Amount</th>
            <th class="px-4 py-3 min-w-[170px]">Date Of Expense</th>
            <th class="px-4 py-3">Created At</th>
            <th class="px-4 py-3 no-print">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y text-sm text-gray-700">
          <?php foreach ($expenses as $expense): ?>
          <tr>
            <td class="px-4 py-2"><?= htmlspecialchars($expense['full_name']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($expense['title']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($expense['description']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($expense['category']) ?></td>
            <td class="px-4 py-2">₵<?= htmlspecialchars($expense['amount']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($expense['expense_date']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($expense['created_at']) ?></td>
            <td class="px-4 py-2 no-print">
             <button class="delete-expense-btn hover:cursor-pointer bg-red-500 text-white p-2 rounded hover:bg-red-600 transition delete-btn" data-id="<?= $expense['expense_id'] ?>"><i class="fa fa-trash" aria-hidden="true"></i></button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="text-center text-gray-500 mt-6">No expenses found.</div>
    <?php endif; ?>
  </div>
    </div>
</main>

<?php include_once 'includes/footer.php'; ?>
<script>
const allRows = [...document.querySelectorAll('#securityexpensesTable tbody tr')];
const paginationControls = document.getElementById('paginationControls');
const rowsPerPageSelect = document.getElementById('rowsPerPage');
let currentPage = 1;
let rowsPerPage = parseInt(rowsPerPageSelect.value);

function paginateRows() {
  const total = allRows.length;
  const totalPages = Math.ceil(total / rowsPerPage);
  const start = (currentPage - 1) * rowsPerPage;
  const end = Math.min(start + rowsPerPage, total);

  allRows.forEach((row, idx) => {
    row.style.display = idx >= start && idx < end ? '' : 'none';
  });

  document.getElementById('startCount').textContent = total === 0 ? 0 : start + 1;
  document.getElementById('endCount').textContent = end;
  document.getElementById('totalCount').textContent = total;

  paginationControls.innerHTML = '';
  for (let i = 1; i <= totalPages; i++) {
    const btn = document.createElement('button');
    btn.textContent = i;
    btn.className = `mx-1 px-3 py-1 rounded ${
      i === currentPage ? 'bg-gray-600 text-white' : 'bg-gray-200 text-gray-700'
    } hover:bg-gray-300 text-sm`;
    btn.onclick = () => { currentPage = i; paginateRows(); };
    paginationControls.appendChild(btn);
  }
}

rowsPerPageSelect.addEventListener('change', () => {
  rowsPerPage = parseInt(rowsPerPageSelect.value);
  currentPage = 1;
  paginateRows();
});

document.getElementById('searchInput').addEventListener('input', function () {
  const keyword = this.value.toLowerCase();
  allRows.forEach(row => {
    const email = row.children[1].textContent.toLowerCase();
    const status = row.children[3].textContent.toLowerCase();
    row.style.display = email.includes(keyword) || status.includes(keyword) ? '' : 'none';
  });
});

//delete single expense
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.delete-expense-btn').forEach(button => {
    button.addEventListener('click', function () {
      const expenseId = this.getAttribute('data-id');

      Swal.fire({
        title: 'Are you sure?',
        text: 'This expense will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e3342f',
        confirmButtonText: 'Yes, delete it'
      }).then(result => {
        if (result.isConfirmed) {
          fetch('./functions/expenses/delete-expense.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
              'Accept': 'application/json'
            },
            body: 'expense_id=' + encodeURIComponent(expenseId)
          })
          .then(res => res.json())
          .then(data => {
            Swal.fire(data.success ? 'Deleted!' : 'Error', data.message, data.success ? 'success' : 'error')
              .then(() => {
                if (data.success) location.reload();
              });
          })
          .catch(() => {
            Swal.fire('Error', 'Network or server error occurred.', 'error');
          });
        }
      });
    });
  });
});

document.addEventListener('DOMContentLoaded', () => {
  const tableBody = document.querySelector('#securityexpensesTable tbody');
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

document.getElementById('printTime').textContent = 'Printed on: ' + new Date().toLocaleString();
window.addEventListener('DOMContentLoaded', paginateRows);

function printTable() {
  const printContent = document.querySelector('.print-area');
  if (!printContent) {
    alert("No printable content found.");
    return;
  }

  const printWindow = window.open('', '', 'width=1200,height=800');

  const style = `
    <style>
      body {
        font-family: Arial, sans-serif;
        padding: 20px;
        font-size: 12px;
        color: #333;
      }
      h3 {
        text-align: center;
        margin-bottom: 5px;
      }
      .watermark {
        position: fixed;
        top: 45%;
        left: 50%;
        transform: translate(-50%, -50%);
        font-size: 80px;
        color: black;
        opacity: 0.05;
        z-index: 0;
      }
      table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
      }
      th, td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
        font-size: 12px;
      }
      tr:nth-child(even) {
        background-color: #f9fafb;
      }
      .no-print {
        display: none !important;
      }
      footer {
        position: fixed;
        bottom: 10px;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 10px;
        color: #999;
      }
    </style>
  `;

  printWindow.document.write(`
    <html>
      <head>
        <title>Print Expenses</title>
        ${style}
      </head>
      <body>
       
        <h3>Consol Hotel - All Expenses</h3>
        <p style="text-align:center;">Printed on: ${new Date().toLocaleString()}</p>
        <div class="watermark">CONSOL HOTEL</div>
        ${printContent.innerHTML}
        <footer>Page <span class="pageNumber"></span></footer>
      </body>
    </html>
  `);

  printWindow.document.close();
  printWindow.focus();

  // Wait for content to fully load
  printWindow.onload = function () {
    printWindow.print();
    printWindow.close();
  };
}

function confirmDeleteAll() {
  Swal.fire({
    title: 'Are you sure?',
    text: 'This will permanently delete all expenses!',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e3342f',
    confirmButtonText: 'Yes, delete all'
  }).then(result => {
    if (result.isConfirmed) {
      fetch('./functions/expenses/delete-all-expenses.php', {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Accept': 'application/json'
        }
      })
      .then(res => res.json())
      .then(data => {
        Swal.fire({
          icon: data.success ? 'success' : 'error',
          title: data.success ? 'Deleted!' : 'Error',
          text: data.message
        }).then(() => {
          if (data.success) {
            location.reload();
          }
        });
      })
      .catch(() => {
        Swal.fire('Error!', 'Network or server error occurred.', 'error');
      });
    }
  });
}

</script>
