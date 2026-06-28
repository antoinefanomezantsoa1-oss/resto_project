<?php
include 'db_connect.php';

$idcom = $_GET['id'] ?? '';
if (empty($idcom)) {
    header("Location: test_commande.php");
    exit();
}

try {
    $menuStmt = $pdo->query("SELECT idplat, nomplat FROM menu");
    $all_menus = $menuStmt->fetchAll(PDO::FETCH_ASSOC);

    $tableStmt = $pdo->query("SELECT idtable, designation, occupation FROM table_resto");
    $all_tables = $tableStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch master metadata from the first entry of the order
    $masterStmt = $pdo->prepare("SELECT nomcli, typecom, idtable, datecom FROM commande WHERE idcom = ? LIMIT 1");
    $masterStmt->execute([$idcom]);
    $master = $masterStmt->fetch(PDO::FETCH_ASSOC);

    if (!$master) {
        die("Commande introuvable.");
    }

    // Fetch all dishes linked to this order code
    $itemsStmt = $pdo->prepare("SELECT idplat, qte FROM commande WHERE idcom = ?");
    $itemsStmt->execute([$idcom]);
    $current_items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur de chargement : " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update') {
    $idplats = $_POST['idplat'] ?? [];
    $qtes    = $_POST['qte'] ?? [];
    $nomcli  = trim($_POST['nomcli']); 
    $typecom = $_POST['typecom'];
    $idtable = !empty($_POST['idtable']) ? $_POST['idtable'] : null;
    $datecom = $_POST['datecom'];

    try {
        $pdo->beginTransaction();

        // 1. Clear previous items for this idcom to rebuild clean rows cleanly
        $deleteQuery = "DELETE FROM commande WHERE idcom = ?";
        $deleteStmt = $pdo->prepare($deleteQuery);
        $deleteStmt->execute([$idcom]);

        // 2. Re-insert the updated lines
        $insertQuery = "INSERT INTO commande (idcom, idplat, nomcli, typecom, idtable, datecom, qte) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $pdo->prepare($insertQuery);

        foreach ($idplats as $index => $idplat) {
            if (!empty($idplat)) {
                $quantite = !empty($qtes[$index]) ? intval($qtes[$index]) : 1;
                $insertStmt->execute([$idcom, $idplat, $nomcli, $typecom, $idtable, $datecom, $quantite]);
            }
        }

        // 3. Keep database room states synchronous
        if ($typecom === 'Sur table') {
            $pdo->prepare("UPDATE table_resto SET occupation = 1 WHERE idtable = ?")->execute([$idtable]);
        }

        $pdo->commit();
        header("Location: test_commande.php");
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $errorMessage = "Erreur de modification : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier la Commande</title>
    <link rel="stylesheet" href="style.css">
    <script>
    function toggleTableSelectionEdit() {
        const typeCom = document.getElementById('typecom').value;
        const tableSelect = document.getElementById('idtable');
        if (typeCom === 'À emporter') {
            tableSelect.value = '';
            tableSelect.disabled = true;
            tableSelect.required = false;
            tableSelect.style.backgroundColor = '#dcdad6';
        } else {
            tableSelect.disabled = false;
            tableSelect.required = true;
            tableSelect.style.backgroundColor = '#ece7e7';
        }
    }

    function ajouterPlatRow() {
        const container = document.getElementById('plats-container');
        const uniqueId = Date.now();
        const newRow = document.createElement('div');
        newRow.className = 'plat-row';
        newRow.style.display = 'flex';
        newRow.style.gap = '10px';
        newRow.style.marginBottom = '10px';
        newRow.id = 'row_' + uniqueId;

        newRow.innerHTML = `
            <select name="idplat[]" required style="flex: 2;">
                <option value="">-- Sélectionner un plat --</option>
                <?php foreach ($all_menus as $mn): ?>
                    <option value="<?php echo htmlspecialchars($mn['idplat']); ?>"><?php echo htmlspecialchars($mn['nomplat']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="qte[]" value="1" min="1" required style="flex: 1; text-align: center;">
            <button type="button" onclick="supprimerPlatRow('${uniqueId}')" style="background-color: #e74c3c; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 4px;">X</button>
        `;
        container.appendChild(newRow);
    }

    function supprimerPlatRow(id) {
        const row = document.getElementById('row_' + id);
        if (row) row.remove();
    }
    </script>
</head>
<body onload="toggleTableSelectionEdit()">
    <a href="test_commande.php" class="btn-home">← Retour au tableau</a>
    <h1>Modification Commande : <?php echo htmlspecialchars($idcom); ?></h1>
    
    <div class="form-card">
        <form action="edit_commande.php?id=<?php echo urlencode($idcom); ?>" method="POST">
            <input type="hidden" name="action" value="update">
            
            <div class="form-group">
                <label>Nom du Client :</label>
                <input type="text" name="nomcli" value="<?php echo htmlspecialchars($master['nomcli']); ?>" required>
            </div>

            <div class="form-group">
                <label>Plat(s) choisi(s) & Quantité :</label>
                <div id="plats-container">
                    <?php foreach ($current_items as $i => $item): $row_id = "row_init_".$i; ?>
                        <div class="plat-row" id="<?php echo $row_id; ?>" style="display: flex; gap: 10px; margin-bottom: 10px;">
                            <select name="idplat[]" required style="flex: 2;">
                                <?php foreach ($all_menus as $mn): ?>
                                    <option value="<?php echo htmlspecialchars($mn['idplat']); ?>" <?php if ($mn['idplat'] === $item['idplat']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($mn['nomplat']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" name="qte[]" value="<?php echo $item['qte']; ?>" min="1" required style="flex: 1; text-align: center;">
                            <button type="button" onclick="supprimerPlatRow('init_<?php echo $i; ?>')" style="background-color: #e74c3c; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 4px;">X</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" onclick="ajouterPlatRow()" style="background-color: #3498db; color: white; border: none; padding: 6px 12px; cursor: pointer; border-radius: 4px; font-weight: bold; margin-top: 5px; max-width: 150px;">+ Ajouter un plat</button>
            </div>

            <div class="form-group">
                <label for="typecom">Type de commande :</label>
                <select id="typecom" name="typecom" onchange="toggleTableSelectionEdit()" required>
                    <option value="Sur table" <?php if ($master['typecom'] === 'Sur table') echo 'selected'; ?>>Sur table</option>
                    <option value="À emporter" <?php if ($master['typecom'] === 'À emporter') echo 'selected'; ?>>À emporter</option>
                </select>
            </div>

            <div class="form-group">
                <label for="idtable">Table :</label>
                <select id="idtable" name="idtable">
                    <option value="">-- Choisir une table --</option>
                    <?php foreach ($all_tables as $tb): ?>
                        <option value="<?php echo htmlspecialchars($tb['idtable']); ?>" 
                                <?php if ($master['idtable'] === $tb['idtable']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($tb['designation']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Date :</label>
                <input type="date" name="datecom" value="<?php echo htmlspecialchars($master['datecom']); ?>" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Enregistrer les Modifications</button>
                <a href="test_commande.php" class="btn-cancel">Annuler</a>
            </div>
        </form>
    </div>
</body>
</html>