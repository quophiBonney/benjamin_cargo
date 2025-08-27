<?php
require('../fpdf/fpdf.php');
include_once __DIR__ . '/../../includes/dbconnection.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid shipping ID.');
}

$shipping_id = (int)$_GET['id'];

// Fetch full shipping info
$query = "SELECT 
    id,
    customer_id,
    entry_date,
    package_name,
    number_of_pieces,
    volume_cbm,
    eta,
    status
FROM shipping_manifest WHERE id = :id";

$stmt = $dbh->prepare($query);
$stmt->bindParam(':id', $shipping_id, PDO::PARAM_INT);
$stmt->execute();
$shipping = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shipping) {
    die('Shipping not found.');
}

// FPDF Invoice
class InvoicePDF extends FPDF {
    function Header() {
        // Company Header
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Benjamin Cargo & Logistics', 0, 1, 'C');

        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 6, '123 Coastal Estate - Spintex', 0, 1, 'C');
        $this->Cell(0, 6, 'Phone: +233 24 000 0000 | Email: info@benjamincargo.com', 0, 1, 'C');
        $this->Ln(10);

        // Invoice Title
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Shipping Invoice', 0, 1, 'C');
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 9);
        $this->Cell(0, 10, 'Thank you for choosing Benjamin Cargo & Logistics. We look forward to shipping your goods again!', 0, 1, 'C');
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new InvoicePDF();
$pdf->AddPage();

// Invoice Metadata
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(100, 10, 'Shipping #: ' . $shipping['id'], 0, 0);
$pdf->Cell(0, 10, 'Date: ' . date('F j, Y'), 0, 1);

$pdf->Ln(8);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Shipping Details', 0, 1);

// Table style
$pdf->SetFont('Arial', '', 12);
$pdf->SetFillColor(230, 230, 230); // Light gray for labels

$pdf->Cell(50, 8, 'Package:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $shipping['package_name'], 1, 1);

$pdf->Cell(50, 8, 'Number of Pieces:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $shipping['number_of_pieces'], 1, 1);

$pdf->Cell(50, 8, 'Volume (CBM):', 1, 0, 'L', true);
$pdf->Cell(0, 8, $shipping['volume_cbm'], 1, 1);

$pdf->Cell(50, 8, 'Estimated Arrival:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $shipping['eta'], 1, 1);

$pdf->Cell(50, 8, 'Status:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $shipping['status'], 1, 1);

$pdf->Ln(10);

// Output PDF
$pdf->Output('I', 'Invoice_shipping_' . $shipping['id'] . '.pdf');
exit;
