<?php
    include 'db_connect.php';
    if (isset($_GET['id'])) {
        $idcom = $_GET['id'];

        try{
            $query = "DELETE from commande WHERE idcom=?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$idcom]);

            echo "<script>window.location.href='test_commande.php';</script>";
            exit();
        }
        catch (PDOException $e){
            echo "Erreur de suppression de SQL" . htmlspecialchars($e->getMessage());
            exit();
        }
    }
    else{
        echo "<script>window.location.href='test_commande.php';</script>";
        exit();
    }
?>