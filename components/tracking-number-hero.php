<div class="tracking-number-bg w-full flex flex-col text-white h-full">
  <div class="filter-container w-full h-full flex flex-col justify-center p-5 md:p-10 lg:p-16 space-y-6 md:m-auto">
    
    <!-- Heading Section -->
    <div class="mt-32 mb-5 md:mb-8 text-left md:text-center">
      <h3 class="text-2xl md:text-3xl lg:text-4xl font-bold">
        Discover The Status of Your Package
      </h3>
      <form id="trackingForm" class="w-full">
        <div class="mt-6 flex justify-center w-full">
            <input
            type="text"
            id="trackingNumber"
            name="tracking_number"
            placeholder="Enter your tracking number"
            class="w-full max-w-lg p-4 rounded-l-lg border-2 border-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-600 text-black"
            required
            />
            <button
            type="submit"
            class="bg-indigo-900 hover:bg-indigo-800 text-white px-6 py-4 rounded-r-lg font-semibold transition duration-300"
            >
            Track Now
            </button>
        </div>
      </form>
    </div>
  </div>
</div>
<div id="trackingResult" class="mt-6 text-center"></div>
<script>
document.getElementById('trackingForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const trackingNumber = document.getElementById('trackingNumber').value.trim();
    const resultDiv = document.getElementById('trackingResult');

    if (!trackingNumber) {
        resultDiv.innerHTML = '<p class="text-red-500">Please enter a tracking number.</p>';
        return;
    }

    resultDiv.innerHTML = '<p class="text-yellow-500">Searching...</p>';

    try {
        const response = await fetch(`./admin/functions/shipment/get-tracking-number.php?tracking_number=${encodeURIComponent(trackingNumber)}`);
        const data = await response.json();

        if (data.success) {
           Swal.fire({
                icon: 'success',
                title: 'Tracking Number Found!',
                html: `<strong>Tracking Number</strong>: ${data.tracking.tracking_number}<br/><strong>Date Received At Warehouse</strong>: ${data.tracking.date_received}`,
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
            });
        }
    } catch (error) {
        resultDiv.innerHTML = '<p class="text-red-500">An error occurred while tracking your shipment.</p>';
        console.error('Error:', error);
    }
});
</script>
