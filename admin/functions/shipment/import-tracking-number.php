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
$skipped  = 0;

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
   DUPLICATE CHECK
=========================*/
function trackingExists($dbh, $trackingNumber) {
    $stmt = $dbh->prepare("SELECT 1 FROM tracking_numbers WHERE tracking_number = ?");
    $stmt->execute([$trackingNumber]);
    return $stmt->fetchColumn();
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

        // 🔐 RAW IDENTIFIER
        $tracking_number = rawCell($sheet, 'A' . $row);

        // 📅 DISPLAY DATE
        $date_received   = displayCell($sheet, 'B' . $row);

        if ($tracking_number === '') {
            continue;
        }

        // 🚫 DUPLICATE PREVENTION
        if (trackingExists($dbh, $tracking_number)) {
            $skipped++;
            continue;
        }

        // 💾 INSERT
        $insert = $dbh->prepare("
            INSERT INTO tracking_numbers (
                tracking_number,
                date_received
            ) VALUES (?, ?)
        ");

        $insert->execute([
            $tracking_number,
            $date_received
        ]);

        $inserted++;
    }

    echo "XLSX import complete. Inserted: $inserted | Skipped duplicates: $skipped";
}

/* =========================
   CSV IMPORT
=========================*/
elseif ($ext === 'csv') {

    if (($handle = fopen($fileTmp, 'r')) !== false) {

        fgetcsv($handle); // skip header

        while (($row = fgetcsv($handle, 0, ',')) !== false) {

            $tracking_number = trim((string)($row[0] ?? ''));
            $date_received   = trim((string)($row[1] ?? ''));

            if ($tracking_number === '') {
                continue;
            }

            // 🚫 DUPLICATE PREVENTION
            if (trackingExists($dbh, $tracking_number)) {
                $skipped++;
                continue;
            }

            $insert = $dbh->prepare("
                INSERT INTO tracking_numbers (
                    tracking_number,
                    date_received
                ) VALUES (?, ?)
            ");

            $insert->execute([
                $tracking_number,
                $date_received
            ]);

            $inserted++;
        }

        fclose($handle);
        echo "CSV import complete. Inserted: $inserted | Skipped duplicates: $skipped";
    }
}

/* =========================
   INVALID FILE
=========================*/
else {
    die("Invalid file format. Only CSV or XLSX allowed.");
}
?>