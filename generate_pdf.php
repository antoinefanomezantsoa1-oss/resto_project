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
    // 1. Fetch Master Order/Client Metadata (Single Row)
    $masterQuery = "SELECT c.idcom, c.nomcli, c.typecom, c.datecom, t.designation 
                    FROM commande c 
                    LEFT JOIN table_resto t ON c.idtable = t.idtable 
                    WHERE c.idcom = ? 
                    LIMIT 1";
    $masterStmt = $pdo->prepare($masterQuery);
    $masterStmt->execute([$idcom]);
    $order = $masterStmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die("Commande introuvable.");
    }

    // 2. Fetch All Accompanying Dishes for this specific Order Code
    $itemsQuery = "SELECT m.nomplat, m.pu, c.qte 
                   FROM commande c 
                   INNER JOIN menu m ON c.idplat = m.idplat 
                   WHERE c.idcom = ?";
    $itemsStmt = $pdo->prepare($itemsQuery);
    $itemsStmt->execute([$idcom]);
    $order_items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

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
$pdf->Cell(80, 5, "Nom du Client: " . utf8_decode(strtoupper($order['nomcli'])), 0, 1, 'L');
$pdf->Cell(80, 5, "Table: " . utf8_decode($order['designation'] ?? 'A emporter'), 0, 1, 'L');
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
$pdf->Cell(12, 6, "Qte", 1, 0, 'C'); 
$pdf->Cell(18, 6, "Total (Ar)", 1, 1, 'R');

// 3. Item Metrics Calculations Loop
$pdf->SetFont('Arial', '', 8);
$total_facture = 0; // Cumulative tracker across all rows

foreach ($order_items as $item) {
    $quantite = isset($item['qte']) ? intval($item['qte']) : 1;
    $total_item = $item['pu'] * $quantite;
    $total_facture += $total_item; // Add to overall bill total

    // Format numbers with thousands separators (e.g., 1.500)
    $pu_formatted = number_format($item['pu'], 0, ',', '.');
    $total_formatted = number_format($total_item, 0, ',', '.');

    // Write dynamic Row Data for this specific item loop step
    $pdf->Cell(35, 6, utf8_decode($item['nomplat']), 1, 0, 'L');
    $pdf->Cell(15, 6, $pu_formatted, 1, 0, 'R');
    $pdf->Cell(12, 6, $quantite, 1, 0, 'C');
    $pdf->Cell(18, 6, $total_formatted, 1, 1, 'R');
}

$pdf->Ln(4);

// 4. Final Total Calculation Summary
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(62, 6, "TOTAL:", 0, 0, 'R');
$pdf->Cell(18, 6, number_format($total_facture, 0, ',', '.') . " Ar", 0, 1, 'R');

// Output browser inline stream preview
$pdf->Output('I', "Facture_" . $order['idcom'] . ".pdf");
?>