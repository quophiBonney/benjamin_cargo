<?php
require('../fpdf/fpdf.php');
include_once '../includes/dbconnection.php';

if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
    die('Invalid booking ID.');
}

$booking_id = (int)$_GET['booking_id'];

// Fetch full booking info
$query = "SELECT 
    b.booking_id,
    b.guest_name,
    b.guest_phone,
    b.room_number,
    b.checkin_date,
    b.checkout_date,
    DATEDIFF(b.checkout_date, b.checkin_date) AS number_of_nights,
    r.room_name,
    r.price_per_night,
    (r.price_per_night * DATEDIFF(b.checkout_date, b.checkin_date)) AS total_price
FROM bookings b
JOIN rooms r ON b.room_number = r.room_number
WHERE b.booking_id = :booking_id";

$stmt = $dbh->prepare($query);
$stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
$stmt->execute();
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die('Booking not found.');
}

// FPDF Invoice
class InvoicePDF extends FPDF {
    function Header() {
        // Hotel Header
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Consol Hotel', 0, 1, 'C');

        $this->SetFont('Arial', '', 12);
        $this->Cell(0, 6, '123 Street, Bawjiase, Ghana', 0, 1, 'C');
        $this->Cell(0, 6, 'Phone: +233 24 000 0000 | Email: info@consolhotel.com', 0, 1, 'C');
        $this->Ln(10);

        // Invoice Title
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'Booking Invoice', 0, 1, 'C');
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 9);
        $this->Cell(0, 10, 'Thank you for choosing Royal Elite Hotel. We look forward to welcoming you again!', 0, 1, 'C');
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new InvoicePDF();
$pdf->AddPage();

// Invoice Metadata
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(100, 10, 'Invoice #: ' . $booking['booking_id'], 0, 0);
$pdf->Cell(0, 10, 'Date: ' . date('F j, Y'), 0, 1);

// Guest Info Table
$pdf->Ln(3);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Guest Details', 0, 1);

$pdf->SetFont('Arial', '', 12);
$pdf->SetFillColor(245, 245, 245);
$pdf->Cell(50, 8, 'Guest Name:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $booking['guest_name'], 1, 1);

$pdf->Cell(50, 8, 'Guest Phone:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $booking['guest_phone'], 1, 1);

$pdf->Cell(50, 8, 'Room Number:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $booking['room_number'], 1, 1);

$pdf->Cell(50, 8, 'Room Name:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $booking['room_name'], 1, 1);

$pdf->Ln(8);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Booking Details', 0, 1);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(50, 8, 'Check-in Date:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $booking['checkin_date'], 1, 1);

$pdf->Cell(50, 8, 'Check-out Date:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $booking['checkout_date'], 1, 1);

$pdf->Cell(50, 8, 'Nights Stayed:', 1, 0, 'L', true);
$pdf->Cell(0, 8, $booking['number_of_nights'], 1, 1);

$pdf->Cell(50, 8, 'Price per Night:', 1, 0, 'L', true);
$pdf->Cell(0, 8, 'GHS' . number_format($booking['price_per_night'], 2), 1, 1);

$pdf->Ln(8);

// Total
$pdf->SetFont('Arial', 'B', 13);
$pdf->SetFillColor(230, 230, 230);
$pdf->Cell(50, 10, 'Total Amount:', 1, 0, 'L', true);
$pdf->Cell(0, 10, 'GHS' . number_format($booking['total_price'], 2), 1, 1);

$pdf->Ln(10);

// Output PDF
$pdf->Output('I', 'Invoice_Booking_' . $booking['booking_id'] . '.pdf');
exit;
