<?php
include 'db_connect.php';

// Grab the targeting table variable id from your payment link query string
$idtable = $_GET['idtable'] ?? '';

if (!empty($idtable)) {
    try {
        $pdo->beginTransaction();

        // 1. Logic here to mark the order invoice or bill as paid...
        // (e.g., UPDATE commande SET statut_paiement = 'paye' WHERE idtable = ? ...)

        // 2. RELEASE THE TABLE: Reset occupation flag parameter to 0 (Libre)
        $releaseQuery = "UPDATE table_resto SET occupation = 0 WHERE idtable = ?";
        $releaseStmt = $pdo->prepare($releaseQuery);
        $releaseStmt->execute([$idtable]);

        $pdo->commit();
        
        // Redirect back to dashboard display overview page
        header("Location: test_table.php?msg=Table_liberee");
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Erreur lors de la libération de la table : " . $e->getMessage());
    }
} else {
    header("Location: test_table.php");
    exit();
}
?>