<?php
session_start();

/* =========================
   AUTH CHECK
=========================*/
if (!isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit;
}

include_once __DIR__ . '/../../../includes/dbconnection.php';

/* =========================
   ROLE CHECK
=========================*/
$allowed_roles = ['admin', 'manager', 'hr'];
$role = strtolower(trim($_SESSION['role'] ?? ''));

if (!in_array($role, $allowed_roles)) {
    header("Location: login.php");
    exit;
}

/* =========================
   FILE CHECK
=========================*/
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    die("No file uploaded.");
}

$fileTmp  = $_FILES['file']['tmp_name'];
$fileName = $_FILES['file']['name'];
$ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

$inserted = 0;
$noMatch  = 0;

/* =========================
   HELPERS
=========================*/
function rawCell($sheet, $cell) {
    return trim((string)$sheet->getCell($cell)->getValue());
}

function displayCell($sheet, $cell) {
    return trim((string)$sheet->getCell($cell)->getFormattedValue());
}

/* =========================
   XLSX IMPORT
=========================*/
if ($ext === 'xlsx') {

    require __DIR__ . '/../../../vendor/autoload.php';

    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
    $spreadsheet = $reader->load($fileTmp);
    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestRow();

    for ($row = 2; $row <= $highestRow; $row++) {

        // 🔐 IDENTIFIERS (RAW — NO FORMATTING)
        $receipt_number      = rawCell($sheet, 'A' . $row);
        $shipping_mark       = rawCell($sheet, 'B' . $row);
        $express_tracking_no = rawCell($sheet, 'I' . $row);
        $supplier_number     = rawCell($sheet, 'N' . $row);

        // 📅 DISPLAY FIELDS (AS SHOWN IN EXCEL)
        $entry_date          = displayCell($sheet, 'C' . $row);
        $package_name        = displayCell($sheet, 'D' . $row);
        $number_of_pieces    = displayCell($sheet, 'E' . $row);
        $volume_cbm          = displayCell($sheet, 'F' . $row);
        $weight              = displayCell($sheet, 'G' . $row);
        $rate                = displayCell($sheet, 'H' . $row);

        $loading_date        = displayCell($sheet, 'J' . $row);
        $departure_date      = displayCell($sheet, 'K' . $row);
        $eta                 = displayCell($sheet, 'L' . $row);
        $eto                 = displayCell($sheet, 'M' . $row);

        if ($receipt_number === '' && $shipping_mark === '') {
            continue;
        }

        // 🔍 CUSTOMER LOOKUP
        $stmt = $dbh->prepare("SELECT customer_id, code FROM customers WHERE code = ?");
        $stmt->execute([$shipping_mark]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            $noMatch++;
            continue;
        }

        // 💾 INSERT
        $insert = $dbh->prepare("
            INSERT INTO shipping_manifest (
                customer_id, receipt_number, shipping_mark, entry_date,
                package_name, number_of_pieces, volume_cbm, weight, rate,
                express_tracking_no, loading_date, departure_date,
                estimated_time_of_arrival, estimated_time_of_offloading
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $insert->execute([
            $customer['customer_id'],
            $receipt_number,
            $customer['code'],
            $entry_date,
            $package_name,
            $number_of_pieces,
            $volume_cbm,
            $weight,
            $rate,
            $express_tracking_no,
            $loading_date,
            $departure_date,
            $eta,
            $eto,
        ]);

        if ($dbh->lastInsertId()) {
            $track = $dbh->prepare("
                INSERT INTO tracking_history
                (shipping_manifest_id, status, tracking_message)
                VALUES (?, ?, ?)
            ");
            $track->execute([
                $dbh->lastInsertId(),
                'shipments received',
                'Shipments have been received'
            ]);
        }

        $inserted++;
    }

    echo "XLSX import complete. Inserted: $inserted | No match: $noMatch";
}

/* =========================
   CSV IMPORT
=========================*/
elseif ($ext === 'csv') {

    if (($handle = fopen($fileTmp, 'r')) !== false) {

        fgetcsv($handle); // skip header

        while (($row = fgetcsv($handle, 0, ',')) !== false) {

            $receipt_number      = trim((string)($row[0] ?? ''));
            $shipping_mark       = trim((string)($row[1] ?? ''));
            $entry_date          = trim((string)($row[2] ?? ''));
            $package_name        = trim((string)($row[3] ?? ''));
            $number_of_pieces    = trim((string)($row[4] ?? ''));
            $volume_cbm          = trim((string)($row[5] ?? ''));
            $weight              = trim((string)($row[6] ?? ''));
            $rate                = trim((string)($row[7] ?? ''));
            $express_tracking_no = trim((string)($row[8] ?? ''));
            $loading_date        = trim((string)($row[9] ?? ''));
            $departure_date      = trim((string)($row[10] ?? ''));
            $eta                 = trim((string)($row[11] ?? ''));
            $eto                 = trim((string)($row[12] ?? ''));

            if ($receipt_number === '' && $shipping_mark === '') {
                continue;
            }

            $stmt = $dbh->prepare("SELECT customer_id, code FROM customers WHERE code = ?");
            $stmt->execute([$shipping_mark]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$customer) {
                $noMatch++;
                continue;
            }

            $insert = $dbh->prepare("
                INSERT INTO shipping_manifest (
                    customer_id, receipt_number, shipping_mark, entry_date,
                    package_name, number_of_pieces, volume_cbm, weight, rate,
                    express_tracking_no, loading_date, departure_date,
                    estimated_time_of_arrival, estimated_time_of_offloading
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insert->execute([
                $customer['customer_id'],
                $receipt_number,
                $customer['code'],
                $entry_date,
                $package_name,
                $number_of_pieces,
                $volume_cbm,
                $weight,
                $rate,
                $express_tracking_no,
                $loading_date,
                $departure_date,
                $eta,
                $eto
            ]);

            $inserted++;
        }

        fclose($handle);
        echo "CSV import complete. Inserted: $inserted | No match: $noMatch";
    }
}

else {
    die("Invalid file format. Only CSV or XLSX allowed.");
}
?>