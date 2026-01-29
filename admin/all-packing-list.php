<?php
include_once 'templates/auth.php';
// Restrict access by role
$allowed_roles = ['admin', 'manager', 'hr'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($session_role, $allowed_roles)) {
    header("Location: login.php");
    exit;
}

// Adjusted include path to reliably reach the includes folder from this file
include_once __DIR__ . '../../includes/dbconnection.php';

// Fetch shipments
$query = "SELECT * FROM shipping_manifest ORDER BY id ASC";
$stmt = $dbh->prepare($query);
$stmt->execute();
$shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include_once 'templates/sidebar.php'; ?>
<?php include_once 'templates/header.php'; ?>
<?php include_once 'templates/app-bar.php'; ?>

<main class="flex-1 md:ml-64 px-4 transition-all">
  <!-- Hidden file input for CSV import -->
  <input type="file" id="fileInput" accept=".csv, .xlsx" style="display: none;">

  <!-- Table Controls -->
  <div class="bg-white shadow-md rounded-md p-6 mt-24 md:mt-32">
    <div class="w-full flex justify-between items-center flex-col lg:flex-row mt-4 mb-4">
      <div class="w-full mt-3 md:mt-0">
        <p>
          Showing <span id="startCount">1</span> to <span id="endCount">0</span> of
          <span id="totalCount">0</span> shipments
        </p>
      </div>
      <div class="w-full flex items-center gap-2 mt-3 md:mt-0">
        <label for="rowsPerPage">Rows per page:</label>
        <select id="rowsPerPage" class="border border-gray-200 rounded bg-white text-black px-2 py-1">
          <option value="50" selected>50</option>
          <option value="100">100</option>
          <option value="200">200</option>
          <option value="400">400</option>
          <option value="500">500</option>
          <option value="1000">1000</option>
        </select>
      </div>
      <div class="mt-3 md:mt-0 w-full flex justify-center" id="paginationControls"></div>
    </div>

    <!-- Action Buttons -->
    <div class="w-full flex justify-between items-center flex-col lg:flex-row mb-4">
      <div class="w-full flex gap-2 mt-5 md:mt-2">
        <button id="importCSV" class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white">
          Import CSV
        </button>
        <button id="openUpdateHistory" class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white">
          Update History
        </button>
         <button id="openFilterCalendar" class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white">
          Filter
        </button>
        <button onclick="printTable()" class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white">
          Print
        </button>
        <button onclick="window.location.reload()" class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white">
          Reset
        </button>
      </div>
      <div class="w-full mt-5 md:mt-0">
        <input type="search" id="searchInput" placeholder="Search by name or number..."
          class="w-full p-2 bg-gray-100 border border-gray-300 rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
      </div>
    </div>

    <!-- Shipments Table -->
    <div class="print-area overflow-x-auto mt-6">
      <div class="watermark" style="display:none;">Benjamin Cargo Logistics</div>
      <table id="shippingManifestTable" class="w-full text-sm text-left text-gray-700 border border-gray-200 rounded-lg overflow-hidden shadow-sm">
        <thead class="bg-gray-700 text-white uppercase text-xs font-semibold border-b border-gray-300">
          <tr>
            <th class="py-3 px-4 min-w-[120px]">Code</th>
            <th class="py-3 px-4 min-w-[120px]">Receipt No.</th>
            <th class="py-3 px-4 min-w-[160px]">Entry Date</th>
            <th class="py-3 px-4 min-w-[180px]">Package Name</th>
            <th class="py-3 px-4">Pieces</th>
            <th class="py-3 px-4">CBM</th>
            <th class="py-3 px-4 min-w-[130px]">Loading Date</th>
             <th class="py-3 px-4 min-w-[130px]">ETA</th>
            <th class="py-3 px-4 min-w-[180px]">Status</th>
            <th class="no-print py-3 px-4">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php if(empty($shipments)): ?>
            <tr>
              <td colspan="8" class="py-4 px-4 text-center text-gray-500">No packing list found</td>
            </tr>
          <?php else: ?>
            <?php foreach ($shipments as $manifest): ?>
              <tr class="even:bg-gray-50 hover:bg-gray-100 transition duration-200">
                <td class="py-2 px-4"><?= htmlspecialchars($manifest['shipping_mark'] ?? '') ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($manifest['receipt_number'] ?? '') ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($manifest['entry_date'] ?? '') ?></td>
                <td class="py-2 px-4 capitalize"><?= htmlspecialchars($manifest['package_name'] ?? '') ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($manifest['number_of_pieces'] ?? '') ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($manifest['volume_cbm'] ?? '') ?></td>
                <td class="py-2 px-4"><?= htmlspecialchars($manifest['loading_date'] ?? '') ?></td>
                 <td class="py-2 px-4"><?= htmlspecialchars($manifest['estimated_time_of_arrival'] ?? '') ?></td>
                 <td class="py-2 px-4">
    <?php if ($manifest['status'] == "shipments received"): ?>
        <span class="text-gray-500 font-bold capitalize">
            <?= htmlspecialchars($manifest['status'] ?? '') ?>
        </span>
    <?php elseif ($manifest['status'] == "shipments in transit"): ?>
        <span class="text-blue-800 font-bold capitalize">
            <?= htmlspecialchars($manifest['status'] ?? '') ?>
        </span>
    <?php elseif ($manifest['status'] == "shipments has arrived at the Port undergoing clearance"): ?>
        <span class="text-orange-500 animate-bounce font-bold capitalize">
            <?= htmlspecialchars($manifest['status'] ?? '') ?>
        </span>
    <?php elseif ($manifest['status'] == "shipments arrived at Benjamin Cargo Logistics Warehouse"): ?>
        <span class="text-blue-500 font-bold capitalize">
            <?= htmlspecialchars($manifest['status'] ?? '') ?>
        </span>
    <?php elseif ($manifest['status'] == "shipments picked up"): ?>
        <span class="text-green-500 font-bold capitalize">
            <?= htmlspecialchars($manifest['status'] ?? '') ?>
        </span>
    <?php endif; ?>
</td>

                <td class="no-print flex gap-1 py-2 px-4">
                <a class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" 
   href="fpdf/shipment-invoice.php?customer_id=<?= htmlspecialchars($manifest['customer_id']) ?>" target="_blank">
   <i class="fa-solid fa-file-invoice"></i>
</a>
                  <button class="bg-gray-500 text-white p-2 rounded hover:bg-gray-600 hover:cursor-pointer transition edit-btn" data-manifest='<?= htmlspecialchars(json_encode($manifest), ENT_QUOTES) ?>'><i class="fas fa-edit"></i></button>
                  <button class="hover:cursor-pointer bg-red-500 text-white p-2 rounded hover:bg-red-600 transition delete-btn" data-id="<?= htmlspecialchars($manifest['id'] ?? '') ?>"><i class="fa fa-trash" aria-hidden="true"></i></button>
            </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Update History Modal -->
  <div id="updateTrackingHistoryModal" class="fixed inset-0 hidden z-50 bg-gray-400/10 backdrop-blur-md flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-2xl relative max-h-screen overflow-y-auto">
      <button onclick="closeTrackModal()" class="absolute bg-red-500 w-8 h-8 rounded text-white top-2 right-2 hover:cursor-pointer">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <h2 class="text-xl font-bold mb-4">Update History</h2>
      <form id="editTrackingHistoryForm">
        <div class="mb-4">
          <label for="tracking_status" class="block text-gray-700">Tracking Status</label>
          <select id="tracking_status" name="status" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
            <option value="shipments received">Received</option>
            <option value="shipments in transit">In Transit</option>
            <option value="shipments has arrived at the Port undergoing clearance">At Port</option>
            <option value="shipments arrived at Benjamin Cargo Logistics Warehouse">At Benjamin Cargo</option>
            <option value="shipments picked up">Picked Up</option>
          </select>
        </div>
        <div class="mb-4">
          <label for="dateSelected" class="block text-gray-700">Date</label>
          <input type="date" id="dateSelected" name="dateSelected" class="bg-gray-100 w-full p-2 border border-gray-300 rounded"/>
        </div>
        <div class="mb-4">
          <label for="trackingMessage" class="block text-gray-700">Tracking Message</label>
          <textarea id="trackingMessage" name="trackingMessage" rows="4" placeholder="Description"
            class="bg-gray-100 w-full p-2 border border-gray-300 rounded"></textarea>
        </div>
        <div class="mt-5">
          <button type="submit" id="trackingSubmitBtn" class="bg-gray-800 text-white px-6 py-2 rounded hover:bg-gray-700">Save History</button>
        </div>
      </form>
    </div>
  </div>

    <!-- Filter Modal -->
  <div id="filterCalendar" class="fixed inset-0 hidden z-50 bg-gray-400/10 backdrop-blur-md flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-2xl relative max-h-screen overflow-y-auto">
      <button onclick="closeFilterModal()" class="absolute bg-red-500 w-8 h-8 rounded text-white top-2 right-2 hover:cursor-pointer">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <h2 class="text-xl font-bold mb-4">Filter Record</h2>
      <form id="filteringForm">
        <div class="mb-4">
          <label for="fromDate" class="block text-gray-700">From</label>
          <input type="date" id="fromDate" name="fromDate" class="bg-gray-100 w-full p-2 border border-gray-300 rounded"/>
        </div>
        <div class="mb-4">
          <label for="toDate" class="block text-gray-700">To</label>
          <input type="date" id="toDate" name="toDate" class="bg-gray-100 w-full p-2 border border-gray-300 rounded"/>
        </div>
        <div class="mt-5">
          <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded hover:bg-gray-700">Filter</button>
        </div>
      </form>
    </div>
  </div>

   <div id="updatePackingList" class="fixed inset-0 hidden z-50 bg-gray-400/10 backdrop-blur-md flex items-center justify-center">
    <div class="mx-3 bg-white p-6 rounded-lg shadow-lg w-full max-w-4xl relative max-h-screen overflow-y-auto">
      <button onclick="closePackingListModal()" class="absolute bg-red-500 w-8 h-8 rounded text-white top-2 right-2 hover:cursor-pointer">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <h2 class="text-xl font-bold mb-4">Packing List Update</h2>
      <form id="packingListForm">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-5 mb-3">
               <div class="">
 <label for="receiptNumber" class="block text-gray-700">Receipt Number</label>
   <input type="numbr" id="receipt_number" name="receipt_number" class="bg-gray-100 w-full p-2 border border-gray-300 rounded"/>
            </div>
          <div class="">
 <label for="shippingMark" class="block text-gray-700">Shipping Mark</label>
   <input type="text" id="shippingMark" name="shippingMark" class="bg-gray-100 w-full p-2 border border-gray-300 rounded"/>
            </div>
             <div class="">
 <label for="entryDate" class="block text-gray-700">Entry Date</label>
   <input type="date" id="entryDate" name="entryDate" class="bg-gray-100 w-full p-2 border border-gray-300 rounded"/>
            </div>
            <div class="">
 <label for="loadingDate" class="block text-gray-700">Loading Date</label>
   <input type="date" id="loadingDate" name="loadingDate" class="bg-gray-100 w-full p-2 border border-gray-300 rounded"/>
            </div>
            <div class="">
 <label for="eta" class="block text-gray-700">Departure Date</label>
   <input type="date" id="departure_date" name="departure_date" class="bg-gray-100 w-full p-2 border border-gray-300 rounded"/>
            </div>
             <div class="">
 <label for="eta" class="block text-gray-700">ETA</label>
   <input type="date" id="eta" name="eta" class="bg-gray-100 w-full p-2 border border-gray-300 rounded"/>
            </div>
            <div class="">
 <label for="eta" class="block text-gray-700">ETO</label>
   <input type="date" id="eto" name="eto" class="bg-gray-100 w-full p-2 border border-gray-300 rounded"/>
            </div>
             <div class="">
 <label for="pieces" class="block text-gray-700">Quantity(Pieces)</label>
   <input type="text" id="pieces" name="pieces" class="bg-gray-100 w-full p-2 border border-gray-300 rounded"/>
            </div>
             <div class="">
 <label for="cbm" class="block text-gray-700">CBM</label>
   <input type="text" id="cbm" name="cbm" class="bg-gray-100 w-full p-2 border border-gray-300 rounded"/>
            </div>
         <div class="">
          <label for="packing_status" class="block text-gray-700">Status</label>
          <select id="packing_status" name="status" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
            <option value="shipments received">Received</option>
            <option value="shipments in transit">In Transit</option>
            <option value="shipments has arrived at the Port undergoing clearance">At Port</option>
            <option value="shipments arrived at Benjamin Cargo Logistics Warehouse">At Benjamin Cargo</option>
            <option value="shipments picked up">Picked Up</option>
          </select>
        </div>
          <div class="w-full">
 <label for="packageName" class="block text-gray-700">Supplier No.</label>
   <input type="text" id="supplier_number" name="	supplier_number" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placehoder="Supplier No."/>
            </div>
            <div class="">
 <label for="expressTrackingNumber" class="block text-gray-700">Express Tracking Number</label>
   <input type="number" id="expressTrackingNumber" name="expressTrackingNumber" class="bg-gray-100 w-full p-2 border border-gray-300 rounded"/>
            </div>
             <div class="w-full">
 <label for="note" class="block text-gray-700">Note</label>
   <input type="text" id="note" name="note" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Note"/>
    </div>
              </div>
              <div class="w-full">
 <label for="packageName" class="block text-gray-700">Package Name</label>
   <textarea type="text" id="packageName" name="packageName" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Package Name"></textarea>
            </div>
        <div class="mt-5">
          <button type="submit" id="packingSubmitBtn" class="bg-gray-800 text-white px-6 py-3 rounded hover:bg-gray-700 w-full">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</main>

<?php include_once 'templates/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const table = document.getElementById('shippingManifestTable');
  const tbody = table.querySelector('tbody');
  const paginationControls = document.getElementById('paginationControls');
  const rowsPerPageSelect = document.getElementById('rowsPerPage');
  const searchInput = document.getElementById('searchInput');
  const startCount = document.getElementById('startCount');
  const endCount = document.getElementById('endCount');
  const totalCount = document.getElementById('totalCount');
  const importBtn = document.getElementById('importCSV');
  const fileInput = document.getElementById('fileInput');
  const updateHistoryBtn = document.getElementById('openUpdateHistory');
  const filterBtn = document.getElementById('openFilterCalendar');
  const trackingModal = document.getElementById('updateTrackingHistoryModal');
  const filterModal = document.getElementById('filterCalendar');
  const packingListModal = document.getElementById('updatePackingList');

  // Store original rows (keep node references and a lowercase text snapshot for searching)
  const originalRows = Array.from(tbody.querySelectorAll('tr')).map(tr => ({
    tr,
    text: tr.textContent.toLowerCase().trim()
  }));

  let filteredRows = [...originalRows];
  let currentPage = 1;
  let rowsPerPage = parseInt(rowsPerPageSelect.value || '50', 10);

  function renderTable() {
    tbody.innerHTML = '';
    const total = filteredRows.length;
    const totalPages = Math.max(1, Math.ceil(total / rowsPerPage));
    if (currentPage > totalPages) currentPage = totalPages;

    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = Math.min(startIndex + rowsPerPage, total);

    for (let i = startIndex; i < endIndex; i++) {
      tbody.appendChild(filteredRows[i].tr);
    }

    startCount.textContent = total === 0 ? 0 : (total === 0 ? 0 : startIndex + 1);
    endCount.textContent = total === 0 ? 0 : endIndex;
    totalCount.textContent = total;

    renderPagination(totalPages);
  }

  function renderPagination(totalPages) {
    paginationControls.innerHTML = '';
    if (totalPages <= 1) return;

    const createBtn = (label, disabled, isActive = false) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.textContent = label;
      b.className = `px-3 py-1 border rounded hover:bg-gray-200 ${isActive ? 'bg-gray-300 font-bold' : ''}`;
      b.disabled = disabled;
      return b;
    };

    const prev = createBtn('Prev', currentPage === 1);
    prev.onclick = () => { currentPage--; renderTable(); };
    paginationControls.appendChild(prev);

    // show pages (for large counts you might want to limit rendered buttons)
    for (let p = 1; p <= totalPages; p++) {
      const btn = createBtn(p, false, p === currentPage);
      btn.onclick = () => { currentPage = p; renderTable(); };
      paginationControls.appendChild(btn);
    }

    const next = createBtn('Next', currentPage === totalPages);
    next.onclick = () => { currentPage++; renderTable(); };
    paginationControls.appendChild(next);
  }

  // Initial render
  renderTable();

  // Search
  searchInput.addEventListener('input', () => {
    const q = searchInput.value.toLowerCase().trim();
    filteredRows = q ? originalRows.filter(r => r.text.includes(q)) : [...originalRows];
    currentPage = 1;
    renderTable();
  });

  rowsPerPageSelect.addEventListener('change', () => {
    rowsPerPage = parseInt(rowsPerPageSelect.value, 10);
    currentPage = 1;
    renderTable();
  });

  //filtering
  // Inside document.addEventListener('DOMContentLoaded', () => { ... });

// Filtering logic
const filterForm = document.getElementById('filteringForm');
if (filterForm) {
  filterForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const fromDate = document.getElementById('fromDate').value;
    const toDate = document.getElementById('toDate').value;

    if (!fromDate && !toDate) {
      // No filter → show all
      filteredRows = [...originalRows];
    } else {
      filteredRows = originalRows.filter(r => {
        // Check Entry Date (3rd column)
        const entryDateCell = r.tr.querySelector('td:nth-child(3)');
        if (!entryDateCell) return false;

        const entryDateText = entryDateCell.textContent.trim();
        if (!entryDateText) return false;

        const entryDate = new Date(entryDateText);
        if (isNaN(entryDate)) return false;

        // Range checks
        if (fromDate && entryDate < new Date(fromDate)) return false;
        if (toDate && entryDate > new Date(toDate)) return false;

        return true;
      });
    }

    currentPage = 1;
    renderTable();
    closeFilterModal(); // Hide modal
  });
}

  // Edit buttons (each row's edit button holds the manifest payload)
  function attachEditButtons() {
    document.querySelectorAll('.edit-btn').forEach(button => {
      // avoid adding multiple listeners
      if (button._hasListener) return;
      button._hasListener = true;

      button.addEventListener('click', function () {
        const manifest = JSON.parse(this.dataset.manifest || '{}');

        // Fill modal fields (packing list modal)
        document.getElementById('receipt_number').value = manifest.receipt_number || '';
        document.getElementById('shippingMark').value = manifest.shipping_mark || '';
           document.getElementById('expressTrackingNumber').value = manifest.express_tracking_no || '';
        document.getElementById('entryDate').value = manifest.entry_date || '';
        document.getElementById('packageName').value = manifest.package_name || '';
        document.getElementById('eta').value = manifest.estimated_time_of_arrival || '';
         document.getElementById('eto').value = manifest.estimated_time_of_offloading || '';
         document.getElementById('loadingDate').value = manifest.loading_date || '';
         document.getElementById('departure_date').value = manifest.departure_date || '';
        document.getElementById('pieces').value = manifest.number_of_pieces || '';
        document.getElementById('cbm').value = manifest.volume_cbm || '';
        document.getElementById('packing_status').value = manifest.status || '';
            document.getElementById('supplier_number').value = manifest.supplier_number || '';
        
            document.getElementById('note').value = manifest.note || '';

        // store the id on the form so the update endpoint knows which record to update
        document.getElementById('packingListForm').dataset.manifestId = manifest.id || '';

        // Show modal
        if (packingListModal) packingListModal.classList.remove('hidden');
      });
    });
  }

  attachEditButtons();

  window.closePackingListModal = () => packingListModal && packingListModal.classList.add('hidden');
  window.closeTrackModal = () => trackingModal && trackingModal.classList.add('hidden');
  window.closeFilterModal = () => filterModal && filterModal.classList.add('hidden');

  // Import CSV
  if (importBtn && fileInput) {
    importBtn.onclick = () => fileInput.click();
    fileInput.onchange = () => {
      const file = fileInput.files[0];
      if (!file) return;
      const formData = new FormData();
      formData.append('file', file);

      Swal.fire({ title: 'Importing...', didOpen: () => Swal.showLoading() });

      fetch('./functions/shipment/import-shipping-manifest.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.text())
      .then(txt => {
        Swal.close();
        Swal.fire('Import Result', txt, 'info').then(() => location.reload());
      })
      .catch(() => {
        Swal.close();
        Swal.fire('Error', 'Import failed', 'error');
      });
    };
  }

  // Update History Modal open
  if (updateHistoryBtn) {
    updateHistoryBtn.addEventListener('click', () => {
      if (trackingModal) trackingModal.classList.remove('hidden');
    });
  }

  // Filter modal open
  if (filterBtn) {
    filterBtn.addEventListener('click', () => {
      if (filterModal) filterModal.classList.remove('hidden');
    });
  }

  // Close modals when clicking outside
  window.addEventListener('click', (e) => {
    if (e.target === trackingModal) trackingModal.classList.add('hidden');
    if (e.target === filterModal) filterModal.classList.add('hidden');
    if (e.target === packingListModal) packingListModal.classList.add('hidden');
  });

  // Tracking history update
  const trackingForm = document.getElementById('editTrackingHistoryForm');
  const submitTracking = document.getElementById("trackingSubmitBtn"); 
  
  if (trackingForm) {
    trackingForm.addEventListener('submit', async function(e) {
      e.preventDefault();
       Swal.fire({ title: 'Updating Tracking History...', didOpen: () => Swal.showLoading() });
      const submitBtn = document.getElementById('trackingSubmitBtn');
      submitBtn.disabled = true;
    
      const formData = new FormData(this);

      try {
          const response = await fetch('./functions/shipment/update-tracking-history.php', {
              method: 'POST',
              body: formData
          });
          const result = await response.json();

          if (result.success) {
              Swal.fire('Updated!', result.message || 'All statuses updated successfully.', 'success')
                  .then(() => location.reload());
          } else {
              Swal.fire('Error', result.message || 'Failed to update statuses.', 'error');
          }
      } catch (error) {
          console.error(error);
          Swal.fire('Error', 'Something went wrong while updating statuses.', 'error');
      } finally {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText;
      }
    });
  }

  // Packing list update
  const packingForm = document.getElementById('packingListForm');
  if (packingForm) {
    packingForm.addEventListener('submit', async function(e) {
      e.preventDefault();

      const submitBtn = document.getElementById('packingSubmitBtn');
      submitBtn.disabled = true;

      const formData = new FormData(this);
      // include manifest id so backend knows which record to update
      const manifestId = this.dataset.manifestId || '';
      formData.append('id', manifestId);

      try {
          const response = await fetch('./functions/shipment/update-shipment.php', {
              method: 'POST',
              body: formData
          });

          const result = await response.json();

          if (result.success) {
              Swal.fire('Updated!', result.message || 'Packing List Updated.', 'success')
                  .then(() => location.reload());
          } else {
              Swal.fire('Error', result.message || 'Failed to update packing list.', 'error');
          }
      } catch (error) {
          console.error(error);
          Swal.fire('Error', 'Something went wrong while updating packing list.', 'error');
      } finally {
          submitBtn.disabled = false;
      }
    });
  }

  // Delete packing list / shipment
  function attachDeleteButtons() {
    document.querySelectorAll('.delete-btn').forEach(button => {
      if (button._hasDeleteListener) return;
      button._hasDeleteListener = true;

      button.addEventListener('click', function () {
        const manifestId = this.dataset.id;
        const row = this.closest('tr');
        Swal.fire({
          title: 'Are you sure?',
          text: 'This packing list will be permanently deleted.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#e3342f',
          cancelButtonColor: '#6b7280',
          confirmButtonText: 'Yes, delete it!'
        }).then(result => {
          if (result.isConfirmed) {
            fetch('./functions/shipment/delete-shipment.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: 'id=' + encodeURIComponent(manifestId)
            })
            .then(res => res.json())
            .then(data => {
              if (data.success) {
                if (row) row.remove();
                Swal.fire('Deleted!', 'The packing list has been deleted.', 'success');
              } else {
                Swal.fire('Error!', data.message || 'Failed to delete packing list.', 'error');
              }
            })
            .catch(() => {
              Swal.fire('Error!', 'Something went wrong.', 'error');
            });
          }
        });
      });
    });
  }

  attachDeleteButtons();

  // Re-attach buttons after table changes (if you update the DOM elsewhere)
  // If you have code that modifies originalRows you'll want to call attachEditButtons() and attachDeleteButtons() again.

});


// Print function
function printTable() {
  const printArea = document.querySelector('.print-area');
  if (!printArea) return;

  const styles = `
    <style>
      @media print {
        body { font-family: Arial, sans-serif; color: #111827; }
        .no-print { display: none !important; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        tr:nth-child(even) { background-color: #f9fafb; }
        .watermark { position: fixed; top: 45%; left: 50%; transform: translate(-50%, -50%); font-size: 80px; opacity: 0.05; }
      }
    </style>
  `;

  const win = window.open('', '', 'height=800,width=1200');
  win.document.write('<html><head><title>Print</title>' + styles + '</head><body>');
  win.document.write('<h3 style="text-align:center">Benjamin Cargo & Logistics - Packing List</h3>');
  win.document.write('<p style="text-align:center;font-size:12px;color:gray;">Printed on: ' + new Date().toLocaleString() + '</p>');
  win.document.write(printArea.innerHTML);
  win.document.write('</body></html>');
  win.document.close();
  win.focus();
  win.print();
  win.close();
}
</script>
</body>
</html>
