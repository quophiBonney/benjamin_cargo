<?php
include_once 'includes/auth.php';
$allowed_roles = ['admin', 'manager', 'receptionist'];
if (!in_array(strtolower($_SESSION['role'] ?? ''), $allowed_roles)) {
    header("Location: staffs-dashboard.php");
    exit;
}

include_once 'includes/dbconnection.php';

$filter_name = $_GET['name'] ?? '';
$filter_month = $_GET['month'] ?? date('m');
$filter_start = $_GET['start_date'] ?? '';
$filter_end = $_GET['end_date'] ?? '';
$year = date('Y');

// Pagination setup
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Date range
$startDate = $filter_start ?: "$year-$filter_month-01";
$endDate = $filter_end ?: date("Y-m-t", strtotime($startDate));

// Build dates
$dates = [];
$start = strtotime($startDate);
$end = strtotime($endDate);
for ($d = $start; $d <= $end; $d = strtotime('+1 day', $d)) {
    $dates[] = date('Y-m-d', $d);
}

// Fetch employees
$params = [];
$where = "";
if (!empty($filter_name)) {
    $where = "WHERE full_name = ?";
    $params[] = $filter_name;
}

$total = $dbh->prepare("SELECT COUNT(*) FROM employees $where");
$total->execute($params);
$total_employees = $total->fetchColumn();

$query = "SELECT employee_id, full_name FROM employees $where ORDER BY full_name ASC LIMIT $per_page OFFSET $offset";
$stmt = $dbh->prepare($query);
$stmt->execute($params);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// All names for dropdown
$all_names = $dbh->query("SELECT full_name FROM employees ORDER BY full_name ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<?php include_once 'includes/sidebar.php'; ?>
<?php include_once 'includes/header.php'; ?>
<?php include_once 'includes/app-bar.php'; ?>


<main class="flex-1 md:ml-64 transition-all">
  <div class="mt-28 px-4 space-y-6">

    <!-- Filter Form -->
    <form id="filter-form" class="bg-white rounded-lg shadow p-4 md:flex md:items-end md:gap-4">
      <div class="flex-1">
        <label class="block text-sm font-semibold">Employee</label>
        <select id="name-select" name="name" class="w-full border rounded p-2"></select>
      </div>

      <div>
        <label class="block text-sm font-semibold">Start Date</label>
        <input type="date" name="start_date" class="border rounded p-2 w-full">
      </div>

      <div>
        <label class="block text-sm font-semibold">End Date</label>
        <input type="date" name="end_date" class="border rounded p-2 w-full">
      </div>

      <div>
        <label class="block text-sm font-semibold invisible">Filter</label>
        <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">Apply</button>
        <button class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-500" onclick="location.reload()">Reset</button>
      </div>
    </form>

    <div id="attendance-container"></div>
  </div>
</main>
<?php include_once 'includes/footer.php'; ?>
<script>
   const form = document.getElementById('filter-form');
  const container = document.getElementById('attendance-container');
  const nameSelect = document.getElementById('name-select');

  const renderTable = (data) => {
    const { employees, dates, current_page, total_pages } = data;

    let html = `<div class="overflow-auto bg-white shadow rounded-lg"><table class="min-w-full text-sm text-left">
    <thead class="bg-gray-600 text-white sticky top-0 z-10"><tr>
    <th class="sticky left-0 bg-gray-500 px-4 py-3 font-bold w-full md:min-w-[220px]">Employee</th>`;

    for (const d of dates) {
      const dt = new Date(d);
      html += `<th class="px-4 py-2 text-center">${dt.getDate()} ${dt.toLocaleString('default', { month: 'short' })}</th>`;
    }

    html += `</tr></thead><tbody class="divide-y">`;

    for (const emp of employees) {
      html += `<tr class="hover:bg-gray-50"><td class="sticky left-0 bg-white px-4 py-2 font-medium">${emp.full_name}</td>`;
      for (const d of dates) {
        const day = emp.days[d];
        if (day.in || day.out) {
          html += `<td class="px-4 py-2 text-xs text-center">
            <div class="text-green-600">In: ${day.in || '--'}</div>
            <div class="text-red-600">Out: ${day.out || '--'}</div>`;
          if (day.lat && day.lng) {
            html += `<a href="location.php?lat=${day.lat}&lng=${day.lng}" target="_blank"
                class="inline-block bg-green-500 text-white px-2 py-0.5 rounded text-xs hover:underline">Location</a>`;
          }
          html += `</td>`;
        } else {
          html += `<td class="px-4 py-2 text-xs text-center">
            <span class="border border-red-500 text-red-500 px-2 py-0.5 rounded italic">Absent</span>
          </td>`;
        }
      }
      html += `</tr>`;
    }

    html += `</tbody></table></div>`;

    // Pagination
    html += `<div class="mt-4 flex justify-center gap-2">`;
    for (let i = 1; i <= total_pages; i++) {
      html += `<button class="page-btn px-3 py-1 rounded ${i === current_page ? 'bg-gray-600 text-white' : 'bg-gray-200'}" data-page="${i}">${i}</button>`;
    }
    html += `</div>`;

    container.innerHTML = html;
  };

  const fetchData = (page = 1) => {
    const fd = new FormData(form);
    const params = new URLSearchParams(fd);
    params.set('page', page);
    fetch(`./api/filter-attendance.php?${params.toString()}`)
      .then(res => res.json())
      .then(renderTable);
  };

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    fetchData(1);
  });

  container.addEventListener('click', (e) => {
    if (e.target.classList.contains('page-btn')) {
      fetchData(e.target.dataset.page);
    }
  });

  const tomSelectInstance = new TomSelect('#name-select', {
    valueField: 'full_name',
    labelField: 'full_name',
    searchField: ['full_name'],
    placeholder: "Type employee name...",
    allowEmptyOption: true,
    load: function(query, callback) {
      if (!query.length) return callback();
      fetch('./api/search-employees.php?q=' + encodeURIComponent(query))
        .then(response => response.json())
        .then(callback).catch(() => callback());
    },
    onClear: () => {
      nameSelect.value = '';
      fetchData(1);
    }
  });

  // Also handle clearing via manual deletion
  nameSelect.addEventListener('change', () => {
    if (!nameSelect.value) {
      fetchData(1);
    }
  });

  // Initial load
  fetchData();
</script>



</body>
</html>
