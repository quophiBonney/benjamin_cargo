<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    header("Location: login.php");
    die();
    exit;
}
 include_once __DIR__ . '/../../../includes/dbconnection.php'; // DB connection

 $allowed_roles = ['admin', 'manager', 'hr'];
$session_role = strtolower(trim($_SESSION['role'] ?? ''));
if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
    header("Location: login.php");
    exit;
}

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
        require  __DIR__ . '/../../../vendor/autoload.php';
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileTmp);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        unset($sheetData[0]); // remove header row

        foreach ($sheetData as $row) {
            $receipt_number      = trim($row[0]);
            $shipping_mark       = trim($row[1]);
            $entry_date          = date('Y-m-d', strtotime($row[2]));
            $package_name        = trim($row[3]);
            $number_of_pieces    = (int)$row[4];
            $volume_cbm          = (float)$row[5];
			$weight              = (float)$row[6];
            $rate                = (float)$row[7];
            $express_tracking_no = trim($row[8]);
            $loading_date        = date('Y-m-d', strtotime($row[9]));
            $departure_date      = date('Y-m-d', strtotime($row[10]));;
            $eta                 = date('Y-m-d', strtotime($row[11]));
            $eto                 = date('Y-m-d', strtotime($row[12]));
            $supplier_number     = trim($row[13]);
            $note                = trim($row[14]);

            // Lookup customer by code
            $stmt = $dbh->prepare("SELECT customer_id, code FROM customers WHERE code = ?");
            $stmt->execute([$shipping_mark]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$customer) {
                $noMatch++;
                continue;
            }

            $customer_id = $customer['customer_id'];
            $shipping_mark = $customer['code']; // take shipping_mark from customers table

            if (!isDuplicate($dbh, $customer_id, $entry_date)) {
                $stmt = $dbh->prepare("INSERT INTO shipping_manifest
    (customer_id, receipt_number, shipping_mark, entry_date, package_name, number_of_pieces,
     volume_cbm, weight, rate, express_tracking_no, loading_date, departure_date,
     estimated_time_of_arrival, estimated_time_of_offloading, supplier_number, note)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    $customer_id, $receipt_number, $shipping_mark, $entry_date, $package_name,
                    $number_of_pieces, $volume_cbm, $weight, $rate, $express_tracking_no, $loading_date,
                    $departure_date, $eta, $eto, $supplier_number, $note
                ]);
                $manifestId = $dbh->lastInsertId();

if ($manifestId) {
    $trackStmt = $dbh->prepare("
        INSERT INTO tracking_history (shipping_manifest_id, status, tracking_message) 
        VALUES (?, ?, ?)
    ");
    $trackStmt->execute([
        $manifestId,                       // ✅ valid FK reference
        'shipments received',              // default
        'Shipments have been received'     // message
    ]);
}
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
            $receipt_number      = trim($row[0]);
            $shipping_mark       = trim($row[1]);
            $entry_date          = date('Y-m-d', strtotime($row[2]));
            $package_name        = trim($row[3]);
            $number_of_pieces    = (int)$row[4];
            $volume_cbm          = (float)$row[5];
			$weight              = (float)$row[6];
            $rate                = (float)$row[7];
            $express_tracking_no = trim($row[8]);
            $loading_date        = date('Y-m-d', strtotime($row[9]));
            $departure_date      = date('Y-m-d', strtotime($row[10]));;
            $eta                 = date('Y-m-d', strtotime($row[11]));
            $eto                 = date('Y-m-d', strtotime($row[12]));
            $supplier_number     = trim($row[13]);
            $note                = trim($row[14]);
                $stmt = $dbh->prepare("SELECT customer_id, code FROM customers WHERE code = ?");
                $stmt->execute([$shipping_mark]);
                $customer = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$customer) {
                    $noMatch++;
                    continue;
                }

                $customer_id = $customer['customer_id'];
                $shipping_mark = $customer['code'];

                if (!isDuplicate($dbh, $customer_id, $entry_date)) {
                   $stmt = $dbh->prepare("INSERT INTO shipping_manifest
    (customer_id, receipt_number, shipping_mark, entry_date, package_name, number_of_pieces,
     volume_cbm, weight, rate, express_tracking_no, loading_date, departure_date,
     estimated_time_of_arrival, estimated_time_of_offloading, supplier_number, note)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                    $stmt->execute([
                        $customer_id, $receipt_number, $shipping_mark, $entry_date, $package_name,
                        $number_of_pieces, $volume_cbm, $weight, $rate, $express_tracking_no, $loading_date,
                        $departure_date, $eta, $eto, $supplier_number, $note
                    ]);
               $trackStmt = $dbh->prepare("
        INSERT INTO tracking_history (shipping_manifest_id, status, tracking_message) 
        VALUES (?, ?, ?)
    ");
    $trackStmt->execute([
        $shipping_mark,  // <- your chosen reference ID
        'shipments received',
        'shipments has been received'
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