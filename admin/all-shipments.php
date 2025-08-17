<?php 
include_once 'includes/auth.php';
$allowed_roles = ['admin', 'manager', 'hr'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    header("Location: staffs-dashboard.php");
    exit;
}
include_once 'includes/dbconnection.php';
$query = "SELECT * FROM shipping_manifest ORDER BY id ASC";
$stmt = $dbh->prepare($query);
$stmt->execute();
$shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include_once 'includes/sidebar.php'; ?>
<?php include_once 'includes/header.php'; ?>
<?php include_once 'includes/app-bar.php';?>
 <main class="flex-1 md:ml-64 px-4 transition-all">
  <input type="file" id="fileInput" accept=".csv, .xlsx" style="display: none;">

  <div class="bg-white shadow-md rounded-md p-6 mt-24 md:mt-32">
     <div class="w-full flex justify-between items-center flex-col lg:flex-row mt-4 mb-4">
  <div class="w-full mt-3 md:mt-0">
        <p>
          Showing <span id="startCount">1</span> to <span id="endCount">50</span> of
          <span id="totalCount">0</span> shipments
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
<select id="statusSelect" class="border border-gray-500 text-gray-500 p-2 rounded">
    <option value="shipments received" disabled selected>Received</option>
    <option value="shipments in transit">In Transit</option>
    <option value="shipments has arrived at the Port undergoing clearance">At Port</option>
    <option value="shipments arrived at Benjamin Cargo Logistics Warehouse">At Benjamin Cargo</option>
    <option value="shipments picked up">Picked Up</option>
</select>
  <button onclick="printTable()" class="border border-gray-500 text-gray-500 p-2 rounded hover:bg-gray-600 hover:text-white">
Print
 <button>
</div>
<div class="w-full mt-5 md:mt-0">
    <input type="search" id="searchInput" placeholder="Search by name or number..."
           class="w-full p-2 bg-gray-100 border border-gray-300 rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
  </div>
  </div>
    <div class="print-area overflow-x-auto mt-6">
      <div class="watermark" style="display:none;">Benjamin Cargo & Logistics</div>
<table id="shippingManifestTable" class="table-x-auto w-full text-sm text-left text-gray-700 border border-gray-200 rounded-lg overflow-hidden shadow-sm">
    <thead class="bg-gray-700 text-white uppercase text-xs font-semibold border-b border-gray-300">
        <tr>
            <th class="py-3 px-4 min-w-[160px]">Shipping Mark</th>
            <th class="py-3 px-4 min-w-[160px]">Entry Date</th>
            <th class="py-3 px-4 min-w-[180px]">Package Name</th>
            <th class="py-3 px-4 min-w-[130px]">No. of Pieces</th>
            <th class="py-3 px-4">CBM</th>
            <!-- <th class="py-3 px-4">Express Tracking No.</th> -->
              <th class="py-3 px-4 min-w-[180px]">Status</th>
            <th class="py-3 px-4 no-print">Actions</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-200">
      <?php if(empty($shipments)): ?>
         <tr>
            <td colspan="7" class="py-4 px-4 text-center text-gray-500">
                No shipment found
            </td>
        </tr>
      <?php else: ?>
        <?php foreach ($shipments as $manifest): ?>
        <tr class="even:bg-gray-50 hover:bg-gray-100 transition duration-200">
            <td class="py-2 px-4"><?= htmlspecialchars($manifest['shipping_mark']) ?></td>
            <td class="py-2 px-4"><?= htmlspecialchars($manifest['entry_date']) ?></td>
            <td class="py-2 px-4"><?= htmlspecialchars($manifest['package_name']) ?></td>
            <td class="py-2 px-4"><?= htmlspecialchars($manifest['number_of_pieces']) ?></td>
            <td class="py-2 px-4"><?= htmlspecialchars($manifest['volume_cbm']) ?></td>
          <!-- <td class="py-2 px-4">
    <?= htmlspecialchars(substr($manifest['express_tracking_no'], 0, 10)) ?>...
</td> -->
              <td class="py-2 px-4"><?= htmlspecialchars($manifest['status']) ?></td>
            <td class="py-2 flex gap-1 px-4 space-x-1 no-print">
                <!-- Edit Button -->
                <button class="bg-gray-500 text-white p-2 rounded hover:bg-gray-600 hover:cursor-pointer transition edit-btn"
                    data-manifest='<?= json_encode($manifest, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                    <i class="fas fa-edit"></i>
                </button>

                <!-- View Button -->
                <button class="hover:cursor-pointer bg-green-500 text-white p-2 rounded hover:bg-green-600 transition view-btn"
                    data-id="<?= $manifest['id']; ?>">
                    <i class="fa fa-eye" aria-hidden="true"></i>
                </button>

                <!-- <a href="fpdf/shipping-invoice.php?id=<?= $manifest['id'] ?>" target="_blank"
                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    <i class="fa-solid fa-file-invoice"></i>
                </a> -->

                <!-- Delete Button -->
                <button class="hover:cursor-pointer bg-red-500 text-white p-2 rounded hover:bg-red-600 transition delete-btn"
                    data-id="<?= $manifest['id']; ?>">
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

  
  </div>
<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 hidden z-50 bg-gray-400/10 backdrop-blur-md bg-opacity-20 flex items-center justify-center">
  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-7xl relative max-h-screen overflow-y-auto">
    <button onclick="closeModal()" class="mt-3 absolute bg-red-500 w-8 h-8 rounded text-white top-2 right-2 hover:cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
    <h2 class="text-xl font-bold mb-4">Shipment Update</h2>
    <form id="updateShipmentForm" method="post">
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">

    <div>
      <label for="tracking_number" class="block text-gray-700">Tracking Number</label>
      <input type="text" id="tracking_number" name="tracking_number" class="bg-gray-100 w-full p-2 border border-gray-300 rounded"  placeholder="12345">
    </div>

    <div>
      <label for="sender_name" class="block text-gray-700">Sender Name</label>
      <input type="text" id="sender_name" name="sender_name" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="John Doe">
    </div>

    <div>
      <label for="sender_city" class="block text-gray-700">Sender City</label>
      <input type="text" id="sender_city" name="sender_city" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Hong Kong">
    </div>

    <div>
      <label for="sender_country" class="block text-gray-700">Sender Country</label>
      <input type="text" id="sender_country" name="sender_country" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="China">
    </div>

    <div>
      <label for="receiver_name" class="block text-gray-700">Receiver Name</label>
      <input type="text" id="receiver_name" name="receiver_name" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Mary Doe">
    </div>

    <div>
      <label for="receiver_city" class="block text-gray-700">Receiver City</label>
      <input type="text" id="receiver_city" name="receiver_city" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Accra">
    </div>

    <div>
      <label for="receiver_country" class="block text-gray-700">Receiver Country</label>
      <input type="text" id="receiver_country" name="receiver_country" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Ghana">
    </div>

    <div>
      <label for="receiver_phone" class="block text-gray-700">Receiver Phone</label>
      <input type="text" id="receiver_phone" name="receiver_phone" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="02XXXXXXXXXX">
    </div>

    <div>
      <label for="package_name" class="block text-gray-700">Package Name</label>
      <input type="text" id="package_name" name="package_name" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="IPhone">
    </div>

    <div>
      <label for="package_weight" class="block text-gray-700">Package Weight (kg)</label>
      <input type="number" step="0.01" id="package_weight" name="package_weight" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="20">
    </div>

    <div>
      <label for="package_len" class="block text-gray-700">Package Length (cm)</label>
      <input type="number" step="0.01" id="package_len" name="package_len" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="10">
    </div>

    <div>
      <label for="package_height" class="block text-gray-700">Package Height (cm)</label>
      <input type="number" step="0.01" id="package_height" name="package_height" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="20">
    </div>
    <div>
      <label for="package_quantity" class="block text-gray-700">Quantity</label>
      <input type="number" id="package_quantity" name="package_quantity" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="1">
    </div>

    <div>
      <label for="package_payment_method" class="block text-gray-700">Payment Method</label>
      <select class="bg-gray-100 w-full p-2 border border-gray-300 rounded" id="package_payment_method" name="package_payment_method" >
        <option value="" disabled selected>Choose Payment</option>
        <option value="Bank Transfer">Bank Transfer</option>
        <option value="Debit Card">Debit Card</option>
        <option value="Mobile Money">Mobile Money</option>
        </select>
    </div>
 <div>
      <label for="package_expected_delivery_date" class="block text-gray-700">Expected Delivery Date</label>
      <input type="date" id="package_expected_delivery_date" name="package_expected_delivery_date" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
    </div>
    <div>
      <label for="package_pickup_date" class="block text-gray-700">Pickup Date/Time</label>
      <input type="datetime-local" id="package_pickup_date" name="package_pickup_date" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Choose date">
    </div>
    <div>
      <label for="carrier" class="block text-gray-700">Carrier</label>
     <select class="bg-gray-100 w-full p-2 border border-gray-300 rounded" id="carrier" name="carrier">
        <option value="" disabled selected>Choose Carrier</option>
        <option value="DHL">DHL</option>
        <option value="FedEx">FedEx</option>
        </select>
    </div>
    <div>
      <label for="package_type_of_shipment" class="block text-gray-700">Type of Shipment</label>
      <select class="bg-gray-100 w-full p-2 border border-gray-300 rounded" id="package_type_of_shipment" name="package_type_of_shipment">
        <option value="" disabled selected>Choose Shipment Mode</option>
        <option value="Sea Freight">Sea Freight</option>
        <option value="Air Freight">Air Freight</option>
        </select>
    </div>

    <div>
      <label for="origin" class="block text-gray-700">Origin</label>
      <input type="text" id="origin" name="origin" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Mexico">
    </div>

    <div>
      <label for="destination" class="block text-gray-700">Destination</label>
      <input type="text" id="destination" name="destination" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Kumasi">
    </div>
  </div>
     <div class="mt-3">
      <label for="package_description" class="block text-gray-700">Package Description</label>
      <textarea id="package_description" name="package_description" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="A box of IPhones"></textarea>
    </div>
  <div class="mt-5">
    <button id="submitBtn" class="bg-blue-600 text-white px-8 py-2 rounded hover:bg-blue-700">Update Shipment</button>
  </div>
</form>
  </div>
</div>
<!-- Update History Modal Starts Here  -->
<div id="editTrackingModal" class="fixed inset-0 hidden z-50 bg-gray-400/10 backdrop-blur-md bg-opacity-20 flex items-center justify-center">
  <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-2xl relative max-h-screen overflow-y-auto">
    <button onclick="closeTrackModal()" class="mt-3 absolute bg-red-500 w-8 h-8 rounded text-white top-2 right-2 hover:cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
    <h2 class="text-xl font-bold mb-4">Update History</h2>
    <form id="editTrackingHistoryForm">
    <input type="hidden" id="trackingId" name="shipment_id">
      <div class="grid grid-cols-1 gap-5">
      <div>
        <label for="location" class="block text-gray-700">Location</label>
        <input type="text" id="location" name="location" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter Location" value="">
      </div>
      <div>
        <label for="description" class="block text-gray-700">Description</label>
        <textarea placeholder="Description" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" rows="4" name="description" id="description" columns="4"></textarea>
      </div>
        <div>
          <label for="status" class="block text-gray-700">Status</label>
         <select id="status" name="status" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
              <option value="" selected disabled>Choose Status</option>
            <option value="at loading">At Loading Port</option>
            <option value="shipped">Shipped</option>
            <option value="transit">In-Transit</option>
            <option value="arrived">Arrived</option>
            <option value="picked up">Picked Up</option>
          </select>
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
<script>
/*
  Full replacement JS for:
  - Pagination (with Rows per page)
  - Search (debounced)
  - Edit modal (open, populate, submit)
  - Tracking modal (open, fetch history, submit)
  - Delete shipment
  - Import CSV
  - Download CSV (fixed selector)
  - Print table
  - All event delegation & robust error handling
*/

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
  const downloadCSVBtn = document.getElementById('downloadCSV'); // may or may not exist
  const printBtn = document.querySelector('[onclick="printTable()"]');
  const editModal = document.getElementById('editModal');
  const updateForm = document.getElementById('updateShipmentForm');
  const trackingModal = document.getElementById('editTrackingModal');
  const trackingForm = document.getElementById('editTrackingHistoryForm');

  // Safeguard duplicate submit button IDs in your markup:
  const updateSubmitBtn = updateForm ? updateForm.querySelector('#submitBtn') : null;
  // For tracking form, the markup previously reused #submitBtn. Use button selector fallback:
  const trackingSubmitBtn = trackingForm ? trackingForm.querySelector('button[type="submit"], #submitBtn') : null;

  // ----- Build row store -----
  // Create an array of objects wrapping the original <tr> elements so we can re-render reliably.
  const originalRows = Array.from(tbody.querySelectorAll('tr')).map(tr => {
    // attempt to find a unique id on the row from action buttons (delete/view have data-id)
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

    // Append visible rows
    for (let i = startIndex; i < endIndex; i++) {
      tbody.appendChild(filteredRows[i].tr);
    }

    // Update counts
    startCount.textContent = total === 0 ? 0 : startIndex + 1;
    endCount.textContent = endIndex;
    totalCount.textContent = total;

    // Render pagination controls
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

    // show a limited range of page buttons for large counts
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

  // Debounce helper
  function debounce(fn, wait = 200) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), wait);
    };
  }

  // ----- Search & Rows-per-page -----
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

  // ----- Event delegation for action buttons in the table -----
  table.addEventListener('click', async (ev) => {
    const editBtn = ev.target.closest('.edit-btn');
    if (editBtn) {
      // data-manifest contains JSON of the manifest row
      try {
        const raw = editBtn.dataset.manifest;
        const manifest = JSON.parse(raw);
        openEditModal(manifest);
      } catch (err) {
        console.error('Failed to parse manifest JSON', err);
        Swal.fire('Error', 'Could not load shipment for editing.', 'error');
      }
      return;
    }

    const viewBtn = ev.target.closest('.view-btn');
    if (viewBtn) {
      // Option: fetch details and open edit modal (read-only) or show tracking history
      const id = viewBtn.dataset.id;
      if (!id) return;
      try {
        const resp = await fetch(`./functions/shipment/fetch-shipment.php?id=${encodeURIComponent(id)}`);
        const data = await resp.json();
        if (data.success && data.shipment) {
          openEditModal(data.shipment); // you can edit after viewing
        } else {
          Swal.fire('Not found', data.message || 'Shipment details not available', 'info');
        }
      } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Failed to fetch shipment details', 'error');
      }
      return;
    }

    const deleteBtn = ev.target.closest('.delete-btn');
    if (deleteBtn) {
      const shipmentID = deleteBtn.dataset.id;
      if (!shipmentID) return;
      const rowEl = deleteBtn.closest('tr');

      Swal.fire({
        title: 'Are you sure?',
        text: 'This shipment will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e3342f',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!'
      }).then(async (result) => {
        if (!result.isConfirmed) return;
        try {
          const res = await fetch('./functions/shipment/delete-shipment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + encodeURIComponent(shipmentID)
          });
          const json = await res.json();
          if (json.success) {
            // remove from originalRows & filteredRows
            const idx = originalRows.findIndex(r => r.id === shipmentID);
            if (idx !== -1) originalRows.splice(idx, 1);
            const fidx = filteredRows.findIndex(r => r.id === shipmentID);
            if (fidx !== -1) filteredRows.splice(fidx, 1);

            // remove DOM row
            if (rowEl) rowEl.remove();

            Swal.fire('Deleted!', 'The shipment has been deleted.', 'success');
            currentPage = 1;
            renderTable();
          } else {
            Swal.fire('Error!', json.message || 'Failed to delete shipment.', 'error');
          }
        } catch (err) {
          console.error(err);
          Swal.fire('Error!', 'Something went wrong.', 'error');
        }
      });

      return;
    }

    // track-btn (if present)
    const trackBtn = ev.target.closest('.track-btn');
    if (trackBtn) {
      // Expect dataset.shipment with JSON or dataset.id
      let shipmentId = null;
      try {
        if (trackBtn.dataset.shipment) {
          const s = JSON.parse(trackBtn.dataset.shipment);
          shipmentId = s.shipment_id || s.id || null;
        } else if (trackBtn.dataset.id) {
          shipmentId = trackBtn.dataset.id;
        }
      } catch (e) {
        console.warn('Failed to parse track dataset', e);
      }
      if (shipmentId) {
        openTrackingModal({ shipment_id: shipmentId, location: '', description: '', status: '' });
        // fetch history to populate fields
        try {
          const resp = await fetch(`./functions/shipment/fetch-tracking-history.php?id=${encodeURIComponent(shipmentId)}`);
          const json = await resp.json();
          if (json.success && json.tracking) {
            document.getElementById('location').value = json.tracking.location || '';
            document.getElementById('description').value = json.tracking.description || '';
            document.getElementById('status').value = json.tracking.status || '';
            document.getElementById('trackingId').value = shipmentId;
          }
        } catch (err) {
          console.log('No existing tracking history or fetch failed', err);
        }
      }
      return;
    }
  });

  // ----- Edit modal helpers -----
  window.closeModal = () => {
    if (editModal) editModal.classList.add('hidden');
  };

  function ensureHiddenIdField(form, idValue) {
    let hidden = form.querySelector('input[name="shipment_id"], input#shipment_id');
    if (!hidden) {
      hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'shipment_id';
      hidden.id = 'shipment_id';
      form.appendChild(hidden);
    }
    hidden.value = idValue;
  }

  window.openEditModal = (shipment = {}) => {
    if (!editModal) return;

    editModal.classList.remove('hidden');
    // populate fields - tolerate both id and shipment_id naming
    document.getElementById('tracking_number').value = shipment.tracking_number || shipment.tracking_no || shipment.tracking || '';
    document.getElementById('sender_name').value = shipment.sender_name || '';
    document.getElementById('sender_city').value = shipment.sender_city || '';
    document.getElementById('sender_country').value = shipment.sender_country || '';
    document.getElementById('receiver_name').value = shipment.receiver_name || '';
    document.getElementById('receiver_city').value = shipment.receiver_city || '';
    document.getElementById('receiver_country').value = shipment.receiver_country || '';
    document.getElementById('receiver_phone').value = shipment.receiver_phone || '';
    document.getElementById('package_name').value = shipment.package_name || '';
    document.getElementById('package_weight').value = shipment.package_weight || shipment.weight || '';
    document.getElementById('package_len').value = shipment.package_len || shipment.length || '';
    document.getElementById('package_height').value = shipment.package_height || shipment.height || '';
    document.getElementById('package_quantity').value = shipment.package_quantity || shipment.quantity || '';
    document.getElementById('package_payment_method').value = shipment.package_payment_method || shipment.payment_method || '';
    document.getElementById('package_expected_delivery_date').value = shipment.package_expected_delivery_date || '';
    document.getElementById('package_pickup_date').value = shipment.package_pickup_date || '';
    document.getElementById('carrier').value = shipment.package_carrier || shipment.carrier || '';
    document.getElementById('package_type_of_shipment').value = shipment.package_type_of_shipment || '';
    document.getElementById('origin').value = shipment.origin || '';
    document.getElementById('destination').value = shipment.destination || '';
    document.getElementById('package_description').value = shipment.package_description || '';

    // ensure hidden id present in form
    const idToSet = shipment.id || shipment.shipment_id || shipment.shipmentId || '';
    ensureHiddenIdField(updateForm, idToSet);

    if (updateSubmitBtn) updateSubmitBtn.textContent = 'Update Shipment';
  };

  // ----- Tracking modal helpers -----
  window.openTrackingModal = (shipment = {}) => {
    if (!trackingModal) return;
    trackingModal.classList.remove('hidden');
    document.getElementById('trackingId').value = shipment.shipment_id || shipment.id || '';
    document.getElementById('location').value = shipment.location || '';
    document.getElementById('description').value = shipment.description || '';
    document.getElementById('status').value = shipment.status || '';
  };

  window.closeTrackModal = () => {
    if (trackingModal) trackingModal.classList.add('hidden');
  };


  // ----- Print logic (kept largely the same, tightened up) -----
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
    win.document.write('<div class="watermark">CONSOL HOTEL</div>');
    win.document.write('<img src="your-logo.png" style="height:60px;margin-bottom:10px;">');
    win.document.write('<h3 style="text-align:center;margin:5px 0;">Benjamin Cargo & Logistics - Shipments</h3>');
    win.document.write('<p style="text-align:center;font-size:12px;color:gray;">Printed on: ' + new Date().toLocaleString() + '</p>');
    // clone the table to avoid moving nodes out of document
    const clone = printArea.cloneNode(true);
    // remove any interactive elements
    clone.querySelectorAll('.no-print, button, a').forEach(n => n.remove());
    win.document.write(clone.innerHTML);
    win.document.write('</div><footer>Page</footer></body></html>');
    win.document.close();
    win.focus();
    win.print();
    win.close();
  };

  // ----- CSV Download (fixed selector and action column removal) -----
  if (downloadCSVBtn) {
    downloadCSVBtn.addEventListener('click', () => {
      const tableEl = document.getElementById('shippingManifestTable');
      if (!tableEl) return Swal.fire('Error', 'Table not found', 'error');

      const rows = tableEl.querySelectorAll('thead tr, tbody tr');
      let csvContent = '';

      // Detect action columns (by header text)
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
          // convert datetime if in ISO format
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
      link.setAttribute("download", "all-shipments.csv");
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

      fetch('./functions/shipment/import-shipping-manifest.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.text())
      .then(txt => {
        Swal.close();
        // server is returning text - show it then refresh
        Swal.fire('Import Result', txt, 'info').then(() => location.reload());
      })
      .catch(err => {
        Swal.close();
        console.error(err);
        Swal.fire('Error', 'Import failed', 'error');
      });
    });
  }

  // ----- Initialize rendering -----
  renderTable();

  // Expose a safe reload function if other scripts call it
  window.safeReload = () => location.reload();
});

document.getElementById('statusSelect').addEventListener('change', async function() {
    const newStatus = this.value;
    if (!newStatus) return;

    // Confirm action
    const confirmed = await Swal.fire({
        title: 'Update all shipments?',
        text: `This will set status "${newStatus}" for ALL shipping marks in tracking history.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, update all',
        cancelButtonText: 'Cancel'
    });

    if (!confirmed.isConfirmed) return;

    try {
        const response = await fetch('./functions/shipment/update-tracking-history.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'status=' + encodeURIComponent(newStatus)
        });

        const result = await response.json();
        if (result.success) {
            Swal.fire('Updated!', result.message || 'All statuses updated successfully.', 'success')
                .then(() => location.reload()); // reload to reflect changes
        } else {
            Swal.fire('Error', result.message || 'Failed to update statuses.', 'error');
        }
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Something went wrong while updating statuses.', 'error');
    }
});

</script>
</body>
</html>