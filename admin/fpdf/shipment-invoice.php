<?php
session_start();
require('../fpdf/fpdf.php');
include_once __DIR__ . '/../../includes/dbconnection.php';

// ✅ Check if admin/employee is logged in
$is_employee = isset($_SESSION['employee_id']);
$shipment_id = $_GET['shipment_id'] ?? null;
$customer_id = $_GET['customer_id'] ?? null;

if ($shipment_id) {
    // Fetch specific shipment
    $query = "SELECT s.customer_id, s.entry_date, s.shipping_mark, s.package_name, s.receipt_number,
                     s.loading_date, s.number_of_pieces, s.volume_cbm, s.weight, s.rate,
                     s.estimated_time_of_arrival, s.estimated_time_of_offloading,
                     c.client_name
              FROM shipping_manifest s
              LEFT JOIN customers c ON s.customer_id = c.customer_id
              WHERE s.id = :shipment_id";
    $stmt = $dbh->prepare($query);
    $stmt->execute([':shipment_id' => $shipment_id]);
    $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $customer_name = $shipments[0]['client_name'] ?? 'Unknown Customer';
} elseif ($customer_id && !$is_employee) {
    // Fetch customer information
    $customerQuery = "SELECT client_name FROM customers WHERE customer_id = :customer_id";
    $customerStmt = $dbh->prepare($customerQuery);
    $customerStmt->execute([':customer_id' => $customer_id]);
    $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
    $customer_name = $customer ? $customer['client_name'] : 'Unknown Customer';
    // Fetch customer shipments
    $query = "SELECT customer_id, entry_date, shipping_mark, package_name, receipt_number,
                     loading_date, number_of_pieces, volume_cbm, weight, rate,
                     estimated_time_of_arrival, estimated_time_of_offloading
              FROM shipping_manifest
              WHERE customer_id = :customer_id
              ORDER BY entry_date DESC";
    $stmt = $dbh->prepare($query);
    $stmt->execute([':customer_id' => $customer_id]);
    $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // For admin/employee, fetch all shipments with customer info
    $customer_name = 'Admin Generated Invoice';
    $query = "SELECT s.entry_date, s.shipping_mark, s.package_name, s.receipt_number,
                     s.loading_date, s.number_of_pieces, s.volume_cbm, s.weight, s.rate,
                     s.estimated_time_of_arrival, s.estimated_time_of_offloading,
                     c.client_name
              FROM shipping_manifest s
              LEFT JOIN customers c ON s.customer_id = c.customer_id
              ORDER BY s.entry_date DESC";
    $stmt = $dbh->prepare($query);
    $stmt->execute();
    $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$shipping_mark = $shipments[0]['shipping_mark'] ?? 'N/A';
if (!$shipments) {
    die('No shipments found.');
}

class InvoicePDF extends FPDF {
function Header() {
    // Company Info
    $this->SetFont('Arial', 'B', 14);
    $this->Cell(100, 6, 'Benjamin Cargo Logistics', 0, 0, 'L');
    $this->Image('logo.png', 165, 10, 30);
    $this->Ln(8);

    // Define locations in an array for three columns
    $locations = [
        ['name' => 'Spintex Warehouse', 'address' => 'Spintex Coastal Junction', 'phone' => '0537412315/0554358745'],
        ['name' => 'Kumasi Warehouse', 'address' => 'Atafoa', 'phone' => '0537412315/0256120389'],
        ['name' => 'Takoradi Warehouse', 'address' => 'Spintex Coastal Junction', 'phone' => '0537412315']
    ];

    // Column positions (starting X, width per column)
    $colWidth = 50;
    $startX = 10;

    // Print headers (names) in first row
    $this->SetFont('Arial', 'B', 10);
    foreach ($locations as $index => $location) {
        $this->SetX($startX + ($index * $colWidth));
        $this->Cell($colWidth, 5, $location['name'], 0, 0, 'L');
    }
    $this->Ln(5);

    // Print addresses in second row
    $this->SetFont('Arial', '', 10);
    foreach ($locations as $index => $location) {
        $this->SetX($startX + ($index * $colWidth));
        $this->Cell($colWidth, 5, $location['address'], 0, 0, 'L');
    }
    $this->Ln(5);

    // Print cities (if any) in third row
    foreach ($locations as $index => $location) {
        if (!empty($location['city'])) {
            $this->SetX($startX + ($index * $colWidth));
            $this->Cell($colWidth, 5, $location['city'], 0, 0, 'L');
        }
    }
 

    // Print phones in fourth row
    foreach ($locations as $index => $location) {
        $this->SetX($startX + ($index * $colWidth));
        $this->Cell($colWidth, 5, $location['phone'], 0, 0, 'L');
    }
    $this->Ln(7);

    $this->SetDrawColor(44, 62, 80);
    $this->SetLineWidth(0.6);
    $this->Line(10, $this->GetY(), 200, $this->GetY());
    $this->Ln(8);
}

    function Footer() {
        $this->SetY(-25);
        $this->SetFont('Arial', 'I', 9);
        $this->Cell(0, 10, 'Thank you for your business.', 0, 1, 'C');
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }

    function InvoiceHeader($customer_name, $shipping_mark) {
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 6, 'Invoice', 0, 1, 'L');
        $this->Ln(2);

        $this->SetFont('Arial', '', 11);
        $this->Cell(70, 6, 'Name: ' . ucwords($customer_name), 0, 0, 'L');
        $this->Cell(60, 6, 'Code: ' . ucwords($shipping_mark), 0, 0, 'L');
        $this->Cell(60, 6, 'Invoice #: ' . rand(100, 999), 0, 1, 'L');

        $this->Cell(70, 6, '', 0, 0, 'L');
        $this->Cell(60, 6, '', 0, 0, 'L');
        $this->Cell(60, 6, 'Date: ' . date('d/m/Y'), 0, 1, 'L');
        $this->Ln(5);
    }

    function ShipmentTable($shipments, $is_employee) {
        $this->SetFont('Arial', 'B', 10);
        $this->SetFillColor(230, 230, 230);
        // ✅ Table Headers
        $this->Cell(65, 8, 'Package', 1, 0, 'C', true);
        $this->Cell(15, 8, 'Pieces', 1, 0, 'C', true);
        $this->Cell(20, 8, 'CBM', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Weight', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Rate ($)', 1, 0, 'C', true);
        $this->Cell(55, 8, 'Loading / ETA / Offload', 1, 1, 'C', true);

        $this->SetFont('Arial', '', 9);
        $totalPrice = 0;

        foreach ($shipments as $s) {
            $desc = ucwords(strtolower($s['package_name']));
            $pieces = $s['number_of_pieces'];
            $cbm = $s['volume_cbm'];
            $weight = $s['weight'];
            $rate = $s['rate'];
            $loading = date('d/m/y', strtotime($s['loading_date']));
            $eta = date('d/m/y', strtotime($s['estimated_time_of_arrival']));
            $offload = date('d/m/y', strtotime($s['estimated_time_of_offloading']));

            // Combine dates into one line
            $dates = "$loading / $eta / $offload";
            $this->Cell(65, 8, $desc, 1, 0, 'L');
            $this->Cell(15, 8, $pieces, 1, 0, 'C');
            $this->Cell(20, 8, number_format($cbm, 2), 1, 0, 'C');
            $this->Cell(20, 8, number_format($weight, 2), 1, 0, 'C');
            $this->Cell(20, 8, '$' . number_format($rate, 2), 1, 0, 'C');
            $this->Cell(55, 8, $dates, 1, 1, 'C');
            $totalPrice += $rate;
        }

        $this->Ln(3);
        $this->SetFont('Arial', '', 10);
        $this->Cell(170, 8, 'Subtotal', 0, 0, 'R');
        $this->Cell(25, 8, '$' . number_format($totalPrice, 2), 0, 1, 'R');

        $this->Cell(170, 8, 'Bonus', 0, 0, 'R');
        $this->Cell(25, 8, '$0.00', 0, 1, 'R');

        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(204, 0, 102);
        $this->Cell(170, 8, 'Total', 0, 0, 'R');
        $this->Cell(25, 8, '$' . number_format($totalPrice, 2), 0, 1, 'R');
        $this->SetTextColor(0, 0, 0);

        $this->Ln(8);
    }
}

// ✅ Generate PDF
$pdf = new InvoicePDF();
$pdf->AddPage();
$pdf->InvoiceHeader($customer_name, $shipping_mark);
$pdf->ShipmentTable($shipments, $customer_name, $is_employee);
$filename = $is_employee ? 'Invoice_Admin_' . date('YmdHis') . '.pdf' : 'Invoice_Customer_' . ($customer_id ?? 'Unknown') . '.pdf';
$pdf->Output('I', $filename);
exit;
?>
