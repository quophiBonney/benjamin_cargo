<?php 
include_once 'templates/auth.php';

$allowed_roles = ['admin', 'manager', 'hr'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($session_role, $allowed_roles)) {
    header("Location: staffs-dashboard.php");
    exit;
}

 include_once __DIR__ . '../../includes/dbconnection.php';
$query = "SELECT * FROM customers ORDER BY customer_id ASC";
$stmt = $dbh->prepare($query);
$stmt->execute();
$shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include_once 'templates/sidebar.php'; ?>
<?php include_once 'templates/header.php'; ?>
<?php include_once 'templates/app-bar.php';?>
<main class="flex-1 md:ml-64 px-4 transition-all">
  <input type="file" id="fileInput" accept=".csv, .xlsx" style="display: none;">

  <div class="bg-white shadow-md rounded-md p-6 mt-24 md:mt-32">
    <div class="w-full flex justify-between items-center flex-col lg:flex-row mt-4 mb-4">
      <div class="w-full mt-3 md:mt-0">
        <p>
          Showing <span id="startCount">1</span> to <span id="endCount">50</span> of
          <span id="totalCount">0</span> customers
        </p>
      </div>
      <div class="w-full flex items-center gap-2 mt-3 md:mt-0">
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
      <div class="mt-3 md:mt-0 w-full flex justify-center" id="paginationControls"></div>
    </div>

    <div class="w-full flex justify-between items-center flex-col lg:flex-row mb-4">
      <div class="w-full flex gap-2 mt-5 md:mt-2">
        <button class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white" id="importCSV">
          Import CSV
        </button>
        <button onclick="printTable()" class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white">
          Print
        </button>
      </div>
      <div class="w-full mt-5 md:mt-0">
        <input type="search" id="searchInput" placeholder="Search by name or number..."
               class="w-full p-2 bg-gray-100 border border-gray-300 rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
      </div>
    </div>

    <div class="print-area overflow-x-auto mt-6">
      <div class="watermark" style="display:none;">Benjamin Cargo Logistics</div>
      <table id="shippingManifestTable" class="table-x-auto w-full text-sm text-left text-gray-700 border border-gray-200 rounded-lg overflow-hidden shadow-sm">
        <thead class="bg-gray-700 text-white uppercase text-xs font-semibold border-b border-gray-300">
          <tr>
			  <th class="py-3 px-4 min-w-[160px]">Customer Name</th>
            <th class="py-3 px-4 min-w-[160px]">Phone Number</th>
            <th class="py-3 px-4 min-w-[130px]">Location</th>
            <th class="py-3 px-4 min-w-[120px]">Code</th>
             <th class="py-3 px-4">Sea</th>
                 <th class="py-3 px-4">Air</th>
                    <th class="py-3 px-4">OTP</th>
            <th class="py-3 px-4 no-print">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php if (empty($shipments)): ?>
            <tr>
              <td colspan="7" class="py-4 px-4 text-center text-gray-500">
                No customers found
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($shipments as $manifest): ?>
              <tr class="even:bg-gray-50 hover:bg-gray-100 transition duration-200">
                <td class="py-2 px-4"><?= htmlspecialchars($manifest['client_name']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($manifest['phone_number']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($manifest['location']) ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($manifest['code']) ?></td>
                  <td class="py-2 px-4"><?= htmlspecialchars($manifest['sea']) ?></td>
                    <td class="py-2 px-4"><?= htmlspecialchars($manifest['air']) ?></td>
                     <td class="py-2 px-4">
    <?php if ($manifest['otp_code'] == ''): ?>
        <span class="">-</span>
    <?php else: ?>
        <span class="">
            <?= htmlspecialchars($manifest['otp_code']) ?>
        </span>
    <?php endif; ?>
</td>
                <td class="py-2 flex gap-1 px-4 space-x-1 no-print">
                  <!-- Edit Button -->
                  <button class="bg-gray-500 text-white p-2 rounded hover:bg-gray-600 hover:cursor-pointer transition edit-btn"
                          data-manifest='<?= json_encode($manifest, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                    <i class="fas fa-edit"></i>
                  </button>

                  <!-- Delete Button -->
                  <button class="hover:cursor-pointer bg-red-500 text-white p-2 rounded hover:bg-red-600 transition delete-btn"
                          data-id="<?= $manifest['customer_id']; ?>">
                    <i class="fa fa-trash" aria-hidden="true"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Edit Modal -->
  <div id="editModal" class="fixed inset-0 hidden z-50 bg-gray-400/10 backdrop-blur-md bg-opacity-20 flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-2xl relative max-h-screen overflow-y-auto">
      <button onclick="closeModal()" class="mt-3 absolute bg-red-500 w-8 h-8 rounded text-white top-2 right-2 hover:cursor-pointer">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <h2 class="text-xl font-bold mb-4">Customer Update</h2>
      <form id="updateCustomerForm" method="post">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-5">
          <div>
            <label for="sn" class="block text-gray-700">SN</label>
            <input type="number" id="sn" name="sn" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="1" min="1">
          </div>

          <div>
            <label for="customerName" class="block text-gray-700">Customer Name</label>
            <input type="text" id="customerName" name="clientName" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="John Doe">
          </div>
          <div>
            <label for="phoneNumber" class="block text-gray-700">Phone Number</label>
            <input type="text" id="phoneNumber" name="phoneNumber" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="02XXXXXXX">
          </div>

          <div>
            <label for="location" class="block text-gray-700">Location</label>
            <input type="text" id="location" name="location" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Accra">
          </div>

          <div>
            <label for="code" class="block text-gray-700">Code</label>
            <input type="text" id="code" name="code" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="BCL 001">
          </div>
            <div>
            <label for="code" class="block text-gray-700">Sea</label>
            <input type="text" id="sea" name="sea" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Yes">
          </div>
            <div>
            <label for="code" class="block text-gray-700">Air</label>
            <input type="text" id="air" name="air" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="No">
          </div>
        </div>
        <div class="mt-3 md:mt-5">
          <button id="submitBtn" class="bg-gray-600 text-white px-8 py-3 rounded hover:bg-gray-700 w-full">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</main>
<?php include_once 'templates/footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  // ----- Element cache -----
  const table = document.getElementById('shippingManifestTable');
  if (!table) return;
  const tbody = table.querySelector('tbody');
  const paginationControls = document.getElementById('paginationControls');
  const rowsPerPageSelect = document.getElementById('rowsPerPage');
  const searchInput = document.getElementById('searchInput');
  const startCount = document.getElementById('startCount');
  const endCount = document.getElementById('endCount');
  const totalCount = document.getElementById('totalCount');
  const importBtn = document.getElementById('importCSV');
  const fileInput = document.getElementById('fileInput');
  const downloadCSVBtn = document.getElementById('downloadCSV'); // optional
  const editModal = document.getElementById('editModal');
  const updateForm = document.getElementById('updateCustomerForm');

  // ----- Build row store -----
  const originalRows = Array.from(tbody.querySelectorAll('tr')).map(tr => {
    const deleteBtn = tr.querySelector('.delete-btn');
    const id = deleteBtn ? deleteBtn.dataset.id : null;
    return { tr, id, text: tr.textContent.toLowerCase().trim() };
  });

  // State
  let filteredRows = [...originalRows];
  let currentPage = 1;
  let rowsPerPage = parseInt(rowsPerPageSelect?.value || '50', 10);

  // ----- Utilities -----
  function clearTbody() {
    while (tbody.firstChild) tbody.removeChild(tbody.firstChild);
  }

  function renderTable() {
    clearTbody();
    const total = filteredRows.length;
    const totalPages = Math.max(1, Math.ceil(total / rowsPerPage));
    if (currentPage > totalPages) currentPage = totalPages;

    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = Math.min(startIndex + rowsPerPage, total);

    for (let i = startIndex; i < endIndex; i++) {
      tbody.appendChild(filteredRows[i].tr);
    }

    startCount.textContent = total === 0 ? 0 : startIndex + 1;
    endCount.textContent = endIndex;
    totalCount.textContent = total;

    renderPagination(totalPages);
  }

  function renderPagination(totalPages) {
    paginationControls.innerHTML = '';
    if (totalPages <= 1) return;

    const createBtn = (label, disabled, cls = '') => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = `px-3 py-1 border rounded hover:bg-gray-200 disabled:opacity-50 ${cls}`;
      b.textContent = label;
      b.disabled = !!disabled;
      return b;
    };

    const prev = createBtn('Prev', currentPage === 1);
    prev.addEventListener('click', () => { currentPage = Math.max(1, currentPage - 1); renderTable(); });
    paginationControls.appendChild(prev);

    const maxButtons = 7;
    let start = Math.max(1, currentPage - Math.floor(maxButtons / 2));
    let finish = start + maxButtons - 1;
    if (finish > totalPages) { finish = totalPages; start = Math.max(1, finish - maxButtons + 1); }

    if (start > 1) {
      const b = createBtn('1', false, '');
      b.addEventListener('click', () => { currentPage = 1; renderTable(); });
      paginationControls.appendChild(b);
      if (start > 2) {
        const dots = document.createElement('span'); dots.className = 'px-2'; dots.textContent = '...';
        paginationControls.appendChild(dots);
      }
    }

    for (let p = start; p <= finish; p++) {
      const isActive = p === currentPage;
      const btn = createBtn(p, false, isActive ? 'bg-gray-300 font-bold' : '');
      btn.addEventListener('click', () => { currentPage = p; renderTable(); });
      paginationControls.appendChild(btn);
    }

    if (finish < totalPages) {
      if (finish < totalPages - 1) {
        const dots = document.createElement('span'); dots.className = 'px-2'; dots.textContent = '...';
        paginationControls.appendChild(dots);
      }
      const b = createBtn(totalPages, false, '');
      b.addEventListener('click', () => { currentPage = totalPages; renderTable(); });
      paginationControls.appendChild(b);
    }

    const next = createBtn('Next', currentPage === totalPages);
    next.addEventListener('click', () => { currentPage = Math.min(totalPages, currentPage + 1); renderTable(); });
    paginationControls.appendChild(next);
  }

  function debounce(fn, wait = 200) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), wait);
    };
  }

  function applySearch() {
    const q = (searchInput.value || '').toLowerCase().trim();
    if (!q) {
      filteredRows = [...originalRows];
    } else {
      filteredRows = originalRows.filter(r => r.text.includes(q));
    }
    currentPage = 1;
    renderTable();
  }

  searchInput.addEventListener('input', debounce(applySearch, 180));
  rowsPerPageSelect.addEventListener('change', () => {
    rowsPerPage = parseInt(rowsPerPageSelect.value || '50', 10);
    currentPage = 1;
    renderTable();
  });

  // ----- Table actions -----
  table.addEventListener('click', async (ev) => {
    const editBtn = ev.target.closest('.edit-btn');
    if (editBtn) {
      try {
        const raw = editBtn.dataset.manifest;
        const manifest = JSON.parse(raw);
        openEditModal(manifest);
      } catch (err) {
        console.error('Failed to parse manifest JSON', err);
        Swal.fire('Error', 'Could not load customer details for editing.', 'error');
      }
      return;
    }

    const deleteBtn = ev.target.closest('.delete-btn');
    if (deleteBtn) {
      const customerID = deleteBtn.dataset.id;
      if (!customerID) return;
      const rowEl = deleteBtn.closest('tr');

      Swal.fire({
        title: 'Are you sure?',
        text: 'This customer will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e3342f',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!'
      }).then(async (result) => {
        if (!result.isConfirmed) return;
        try {
          const res = await fetch('./functions/customers/delete-customer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + encodeURIComponent(customerID)
          });
          const json = await res.json();
          if (json.success) {
            const idx = originalRows.findIndex(r => r.id === customerID);
            if (idx !== -1) originalRows.splice(idx, 1);
            const fidx = filteredRows.findIndex(r => r.id === customerID);
            if (fidx !== -1) filteredRows.splice(fidx, 1);

            if (rowEl) rowEl.remove();

            Swal.fire('Deleted!', 'The customer has been deleted.', 'success');
            currentPage = 1;
            renderTable();
          } else {
            Swal.fire('Error!', json.message || 'Failed to delete customer.', 'error');
          }
        } catch (err) {
          console.error(err);
          Swal.fire('Error!', 'Something went wrong.', 'error');
        }
      });

      return;
    }
  });

  // ----- Edit modal helpers -----
  window.closeModal = () => {
    if (editModal) editModal.classList.add('hidden');
  };

  function ensureHiddenIdField(form, idValue) {
    let hidden = form.querySelector('input[name="id"], input#customer_id');
    if (!hidden) {
      hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'id';
      hidden.id = 'customer_id';
      form.appendChild(hidden);
    }
    hidden.value = idValue;
  }

  window.openEditModal = (customer = {}) => {
    if (!editModal) return;

    editModal.classList.remove('hidden');
    document.getElementById('sn').value = customer.sn || '';
    document.getElementById('customerName').value = customer.client_name || '';
    document.getElementById('location').value = customer.location || '';
    document.getElementById('phoneNumber').value = customer.phone_number || '';
    document.getElementById('code').value = customer.code || '';
       document.getElementById('sea').value = customer.sea || '';
          document.getElementById('air').value = customer.air || '';
    // ensure we pass the correct primary key to PHP update endpoint
    const idToSet = customer.customer_id || customer.id || customer.customerID || '';
    ensureHiddenIdField(updateForm, idToSet);

    const updateSubmitBtn = document.getElementById('submitBtn');
    if (updateSubmitBtn) updateSubmitBtn.textContent = 'Update Customer';
  };

  // ----- Submit handler (outside table listener to avoid duplicates) -----
  if (updateForm) {
    updateForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(updateForm);

      try {
        const res = await fetch('./functions/customers/update-customer.php', {
          method: 'POST',
          body: formData,
          credentials: 'include'
        });

        const result = await res.json();

        if (result.success) {
          Swal.fire({
            icon: 'success',
            text: 'Customer updated successfully',
            timer: 2500,
            timerProgressBar: true
          }).then(() => {
            location.reload();
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: result.message || 'Failed to update customer.'
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
  }

  // ----- Print logic -----
  window.printTable = function () {
    const printArea = document.querySelector('.print-area');
    if (!printArea) {
      Swal.fire('Error', 'Nothing to print', 'error');
      return;
    }

    const styles = `
      <style>
        @media print {
          body { font-family: Arial, Helvetica, sans-serif; color: #111827; }
          .no-print { display: none !important; }
          .print-area { padding: 30px; }
          table { width: 100%; border-collapse: collapse; font-size: 13px; }
          th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
          tr:nth-child(even) { background-color: #f9fafb; }
          .watermark { position: fixed; top: 45%; left: 50%; transform: translate(-50%, -50%); font-size: 80px; color: black; opacity: 0.05; pointer-events: none; z-index: 0; }
          footer { position: fixed; bottom: 0; text-align: center; font-size: 12px; color: #9ca3af; width: 100%; }
        }
      </style>
    `;

    const win = window.open('', '', 'height=800,width=1200');
    win.document.write('<html><head><title>Print</title>' + styles + '</head><body>');
    win.document.write('<div class="print-area">');
    win.document.write('<div class="watermark">Benjamin Cargo Logistics</div>');
    win.document.write('<img src="your-logo.png" style="height:60px;margin-bottom:10px;">');
    win.document.write('<h3 style="text-align:center;margin:5px 0;">Benjamin Cargo Logistics - Customers</h3>');
    win.document.write('<p style="text-align:center;font-size:12px;color:gray;">Printed on: ' + new Date().toLocaleString() + '</p>');

    const clone = printArea.cloneNode(true);
    clone.querySelectorAll('.no-print, button, a').forEach(n => n.remove());
    win.document.write(clone.innerHTML);
    win.document.write('</div><footer>Page</footer></body></html>');
    win.document.close();
    win.focus();
    win.print();
    win.close();
  };

  // ----- CSV Download (optional button) -----
  if (downloadCSVBtn) {
    downloadCSVBtn.addEventListener('click', () => {
      const tableEl = document.getElementById('shippingManifestTable');
      if (!tableEl) return Swal.fire('Error', 'Table not found', 'error');

      const rows = tableEl.querySelectorAll('thead tr, tbody tr');
      let csvContent = '';

      const headerCols = rows[0].querySelectorAll('th');
      const actionIndexes = [];
      headerCols.forEach((th, i) => {
        const text = th.textContent.trim().toLowerCase();
        if (text === 'action' || text.includes('actions')) actionIndexes.push(i);
      });

      rows.forEach(row => {
        const cols = row.querySelectorAll('th, td');
        const rowData = Array.from(cols).map((col, i) => {
          if (actionIndexes.includes(i)) return null;
          let text = col.textContent.trim().replace(/"/g, '""');
          if (/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/.test(text)) {
            const d = new Date(text);
            if (!isNaN(d)) text = d.toLocaleString();
          }
          return `"${text}"`;
        }).filter(Boolean);
        csvContent += rowData.join(',') + "\n";
      });

      const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement("a");
      link.setAttribute("href", URL.createObjectURL(blob));
      link.setAttribute("download", "customers.csv");
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    });
  }

  // ----- Import CSV -----
  if (importBtn && fileInput) {
    importBtn.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', () => {
      const file = fileInput.files[0];
      if (!file) return;
      const formData = new FormData();
      formData.append('file', file);

      Swal.fire({
        title: 'Importing...',
        text: 'Please wait while the file is uploaded.',
        didOpen: () => Swal.showLoading()
      });

      fetch('./functions/customers/import-customers.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.text())
      .then(txt => {
        Swal.close();
        Swal.fire('Import Result', txt, 'info').then(() => location.reload());
      })
      .catch(err => {
        Swal.close();
        console.error(err);
        Swal.fire('Error', 'Import failed', 'error');
      });
    });
  }

  // ----- Initialize -----
  renderTable();
  window.safeReload = () => location.reload();
});
</script>
</body>
</html>
