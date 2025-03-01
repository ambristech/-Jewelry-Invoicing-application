<?php
require_once 'db_connect.php';
require('fpdf/fpdf.php'); // Download from http://www.fpdf.org/

$invoice_id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM invoices WHERE id = ?");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch();

$stmt = $conn->prepare("SELECT ii.*, i.name FROM invoice_items ii JOIN items i ON ii.item_id = i.id WHERE ii.invoice_id = ?");
$stmt->execute([$invoice_id]);
$items = $stmt->fetchAll();

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Invoice #' . $invoice['invoice_number'],0,1,'C');
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,10,'Customer: ' . $invoice['customer_name'],0,1);
$pdf->Cell(0,10,'Date: ' . $invoice['created_at'],0,1);

$pdf->Ln(10);
$pdf->SetFont('Arial','B',12);
$pdf->Cell(80,10,'Item',1);
$pdf->Cell(30,10,'Quantity',1);
$pdf->Cell(40,10,'Price',1);
$pdf->Cell(40,10,'Total',1);
$pdf->Ln();

$pdf->SetFont('Arial','',12);
foreach ($items as $item) {
    $pdf->Cell(80,10,$item['name'],1);
    $pdf->Cell(30,10,$item['quantity'],1);
    $pdf->Cell(40,10,$item['price'],1);
    $pdf->Cell(40,10,$item['quantity'] * $item['price'],1);
    $pdf->Ln();
}

$pdf->SetFont('Arial','B',12);
$pdf->Cell(150,10,'Total',1);
$pdf->Cell(40,10,$invoice['total_amount'],1);

$pdf->Output('D', 'invoice_' . $invoice['invoice_number'] . '.pdf');
?>