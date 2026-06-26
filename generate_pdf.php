<?php
// Include the library and database connection
define('FPDF_FONTPATH', 'font/');
require('fpdf.php');
include 'db_connect.php';

// Get the order ID from the URL string parameter
$idcom = $_GET['id'] ?? '';

if (empty($idcom)) {
    die("ID Commande manquant.");
}

try {
    // Fetch order details mixed with menu and table designations
    $query = "SELECT c.*, m.nomplat, m.pu, t.designation 
              FROM commande c 
              INNER JOIN menu m ON c.idplat = m.idplat 
              LEFT JOIN table_resto t ON c.idtable = t.idtable 
              WHERE c.idcom = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$idcom]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("Commande introuvable.");
    }
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// Instantiate and build the PDF Document Layout
$pdf = new FPDF('P', 'mm', array(100, 150), ''); // Compact ticket style sizing
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);

// 1. Header Information
$pdf->Cell(80, 5, "NOM DU RESTAURANT", 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(80, 5, "Code Commande: " . $order['idcom'], 0, 1, 'L');
$pdf->Cell(80, 5, "Nom du Client: " . strtoupper($order['nomcli']), 0, 1, 'L');
$pdf->Cell(80, 5, "Table: " . ($order['designation'] ?? 'A emporter'), 0, 1, 'L');
$pdf->Cell(80, 5, "Date: " . $order['datecom'], 0, 1, 'L');
$pdf->Ln(4);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(80, 5, "Votre facture en detail", 0, 1, 'L');
$pdf->Line(10, $pdf->GetY(), 90, $pdf->GetY()); // Separation Line
$pdf->Ln(2);

// 2. Table Headers
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(35, 6, "Menu", 1, 0, 'L');
$pdf->Cell(15, 6, "PU (Ar)", 1, 0, 'R');
$pdf->Cell(12, 6, "Qte", 1, 0, 'C'); // Note: Make sure 'qte' exists in your schema table or defaults to 1
$pdf->Cell(18, 6, "Total (Ar)", 1, 1, 'R');

// 3. Item Metrics Calculations
$pdf->SetFont('Arial', '', 8);
$quantite = isset($order['qte']) ? intval($order['qte']) : 1; // Safeguard if column variation occurs
$total_item = $order['pu'] * $quantite;

// Format numbers with thousands separators to match 1.500 Ar blueprint notation
$pu_formatted = number_format($order['pu'], 0, ',', '.');
$total_formatted = number_format($total_item, 0, ',', '.');

// Write Row Data
$pdf->Cell(35, 6, utf8_decode($order['nomplat']), 1, 0, 'L');
$pdf->Cell(15, 6, $pu_formatted, 1, 0, 'R');
$pdf->Cell(12, 6, $quantite, 1, 0, 'C');
$pdf->Cell(18, 6, $total_formatted, 1, 1, 'R');

$pdf->Ln(4);

// 4. Final Total Calculation Summary
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(62, 6, "TOTAL:", 0, 0, 'R');
$pdf->Cell(18, 6, number_format($total_item, 0, ',', '.') . " Ar", 0, 1, 'R');

// Output browser inline stream preview
$pdf->Output('I', "Facture_" . $order['idcom'] . ".pdf");
?>