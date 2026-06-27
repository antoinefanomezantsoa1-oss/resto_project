<?php
 include 'db_connect.php';

 if (isset($_GET['idtable'])) {
        $idtable = $_GET['idtable'];

        try{
            $query = "DELETE from table_resto WHERE idtable=?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$idtable]);

            echo "<script>window.location.href='test_table.php';</script>";
            exit();
        }
        catch (PDOException $e){
            echo "Erreur de suppression de SQL" . htmlspecialchars($e->getMessage());
            exit();
        }
    }
    else{
        echo "<script>window.location.href='test_table.php';</script>";
        exit();
    }
?>