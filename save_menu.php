<?php
  include 'db_connect.php';

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idplat = $_POST['idplat'];
    $nomplat = $_POST['nomplat'];
    $pu = $_POST['pu'];

    try{
        $query = "INSERT into menu 
        (idplat, nomplat, pu) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($query);

        $stmt->execute([$idplat, $nomplat, $pu]);

        echo "<h1>Succes! Vous avez ajouté
        un nouveau plat dans le menu</h1>";
        echo  "<a href='add_menu.html'>
        Ajouter un nouveau plat</a>";
    }
    catch(PDOException $e){
        echo "<h1>Erreur lors de l'ajout du
        nouveau plat</h1>" . $e;
        echo "<a href='add_menu.html'>
        Revenir</a>";
    }
  }
  else {
    header("Location: add_menu.html");
    exit();
  }
?>