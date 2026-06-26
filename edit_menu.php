<?php
   include 'db_connect.php';

   if (isset($_GET['id'])) {
    $idplat = $_GET['id'];  
    
    $query = "SELECT * FROM menu WHERE idplat = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$idplat]);
    $plat = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plat){
      echo "Plat non trouvé!";
      exit();
    }
   }

   if ($_SERVER["REQUEST_METHOD"] == "POST"){
      $idplat = $_POST['idplat'];
      $nomplat = $_POST['nomplat'];
      $pu = $_POST['pu'];

      try {
         $query = "UPDATE menu SET nomplat = ?, pu = ? WHERE idplat = ?";
         $stmt = $pdo->prepare($query);
         $stmt->execute([$nomplat, $pu, $idplat]);

         header("Location: test_menu.php");
         exit();
      }
      catch (PDOException $e){
          echo "Erreur lors de la modification des données : " . $e->getMessage();
      }
   }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier un Plat</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 40px; 
            background-color: #f9f9f9; 
            color: #333; 
            max-width: 500px; 
        }
        h1 { color: #2c3e50; border-bottom: 2px solid #34495e; padding-bottom: 10px; }
        .info-id { background-color: #eef2f7; padding: 8px 12px; border-left: 4px solid #3498db; border-radius: 4px; font-weight: bold; color: #7f8c8d; display: inline-block; margin-bottom: 15px; }
        label { font-weight: bold; display: block; margin-top: 20px; margin-bottom: 5px; color: #34495e; }
        input[type="text"], input[type="number"] { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            box-sizing: border-box; 
            font-size: 16px;
        }
        .btn-submit { 
            margin-top: 25px;
            padding: 12px 20px; 
            background-color: #3498db; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            font-weight: bold; 
            cursor: pointer;
            font-size: 16px;
        }
        .btn-submit:hover { background-color: #2980b9; }
        .btn-cancel { display: inline-block; margin-left: 20px; color: #e74c3c; text-decoration: none; font-weight: bold; }
        .btn-cancel:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Modifier un plat</h1>

    <div class="info-id">Modification du plat ID : <?php echo htmlspecialchars($plat['idplat'] ?? ''); ?></div>

    <form action="edit_menu.php" method="POST">
        <input type="hidden" name="idplat" value="<?php echo htmlspecialchars($plat['idplat'] ?? ''); ?>">

        <label for="nomplat">Nom Plat:</label>
        <input type="text" id="nomplat" name="nomplat" value="<?php echo htmlspecialchars($plat['nomplat'] ?? ''); ?>" required>

        <label for="pu">Prix unitaire (Ar):</label>
        <input type="number" id="pu" name="pu" value="<?php echo htmlspecialchars($plat['pu'] ?? ''); ?>" required>

        <button type="submit" class="btn-submit">Mettre à jour</button>
        <a href="test_menu.php" class="btn-cancel">Annuler</a>
    </form>
</body>
</html>