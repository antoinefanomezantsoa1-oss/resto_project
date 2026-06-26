<?php
  include 'db_connect.php';

    if (isset($_GET['id'])) {
    $idtable = $_GET['id'];  
    
    $query = "SELECT * FROM table_resto WHERE idtable = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$idtable]);
    $tb = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tb){
      echo "Table non trouvé!";
      exit();
    }
   }

    if ($_SERVER["REQUEST_METHOD"] == "POST"){
      $idtable = $_POST['idtable'];
      $designation = $_POST['designation'];
      $occupation = $_POST['occupation'];

      try {
         $query = "UPDATE table_resto SET designation = ?, occupation = ? WHERE idtable = ?";
         $stmt = $pdo->prepare($query);
         $stmt->execute([$designation, $occupation, $idtable]);

         header("Location: test_table.php");
         exit();
      }
      catch (PDOException $e){
          echo "Erreur lors de la modification des données : " . $e->getMessage();
      }
   }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modification des tables</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 40px; 
            background-color: #f9f9f9; 
            color: #333; 
            max-width: 500px; 
        }
        h1 { color: #2c3e50; border-bottom: 2px solid #34495e; padding-bottom: 10px; }
        .info-id { background-color: #ece7e7; padding: 8px 12px; border-left: 4px solid #6707b6; border-radius: 4px; font-weight: bold; color: #333; display: inline-block; margin-bottom: 15px; }
        label { font-weight: bold; display: block; margin-top: 20px; margin-bottom: 5px; color: #34495e; }
        input[type="text"], select { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            box-sizing: border-box; 
            font-size: 16px;
            background-color: #ece7e7;
            color: #333;
        }
        .btn-submit { 
            margin-top: 25px;
            padding: 12px 20px; 
            background-color: #2ecc71; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            font-weight: bold; 
            cursor: pointer;
            font-size: 16px;
        }
        .btn-submit:hover { background-color: #27ae60; }
        .btn-cancel { display: inline-block; margin-left: 20px; color: #e74c3c; text-decoration: none; font-weight: bold; }
        .btn-cancel:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Modifier une table</h1>

    <div class="info-id">Modification de la table ID : <?php echo htmlspecialchars($tb['idtable'] ?? ''); ?></div>

        <form action="edit_table.php" method="POST">
        <input type="hidden" name="idtable" value="<?php echo htmlspecialchars($tb['idtable'] ?? ''); ?>">

        <label for="designation">Designation:</label>
        <input type="text" id="designation" name="designation" value="<?php echo htmlspecialchars($tb['designation'] ?? ''); ?>" required>

        <label for="occupation">Occupation:</label>
        <select id="occupation" name="occupation" required style="width: 160px;">
                        <option value=0 <?php if (($tb['occupation'] ?? '') === 0) echo 'selected'; ?>>Libre</option>
                        <option value=1 <?php if (($tb['occupation'] ?? '') === 1) echo 'selected'; ?>>Occupée</option>
        </select>
        <br>
        <button type="submit" class="btn-submit">Mettre à jour</button>
        <a href="test_table.php" class="btn-cancel">Annuler</a>
    </form>

</body>
</html>