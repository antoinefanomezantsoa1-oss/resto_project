<?php
include 'db_connect.php';

if (isset($_GET['idreserv'])) {
    $idreserv = $_GET['idreserv'];

    try {
        $query = "DELETE FROM reserver WHERE idreserv = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$idreserv]);
        
        header("Location: test_reservation.php");
        exit();
    } catch (PDOException $e) {
        echo "Erreur lors de la suppression de la réservation : " . $e->getMessage();
    }
} else {
    header("Location: test_reservation.php");
    exit();
}
?>