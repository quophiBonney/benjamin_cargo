<?php
require "../../includes/dbconnection.php"; // DB connection

if (isset($_FILES['file']['name'])) {

    $fileName = $_FILES['file']['name'];
    $fileTmp  = $_FILES['file']['tmp_name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Function to check duplicates
    function isDuplicate($dbh, $customer_id, $entry_date) {
        $check = $dbh->prepare("SELECT COUNT(*) FROM shipping_manifest WHERE customer_id = ? AND entry_date = ?");
        $check->execute([$customer_id, $entry_date]);
        return $check->fetchColumn() > 0;
    }

    $inserted = 0;
    $skipped = 0;
    $noMatch = 0;

    // Excel import
    if ($ext === 'xlsx') {
        require '../../vendor/autoload.php';
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileTmp);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        unset($sheetData[0]); // remove header row

        foreach ($sheetData as $row) {
            $file_code           = trim($row[0]); // customer code from file
            $entry_date          = date('Y-m-d', strtotime($row[1]));
            $package_name        = trim($row[2]); // adjust if column index is different
            $number_of_pieces    = (int)$row[3];
            $volume_cbm          = (float)$row[4];
            $express_tracking_no = trim($row[5]);

            // Lookup customer by code
            $stmt = $dbh->prepare("SELECT customer_id, code FROM customers WHERE code = ?");
            $stmt->execute([$file_code]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$customer) {
                $noMatch++;
                continue;
            }

            $customer_id = $customer['customer_id'];
            $shipping_mark = $customer['code']; // take shipping_mark from customers table

            if (!isDuplicate($dbh, $customer_id, $entry_date)) {
                $stmt = $dbh->prepare("INSERT INTO shipping_manifest
                    (customer_id, shipping_mark, entry_date, package_name, number_of_pieces, volume_cbm, express_tracking_no)
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $customer_id,
                    $shipping_mark,
                    $entry_date,
                    $package_name,
                    $number_of_pieces,
                    $volume_cbm,
                    $express_tracking_no
                ]);
                $inserted++;
            } else {
                $skipped++;
            }
        }

        echo "Excel import completed. Inserted: $inserted, Skipped: $skipped, No matching customer: $noMatch";

    } 
    // CSV import
    elseif ($ext === 'csv') {
        if (($handle = fopen($fileTmp, 'r')) !== false) {
            fgetcsv($handle); // skip header row

            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $file_code           = trim($row[0]);
                $entry_date          = date('Y-m-d', strtotime($row[1]));
                $package_name        = trim($row[2]);
                $number_of_pieces    = (int)$row[3];
                $volume_cbm          = (float)$row[4];
                $express_tracking_no = trim($row[5]);

                $stmt = $dbh->prepare("SELECT customer_id, code FROM customers WHERE code = ?");
                $stmt->execute([$file_code]);
                $customer = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$customer) {
                    $noMatch++;
                    continue;
                }

                $customer_id = $customer['customer_id'];
                $shipping_mark = $customer['code'];

                if (!isDuplicate($dbh, $customer_id, $entry_date)) {
                    $stmt = $dbh->prepare("INSERT INTO shipping_manifest
                        (customer_id, shipping_mark, entry_date, package_name, number_of_pieces, volume_cbm, express_tracking_no)
                        VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $customer_id,
                        $shipping_mark,
                        $entry_date,
                        $package_name,
                        $number_of_pieces,
                        $volume_cbm,
                        $express_tracking_no
                    ]);
                    $inserted++;
                } else {
                    $skipped++;
                }
            }
            fclose($handle);
            echo "CSV import completed. Inserted: $inserted, Skipped: $skipped, No matching customer: $noMatch";
        }
    } 
    else {
        echo "Invalid file format. Only CSV or XLSX allowed.";
    }

} else {
    echo "No file uploaded.";
}
?>
