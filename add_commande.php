<?php
include 'db_connect.php';

try {
    $menuStmt = $pdo->query("SELECT idplat, nomplat FROM menu");
    $all_menus = $menuStmt->fetchAll(PDO::FETCH_ASSOC);

    $tableStmt = $pdo->query("SELECT idtable, designation FROM table_resto");
    $all_tables = $tableStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de chargement : " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $idcom   = $_POST['idcom'];
    $idplat  = $_POST['idplat'];
    $nomcli  = $_POST['nomcli'];
    $typecom = $_POST['typecom'];
    $idtable = !empty($_POST['idtable']) ? $_POST['idtable'] : null;
    $datecom = $_POST['datecom'];

    try {
        $insertQuery = "INSERT INTO commande (idcom, idplat, nomcli, typecom, idtable, datecom) VALUES (?, ?, ?, ?, ?, ?)";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([$idcom, $idplat, $nomcli, $typecom, $idtable, $datecom]);
        
        header("Location: test_commande.php");
        exit();
    } catch (PDOException $e) {
        $errorMessage = "Erreur d'ajout de la commande : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter une Commande</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 40px; background-color: #f9f9f9; color: #333; max-width: 500px; }
        h1 { color: #2c3e50; }
        .form-card { background-color: #ece7e7; padding: 30px; border-radius: 6px; border: 1px solid #6707b6; }
        .form-group { display: flex; flex-direction: column; margin-bottom: 20px; }
        .form-group label { font-weight: bold; margin-bottom: 8px; color: #34495e; font-size: 14px; }
        .form-group input, .form-group select { padding: 10px; border: 1px solid #cccccc; border-radius: 4px; font-size: 15px; background-color: #ece7e7; color: #333; }
        .btn-submit { padding: 12px 25px; background-color: #2ecc71; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .btn-cancel { color: #e74c3c; text-decoration: none; font-weight: bold; margin-left: 20px; }
    </style>
    <script>
    function toggleTableSelectionAdd() {
        const typeCom = document.getElementById('typecom').value;
        const tableSelect = document.getElementById('idtable');
        
        if (typeCom === 'À emporter') {
            tableSelect.value = '';        // Clear any selected table
            tableSelect.disabled = true;   // Freeze the field
            tableSelect.style.backgroundColor = '#dcdad6'; // Make it look disabled
        } else {
            tableSelect.disabled = false;  // Unfreeze
            tableSelect.style.backgroundColor = '#ece7e7'; // Restore normal style
        }
    }
    // Run it on page load to ensure state correctness
    window.onload = toggleTableSelectionAdd;
    </script>
</head>
<body>
    <h1>Gestion des Commandes</h1>
    <div class="form-card">
        <h2>Passer une nouvelle commande</h2>
        <?php if (isset($errorMessage)) echo "<p style='color:red;'>$errorMessage</p>"; ?>
        <form action="add_commande.php" method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label for="idcom">Code Commande :</label>
                <input type="text" id="idcom" name="idcom" placeholder="Ex: A0056" required>
            </div>
            <div class="form-group">
                <label for="nomcli">Nom du Client :</label>
                <input type="text" id="nomcli" name="nomcli" required>
            </div>
            <div class="form-group">
                <label for="idplat">Plat choisi :</label>
                <select id="idplat" name="idplat" required>
                    <option value="">-- Sélectionner un plat --</option>
                    <?php foreach ($all_menus as $mn): ?>
                        <option value="<?php echo htmlspecialchars($mn['idplat']); ?>"><?php echo htmlspecialchars($mn['nomplat']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="typecom">Type de commande :</label>
                <!-- Added onchange event -->
                <select id="typecom" name="typecom" onchange="toggleTableSelectionAdd()" required>
                    <option value="Sur table">Sur table</option>
                    <option value="À emporter">À emporter</option>
                </select>
            </div>

            <div class="form-group">
                <label for="idtable">Table (Si sur table) :</label>
                <select id="idtable" name="idtable">
                    <option value="">-- Choisir une table --</option>
                    <?php foreach ($all_tables as $tb): ?>
                        <option value="<?php echo htmlspecialchars($tb['idtable']); ?>"><?php echo htmlspecialchars($tb['designation']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="datecom">Date :</label>
                <input type="date" id="datecom" name="datecom" required>
            </div>
            <button type="submit" class="btn-submit">Enregistrer</button>
            <a href="test_commande.php" class="btn-cancel">Annuler</a>
        </form>
    </div>
</body>
</html>