<?php 
include_once 'includes/auth.php';
$allowed_roles = ['admin', 'manager', 'receptionist'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    header("Location: staffs-dashboard.php");
    exit;
}
?>
<?php include_once 'includes/sidebar.php'; ?>
<?php include_once 'includes/header.php'; ?>
<?php include_once 'includes/app-bar.php';?>
 <main class="flex-1 md:ml-64 px-4 transition-all">
<div class="bg-white shadow-md rounded-md p-6 mt-24">
    <h3 class="text-2xl font-semibold mb-4">Add A Room</h3>
    <?php if (!empty($errors)): ?>
    <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-700 rounded">
      <ul class="list-disc list-inside">
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <!-- <form method="post" action="add-room.php" enctype="multipart/form-data"> -->
    <form id="addRoomForm" enctype="multipart/form-data">
      <div class="mb-3">
        <label for="roomName" class="block text-gray-700">Room Name</label>
        <input type="text" id="roomName" name="roomName" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter room name" value="">
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
          <label for="roomNumber" class="block text-gray-700">Room Number</label>
          <input type="number" id="roomNumber" name="roomNumber" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter room number">
        </div>
        <div>
          <label for="numberOfRooms" class="block text-gray-700">Number of Rooms</label>
          <input type="number" id="numberOfRooms" name="numberOfRooms" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter number of room(s)">
        </div>
        <div>
          <label for="numberOfAC" class="block text-gray-700">Number of AC</label>
          <input type="number" id="numberOfAC" name="numberOfAC" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter number of AC">
        </div>
        <div>
          <label for="numberOfBathroom" class="block text-gray-700">Number of Bathroom</label>
          <input type="number" id="numberOfBathroom" name="numberOfBathroom" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter number of bathroom">
        </div>
        <div>
          <label for="numberOfFan" class="block text-gray-700">Number of Fan</label>
          <input type="number" id="numberOfFan" name="numberOfFan" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Enter number of fan">
        </div>
        <div>
          <label for="price" class="block text-gray-700">Price Per Night</label>
          <input type="number" id="price" name="price" class="bg-gray-100 w-full p-2 border border-gray-300 rounded" placeholder="Price per night">
        </div>
      </div>
        <div class="mt-4">
          <div id="imageDropzone" class="dropzone border border-dashed border-gray-300 rounded p-4 bg-gray-100"></div>
        </div>
   
      <div class="mt-5">
        <button type="submit" name="submit" id="submitBtn" class="bg-gray-800 text-white px-6 py-2 rounded hover:bg-gray-700 hover:cursor-pointer">Add Room</button>
      </div>
    </form>
  </div>
</main>

<!-- Scripts -->
<?php include_once 'includes/footer.php'; ?>
<script>
  Dropzone.autoDiscover = false;
const myDropzone = new Dropzone("#imageDropzone", {
  url: "./functions/upload-room-image.php", // dummy, because we'll manually append the file
  autoProcessQueue: false,
  maxFiles: 5,
  acceptedFiles: "image/*",
  addRemoveLinks: true
});

document.getElementById('addRoomForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const submitBtn = document.getElementById('submitBtn');
  submitBtn.disabled = true;

  const formData = new FormData(this);
  if (myDropzone.files.length > 0) {
    formData.append('image', myDropzone.files[0]);
  }

  try {
    const response = await fetch('./functions/room/add-new-room.php', {
      method: 'POST',
      body: formData
    });

    let result;
    const contentType = response.headers.get("Content-Type");

    if (!response.ok) {
      // Try to extract the body if it’s still JSON error
      try {
        result = await response.json();
      } catch {
        throw new Error('Server error, invalid response');
      }
      throw new Error(result.errors ? result.errors.join('<br>') : 'Server error');
    }

    // Check if the response is JSON
    if (contentType && contentType.includes("application/json")) {
      result = await response.json();
    } else {
      throw new Error('Invalid response format');
    }

    if (result.success) {
      Swal.fire({
        icon: 'success',
        title: 'Room Added',
        text: 'Room added successfully!',
        timer: 2000,
        showConfirmButton: false
      });
      this.reset();
      myDropzone.removeAllFiles();
      window.location.href = "all-rooms.php";
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        html: (result.errors || ['Unknown error occurred.']).map(e => `<div>${e}</div>`).join('')
      });
      return;
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
});
</script>
</body>
</html>
