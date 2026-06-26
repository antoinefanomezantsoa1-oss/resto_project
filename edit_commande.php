<?php
include 'db_connect.php';

try {
    $menuStmt = $pdo->query("SELECT idplat, nomplat FROM menu");
    $all_dishes = $menuStmt->fetchAll(PDO::FETCH_ASSOC);

    $tableStmt = $pdo->query("SELECT idtable, designation FROM table_resto");
    $all_tables = $tableStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de chargement des listes : " . $e->getMessage());
}

if (isset($_GET['id'])) {
    $idcom = $_GET['id'];

    $query = "SELECT * FROM commande WHERE idcom = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$idcom]);
    $com = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$com) {
        echo "Commande non trouvée";
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idcom   = $_POST['idcom'];
    $idplat  = $_POST['idplat'];
    $nomcli  = $_POST['nomcli'];
    $typecom = $_POST['typecom'];
    $idtable = ($typecom === 'à emporter') ? null : $_POST['idtable'];
    $datecom = $_POST['datecom'];

    try {
        $query = "UPDATE commande 
                  SET nomcli = ?, idplat = ?, typecom = ?, idtable = ?, datecom = ?
                  WHERE idcom = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$nomcli, $idplat, $typecom, $idtable, $datecom, $idcom]);

        header("Location: test_commande.php");
        exit();
    } catch (PDOException $e) {
        echo "Erreur lors de la modification : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modification de la commande</title>
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
        input[type="text"], input[type="date"], select { 
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
    <script>
        function toggleTableField() {
            var typecom = document.getElementById("typecom").value;
            var tableSelect = document.getElementById("idtable");
            if (typecom === "à emporter") {
                tableSelect.value = "";
                tableSelect.disabled = true;
                tableSelect.style.backgroundColor = "#d3cccc";
            } else {
                tableSelect.disabled = false;
                tableSelect.style.backgroundColor = "#ece7e7";
            }
        }
        window.onload = function() {
            toggleTableField();
        };
    </script>
</head>
<body>
    <h1>Modifier une commande</h1>
    <div class="info-id">Modification de la commande ID : <?php echo htmlspecialchars($com['idcom'] ?? ''); ?></div>
    
    <form action="edit_commande.php?id=<?php echo urlencode($com['idcom'] ?? ''); ?>" method="POST">
        <input type="hidden" name="idcom" value="<?php echo htmlspecialchars($com['idcom'] ?? ''); ?>">

        <label for="nomcli">Nom Client :</label>
        <input type="text" id="nomcli" name="nomcli" value="<?php echo htmlspecialchars($com['nomcli'] ?? ''); ?>" required>

        <label for="idplat">Plat choisi :</label>
        <select id="idplat" name="idplat" required>
            <?php foreach ($all_dishes as $dish): ?>
                <option value="<?php echo htmlspecialchars($dish['idplat']); ?>" <?php if (($com['idplat'] ?? '') === $dish['idplat']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($dish['nomplat']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="typecom">Type commande :</label>
        <select id="typecom" name="typecom" onchange="toggleTableField()" required>
            <option value="sur table" <?php if (($com['typecom'] ?? '') === 'sur table') echo 'selected'; ?>>Sur table</option>
            <option value="à emporter" <?php if (($com['typecom'] ?? '') === 'à emporter') echo 'selected'; ?>>À emporter</option>
        </select>

        <label for="idtable">Table affectée :</label>
        <select id="idtable" name="idtable">
            <option value="">-- Aucune table (À emporter) --</option>
            <?php foreach ($all_tables as $tb): ?>
                <option value="<?php echo htmlspecialchars($tb['idtable']); ?>" <?php if (($com['idtable'] ?? '') === $tb['idtable']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($tb['designation']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="datecom">Date de la commande :</label>
        <input type="date" id="datecom" name="datecom" value="<?php echo htmlspecialchars($com['datecom'] ?? ''); ?>" required>
        
        <br>
        <button type="submit" class="btn-submit">Mettre à jour</button>
        <a href="test_commande.php" class="btn-cancel">Annuler</a>
    </form>
</body>
</html>