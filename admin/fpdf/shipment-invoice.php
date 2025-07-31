<?php
require('../fpdf/fpdf.php');
include_once '../includes/dbconnection.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid shipping ID.');
}

$shipping_id = (int)$_GET['id'];

// Fetch full shipping info
$query = "SELECT 
    shipment_id,
    sender_name,
    sender_country,
    sender_city,
    receiver_name,
    receiver_phone,
    package_name,
    package_weight,
    package_len,
    package_quantity,
    package_payment_method,
    package_pickup_date,
    package_expected_delivery_date,
    origin,
    destination,
    receiver_country,
    receiver_city,
    status
FROM shipments WHERE shipment_id = :id";

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
        // Hotel Header
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
        $this->Cell(0, 10, 'Thank you for choosing Benjamin Cargo & Logisitcs. We look forward to shipping your goods again!', 0, 1, 'C');
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new InvoicePDF();
$pdf->AddPage();

// Invoice Metadata
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(100, 10, 'Shipping #: ' . $shipping['shipment_id'], 0, 0);
$pdf->Cell(0, 10, 'Date: ' . date('F j, Y'), 0, 1);

//Sender Info
$pdf->Ln(3);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Sender Details', 0, 1);

$pdf->SetFont('Arial', '', 12);
$pdf->SetFillColor(245, 245, 245);
$pdf->Cell(50, 8, 'Sender Name:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $shipping['sender_name'], 1, 1);

$pdf->Cell(50, 8, 'Sender Country:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $shipping['sender_country'], 1, 1);

//Receiver Info
$pdf->Ln(3);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Receiver Details', 0, 1);

$pdf->SetFont('Arial', '', 12);
$pdf->SetFillColor(245, 245, 245);
$pdf->Cell(50, 8, 'Receiver Name:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $shipping['receiver_name'], 1, 1);

$pdf->Cell(50, 8, 'Receiver Country:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $shipping['receiver_country'], 1, 1);

$pdf->Cell(50, 8, 'Receiver City:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $shipping['receiver_city'], 1, 1);

$pdf->Cell(50, 8, 'Receiver Phone:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $shipping['receiver_phone'], 1, 1);

$pdf->Ln(8);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Shipping Details', 0, 1);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 8, 'Package:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $shipping['package_name'], 1, 1);

$pdf->Cell(50, 8, 'Origin:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $shipping['origin'], 1, 1);

$pdf->Cell(50, 8, 'Quantity:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $shipping['package_quantity'], 1, 1);


$pdf->Cell(50, 8, 'Payment Mode:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $shipping['package_payment_method'], 1, 1);

$pdf->Cell(50, 8, 'Expected Delivery Date:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $shipping['package_expected_delivery_date'], 1, 1);

$pdf->Cell(50, 8, 'Destination:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $shipping['destination'], 1, 1);

$pdf->Cell(50, 8, 'Status:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $shipping['status'], 1, 1);

$pdf->Ln(8);



$pdf->Ln(10);

// Output PDF
$pdf->Output('I', 'Invoice_shipping_' . $shipping['shipment_id'] . '.pdf');
exit;
