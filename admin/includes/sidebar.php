<?php
include_once 'includes/auth.php';
$role = $_SESSION['role'] ?? '';
?>
<aside id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-gradient-to-b from-gray-900 via-gray-800 to-gray-700 text-white z-40 transform transition-transform duration-300 ease-in-out md:translate-x-0 -translate-x-full">
  <div class="flex items-center justify-center p-6 border-b border-gray-700">
    <h1 class="text-xl md:text-2xl font-bold">Benjamin Cargo</h1>
  </div>

  <nav class="px-6 py-4 space-y-4">
    <?php
    $icons = [
      "Dashboard" => "fa-gauge-high",
      "Staffs Dashboard" => "fa-gauge-high",
       "Shipment" => "fa-gear",
      "Employee" => "fa-address-card",
      "Announcement" => "fa-bullhorn",
      "Attendance" => "fa-solid fa-clipboard-user", 
      "Finance" => "fa-solid fa-money-bill-1",
    ];

    $menus = [
      "Dashboard" => [
        "links" => [
          "dashboard.php" => ["text" => "Admin Dashboard", "roles" => ["admin", "manager"]],
          "staffs-dashboard.php" => ["text" => "Staff Dashboard", "roles" => ["admin", "manager", "receptionist", "staff", "cleaner", "hr"]],
        ],
        "roles" => ["admin", "manager", "hr", "receptionist", "staff", "cleaner"]
      ],
         "Shipment" => [
        "links" => [
          "create-shipment.php" => ["text" => "Create Shipment", "roles" => ["admin", "manager", "hr"]],
          "all-shipments.php" => ["text" => "View All Shipments", "roles" => ["admin", "manager", "hr"]],
        ],
        "roles" => ["admin", "manager", "hr", "sales"]
      ],
        "Attendance" => [
        "links" => [
           "mark-attendance.php" => ["text" => "Mark Attendance", "roles" => ["admin", "manager", "receptionist", "staff", "cleaner"]],
           "view-all-attendance.php" => ["text" => "View Attendance", "roles" => ["admin", "manager", "cleaner", "staff"]],
        ],
        "roles" => ["admin", "manager", "receptionist", "staff", "cleaner"]
      ],
      "Employee" => [
        "links" => [
          "add-employee.php" => ["text" => "Add Employee", "roles" => ["admin", "manager"]],
          "all-employees.php" => ["text" => "View All Employees", "roles" => ["admin", "manager"]],
           "employee-leave-submission.php" => ["text" => "Request For Leave", "roles" => ["admin", "manager", "staff", "hr", "cleaner", "receptionist"]],
           "leave-applications.php" => ["text" => "All Leave Applications", "roles" => ["admin", "manager","hr", "staff", "receptionist", "cleaner"]],
        ],
        "roles" => ["admin", "manager", "hr", "receptionist", "staff", "cleaner"]
      ],
       "Finance" => [
        "links" => [
          "new-expense.php" => ["text" => "Add Expense", "roles" => ["admin", "manager", "hr"]],
          "all-expenses.php" => ["text" => "View All Expenses", "roles" => ["admin", "manager", "hr"]],
        ],
        "roles" => ["admin", "manager", "hr"]
      ],
      "Announcement" => [
        "links" => [
          "post-announcement.php" => ["text" => "Post Announcement", "roles" => ["admin", "manager"]],
          "all-announcement.php" => ["text" => "All Announcements", "roles" => ["admin", "manager"]],
        ],
        "roles" => ["admin", "manager", "receptionist", "staff", "cleaner"]
      ]
    ];

    foreach ($menus as $menuTitle => $data) {
      if (!in_array($role, $data['roles'])) continue;

      echo '<div class="relative">';
      echo '<button onclick="toggleMenu(this)" class="flex justify-between items-center w-full px-4 py-2 text-left text-gray-300 hover:bg-gray-600 rounded">';
      echo '<span class="flex items-center gap-2"><i class="fa-solid ' . $icons[$menuTitle] . '"></i> ' . $menuTitle . '</span>';
      echo '<svg class="w-4 h-4 transition-transform transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">';
      echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></button>';

      echo '<div class="hidden mt-2 bg-white text-gray-900 rounded-md shadow ring-1 ring-black ring-opacity-5 p-2 w-full">';

      foreach ($data['links'] as $href => $item) {
        if (is_array($item)) {
          $text = $item['text'];
          $allowed = $item['roles'] ?? ['admin'];
        } else {
          $text = $item;
          $allowed = $data['roles'];
        }

        if (in_array($role, $allowed)) {
          echo '<a href="' . $href . '" class="block px-5 py-2 text-sm hover:bg-gray-100">' . $text . '</a>';
        }
      }

      echo '</div></div>';
    }

    ?>
   <div>
      <form method="POST" action="includes/logout.php">
      <button type="submit" class="w-full flex items-center gap-3 hover:bg-gray-700 px-3 py-2 rounded transition">
        <i class="fa-solid fa-right-from-bracket"></i> Sign Out
      </button>
    </form>
  </div>
  </nav>
</aside>

<script>
  function toggleMenu(button) {
    const content = button.nextElementSibling;
    content.classList.toggle('hidden');
  }
</script>
