<?php 
include_once 'templates/auth.php';
$allowed_roles = ['admin', 'manager', 'hr'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    header("Location: login.php");
    exit;
}

include_once __DIR__ . '../../includes/dbconnection.php';

// Fetch reviews with customer names
$query = "SELECT r.id, r.rating, r.comment, r.created_at, c.client_name AS customer_name
        FROM customer_reviews r
        JOIN customers c ON r.customer_id = c.customer_id
        ORDER BY r.created_at DESC";
$stmt = $dbh->prepare($query);
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
              <span id="totalCount"><?= count($reviews) ?></span> reviews
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
        <th class="py-3 px-4">Customer Name</th>
        <th class="py-3 px-4">Rating</th>
        <th class="py-3 px-4">Sent At</th>
        <th class="no-print py-3 px-4">Share</th>
        <th class="no-print py-3 px-4">Action</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-200">
  <?php foreach ($reviews as $review): 
    $rating = intval($review['rating']);
    $stars = str_repeat('⭐', $rating) . str_repeat('☆', 5 - $rating);

    // Direct link to reviews.php?id=...
    $reviewUrl = "https://www.benjamincargo.com/reviews.php?id=" . $review['id'];
?>
<tr class="even:bg-gray-50 hover:bg-gray-100 transition duration-200">
  <td class="py-2 px-4"><?= htmlspecialchars($review['customer_name']) ?></td>
  <td class="py-2 px-4 text-yellow-500"><?= $stars ?></td>
  <td class="py-2 px-4"><?= htmlspecialchars($review['created_at']) ?></td>
  <td class="no-print py-2 px-4 flex flex-wrap gap-2">
    <!-- WhatsApp -->
    <a href="https://api.whatsapp.com/send?text=<?= urlencode($reviewUrl) ?>" 
       target="_blank" class="text-green-600 hover:text-green-800"><i class="fa-brands fa-whatsapp text-xl"></i></a>

    <!-- Facebook -->
    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($reviewUrl) ?>" 
       target="_blank" class="text-blue-600 hover:text-blue-800"><i class="fa-brands fa-facebook text-xl"></i></a>

    <!-- Twitter / X -->
    <a href="https://twitter.com/intent/tweet?url=<?= urlencode($reviewUrl) ?>" 
       target="_blank" class="text-sky-500 hover:text-sky-700"><i class="fa-brands fa-twitter text-xl"></i></a>

    <!-- LinkedIn -->
  </td>
  <td class="no-print py-2 px-4">
    <button 
      class="view-btn bg-gray-600 text-white px-3 py-1 rounded hover:bg-gray-700"
      data-customer="<?= htmlspecialchars($review['customer_name']) ?>"
      data-rating="<?= $stars ?>"
      data-comment="<?= htmlspecialchars($review['comment']) ?>"
      data-date="<?= htmlspecialchars($review['created_at']) ?>"
    >
      View
    </button>
  </td>
</tr>
<?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Modal -->
<div id="reviewModal" class="fixed inset-0 hidden z-50 bg-gray-400/10 backdrop-blur-md bg-opacity-20 flex items-center justify-center">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-3xl p-6 relative">
    <button id="closeModal" class="bg-red-500 p-2 h-9 w-9 rounded absolute top-2 right-2 text-white">✖</button>
    <h2 class="text-xl font-bold mb-4">Review Details</h2>
    <div class="space-y-3">
      <p><strong>Customer:</strong> <span id="modalCustomer"></span></p>
      <p><strong>Rating:</strong> <span id="modalRating" class="text-yellow-500"></span></p>
      <p><strong>Comment:</strong></p>
      <p id="modalComment" class="italic text-gray-700 bg-gray-50 p-2 rounded"></p>
      <p><strong>Sent At:</strong> <span id="modalDate"></span></p>
    </div>
  </div>
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
  const downloadCSVBtn = document.getElementById('downloadCSV'); // may or may not exist
  const printBtn = document.querySelector('[onclick="printTable()"]');


  

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
    win.document.write('<div class="watermark">Benjamin Cargo Logistics</div>');
    win.document.write('<img src="your-logo.png" style="height:60px;margin-bottom:10px;">');
    win.document.write('<h3 style="text-align:center;margin:5px 0;">Benjamin Cargo Logistics - All Reviews</h3>');
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
  
  // ----- Initialize rendering -----
  renderTable();

  // Expose a safe reload function if other scripts call it
  window.safeReload = () => location.reload();
});

function copyReviewText(text) {
  navigator.clipboard.writeText(text).then(() => {
    Swal.fire('Copied!', 'Review text copied. Paste it in TikTok or any app.', 'success');
  }).catch(err => {
    alert('Failed to copy: ' + err);
  });
}
document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("reviewModal");
  const closeModal = document.getElementById("closeModal");

  const modalCustomer = document.getElementById("modalCustomer");
  const modalRating = document.getElementById("modalRating");
  const modalComment = document.getElementById("modalComment");
  const modalDate = document.getElementById("modalDate");

  document.querySelectorAll(".view-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      modalCustomer.textContent = btn.dataset.customer;
      modalRating.textContent = btn.dataset.rating;
      modalComment.textContent = btn.dataset.comment;
      modalDate.textContent = btn.dataset.date;

      modal.classList.remove("hidden");
    });
  });

  closeModal.addEventListener("click", () => modal.classList.add("hidden"));
  modal.addEventListener("click", (e) => {
    if (e.target === modal) modal.classList.add("hidden");
  });
});
</script>
</body>
</html>