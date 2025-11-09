<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    die();
}
include_once __DIR__ . '/../../../includes/dbconnection.php'; // DB connection

if (!isset($_FILES['file']['name'])) {
    exit("No file uploaded.");
}

$fileName = $_FILES['file']['name'];
$fileTmp  = $_FILES['file']['tmp_name'];
$ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

require __DIR__ . '/../../../vendor/autoload.php'; // PhpSpreadsheet

$inserted = 0;
$skipped  = 0;
$dataRows = [];

/**
 * Parse XLSX
 */
if ($ext === 'xlsx') {
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileTmp);
    $sheetData   = $spreadsheet->getActiveSheet()->toArray();
    unset($sheetData[0]); // remove header
    $dataRows = $sheetData;
}
/**
 * Parse CSV
 */
elseif ($ext === 'csv') {
    if (($handle = fopen($fileTmp, 'r')) !== false) {
        fgetcsv($handle); // skip header
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $dataRows[] = $row;
        }
        fclose($handle);
    }
}
/**
 * Invalid file type
 */
else {
    exit("Invalid file format. Only CSV or XLSX allowed.");
}

// Process rows
foreach ($dataRows as $row) {
    $sn           = trim($row[0]);
    $code         = trim($row[1]);
    $client_name  = trim($row[2]);
    $location     = trim($row[3]);
    $phone_number = trim($row[4]);
    $email        = trim($row[5]);
    $sea          = trim($row[6]);
    $air          = trim($row[7]);

    // Skip if email already exists
    $stmt = $dbh->prepare("SELECT customer_id FROM customers WHERE email_address = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn()) {
        $skipped++;
        continue;
    }

    // Insert new record
   try {
     $stmt = $dbh->prepare("INSERT INTO customers 
        (sn, code, client_name, phone_number, email_address, location, sea, air) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$sn, $code, $client_name, $phone_number, $email, $location, $sea, $air]);$inserted++;
   }
   catch(PDOException $e) {
 if ($e->getCode() == 23000) { 
        // Duplicate entry error
        $skipped++;
        continue;
    } else {
        throw $e; // Re-throw other errors
    }
   }
}

echo "Customer import completed. Inserted: $inserted, Skipped (duplicate emails): $skipped";
