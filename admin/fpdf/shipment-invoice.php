<?php
session_start();
require('../fpdf/fpdf.php');
include_once __DIR__ . '/../../includes/dbconnection.php';

// =============================
// CHECK EMPLOYEE SESSION
// =============================
if (!isset($_SESSION['employee_id'])) {
    die('Access denied. You must be logged in as an employee.');
}

$employee_id = $_SESSION['employee_id'];

// =============================
// VALIDATE CUSTOMER_ID FROM URL
// =============================
if (!isset($_GET['customer_id']) || !is_numeric($_GET['customer_id'])) {
    die('Missing or invalid customer_id in URL. Example: ?customer_id=123');
}

$customer_id = (int)$_GET['customer_id'];

// =============================
// FETCH CUSTOMER
// =============================
$customerStmt = $dbh->prepare(
    "SELECT client_name FROM customers WHERE customer_id = :id"
);
$customerStmt->execute([':id' => $customer_id]);
$customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
$customer_name = $customer['client_name'] ?? 'Unknown Customer';

// =============================
// FETCH SHIPMENTS FOR THAT CUSTOMER
// =============================
$stmt = $dbh->prepare(
    "SELECT shipping_mark,
            package_name,
            volume_cbm,
            rate,
            loading_date,
            estimated_time_of_arrival
     FROM shipping_manifest
     WHERE customer_id = :customer_id
     ORDER BY entry_date DESC"
);
$stmt->execute([':customer_id' => $customer_id]);
$shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$shipments) {
    die('No shipments found for this customer.');
}

// =============================
// PDF CLASS (DESIGN UNCHANGED)
// =============================
class InvoicePDF extends FPDF {

    public $shipments = [];

    function __construct($shipments) {
        parent::__construct();
        $this->shipments = $shipments;
    }

    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(180, 0, 0);
        $this->Image('logo.png', 10, 10, 30);
        $this->SetXY(50, 12); 
        $this->Cell(0, 8, 'BENJAMIN CARGO LOGISTICS BILL', 0, 1, 'C');

        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 10);
        $this->Ln(1);

        $startX = 60;
        $y = $this->GetY();
        $spacing = 40; 
        $warehouses = [
            'Accra Warehouse'   => '+233 554 358 745',
            'Kumasi Warehouse'  => '+233 256 120 389',
            'Takoradi Warehouse'=> '+233 545 161 026'
        ];

        $i = 0;
        foreach ($warehouses as $name => $phone) {
            $this->SetXY($startX + ($i * $spacing), $y);
            $this->Cell(50, 6, $name, 0, 2, 'C');
            $this->Cell(50, 4, $phone, 0, 2, 'C');
            $i++;
        }

        $this->Ln(6);
        $this->SetLineWidth(0.6);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(6);
    }

    function Footer() {
        $this->SetY(-25);
        $this->SetFont('Arial','I',9);
        $this->Cell(0,6,'Thank you for your business.',0,1,'C');
        $this->Cell(0,6,'Page '.$this->PageNo(),0,0,'C');
    }

    function InvoiceTop($customer, $code, $loadingDate, $etaDate) {
        $this->SetFont('Arial','B',12);
        $this->Cell(120,8,'Customer Name: '.strtoupper($customer),0,0,'L');
        $this->Cell(0,8,'Code: '.$code,0,1,'R');

        $this->SetFont('Arial','',10);
        $this->Cell(120,6,'Estimated Time of Arrival: '.$etaDate,0,0,'L');
        $this->Cell(0,6,'Loading Date: '.$loadingDate,0,1,'R');

        $this->Ln(5);
    }

    function NbLines($w, $txt) {
        $cw = $this->CurrentFont['cw'];
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $txt = str_replace("\r", '', $txt);
        $nb = strlen($txt);
        $sep = -1; $i = 0; $j = 0; $l = 0; $nl = 1;

        while ($i < $nb) {
            $c = $txt[$i];
            if ($c == "\n") { $i++; $sep=-1; $j=$i; $l=0; $nl++; continue; }
            if ($c==' ') $sep=$i;
            $l+=$cw[$c];
            if ($l>$wmax) {
                if($sep==-1){if($i==$j) $i++;} else $i=$sep+1;
                $sep=-1; $j=$i; $l=0; $nl++;
            } else { $i++; }
        }
        return $nl;
    }

    function TableRow($data, $widths, $aligns) {
        $nb=0;
        foreach ($data as $i=>$txt) $nb = max($nb,$this->NbLines($widths[$i],$txt));
        $h = 8*$nb;
        if($this->GetY()+$h>$this->PageBreakTrigger) $this->AddPage();
        foreach($data as $i=>$txt) {
            $x = $this->GetX();
            $y = $this->GetY();
            $this->Rect($x,$y,$widths[$i],$h);
            $this->MultiCell($widths[$i],8,$txt,0,$aligns[$i]);
            $this->SetXY($x+$widths[$i],$y);
        }
        $this->Ln($h);
    }

    function InvoiceTable() {
        $shipments = $this->shipments;
        $widths = [45, 70, 25, 30, 20];
        $aligns = ['C','L','C','C','C'];

        $this->SetFont('Arial','B',10);
        $this->SetFillColor(220,220,220);
        $headers = ['CODE','ITEM','CBM','UNIT PRICE','AMOUNT'];

        foreach($headers as $i=>$h) $this->Cell($widths[$i],10,$h,1,0,'C',true);
        $this->Ln();

        $this->SetFont('Arial','',10);

        $totalCBM = 0;
        $totalAmount = 0;

        foreach($shipments as $s) {
            $cbm  = (float)($s['volume_cbm'] ?? 0);
            $rate = (float)($s['rate'] ?? 0);
            $amount = $cbm * $rate;

            $totalCBM += $cbm;
            $totalAmount += $amount;

            $this->TableRow([
                $s['shipping_mark'],
                ucwords(strtolower($s['package_name'])),
                number_format($cbm,3),
                '$'.number_format($rate,2),
                number_format($amount,2)
            ], $widths, $aligns);
        }

        $this->Ln(4);
        $this->SetFont('Arial','B',12);
        $this->Cell(160,10,'Total CBM:',0,0,'R');
        $this->Cell(30,10,number_format($totalCBM,2),0,1,'R');
        $this->Cell(160,5,'Total:',0,0,'R');
        $this->Cell(30,5,'$'.number_format($totalAmount,2),0,1,'R');
    }
}

// =============================
// BUILD PDF
// =============================
$pdf = new InvoicePDF($shipments);
$pdf->AddPage();

$first = $shipments[0];

$pdf->InvoiceTop(
    $customer_name,
    $first['shipping_mark'] ?? '',
    $first['loading_date'] ?? 'N/A',
    $first['estimated_time_of_arrival'] ?? 'N/A'
);

$pdf->InvoiceTable();
$pdf->Output('I', 'Invoice_Customer_'.$customer_id.'.pdf');
exit;
?>
