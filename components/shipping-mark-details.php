<?php
include_once '../admin/includes/dbconnection.php';

$mark = $_GET['mark'] ?? '';

if (empty($mark)) {
    die('No shipping mark specified.');
}

$stmt = $dbh->prepare("SELECT * FROM shipping_manifest WHERE shipping_mark = :mark");
$stmt->execute([':mark' => $mark]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$records) {
    die('Shipping mark not found.');
}
?>

<!-- Sidebar -->
<aside class="hidden lg:block w-64 bg-white shadow-md h-screen fixed">
    <div class="p-6 border-b">
        <h2 class="text-lg font-bold text-gray-700">Dashboard</h2>
    </div>
</aside>

<!-- Main Content -->
<main class="flex-1 lg:ml-64 px-4">
    <div class="overflow-x-auto bg-white shadow rounded">
       <table class="min-w-full border border-gray-200 bg-white shadow">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left border-b min-w-[180px]">Shipping Mark</th>
                    <th class="px-4 py-2 text-left border-b">Entry Date</th>
                    <th class="px-4 py-2 text-left border-b">Item</th>
                    <th class="px-4 py-2 text-left border-b">Quantity</th>
                    <th class="px-4 py-2 text-left border-b">CBM</th>
                    <th class="px-4 py-2 text-left border-b">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($records)): ?>
                    <?php foreach ($records as $data): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 border-b"><?= htmlspecialchars($data['shipping_mark']) ?></td>
                            <td class="px-4 py-2 border-b"><?= htmlspecialchars($data['entry_date']) ?></td>
                            <td class="px-4 py-2 border-b"><?= htmlspecialchars($data['package_name']) ?></td>
                            <td class="px-4 py-2 border-b"><?= htmlspecialchars($data['number_of_pieces']) ?></td>
                            <td class="px-4 py-2 border-b"><?= htmlspecialchars($data['volume_cbm']) ?></td>
                                 <td class="py-2 flex gap-1 px-4 space-x-1 no-print">
                <!-- Edit Button -->
                <button class="bg-gray-500 text-white p-2 rounded hover:bg-gray-600 hover:cursor-pointer transition edit-btn"
                    data-manifest='<?= json_encode($manifest, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                    <i class="fas fa-edit"></i>
                </button>
                <a href="fpdf/shipping-invoice.php?id=<?= $manifest['id'] ?>" target="_blank"
                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    <i class="fa-solid fa-file-invoice"></i>
                </a>
            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                            No records found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

