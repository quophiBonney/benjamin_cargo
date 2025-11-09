<?php
 include_once __DIR__ . '/../../../includes/dbconnection.php';// DB connection

if (!isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    die();
}

if (isset($_FILES['file']['name'])) {
    $fileName = $_FILES['file']['name'];
    $fileTmp  = $_FILES['file']['tmp_name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $inserted = 0;
    $skipped  = 0;

    // Excel Import
    if ($ext === 'xlsx') {
         require  __DIR__ . '/../../../vendor/autoload.php';// PhpSpreadsheet
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileTmp);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();
        unset($sheetData[0]);

        foreach ($sheetData as $row) {
            $full_name     = trim($row[0]);
            $email_address = trim($row[1]);
            $phone_number  = trim($row[2]);
            $role          = trim($row[3]);
            $password_raw  = trim($row[4]);

            // Skip if email already exists
            $stmt = $dbh->prepare("SELECT employee_id FROM employees WHERE email = ?");
            $stmt->execute([$email_address]);
            if ($stmt->fetchColumn()) {
                $skipped++;
                continue;
            }

            // Hash password
            $password_hashed = password_hash($password_raw, PASSWORD_DEFAULT);

            $stmt = $dbh->prepare("INSERT INTO employees (full_name, email, phone, role, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$full_name, $email_address, $phone_number, $role, $password_hashed]);
            $inserted++;
        }

        echo "Users import completed. Inserted: $inserted, Skipped (duplicate emails): $skipped";
    }

    // CSV Import
    elseif ($ext === 'csv') {
        if (($handle = fopen($fileTmp, 'r')) !== false) {
            fgetcsv($handle); // skip header

            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $full_name     = trim($row[0]);
                $email_address = trim($row[1]);
                $phone_number  = trim($row[2]);
                $role          = trim($row[3]);
                $password_raw  = trim($row[4]);

                // Skip if email already exists
                $stmt = $dbh->prepare("SELECT employee_id FROM employees WHERE email = ?");
                $stmt->execute([$email_address]);
                if ($stmt->fetchColumn()) {
                    $skipped++;
                    continue;
                }

                // Hash password
                $password_hashed = password_hash($password_raw, PASSWORD_DEFAULT);

                $stmt = $dbh->prepare("INSERT INTO employees (full_name, email, phone, role, password) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$full_name, $email_address, $phone_number, $role, $password_hashed]);
                $inserted++;
            }
            fclose($handle);

            echo "Users import completed. Inserted: $inserted, Skipped (duplicate emails): $skipped";
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
