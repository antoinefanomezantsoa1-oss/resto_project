<?php
include 'db_connect.php';

$idcom    = $_GET['idcom'] ?? '';
$idtable  = $_GET['idtable'] ?? '';
$scroll_y = $_GET['scroll_y'] ?? '0'; // Capture coordinates safely

if (empty($idcom)) {
    header("Location: test_commande.php");
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Mark status as Paid
    $updateOrderQuery = "UPDATE commande SET statut = 'Payé' WHERE idcom = ?";
    $updateOrderStmt = $pdo->prepare($updateOrderQuery);
    $updateOrderStmt->execute([$idcom]);

    // 2. Clear table occupation
    if (!empty($idtable)) {
        $updateTableQuery = "UPDATE table_resto SET occupation = 0 WHERE idtable = ?";
        $updateTableStmt = $pdo->prepare($updateTableQuery);
        $updateTableStmt->execute([$idtable]);
    }

    $pdo->commit();
    
    // FIXED: Redirect while keeping the view locked right at the active row
    header("Location: test_commande.php?scroll_y=" . urlencode($scroll_y));
    exit();

} catch (PDOException $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    die("Erreur lors du traitement du paiement : " . $e->getMessage());
}
?>