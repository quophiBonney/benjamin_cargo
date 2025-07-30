<?php
// db connection
include_once './admin/includes/dbconnection.php';
header('Content-Type: text/html; charset=utf-8');
?>
<div class="w-full tracking-hero-bg h-full flex flex-col justify-center items-center">
    <div class="px-5 md:px-16 mt-40 md:mt-48 lg:max-w-5xl space-y-3 text-white text-center mb-10">
      <h1 class="text-2xl md:text-5xl font-bold uppercase">Track Your Package</h1>
      <p class="text-sm md:text-md lg:text-lg">
        The seamless way to track your package and stay updated on its status. Just enter your tracking number and there you go.
      </p>
<form id="tracking-form" class="w-full">
  <div class="w-full bg-white rounded-md p-1.5 flex items-center gap-2 border border-gray-200 shadow-md">
    <input 
      type="text" 
      name="tracking_number"
      id="tracking_number"
      placeholder="Enter tracking number"
      required
      class="w-full p-3 text-black focus:outline-none rounded-md"
    />
    <button 
      type="submit"
      class="bg-blue-900 hover:bg-blue-800 text-white px-5 py-2 rounded-md transition-all"
    >
      Track
    </button>
  </div>
</form>
    <!-- Shipment Result -->
   <div class="w-full grid grid-col-1 md:grid-cols-2 gap-4">
     <div id="shipment-result" class="text-white"></div>

    <!-- Shipment Timeline -->
    <div id="shipment-timeline" class="text-white"></div>
   </div>
  </div>
</div>
<script>
  document.getElementById('tracking-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const trackingNumber = document.getElementById('tracking_number').value.trim();
    if (!trackingNumber) return;

    try {
      const response = await fetch('components/track-shipment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ tracking_number: trackingNumber })
      });

      const result = await response.json();

      if (result.status === 'error') {
        Swal.fire({
          icon: 'error',
          title: 'Tracking Failed',
          text: result.message,
          confirmButtonColor: '#3085d6'
        });
      } else if (result.status === 'success') {
        const data = result.data;
        const timelineData = result.timeline || [];

        // Shipment info
        const shipmentHtml = `
          <div class="bg-white text-gray-900 p-6 rounded-md shadow-lg w-full">
            <h2 class="text-xl font-bold mb-4">Shipment Details</h2>
            <ul class="space-y-2">
              <li><strong>Tracking Number:</strong> ${data.tracking_number}</li>
              <li><strong>Sender:</strong> ${data.sender_name} (${data.sender_city}, ${data.sender_country})</li>
              <li><strong>Receiver:</strong> ${data.receiver_name} (${data.receiver_city}, ${data.receiver_country})</li>
            </ul>
          </div>
        `;
        document.getElementById('shipment-result').innerHTML = shipmentHtml;

        // Define timeline stages with lowercased keys for comparison
        const stages = [
          { key: "at loading", icon: "⏳", label: "Loading Port" },
          { key: "shipped", icon: "✈️", label: "Shipped" },
          { key: "transit", icon: "🚚", label: "In Transit" },
          { key: "arrived", icon: "📦", label: "Arrived in Ghana" },
          { key: "picked up", icon: "✅", label: "Picked Up" }
        ];

        // Normalize tracking_history status values
        const completedStatuses = timelineData.map(item => item.status.toLowerCase());

        // Find the index of the most recent status in the stages list
        let currentStageIndex = -1;
        for (let i = stages.length - 1; i >= 0; i--) {
          if (completedStatuses.includes(stages[i].key)) {
            currentStageIndex = i;
            break;
          }
        }

        // Log to help debugging
        console.log("TimelineData:", timelineData);
        console.log("CurrentStageIndex:", currentStageIndex);

        // Build the timeline UI
        const timelineHtml = stages.map((stage, index) => {
          const matchedItem = timelineData.find(item =>
            item.status.toLowerCase().includes(stage.key)
          );

          let stateClass = '';
          let badgeStyle = '';
          let detailHtml = '';

          if (index < currentStageIndex) {
            stateClass = 'border-green-600 text-green-700';
            badgeStyle = 'bg-green-600 text-white';
            if (matchedItem) {
              detailHtml = `
                <p class="text-xs text-gray-600">${matchedItem.description || ''}</p>
                <p class="text-xs text-gray-500">${matchedItem.date_time ? new Date(matchedItem.date_time).toLocaleString() : ''}</p>
              `;
            }
          } else if (index === currentStageIndex) {
            stateClass = 'border-blue-600 text-blue-800 font-bold';
            badgeStyle = 'bg-blue-600 text-white animate-pulse';
            if (matchedItem) {
              detailHtml = `
                <p class="text-xs text-blue-700">${matchedItem.description || ''}</p>
                <p class="text-xs text-blue-600">${matchedItem.date_time ? new Date(matchedItem.date_time).toLocaleString() : ''}</p>
              `;
            }
          } else {
            stateClass = 'border-gray-300 text-gray-400';
            badgeStyle = 'bg-gray-200 text-gray-400';
            detailHtml = `<p class="text-xs italic text-gray-400">Pending</p>`;
          }

          return `
            <div class="relative pl-8 pb-6 border-l-2 ${stateClass}">
              <div class="absolute -left-3 top-0 w-6 h-6 rounded-full border-2 flex items-center justify-center text-xs ${badgeStyle}">
                ${stage.icon}
              </div>
              <p class="text-sm">${stage.label}</p>
              ${detailHtml}
            </div>
          `;
        }).join('');

        document.getElementById('shipment-timeline').innerHTML = `
          <div class="bg-white text-gray-900 p-6 rounded-md shadow-lg">
            <h2 class="text-xl font-bold mb-4">Tracking Timeline</h2>
            <div class="space-y-6">${timelineHtml}</div>
          </div>
        `;
      }
    } catch (error) {
      console.error('JS Error:', error);
      Swal.fire({
        icon: 'error',
        title: 'Unexpected Error',
        text: 'Something went wrong. Please try again later.',
      });
    }
  });
</script>
</body>
</html>
