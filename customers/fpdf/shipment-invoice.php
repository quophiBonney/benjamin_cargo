<?php
require('../fpdf/fpdf.php');
include_once __DIR__ . '/../../includes/dbconnection.php';

// ✅ Check if customer_id exists in URL
if (!isset($_GET['customer_id']) || !is_numeric($_GET['customer_id'])) {
    die('Invalid customer ID.');
}
$customer_id = (int)$_GET['customer_id'];

// ✅ Fetch all shipments for this customer
$query = "SELECT customer_id, entry_date, shipping_mark, package_name, receipt_number, 
                 loading_date, number_of_pieces, volume_cbm, weight, rate,
                 estimated_time_of_arrival, estimated_time_of_offloading
          FROM shipping_manifest 
          WHERE customer_id = :customer_id
          ORDER BY entry_date DESC";
$stmt = $dbh->prepare($query);
$stmt->execute([':customer_id' => $customer_id]);
$shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$shipments) {
    die('No shipments found for this customer.');
}

// ✅ Invoice PDF Class
class InvoicePDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Benjamin Cargo Logistics', 0, 1, 'C');
        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 6, 'Spintex, Accra, Ghana', 0, 1, 'C');
        $this->Cell(0, 6, 'Phone: +233 24 000 0000 | Email: info@benjamincargo.com', 0, 1, 'C');
        $this->Ln(8);

        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Shipping Invoice - Customer Shipments', 0, 1, 'C');
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 9);
        $this->Cell(0, 10, 'Thank you for choosing Benjamin Cargo & Logistics.', 0, 1, 'C');
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }

    // ✅ First Table - Basic Shipment Info
    function TableBasicInfo($shipments) {
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(40, 8, 'Code', 1, 0, 'C');
        $this->Cell(80, 8, 'Package', 1, 0, 'C');
        $this->Cell(50, 8, 'Entry Date', 1, 1, 'C');

        $this->SetFont('Arial', '', 10);
        foreach ($shipments as $s) {
            $this->Cell(40, 8, $s['shipping_mark'], 1, 0, 'C');
            $this->Cell(80, 8, ucwords(strtolower($s['package_name'])), 1, 0, 'C');
            $this->Cell(50, 8, $s['entry_date'], 1, 1, 'C');
        }
        $this->Ln(6);
    }

    // ✅ Second Table - Shipment Quantities and Rates
    function TableQuantities($shipments) {
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(40, 8, 'Pieces', 1, 0, 'C');
        $this->Cell(40, 8, 'CBM', 1, 0, 'C');
        $this->Cell(40, 8, 'Weight', 1, 0, 'C');
        $this->Cell(50, 8, 'Rate($)', 1, 1, 'C');

        $this->SetFont('Arial', '', 10);
        $totalPieces = $totalCBM = $totalWeight = $totalRate = 0;

        foreach ($shipments as $s) {
            $this->Cell(40, 8, $s['number_of_pieces'], 1, 0, 'C');
            $this->Cell(40, 8, number_format($s['volume_cbm'], 2), 1, 0, 'C');
            $this->Cell(40, 8, $s['weight'], 1, 0, 'C');
            $this->Cell(50, 8, $s['rate'], 1, 1, 'C');

            $totalPieces += $s['number_of_pieces'];
            $totalCBM += $s['volume_cbm'];
        }

        // ✅ Totals Row
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(40, 8, $totalPieces, 1, 0, 'C');
        $this->Cell(40, 8, number_format($totalCBM, 2), 1, 0, 'C');
        $this->Cell(40, 8, '-', 1, 0, 'C');
        $this->Cell(50, 8, '-', 1, 1, 'C');
        $this->Ln(6);
    }

    // ✅ Third Table - Timeline Details
    function TableTimeline($shipments) {
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(65, 8, 'Loading Date', 1, 0, 'C');
        $this->Cell(65, 8, 'Estimated Arrival', 1, 0, 'C');
        $this->Cell(65, 8, 'Offloading Date', 1, 1, 'C');

        $this->SetFont('Arial', '', 10);
        foreach ($shipments as $s) {
            $this->Cell(65, 8, $s['loading_date'] ?: '-', 1, 0, 'C');
            $this->Cell(65, 8, $s['estimated_time_of_arrival'] ?: '-', 1, 0, 'C');
            $this->Cell(65, 8, $s['estimated_time_of_offloading'] ?: '-', 1, 1, 'C');
        }
        $this->Ln(8);
    }
}

// ✅ Generate PDF
$pdf = new InvoicePDF();
$pdf->AddPage();

// ✅ Invoice Metadata
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(100, 8, 'Customer ID: ' . $customer_id, 0, 0);
$pdf->Cell(0, 8, 'Invoice Date: ' . date('F j, Y'), 0, 1);
$pdf->Cell(100, 8, 'Invoice No: INV-' . strtoupper(uniqid()), 0, 1);
$pdf->Ln(8);

// ✅ Tables
$pdf->SetFont('Arial', 'B', 13);
$pdf->Cell(0, 8, 'Shipment Basic Information', 0, 1);
$pdf->TableBasicInfo($shipments);

$pdf->SetFont('Arial', 'B', 13);
$pdf->Cell(0, 8, 'Shipment Quantities & Rates', 0, 1);
$pdf->TableQuantities($shipments);

$pdf->SetFont('Arial', 'B', 13);
$pdf->Cell(0, 8, 'Shipment Timeline Details', 0, 1);
$pdf->TableTimeline($shipments);

// ✅ Output PDF
$pdf->Output('I', 'Invoice_Customer_' . $customer_id . '.pdf');
exit;
