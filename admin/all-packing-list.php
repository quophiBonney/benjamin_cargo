<?php
// shipments-page.php (updated)
// Auth & role check (unchanged)
include_once 'templates/auth.php';
$allowed_roles = ['admin', 'manager', 'hr'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($session_role, $allowed_roles)) {
    header("Location: login.php");
    exit;
}

// DB include (unchanged)
include_once __DIR__ . '../../includes/dbconnection.php';

// Fetch shipments
$query = "SELECT * FROM shipping_manifest ORDER BY id ASC";
$stmt = $dbh->prepare($query);
$stmt->execute();
$shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper: convert entry_date to nice day label (e.g. "3rd November 2026")
function entryDateLabel($entry) {
    if (!$entry) return '';
    $ts = strtotime($entry);
    if ($ts === false) return '';
    return date('jS F Y', $ts);
}

// Build containers mapping: prefer import_file per-record, else group by full entry_date (day-month-year)
$containers = [];
$containerManifests = [];
// We'll also dedupe per container using a seen-key set
$seenPerContainer = [];

// Group and dedupe per-row (use import_file when present)
foreach ($shipments as $m) {
    // choose group key per-record
    $importFileRaw = trim((string)($m['import_file'] ?? ''));
    if ($importFileRaw !== '') {
        // Use basename in case path was stored
        $key = pathinfo($importFileRaw, PATHINFO_BASENAME) ?: $importFileRaw;
    } else {
        $entry = $m['entry_date'] ?? '';
        $label = entryDateLabel($entry);
        $key = $label !== '' ? $label : 'Unspecified Import';
    }

    if (!array_key_exists($key, $containerManifests)) {
        $containerManifests[$key] = [];
        $containers[] = $key;
        $seenPerContainer[$key] = [];
    }

    // Deduplication: create a stable unique key per manifest
    // Use receipt_number + shipping_mark + entry_date as the uniqueness tuple (fallback to id)
    $receipt = trim((string)($m['receipt_number'] ?? ''));
    $mark = trim((string)($m['shipping_mark'] ?? ''));
    $entry = trim((string)($m['entry_date'] ?? ''));
    $uniqueKey = $receipt . '||' . $mark . '||' . $entry;
    if ($uniqueKey === '||' && !empty($m['id'])) {
        // If receipt and mark and entry_date empty, fallback to id
        $uniqueKey = 'id::' . $m['id'];
    }

    if (!in_array($uniqueKey, $seenPerContainer[$key], true)) {
        $containerManifests[$key][] = $m;
        $seenPerContainer[$key][] = $uniqueKey;
    } else {
        // duplicate — skip adding to the group (this prevents duplicates per container in the UI)
        continue;
    }
}

// Sort containers: most recent first (based on latest entry_date within each container)
$containerDates = [];
foreach ($containerManifests as $label => $rows) {
    $maxTs = 0;
    foreach ($rows as $r) {
        $ts = strtotime($r['entry_date'] ?? '');
        if ($ts && $ts > $maxTs) $maxTs = $ts;
    }
    // If no entry_date found for all rows, fall back to 0 so they appear last
    $containerDates[$label] = $maxTs;
}
usort($containers, function($a, $b) use ($containerDates) {
    return ($containerDates[$b] ?? 0) <=> ($containerDates[$a] ?? 0);
});

// JSON encode for client-side JS
$containerManifestsJson = json_encode($containerManifests, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$containersJson = json_encode($containers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<?php include_once 'templates/sidebar.php'; ?>
<?php include_once 'templates/header.php'; ?>
<?php include_once 'templates/app-bar.php'; ?>

<main class="flex-1 md:ml-64 px-4 transition-all">
  <input type="file" id="fileInput" accept=".csv, .xlsx" style="display: none;">

  <div class="bg-white shadow-md rounded-md p-6 mt-24 md:mt-32">
    <div class="flex flex-col gap-4">

      <!-- Imports list (now full-width list) -->
      <div class="w-full p-3 border rounded">
        <div class="flex items-start justify-between">
          <div>
            <h3 class="font-bold mb-1">Imports / Containers</h3>
            <p class="text-sm text-gray-600 mb-2">Click a container to view its packing list</p>
          </div>
          <div class="flex flex-row gap-2">
            <button id="importCSV" class="border border-gray-500 text-gray-500 px-3 py-1 rounded hover:bg-gray-600 hover:text-white text-sm">
              Import CSV
            </button>
          </div>
        </div>

        <div id="importsList" class="space-y-2 overflow-y-auto max-h-[70vh] mt-3">
          <?php if (empty($containers)): ?>
            <div class="text-sm text-gray-500">No imports yet.</div>
          <?php else: ?>
            <?php foreach ($containers as $idx => $label): ?>
              <button
                class="w-full text-left px-3 py-2 rounded border hover:bg-gray-100 import-item flex items-center justify-between"
                data-container="<?= htmlspecialchars($label, ENT_QUOTES) ?>">
                <div class="truncate">
                  <span class="font-medium"><?= htmlspecialchars($label) ?></span>
                  <div class="text-xs text-gray-500"><?= count($containerManifests[$label]) ?> records</div>
                </div>
                <div class="ml-2 text-xs text-gray-400">View</div>
              </button>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Container Modal (wider, with filters at top and pagination beneath the table) -->
  <div id="containerModal" class="fixed inset-0 hidden z-50 bg-gray-400/20 backdrop-blur-md flex items-start justify-center pt-12 px-4">
    <div class="bg-white p-4 rounded shadow-lg w-full max-w-[95vw] max-w-7xl relative max-h-[88vh] overflow-hidden">
      <!-- Close button (also clickable on mobile) -->
      <button id="closeContainerModalTop" class="absolute bg-red-500 w-9 h-9 rounded text-white top-3 right-3 hover:cursor-pointer md:hidden">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <!-- Modal header: title + filters on top -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3 m-3">
        <h2 id="containerTitle" class="text-xl font-bold">Container</h2>

        <div class="flex flex-col md:flex-row gap-2 items-stretch">
          <!-- Update history button will open the tracking modal and carry the current container label -->
          <button id="modalOpenUpdateHistory" class="border border-gray-500 text-gray-500 px-3 py-1 rounded hover:bg-gray-600 hover:text-white text-sm">
            Update History
          </button>

          <input id="modalSearch" type="search" placeholder="Search code / package / note..." class="p-2 border rounded w-full md:w-64 bg-gray-100" />
        </div>
      </div>

      <!-- Table area (scrollable) -->
      <div class="overflow-auto border rounded max-h-[64vh] m-3 bg-white">
        <table id="containerTable" class="w-full text-sm text-left text-gray-700 border-collapse">
          <thead class="bg-gray-700 text-white uppercase text-xs font-semibold sticky top-0">
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
          <tbody id="containerTableBody" class="divide-y divide-gray-200"></tbody>
        </table>
      </div>

      <!-- Pagination (modern, beneath the table) -->
      <div class="flex items-center justify-between gap-3 mt-3 mx-3">
        <div class="flex items-center gap-2">
          <label for="modalRowsPerPage" class="text-sm text-gray-600">Rows per page</label>
          <select id="modalRowsPerPage" class="border rounded p-1">
            <option value="10">10</option>
            <option value="25" selected>25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>

        <div class="flex items-center gap-2">
          <button id="modalPrevPage" class="px-3 py-1 border rounded hover:bg-gray-100">Prev</button>
          <div id="modalPaginationNumbers" class="flex items-center gap-1"></div>
          <button id="modalNextPage" class="px-3 py-1 border rounded hover:bg-gray-100">Next</button>
        </div>

        <div class="text-sm text-gray-600">
          Showing <span id="modalStartCount">0</span> - <span id="modalEndCount">0</span> of <span id="modalTotalCount">0</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Update Tracking History Modal (taken from your second file, connected to container modal) -->
  <div id="updateTrackingHistoryModal" class="fixed inset-0 hidden z-50 bg-gray-400/10 backdrop-blur-md flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-2xl relative max-h-screen overflow-y-auto">
      <button onclick="closeTrackModal()" class="absolute bg-red-500 w-8 h-8 rounded text-white top-2 right-2 hover:cursor-pointer">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <h2 class="text-xl font-bold mb-4">Update History</h2>
      <form id="editTrackingHistoryForm">
        <input type="hidden" id="tracking_container_label" name="container_label" value="" />
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

  <!-- Packing list update modal (unchanged) -->
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
            <input type="text" id="receipt_number" name="receipt_number" class="bg-gray-100 w-full p-2 border border-gray-300 rounded"/>
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
            <input type="text" id="supplier_number" name="supplier_number" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Supplier No."/>
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
          <textarea id="packageName" name="packageName" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Package Name"></textarea>
        </div>
        <div class="mt-5">
          <button type="submit" id="packingSubmitBtn" class="bg-gray-800 text-white px-6 py-3 rounded hover:bg-gray-700 w-full">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <?php include_once 'templates/footer.php'; ?>

<script>
/* ====== Data from PHP ====== */
const containerManifests = <?= $containerManifestsJson ?: '{}' ?>;
const containers = <?= $containersJson ?: '[]' ?>;

/* ====== Page: imports list & import handling ====== */
document.addEventListener('DOMContentLoaded', () => {
  const fileInput = document.getElementById('fileInput');
  const importBtn = document.getElementById('importCSV');

  if (importBtn && fileInput) {
    importBtn.onclick = () => fileInput.click();
    fileInput.onchange = () => {
      const file = fileInput.files[0];
      if (!file) return;

      // Basic client-side filename duplicate check: if same import filename exists, warn user
      const filename = file.name;
      if (Object.keys(containerManifests).includes(filename)) {
        Swal.fire({
          icon: 'warning',
          title: 'Duplicate import?',
          text: 'A container with that filename already exists. Importing may create duplicates. Continue?',
          showCancelButton: true
        }).then(res => {
          if (res.isConfirmed) {
            submitImport(file);
          }
        });
      } else {
        submitImport(file);
      }
    };
  }

  function submitImport(file) {
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
  }

  // Wire import-item clicks (list items)
  document.querySelectorAll('.import-item').forEach(btn => {
    btn.addEventListener('click', () => {
      const key = btn.dataset.container;
      openContainerModal(key);
    });
  });
});

/* ====== Container modal + filtering + pagination + edit/delete handlers ====== */
(function () {
  const containerModal = document.getElementById('containerModal');
  const containerTitle = document.getElementById('containerTitle');
  const containerTableBody = document.getElementById('containerTableBody');

  const closeBtnTop = document.getElementById('closeContainerModalTop');
  const modalOpenUpdateHistory = document.getElementById('modalOpenUpdateHistory');

  const modalSearch = document.getElementById('modalSearch');
  const modalFromDate = document.getElementById('modalFromDate');
  const modalToDate = document.getElementById('modalToDate');
  const clearModalFilters = document.getElementById('clearModalFilters');

  // Pagination controls
  const modalRowsPerPage = document.getElementById('modalRowsPerPage');
  const modalPrevPage = document.getElementById('modalPrevPage');
  const modalNextPage = document.getElementById('modalNextPage');
  const modalPaginationNumbers = document.getElementById('modalPaginationNumbers');
  const modalStartCount = document.getElementById('modalStartCount');
  const modalEndCount = document.getElementById('modalEndCount');
  const modalTotalCount = document.getElementById('modalTotalCount');

  let currentRows = []; // normalized rows for current container (full unfiltered set)
  let filteredRows = []; // result after search & date filters
  let currentContainerLabel = '';
  // pagination state
  let currentPage = 1;
  let rowsPerPage = parseInt(modalRowsPerPage.value || '25', 10);

  // open modal for a given container key
  window.openContainerModal = function(containerKey) {
    if (!containerKey || !containerManifests[containerKey]) return;
    currentContainerLabel = containerKey;
    containerTitle.textContent = containerKey + ' — Packing List';
    currentRows = (containerManifests[containerKey] || []).map(r => normalizeRow(r));

    // reset filters & pagination
    if (modalSearch) modalSearch.value = '';
    if (modalFromDate) modalFromDate.value = '';
    if (modalToDate) modalToDate.value = '';
    currentPage = 1;
    rowsPerPage = parseInt(modalRowsPerPage.value || '25', 10);

    applyModalFilters(); // this will set filteredRows and render the page
    containerModal.classList.remove('hidden');
  };

  // Normalize row into an object with safe strings (avoid null/undefined)
  function normalizeRow(r) {
    const safe = v => (v === null || v === undefined) ? '' : String(v);
    return {
      id: safe(r.id),
      shipping_mark: safe(r.shipping_mark),
      receipt_number: safe(r.receipt_number),
      entry_date: safe(r.entry_date),
      package_name: safe(r.package_name),
      number_of_pieces: safe(r.number_of_pieces),
      volume_cbm: safe(r.volume_cbm),
      loading_date: safe(r.loading_date),
      estimated_time_of_arrival: safe(r.estimated_time_of_arrival),
      estimated_time_of_offloading: safe(r.estimated_time_of_offloading),
      status: safe(r.status),
      customer_id: safe(r.customer_id),
      express_tracking_no: safe(r.express_tracking_no),
      supplier_number: safe(r.supplier_number),
      note: safe(r.note),
      __raw: r
    };
  }

  // Apply filters to currentRows and then re-render pagination/page
  function applyModalFilters() {
    let rows = currentRows.slice();
    const q = (modalSearch && modalSearch.value) ? modalSearch.value.trim().toLowerCase() : '';
    const from = (modalFromDate && modalFromDate.value) ? new Date(modalFromDate.value) : null;
    const to = (modalToDate && modalToDate.value) ? new Date(modalToDate.value) : null;
    if (to) { to.setHours(23,59,59,999); }

    if (q) {
      rows = rows.filter(r => {
        const hay = (
          r.shipping_mark + ' ' +
          r.receipt_number + ' ' +
          r.package_name + ' ' +
          r.entry_date + ' ' +
          r.status + ' ' +
          r.express_tracking_no + ' ' +
          r.supplier_number + ' ' +
          r.note
        ).toLowerCase();
        return hay.includes(q);
      });
    }

    if (from || to) {
      rows = rows.filter(r => {
        if (!r.entry_date) return false;
        const d = new Date(r.entry_date);
        if (isNaN(d)) return false;
        if (from && d < from) return false;
        if (to && d > to) return false;
        return true;
      });
    }

    filteredRows = rows;
    currentPage = 1; // reset to first page on filter change
    renderModalPage();
  }

  // Render only the current page of filteredRows
  function renderModalPage() {
    containerTableBody.innerHTML = '';
    const total = filteredRows.length;
    const totalPages = Math.max(1, Math.ceil(total / rowsPerPage));
    if (currentPage > totalPages) currentPage = totalPages;

    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = Math.min(startIndex + rowsPerPage, total);

    // Fill visible rows
    const fragment = document.createDocumentFragment();
    for (let i = startIndex; i < endIndex; i++) {
      const m = filteredRows[i];
      const tr = document.createElement('tr');
      tr.className = 'even:bg-gray-50 hover:bg-gray-100 transition duration-150';
      tr.innerHTML = `
        <td class="py-2 px-4">${escapeHtml(m.shipping_mark)}</td>
        <td class="py-2 px-4">${escapeHtml(m.receipt_number)}</td>
        <td class="py-2 px-4">${escapeHtml(m.entry_date)}</td>
        <td class="py-2 px-4 capitalize">${escapeHtml(m.package_name)}</td>
        <td class="py-2 px-4">${escapeHtml(m.number_of_pieces)}</td>
        <td class="py-2 px-4">${escapeHtml(m.volume_cbm)}</td>
        <td class="py-2 px-4">${escapeHtml(m.loading_date)}</td>
        <td class="py-2 px-4">${escapeHtml(m.estimated_time_of_arrival)}</td>
        <td class="py-2 px-4"><span class="capitalize">${escapeHtml(m.status)}</span></td>
        <td class="no-print flex gap-1 py-2 px-4">
          <a class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
             href="fpdf/shipment-invoice.php?customer_id=${encodeURIComponent(m.customer_id)}" target="_blank">
             <i class="fa-solid fa-file-invoice"></i>
          </a>
          <button class="bg-gray-500 text-white p-2 rounded hover:bg-gray-600 edit-btn" data-manifest='${escapeHtml(JSON.stringify(m.__raw))}'><i class="fas fa-edit"></i></button>
          <a class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" download="invoice.pdf"
             href="fpdf/shipment-invoice.php?customer_id=${encodeURIComponent(m.customer_id)}">
             <i class="fa-solid fa-download"></i>
          </a>
          <button class="bg-red-500 text-white p-2 rounded hover:bg-red-600 delete-btn" data-id="${escapeHtml(m.id)}"><i class="fa fa-trash" aria-hidden="true"></i></button>
        </td>
      `;
      fragment.appendChild(tr);
    }
    containerTableBody.appendChild(fragment);

    // Pagination UI
    renderModalPagination(totalPages, startIndex + 1, endIndex, total);

    // Attach edit/delete handlers to visible rows
    attachModalEditDeleteHandlers();
  }

  // Render page numbers and counts
  function renderModalPagination(totalPages, startCount, endCount, total) {
    modalPaginationNumbers.innerHTML = '';
    // show up to 7 page buttons (with ellipsis)
    const maxButtons = 7;
    let start = 1;
    let end = totalPages;
    if (totalPages > maxButtons) {
      const half = Math.floor(maxButtons / 2);
      start = Math.max(1, currentPage - half);
      end = start + maxButtons - 1;
      if (end > totalPages) {
        end = totalPages;
        start = end - maxButtons + 1;
      }
    }
    for (let p = start; p <= end; p++) {
      const b = document.createElement('button');
      b.className = `px-3 py-1 border rounded ${p === currentPage ? 'bg-gray-800 text-white' : 'hover:bg-gray-100'}`;
      b.textContent = p;
      b.onclick = () => { currentPage = p; renderModalPage(); };
      modalPaginationNumbers.appendChild(b);
    }

    modalStartCount.textContent = total === 0 ? 0 : startCount;
    modalEndCount.textContent = total === 0 ? 0 : endCount;
    modalTotalCount.textContent = total;

    modalPrevPage.disabled = (currentPage <= 1);
    modalNextPage.disabled = (currentPage >= totalPages);
  }

  // Prev/Next buttons
  modalPrevPage.addEventListener('click', () => {
    if (currentPage > 1) { currentPage--; renderModalPage(); }
  });
  modalNextPage.addEventListener('click', () => {
    const totalPages = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));
    if (currentPage < totalPages) { currentPage++; renderModalPage(); }
  });

  modalRowsPerPage.addEventListener('change', () => {
    rowsPerPage = parseInt(modalRowsPerPage.value || '25', 10);
    currentPage = 1;
    renderModalPage();
  });

  // Edit/Delete inside modal (handlers)
  function attachModalEditDeleteHandlers() {
    // EDIT
    document.querySelectorAll('#containerTable .edit-btn').forEach(button => {
      if (button._hasModalEdit) return;
      button._hasModalEdit = true;
      button.addEventListener('click', function () {
        const manifest = JSON.parse(this.dataset.manifest || '{}');
        const packingListModal = document.getElementById('updatePackingList');
        // fill packing modal
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
        document.getElementById('packingListForm').dataset.manifestId = manifest.id || '';
        if (packingListModal) packingListModal.classList.remove('hidden');
      });
    });

    // DELETE
    document.querySelectorAll('#containerTable .delete-btn').forEach(button => {
      if (button._hasModalDelete) return;
      button._hasModalDelete = true;
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
                // Also update currentRows/filteredRows to keep UI consistent
                currentRows = currentRows.filter(r => r.id !== manifestId);
                filteredRows = filteredRows.filter(r => r.id !== manifestId);
                renderModalPage();
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

  // Clear and live filter wiring
  if (clearModalFilters) {
    clearModalFilters.addEventListener('click', () => {
      if (modalSearch) modalSearch.value = '';
      if (modalFromDate) modalFromDate.value = '';
      if (modalToDate) modalToDate.value = '';
      applyModalFilters();
    });
  }
  if (modalSearch) modalSearch.addEventListener('input', applyModalFilters);
  if (modalFromDate) modalFromDate.addEventListener('change', applyModalFilters);
  if (modalToDate) modalToDate.addEventListener('change', applyModalFilters);

  // Close modal handlers
  closeBtnTop.addEventListener('click', () => containerModal.classList.add('hidden'));
  document.getElementById('closeContainerModalTop').addEventListener('click', () => containerModal.classList.add('hidden'));
  containerModal.addEventListener('click', (e) => {
    if (e.target === containerModal) containerModal.classList.add('hidden');
  });

  // Expose update history button to open tracking modal with container context
  if (modalOpenUpdateHistory) {
    modalOpenUpdateHistory.addEventListener('click', () => {
      const trackModal = document.getElementById('updateTrackingHistoryModal');
      const containerField = document.getElementById('tracking_container_label');
      if (containerField) containerField.value = currentContainerLabel || '';
      if (trackModal) trackModal.classList.remove('hidden');
    });
  }

  // escape helper
  function escapeHtml(unsafe) {
    return String(unsafe)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }
})();

/* ====== Tracking history form handling (works for container-level updates) ====== */
(function () {
  const trackingForm = document.getElementById('editTrackingHistoryForm');
  if (!trackingForm) return;

  trackingForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    const submitBtn = document.getElementById('trackingSubmitBtn');
    const originalText = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Saving...';
    }
    const formData = new FormData(this);
    // Optionally include container label (already set when opened)
    try {
      const response = await fetch('./functions/shipment/update-tracking-history.php', {
        method: 'POST',
        body: formData
      });
      const result = await response.json();
      if (result.success) {
        Swal.fire('Updated!', result.message || 'All statuses updated successfully.', 'success').then(() => {
          // close modal and reload or update UI
          const trackModal = document.getElementById('updateTrackingHistoryModal');
          if (trackModal) trackModal.classList.add('hidden');
          // reload to reflect changes globally
          location.reload();
        });
      } else {
        Swal.fire('Error', result.message || 'Failed to update statuses.', 'error');
      }
    } catch (err) {
      console.error(err);
      Swal.fire('Error', 'Something went wrong while updating statuses.', 'error');
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      }
    }
  });
})();

/* ====== Packing list update & delete handlers (unchanged behavior reused) ====== */
// Packing list form submit
(function () {
  const packingForm = document.getElementById('packingListForm');
  if (!packingForm) return;
  packingForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    const submitBtn = document.getElementById('packingSubmitBtn');
    submitBtn.disabled = true;
    const formData = new FormData(this);
    const manifestId = this.dataset.manifestId || '';
    formData.append('id', manifestId);
    try {
      const response = await fetch('./functions/shipment/update-shipment.php', {
        method: 'POST',
        body: formData
      });
      const result = await response.json();
      if (result.success) {
        Swal.fire('Updated!', result.message || 'Packing List Updated.', 'success').then(() => location.reload());
      } else {
        Swal.fire('Error', result.message || 'Failed to update packing list.', 'error');
      }
    } catch (err) {
      console.error(err);
      Swal.fire('Error', 'Something went wrong while updating packing list.', 'error');
    } finally {
      submitBtn.disabled = false;
    }
  });
})();

// Modal close functions
window.closeTrackModal = () => {
  const trackModal = document.getElementById('updateTrackingHistoryModal');
  if (trackModal) trackModal.classList.add('hidden');
};

window.closePackingListModal = () => {
  const packingModal = document.getElementById('updatePackingList');
  if (packingModal) packingModal.classList.add('hidden');
};

// Click outside to close modals
document.getElementById('updateTrackingHistoryModal').addEventListener('click', (e) => {
  if (e.target === document.getElementById('updateTrackingHistoryModal')) {
    closeTrackModal();
  }
});

document.getElementById('updatePackingList').addEventListener('click', (e) => {
  if (e.target === document.getElementById('updatePackingList')) {
    closePackingListModal();
  }
});

</script>
</body>
</html>
