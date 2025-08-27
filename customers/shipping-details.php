<?php
session_start();
 include_once __DIR__ . '../../includes/dbconnection.php';

if (!isset($_SESSION['casaid'])) {
    header("Location: login.php");
    exit;
}

$customer_id = $_SESSION['casaid'];
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
    <div class="grid grid-cols-1 space-y-3 md:space-y-0 md:grid-cols-3 mt-32 md:space-x-2 mb-5">

        <!-- Shipping Table -->
        <div class="col-span-2 overflow-x-auto bg-white rounded">
            <table class="w-full text-sm text-left text-gray-700 border border-gray-200 overflow-hidden">
                <thead class="bg-gray-700 text-white uppercase text-xs font-semibold border-b border-gray-300">
                    <tr>
                        <th class="px-4 py-2 text-left border-b min-w-[180px]">Code</th>
                        <th class="px-4 py-2 text-left border-b">Item</th>
                        <th class="px-4 py-2 text-left border-b">Quantity</th>
                        <th class="px-4 py-2 text-left border-b">CBM</th>
                        <th class="px-4 py-2 text-left border-b">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($records as $data): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border-b"><?= htmlspecialchars($data['shipping_mark']) ?></td>
                            <td class="px-4 py-2 border-b"><?= htmlspecialchars($data['package_name']) ?></td>
                            <td class="px-4 py-2 border-b"><?= htmlspecialchars($data['number_of_pieces']) ?></td>
                            <td class="px-4 py-2 border-b"><?= htmlspecialchars($data['volume_cbm']) ?></td>
                            <td class="py-2 flex gap-1 px-4 space-x-1 no-print">
                                <!-- Edit Button -->
                                <a href="./fpdf/shipment-invoice.php?id=<?= $data['id'] ?>" target="_blank"
                                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                    <i class="fa-solid fa-file-invoice"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Tracking History -->
        <div class="w-full">
            <div id="shipment-result"></div>
            <div id="shipment-timeline" class="text-white"></div>
        </div>
    </div>
</main>

<?php include_once 'includes/footer.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", () => {
    async function fetchShipment(trackingNumber) {
        try {
            const response = await fetch('../components/track-shipment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ tracking_number: trackingNumber })
            });
            console.log(trackingNumber);
            const result = await response.json();

            if (result.status === 'error') {
                Swal.fire({
                    icon: 'error',
                    title: 'Tracking Failed',
                    text: result.message,
                    confirmButtonColor: '#031186ff'
                });
            } else if (result.status === 'success') {
                const timelineData = result.timeline || [];

                // ✅ Define tracking stages
                const stages = [
                    { key: "shipments received", icon: "📦", label: "Shipments Received" },
                    { key: "shipments in transit", icon: "🚚", label: "Shipments In Transit" },
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
                            detailHtml = `
                                <p class="text-xs text-gray-600">${matchedItem.description || ''}</p>
                                <p class="text-xs text-gray-500">${matchedItem.date ? new Date(matchedItem.date).toLocaleDateString() : ''}</p>
                            `;
                        }
                    } else if (index === currentStageIndex) {
                        stateClass = 'border-indigo-600 text-indigo-800 font-bold';
                        badgeStyle = 'bg-indigo-600 text-white animate-pulse';
                        if (matchedItem) {
                            detailHtml = `
                                <p class="text-xs text-indigo-700">${matchedItem.description || ''}</p>
                                <p class="text-xs text-indigo-600">${matchedItem.date ? new Date(matchedItem.date).toLocaleDateString() : ''}</p>
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

                // ✅ Inject into page
                document.getElementById('shipment-timeline').innerHTML = `
                    <div class="w-full text-gray-900 bg-white p-5 rounded">
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
    }

    // Auto-load first record’s tracking
    const firstRow = document.querySelector("tbody tr");
    if (firstRow) {
        const trackingNumber = firstRow.querySelector("td").innerText.trim();
        fetchShipment(trackingNumber);
    }
});
</script>
