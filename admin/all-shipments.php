<?php 
include_once 'includes/auth.php';
$allowed_roles = ['admin', 'manager', 'hr'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    header("Location: staffs-dashboard.php");
    exit;
}
include_once 'includes/dbconnection.php';
$query = "SELECT * FROM shipments ORDER BY shipment_id ASC";
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
          Showing <span id="startCount">1</span> to <span id="endCount">50</span> of
          <span id="totalCount">0</span> shipments
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
    <a href="create-shipment.php">Add New Shipment</a>
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
             <th class="py-3 px-4">Tracking No.</th>
            <th class="py-3 px-4 min-w-[150px]">Receiver Name</th>
            <th class="py-3 px-4 min-w-[150px]">Receiver Phone</th>
            <th class="py-3 px-4">Package</th>
            <th class="py-3 px-4">Weight</th>
            <th class="py-3 px-4">Height</th>
            <th class="py-3 px-4">Length</th>
            <th class="py-3 px-4">Status</th>
            <th class="py-3 px-4 no-print">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php foreach ($employees as $employee): ?>
          <tr class="even:bg-gray-50 hover:bg-gray-100 transition duration-200">
             <td class="py-2 px-4"><?= htmlspecialchars($employee['tracking_number']) ?></td>
            <td class="py-2 px-4"><?= htmlspecialchars($employee['receiver_name']) ?></td>
            <td class="py-2 px-4"><?= htmlspecialchars($employee['receiver_phone']) ?></td>
            <td class="py-2 px-4"><?= htmlspecialchars($employee['package_name']) ?></td>
            <td class="py-2 px-4"><?= htmlspecialchars($employee['package_weight']) ?></td>
            <!-- <td class="py-2 px-4"><?= htmlspecialchars($employee['package_length']) ?></td> -->
            <td class="py-2 px-4"><?= htmlspecialchars($employee['package_height']) ?></td>
            <td class="py-2 px-4"><?= htmlspecialchars($employee['package_quantity']) ?></td>
             <td class="py-2 px-4"><?= htmlspecialchars($employee['status']) ?></td>
            <td class="py-2 flex gap-1 px-4 space-x-1 no-print">
              <button class="bg-gray-500 text-white p-2 rounded hover:bg-gray-600 hover:cursor-pointer transition edit-btn" data-employee='<?= json_encode($employee) ?>'><i class="fas fa-edit"></i></button>
              <!-- View Button -->
               <button class="hover:cursor-pointer bg-green-500 text-white p-2 rounded hover:bg-green-600 transition delete-btn" data-id="<?= $employee['shipment_id']; ?>"><i class="fa fa-eye" aria-hidden="true"></i></button>
                <a href="fpdf/shipment-invoice.php?id=<?= $employee['shipment_id'] ?>" target="_blank" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"><i class="fa-solid fa-file-invoice"></i></a>
               <!-- Track History Button -->
<button class="hover:cursor-pointer bg-yellow-600 text-white p-2 rounded hover:bg-yellow-700 transition track-btn"
    data-shipment='<?= json_encode($employee, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
    <i class="fa-solid fa-truck-fast"></i>
</button>
               <!-- Delete Button -->
              <button class="hover:cursor-pointer bg-red-500 text-white p-2 rounded hover:bg-red-600 transition delete-btn" data-id="<?= $employee['shipment_id']; ?>"><i class="fa fa-trash" aria-hidden="true"></i></button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php else: ?>
    <div class="mt-24 bg-white shadow-md rounded-md p-6 px-4 text-center text-gray-600">
      No shipment found.
    </div>
  <?php endif; ?>
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
document.addEventListener('DOMContentLoaded', function () {

  // Search filter
  document.getElementById('searchInput').addEventListener('input', function () {
    const searchValue = this.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
      const fullName = row.children[0].textContent.toLowerCase();
      const ghanaCardNumber = row.children[1].textContent.toLowerCase();
      row.style.display = fullName.includes(searchValue) || ghanaCardNumber.includes(searchValue) ? '' : 'none';
    });
  });

  // Modal control
  window.closeModal = function () {
    document.getElementById('editModal').classList.add('hidden');
  }

 window.openEditModal = function (shipment) {
  document.getElementById('editModal').classList.remove('hidden');
  document.getElementById('tracking_number').value = shipment.tracking_number;
  document.getElementById('sender_name').value = shipment.sender_name;
  document.getElementById('sender_city').value = shipment.sender_city;
  document.getElementById('sender_country').value = shipment.sender_country;
  document.getElementById('receiver_name').value = shipment.receiver_name;
  document.getElementById('receiver_city').value = shipment.receiver_city;
  document.getElementById('receiver_country').value = shipment.receiver_country;
  document.getElementById('receiver_phone').value = shipment.receiver_phone;
  document.getElementById('package_name').value = shipment.package_name;
  document.getElementById('package_weight').value = shipment.package_weight;
  document.getElementById('package_len').value = shipment.package_len;
  document.getElementById('package_height').value = shipment.package_height;
  document.getElementById('package_quantity').value = shipment.package_quantity;
  document.getElementById('package_payment_method').value = shipment.package_payment_method;
  document.getElementById('package_expected_delivery_date').value = shipment.package_expected_delivery_date;
  document.getElementById('package_pickup_date').value = shipment.package_pickup_date;
  document.getElementById('carrier').value = shipment.package_carrier;
  document.getElementById('package_type_of_shipment').value = shipment.package_type_of_shipment;
  document.getElementById('origin').value = shipment.origin;
  document.getElementById('destination').value = shipment.destination;
  document.getElementById('package_description').value = shipment.package_description;

  // Append shipment ID hidden field if not already in form
  if (!document.getElementById('shipment_id')) {
    const hiddenId = document.createElement('input');
    hiddenId.type = 'hidden';
    hiddenId.name = 'shipment_id';
    hiddenId.id = 'shipment_id';
    hiddenId.value = shipment.shipment_id;
    document.getElementById('updateShipmentForm').appendChild(hiddenId);
  } else {
    document.getElementById('shipment_id').value = shipment.shipment_id;
  }

  // Change submit button text to indicate update
  document.getElementById('submitBtn').textContent = 'Update Shipment';
};


document.querySelectorAll('.edit-btn').forEach(button => {
  button.addEventListener('click', function () {
    const shipment = JSON.parse(this.dataset.employee); // correct: `employee` holds shipment data
    window.openEditModal(shipment);
  });
});


  // Tracking Modal
  window.openTrackingModal = function (shipment) {
    document.getElementById('editTrackingModal').classList.remove('hidden');
     document.getElementById('trackingId').value = shipment.shipment_id;
  document.getElementById('location').value = shipment.location || '';
  document.getElementById('description').value = shipment.description || '';
  document.getElementById('status').value = shipment.status || '';
  }

  window.closeTrackModal = function () {
    document.getElementById('editTrackingModal').classList.add('hidden');
  }

 document.querySelectorAll('.track-btn').forEach(button => {
  button.addEventListener('click', async function () {
    const shipment = JSON.parse(this.dataset.shipment);
    // Always open the modal with shipment ID
    window.openTrackingModal({
      shipment_id: shipment.shipment_id,
      location: '',
      description: '',
      status: ''
    });
    
    try {
      const response = await fetch('./functions/shipment/fetch-tracking-history.php?id=' + encodeURIComponent(shipment.shipment_id));
      const data = await response.json();

      if (data.success && data.tracking) {
        // Update modal with fetched data
        document.getElementById('location').value = data.tracking.location || '';
        document.getElementById('description').value = data.tracking.description || '';
        document.getElementById('status').value = data.tracking.status || '';
      }
    } catch (error) {
      console.log('No existing tracking history');
    }
  });
});

  // Submit tracking form
  document.getElementById('editTrackingHistoryForm').addEventListener('submit', async function (event) {
    event.preventDefault();
    const formData = new FormData(this);

    try {
      const res = await fetch('./functions/shipment/update-tracking-history.php', {
        method: 'POST',
        body: formData
      });

      const result = await res.json();

      if (result.success) {
        Swal.fire({
          icon: 'success',
          title: 'Tracking Updated',
          text: 'Tracking history updated successfully',
          timer: 2000,
          timerProgressBar: true
        }).then(() => {
          window.closeTrackModal();
          window.lcation.reload()
        });
      } else {
        Swal.fire('Error', result.message || 'Could not update tracking info', 'error');
      }
    } catch (error) {
      Swal.fire('Error', 'An error occurred while updating.', 'error');
    }
  });

  // Delete employee
  document.querySelectorAll('.delete-btn').forEach(button => {
    button.addEventListener('click', function () {
      const shipmentID = this.dataset.id;
      const row = this.closest('tr');
      Swal.fire({
        title: 'Are you sure?',
        text: 'This shipment will be permanently deleted.',
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
            body: 'id=' + encodeURIComponent(shipmentID)
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              row.remove();
              Swal.fire('Deleted!', 'The shipment has been deleted.', 'success');
            } else {
              Swal.fire('Error!', data.message || 'Failed to delete shipment.', 'error');
            }
          })
          .catch(() => {
            Swal.fire('Error!', 'Something went wrong.', 'error');
          });
        }
      });
    });
  });

  // Submit employee form
document.getElementById('updateShipmentForm').addEventListener('submit', async function (event) {
  event.preventDefault();
  const formData = new FormData(this);

  try {
    const res = await fetch('./functions/shipment/update-shipment.php', {
      method: 'POST',
      body: formData
    });

    const result = await res.json();

    if (result.success) {
      Swal.fire({
        icon: 'success',
        title: 'Shipment Updated',
        text: 'Shipment information updated successfully',
        timer: 2500,
        timerProgressBar: true
      }).then(() => {
        location.reload();
      });
    } else {
      Swal.fire('Validation Error', result.message || 'Failed to update shipment.', 'error');
    }
  } catch (err) {
    Swal.fire('Network Error', 'Something went wrong.', 'error');
  }
});


  // Print logic
  window.printTable = function () {
    const printArea = document.querySelector('.print-area');
    const styles = `
      <style>
        @media print {
          body { font-family: sans-serif; }
          .no-print { display: none !important; }
          .print-area { padding: 30px; }
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
          footer {
            position: fixed;
            bottom: 0;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
          }
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
    win.document.write(printArea.innerHTML);
    win.document.write('</div><footer>Page</footer></body></html>');
    win.document.close();
    win.focus();
    win.print();
    win.close();
  }

  // Download CSV
  document.getElementById('downloadCSV').addEventListener('click', function () {
    const table = document.getElementById('employeesTable');
    const rows = table.querySelectorAll('thead tr, tbody tr');
    let csvContent = "";

    // Get action column indexes
    const actionIndexes = [];
    const headerCols = rows[0].querySelectorAll('th');
    headerCols.forEach((th, i) => {
      const text = th.textContent.trim().toLowerCase();
      if (text === 'action' || text.includes('actions')) {
        actionIndexes.push(i);
      }
    });

    rows.forEach(row => {
      const cols = row.querySelectorAll('th, td');
      const rowData = Array.from(cols).map((col, i) => {
        if (actionIndexes.includes(i)) return null;
        let text = col.textContent.trim().replace(/"/g, '""');
        if (/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/.test(text)) {
          const date = new Date(text);
          if (!isNaN(date)) {
            text = date.toLocaleString();
          }
        }
        return `"${text}"`;
      }).filter(Boolean);

      csvContent += rowData.join(",") + "\n";
    });

    const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    link.setAttribute("href", URL.createObjectURL(blob));
    link.setAttribute("download", "all-shipments.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  });

});
</script>

</body>
</html>