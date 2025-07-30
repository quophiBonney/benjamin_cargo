
<?php 
include_once 'includes/auth.php';
$allowed_roles = ['admin', 'manager', 'hr'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
?>
<?php include_once 'includes/sidebar.php'; ?>
<?php include_once 'includes/header.php'; ?>
<?php include_once 'includes/app-bar.php';?>
 <main class="flex-1 md:ml-64 px-4 transition-all">
  <div class="bg-white shadow-md rounded-md p-6 mt-24">
    <h3 class="text-2xl font-semibold mb-4">Create Shipments</h3>

    <?php if (!empty($errors)): ?>
    <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-700 rounded">
      <ul class="list-disc list-inside">
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

<form id="addShipmentForm" method="post">
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

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
      <label for="package_pickup_date" class="block text-gray-700">Pickup Date</label>
      <input type="date" id="package_pickup_date" name="package_pickup_date" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Choose date">
    </div>

    <div>
      <label for="package_pickup_time" class="block text-gray-700">Pickup Time</label>
      <input type="time" id="package_pickup_time" name="package_pickup_time" class="bg-gray-100 w-full p-2 border border-gray-300 rounded">
    </div>
    <div>
      <label for="package_carrier" class="block text-gray-700">Carrier</label>
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
      <label for="package_departure_info" class="block text-gray-700">Departure Info</label>
      <textarea id="package_departure_info" name="package_departure_info" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="We are expecting the package to arrive on time"></textarea>
    </div>
     <div class="mt-3">
      <label for="package_description" class="block text-gray-700">Package Description</label>
      <textarea id="package_description" name="package_description" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="A box of IPhones"></textarea>
    </div>
  <div class="mt-5">
    <button id="submitBtn" class="bg-blue-600 text-white px-8 py-2 rounded hover:bg-blue-700">Add Shipment</button>
  </div>
</form>

  </div>
</main>

<!-- Scripts -->
<?php include_once 'includes/footer.php'; ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const addShipmentForm = document.getElementById('addShipmentForm');
  const submitBtn = document.getElementById('submitBtn');

  addShipmentForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    submitBtn.disabled = true;
const formData = new FormData(addShipmentForm);  
  try {
        const response = await fetch('functions/shipment/insert-shipment.php', {
          method: 'POST',
          body: formData
        });

        let result;
        const contentType = response.headers.get("Content-Type");

        if (!response.ok) {
          try {
            result = await response.json();
          } catch {
            throw new Error('Server error, invalid response');
          }
          throw new Error(result.errors ? result.errors.join('<br>') : 'Server error');
        }

        if (contentType && contentType.includes("application/json")) {
          result = await response.json();
        } else {
          throw new Error('Invalid response format');
        }

        if (result.success) {
        Swal.fire({
  icon: 'success',
  title: 'Success',
  text: result.message || 'User created successfully',
  timer: 2000,
  showConfirmButton: false
}).then (() => {
  this.reset();
  window.location.href ="all-shipments.php";
})
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: (result.errors || ['Unknown error occurred.']).map(e => `<div>${e}</div>`).join('')
          });
        }

      } catch (err) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          html: err.message
        });
      } finally {
        submitBtn.disabled = false;
      }

    }, () => {
      Swal.fire({
        icon: 'error',
        title: 'Location Error',
        text: 'Permission denied or failed to get location.'
      });
      submitBtn.disabled = false;
  });
});
</script>
</body>
</html>
