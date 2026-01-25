<?php
session_start();
include_once __DIR__ . '../../includes/dbconnection.php';

if (!isset($_SESSION['casaid'])) {
    header("Location: login.php");
    exit;
}

$customer_id = $_SESSION['casaid'];

// ✅ Check if OTP was already verified in DB
$stmt = $dbh->prepare("SELECT otp_verified FROM customers WHERE customer_id = :customer_id");
$stmt->execute([':customer_id' => $customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer || $customer['otp_verified'] != 1) {
    // If OTP not verified, redirect
    header("Location: verify-otp.php");
    exit;
}

// ✅ Fetch customer’s shipments
$stmt = $dbh->prepare("
    SELECT * 
    FROM shipping_manifest 
    WHERE customer_id = :customer_id
");
$stmt->execute([':customer_id' => $customer_id]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$records) {
    echo "<script>alert('No shipping records found for your account.')</script>";
    echo "<script>window.location.href='login.php'</script>";
    die;
}
?>

<?php include_once 'includes/sidebar.php'; ?>
<?php include_once 'includes/header.php'; ?>

<!-- Main Content -->
<main class="flex-1 md:ml-64 px-4 transition-all">
    <?php include_once 'includes/app-bar.php'; ?>
    <div class="grid grid-cols-1 space-y-3 mt-32 md:space-x-2 mb-5">
        <!-- Shipping Table -->
        <div class="overflow-x-auto bg-white rounded">
            <table class="w-full text-sm text-left text-gray-700 border border-gray-200 overflow-hidden">
                <thead class="bg-gray-700 text-white uppercase text-xs font-semibold border-b border-gray-300">
                    <tr>
                        <th class="px-4 py-2 text-left border-b min-w-[100px]">Code</th>
                         <th class="px-4 py-2 text-left border-b min-w-[170px]">Receipt No.</th>
                        <th class="px-4 py-2 text-left border-b min-w-[200px]">Package Name</th>
                        <th class="px-4 py-2 text-left border-b">Quantity</th>
                        <th class="px-4 py-2 text-left border-b">CBM</th>
                         <th class="px-4 py-2 text-left border-b min-w-[170px]">Date of Arrival</th>
                         <th class="px-4 py-2 text-left border-b min-w-[170px]">Date Of Offloading</th>
                        <th class="px-4 py-2 text-left border-b">Invoice</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($records as $data): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border-b"><?= htmlspecialchars($data['shipping_mark']) ?></td>
                            <td class="px-4 py-2 border-b"><?= htmlspecialchars($data['receipt_number']) ?></td>
                            <td class="px-4 py-2 border-b capitalize"><?= htmlspecialchars($data['package_name']) ?></td>
                            <td class="px-4 py-2 border-b"><?= htmlspecialchars($data['number_of_pieces']) ?></td>
                            <td class="px-4 py-2 border-b"><?= htmlspecialchars($data['volume_cbm']) ?></td>
                             <td class="px-4 py-2 border-b"><?= htmlspecialchars($data['estimated_time_of_arrival']) ?></td>
                             <td class="px-4 py-2 border-b"><?= htmlspecialchars($data['estimated_time_of_offloading']) ?></td>
                            <td class="py-2 flex gap-1 px-4 space-x-1 no-print">
                                <!-- Edit Button -->
                                 <a class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600" href="./fpdf/shipment-invoice.php?customer_id=<?= $data['customer_id'] ?>" target="_blank">
                                   <i class="fa-solid fa-file-invoice"></i>
                    </a>
                                <button class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600" data-tracking="<?= htmlspecialchars($data['shipping_mark']) ?>" data-eta="<?= htmlspecialchars($data['estimated_time_of_arrival']) ?>" onclick="fetchShipment(this.dataset.tracking, this.dataset.eta)">
                                    <i class="fa-solid fa-clock"></i> 
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
 </div>
        <!-- Tracking History -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-10 h-full">
  <!-- Left card -->
  <div class="w-full bg-white rounded-md p-3">
      <div id="shipment-result"></div>
      <div id="shipment-timeline"></div>
  </div>

  <!-- Right card -->
  <div class="bg-white rounded-md p-3 h-full flex">
      <div class="swiper w-full">
          <div class="swiper-wrapper">
              <div class="swiper-slide">
                  <img src="../assets/announcement1.jpg" class="w-full rounded-md"/>
              </div>
          </div>
      </div>
  </div>
</div>
</main>

<?php include_once 'includes/footer.php'; ?>

<script>


async function fetchShipment(trackingNumber, eta) {
    console.log('fetchShipment called with:', trackingNumber, 'ETA:', eta);
    try {
        const response = await fetch('../components/track-shipment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ tracking_number: trackingNumber, eta: eta })
        });
        console.log('Response status:', response.status);
        const result = await response.json();
        console.log('Result:', result);

        if (result.status === 'error') {
            console.log('Error message:', result.message);
            Swal.fire({
                icon: 'error',
                title: 'Tracking Failed',
                text: result.message,
                confirmButtonColor: '#031186ff'
            });
        } else if (result.status === 'success') {
            console.log('Success, processing timeline');
            const timelineData = result.timeline || [];
            console.log('timelineData:', timelineData);

            if (timelineData.length === 0) {
                console.log('No timeline data, showing message');
                document.getElementById('shipment-timeline').innerHTML = '<div class="w-full text-gray-900 bg-white p-5 rounded"><h2 class="text-xl font-bold mb-4">Tracking Timeline</h2><p class="text-gray-600">No tracking history available for this shipment.</p></div>';
                return;
            }

            // ✅ Define tracking stages
            const stages = [
                { key: "shipments received", icon: "📦", label: "Shipments Received" },
                { key: "shipments in transit", icon: "🚢", label: "Shipments In Transit" },
                { key: "shipments has arrived at the port undergoing clearance", icon: "⚓", label: "Shipments has arrived at the Port undergoing clearance" },
                { key: "shipments arrived at benjamin cargo logistics warehouse", icon: "🏢", label: "Shipments At Benjamin Cargo & Logistics Warehouse" },
                { key: "shipments picked up", icon: "✅", label: "Shipments Picked Up" }
            ];

            if (!Array.isArray(timelineData)) {
                console.error("timelineData is not an array:", timelineData);
                return;
            }

            // Normalize statuses to lowercase
            const completedStatuses = timelineData
                .map(item => (item.status ? item.status.toLowerCase() : null))
                .filter(Boolean);
            let currentStageIndex = -1;
            for (let i = stages.length - 1; i >= 0; i--) {
                if (completedStatuses.includes(stages[i].key.toLowerCase())) {
                    currentStageIndex = i;
                    break;
                }
            }

            // ✅ Build timeline HTML
            const timelineHtml = stages.map((stage, index) => {
                const matchedItem = timelineData.find(
                    item => item.status && item.status.toLowerCase() === stage.key.toLowerCase()
                );

                let stateClass = '';
                let badgeStyle = '';
                let detailHtml = '';

                if (index < currentStageIndex) {
                    stateClass = 'border-green-600 text-green-700';
                    badgeStyle = 'bg-green-600 text-white';
                    if (matchedItem) {
                        detailHtml = '<p class="text-xs text-gray-600">' + (matchedItem.description || '') + '</p><p class="text-xs text-gray-500">' + (matchedItem.date ? new Date(matchedItem.date).toLocaleDateString() : '') + '</p>';
                    }
                } else if (index === currentStageIndex) {
                    stateClass = 'border-indigo-600 text-indigo-800 font-bold';
                    badgeStyle = 'bg-indigo-600 text-white animate-pulse';
                    if (matchedItem) {
                        detailHtml = '<p class="text-xs text-indigo-700">' + (matchedItem.description || '') + '</p><p class="text-xs text-indigo-600">' + (matchedItem.date ? new Date(matchedItem.date).toLocaleDateString() : '') + '</p>';
                    }
                } else {
                    stateClass = 'border-gray-300 text-gray-400';
                    badgeStyle = 'bg-gray-200 text-gray-400';
                    detailHtml = '<p class="text-xs italic text-gray-400">Pending</p>';
                }

                return '<div class="relative pl-8 pb-6 border-l-2 ' + stateClass + '"><div class="absolute -left-3 top-0 w-6 h-6 rounded-full border-2 flex items-center justify-center text-xs ' + badgeStyle + '">' + stage.icon + '</div><p class="text-sm">' + stage.label + '</p>' + detailHtml + '</div>';
            }).join('');

            // ✅ Inject into page
            document.getElementById('shipment-timeline').innerHTML = '<div class="w-full text-gray-900 bg-white p-5 rounded"><h2 class="text-xl font-bold mb-4">Tracking Timeline</h2><div class="space-y-6">' + timelineHtml + '</div></div>';
        }
    } catch (error) {
        console.error('JS Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Unexpected Error',
            text: 'Something went wrong. Please try again later.',
        });
    }
}

document.addEventListener("DOMContentLoaded", () => {
    // Auto-load first record’s tracking
    const firstRow = document.querySelector("tbody tr");
    if (firstRow) {
        const trackingNumber = firstRow.querySelector("td").innerText.trim();
        const eta = firstRow.querySelector("button[data-tracking]").dataset.eta;
        fetchShipment(trackingNumber, eta);
    }
});
  // ✅ Review Reminder Notification
setTimeout(() => {
    Swal.fire({
        title: '💬 We Value Your Feedback!',
        text: 'Please take a moment to leave us a review about your shipment experience.',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Leave a Review',
        cancelButtonText: 'Maybe Later',
        confirmButtonColor: '#0143d1ff', // Tailwind blue
        cancelButtonColor: '#6b7280',  // Tailwind gray
        background: '#f9fafb',
        customClass: {
            popup: 'animate__animated animate__bounceIn'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'customer-review.php';
        }
    });
}, 30000); // ⏱️ 30 seconds after page load

</script>
