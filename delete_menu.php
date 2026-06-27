<?php
include 'db_connect.php';

if (isset($_GET['idplat'])) {
    $idplat = $_GET['idplat'];

    try {
        $query = "DELETE FROM menu WHERE idplat = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$idplat]);

        echo "<script>window.location.href='test_menu.php';</script>";
        exit();
    } catch (PDOException $e) {
        echo "Erreur de suppression SQL : " . htmlspecialchars($e->getMessage());
        exit();
    }
} else {
    echo "<script>window.location.href='test_menu.php';</script>";
    exit();
}
?>