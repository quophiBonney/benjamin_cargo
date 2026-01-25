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
   XLSX IMPORT (preserve displayed values)
   We use getFormattedValue() for date/display strings so
   the inserted text matches what Excel shows (e.g. 3/11/2025).
=========================*/
if ($ext === 'xlsx') {

    require __DIR__ . '/../../../vendor/autoload.php';

    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($fileTmp);
$sheet = $spreadsheet->getActiveSheet();
    $highestRow = $sheet->getHighestRow();

    // Start from row 2 (skip header)
    for ($row = 2; $row <= $highestRow; $row++) {

        $receipt_number = trim((string)$sheet->getCell('A' . $row)->getFormattedValue());
        $shipping_mark  = trim((string)$sheet->getCell('B' . $row)->getFormattedValue());
        $entry_date     = trim((string)$sheet->getCell('C' . $row)->getFormattedValue());

        // Other fields - keep as displayed text (safe)
        $package_name        = trim((string)$sheet->getCell('D' . $row)->getFormattedValue());
        $number_of_pieces    = trim((string)$sheet->getCell('E' . $row)->getFormattedValue());
        $volume_cbm          = trim((string)$sheet->getCell('F' . $row)->getFormattedValue());
        $weight              = trim((string)$sheet->getCell('G' . $row)->getFormattedValue());
        $rate                = trim((string)$sheet->getCell('H' . $row)->getFormattedValue());
        $express_tracking_no = trim((string)$sheet->getCell('I' . $row)->getFormattedValue());

        $loading_date   = trim((string)$sheet->getCell('J' . $row)->getFormattedValue());
        $departure_date = trim((string)$sheet->getCell('K' . $row)->getFormattedValue());
        $eta            = trim((string)$sheet->getCell('L' . $row)->getFormattedValue());
        $eto            = trim((string)$sheet->getCell('M' . $row)->getFormattedValue());

        $supplier_number = trim((string)$sheet->getCell('N' . $row)->getFormattedValue());
        $note            = trim((string)$sheet->getCell('O' . $row)->getFormattedValue());

        // If receipt_number or shipping_mark is empty, skip row (optional)
        if ($receipt_number === '' && $shipping_mark === '') {
            continue;
        }

        // Lookup customer by shipping_mark (exact match)
        $stmt = $dbh->prepare("SELECT customer_id, code FROM customers WHERE code = ?");
        $stmt->execute([$shipping_mark]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            $noMatch++;
            continue;
        }

        // Insert into shipping_manifest - dates are stored as TEXT exactly as provided
        $insert = $dbh->prepare("
            INSERT INTO shipping_manifest (
                customer_id, receipt_number, shipping_mark, entry_date,
                package_name, number_of_pieces, volume_cbm, weight, rate,
                express_tracking_no, loading_date, departure_date,
                estimated_time_of_arrival, estimated_time_of_offloading,
                supplier_number, note
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
            $supplier_number,
            $note
        ]);

        $manifestId = $dbh->lastInsertId();

        if ($manifestId) {
            $track = $dbh->prepare("
                INSERT INTO tracking_history
                (shipping_manifest_id, status, tracking_message)
                VALUES (?, ?, ?)
            ");
            $track->execute([
                $manifestId,
                'shipments received',
                'Shipments have been received'
            ]);
        }

        $inserted++;
    }

    echo "XLSX import done. Inserted: $inserted | No customer match: $noMatch";
}

/* =========================
   CSV IMPORT (treat everything as text exactly as appears)
=========================*/
elseif ($ext === 'csv') {

    if (($handle = fopen($fileTmp, 'r')) !== false) {

        // read header (if present)
        $hdr = fgetcsv($handle);

        while (($row = fgetcsv($handle, 0, ',')) !== false) {

            // Use null coalescing to prevent undefined offsets
            $receipt_number      = trim((string)($row[0] ?? ''));
            $shipping_mark       = trim((string)($row[1] ?? ''));
            $entry_date          = trim((string)($row[2] ?? ''));

            $package_name        = trim((string)($row[3] ?? ''));
            $number_of_pieces    = trim((string)($row[4] ?? ''));
            $volume_cbm          = trim((string)($row[5] ?? ''));
            $weight              = trim((string)($row[6] ?? ''));
            $rate                = trim((string)($row[7] ?? ''));
            $express_tracking_no = trim((string)($row[8] ?? ''));

            $loading_date   = trim((string)($row[9]  ?? ''));
            $departure_date = trim((string)($row[10] ?? ''));
            $eta            = trim((string)($row[11] ?? ''));
            $eto            = trim((string)($row[12] ?? ''));

            $supplier_number = trim((string)($row[13] ?? ''));
            $note            = trim((string)($row[14] ?? ''));

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
                    estimated_time_of_arrival, estimated_time_of_offloading,
                    supplier_number, note
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
                $supplier_number,
                $note
            ]);

            $manifestId = $dbh->lastInsertId();

            if ($manifestId) {
                $track = $dbh->prepare("
                    INSERT INTO tracking_history
                    (shipping_manifest_id, status, tracking_message)
                    VALUES (?, ?, ?)
                ");
                $track->execute([
                    $manifestId,
                    'shipments received',
                    'Shipments have been received'
                ]);
            }

            $inserted++;
        }

        fclose($handle);
        echo "CSV import done. Inserted: $inserted | No customer match: $noMatch";
    } else {
        die("Unable to open CSV file.");
    }
}

else {
    die("Invalid file format. Only CSV or XLSX allowed.");
}
