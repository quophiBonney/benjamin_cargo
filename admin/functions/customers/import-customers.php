<?php
require "../../includes/dbconnection.php"; // DB connection

if (isset($_FILES['file']['name'])) {
    $fileName = $_FILES['file']['name'];
    $fileTmp  = $_FILES['file']['tmp_name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $inserted = 0;
    $skipped  = 0;

    // Excel Import
    if ($ext === 'xlsx') {
        require '../../vendor/autoload.php'; // PhpSpreadsheet
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileTmp);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        unset($sheetData[0]); // remove header row

        foreach ($sheetData as $row) {
            $sn           = trim($row[0]);
            $code         = trim($row[1]);
            $client_name  = trim($row[2]);
            $location     = trim($row[3]);
            $phone_number = trim($row[4]);
            $email        = trim($row[5]);

            // Skip if email already exists
            $stmt = $dbh->prepare("SELECT customer_id FROM customers WHERE email_address = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn()) {
                $skipped++;
                continue;
            }

            $stmt = $dbh->prepare("INSERT INTO customers (sn, code, client_name, phone_number, email_address, location) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$sn, $code, $client_name, $phone_number, $email, $location]);
            $inserted++;
        }

        echo "Customer import completed. Inserted: $inserted, Skipped (duplicate emails): $skipped";
    }

    // CSV Import
    elseif ($ext === 'csv') {
        if (($handle = fopen($fileTmp, 'r')) !== false) {
            fgetcsv($handle); // skip header

            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $sn           = trim($row[0]);
                $code         = trim($row[1]);
                $client_name  = trim($row[2]);
                $location     = trim($row[3]);
                $phone_number = trim($row[4]);
                $email        = trim($row[5]);

                // Skip if email already exists
                $stmt = $dbh->prepare("SELECT customer_id FROM customers WHERE email_address = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn()) {
                    $skipped++;
                    continue;
                }

                $stmt = $dbh->prepare("INSERT INTO customers (sn, code, client_name, phone_number, email_address, location) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$sn, $code, $client_name, $phone_number, $email, $location]);
                $inserted++;
            }
            fclose($handle);

            echo "Customer import completed. Inserted: $inserted, Skipped (duplicate emails): $skipped";
        }
    }

    else {
        echo "Invalid file format. Only CSV or XLSX allowed.";
    }
} 
else {
    echo "No file uploaded.";
}
?>
