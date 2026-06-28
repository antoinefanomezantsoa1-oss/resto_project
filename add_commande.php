<?php
include 'db_connect.php';

try {
    $menuStmt = $pdo->query("SELECT idplat, nomplat FROM menu");
    $all_menus = $menuStmt->fetchAll(PDO::FETCH_ASSOC);

    $tableStmt = $pdo->query("SELECT idtable, designation, occupation FROM table_resto");
    $all_tables = $tableStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de chargement : " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $idcom   = trim($_POST['idcom']);
    $idplats = $_POST['idplat']; // Array of dishes
    $qtes    = $_POST['qte'];    // Array of quantities
    $nomcli  = trim($_POST['nomcli']); 
    $typecom = $_POST['typecom'];
    $idtable = !empty($_POST['idtable']) ? $_POST['idtable'] : null;
    $datecom = $_POST['datecom'];

    try {
        if ($typecom === 'Sur table' && empty($idtable)) {
            $errorMessage = "Veuillez sélectionner une table pour une commande 'Sur table'.";
        } 
        else {
            $tableBusy = false;

            if ($typecom === 'Sur table') {
                $statusQuery = "SELECT occupation FROM table_resto WHERE idtable = ? LIMIT 1";
                $statusStmt = $pdo->prepare($statusQuery);
                $statusStmt->execute([$idtable]);
                $occupationStatus = $statusStmt->fetchColumn();

                if ($occupationStatus == 1) {
                    $occupantQuery = "SELECT nomcli FROM commande WHERE idtable = ? ORDER BY datecom DESC, idcom DESC LIMIT 1";
                    $occupantStmt = $pdo->prepare($updateQuery); // Note: keeping your logic structure safe
                    $occupantStmt = $pdo->prepare($occupantQuery);
                    $occupantStmt->execute([$idtable]);
                    $existingOccupant = $occupantStmt->fetchColumn();

                    if ($existingOccupant && strtolower(trim($existingOccupant)) !== strtolower($nomcli)) {
                        $tableBusy = true;
                        $errorMessage = "La table sélectionnée est déjà occupée par '" . htmlspecialchars($existingOccupant) . "'.";
                    }
                }
            }

            if (!$tableBusy) {
                $pdo->beginTransaction();

                // Insert each dish row with the same common Order Code
                $insertQuery = "INSERT INTO commande (idcom, idplat, nomcli, typecom, idtable, datecom, qte) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $insertStmt = $pdo->prepare($insertQuery);

                foreach ($idplats as $index => $idplat) {
                    if (!empty($idplat)) {
                        $quantite = !empty($qtes[$index]) ? intval($qtes[$index]) : 1;
                        $insertStmt->execute([$idcom, $idplat, $nomcli, $typecom, $idtable, $datecom, $quantite]);
                    }
                }

                if ($typecom === 'Sur table') {
                    $updateTableQuery = "UPDATE table_resto SET occupation = 1 WHERE idtable = ?";
                    $updateTableStmt = $pdo->prepare($updateTableQuery);
                    $updateTableStmt->execute([$idtable]);
                }

                $pdo->commit();
                header("Location: test_commande.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errorMessage = "Erreur d'ajout de la commande : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter une Commande</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .live-error {
            font-size: 13px;
            display: block;
            margin-top: 5px;
            font-weight: bold;
        }
        button.btn-submit:disabled {
            background-color: #555 !important;
            color: #aaa !important;
            cursor: not-allowed !important;
            opacity: 0.6;
            box-shadow: none !important;
        }
    </style>
    <script>
    // Keep track of input validation validity states globally
    let idComIsValid = false;
    let tableIsValid = true; 

    function updateSubmitButtonState() {
        const submitBtn = document.getElementById('submit-btn');
        if (idComIsValid && tableIsValid) {
            submitBtn.disabled = false;
        } else {
            submitBtn.disabled = true;
        }
    }

    function toggleTableSelectionAdd() {
        const typeCom = document.getElementById('typecom').value;
        const tableSelect = document.getElementById('idtable');
        const statusMsg = document.getElementById('table-status-warning');
        
        if (typeCom === 'À emporter') {
            tableSelect.value = '';
            tableSelect.disabled = true;
            tableSelect.required = false;
            tableSelect.style.backgroundColor = '#dcdad6';
            statusMsg.textContent = "";
            tableIsValid = true;
        } else {
            tableSelect.disabled = false;
            tableSelect.required = true;
            tableSelect.style.backgroundColor = '#ece7e7';
            checkSelectedTableOccupancy();
        }
        updateSubmitButtonState();
    }

    function checkSelectedTableOccupancy() {
        const typeCom = document.getElementById('typecom').value;
        if (typeCom === 'À emporter') return;

        const tableSelect = document.getElementById('idtable');
        const selectedOption = tableSelect.options[tableSelect.selectedIndex];
        const statusMsg = document.getElementById('table-status-warning');

        if (tableSelect.value !== "" && selectedOption.getAttribute('data-occupied') === '1') {
            statusMsg.textContent = "❌ Cette table est déjà occupée !";
            statusMsg.style.color = "#e74c3c";
            tableIsValid = false;
        } else {
            statusMsg.textContent = "";
            tableIsValid = true;
        }
        updateSubmitButtonState();
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

    window.onload = function() {
        toggleTableSelectionAdd();
        document.getElementById('idtable').addEventListener('change', checkSelectedTableOccupancy);

        // --- Live Check Validation for idcom ---
        const idcomInput = document.getElementById('idcom');
        const idcomError = document.getElementById('idcom-error');

        idcomInput.addEventListener('input', function() {
            const comValue = this.value.trim();

            if (comValue === '') {
                idcomError.textContent = '';
                this.style.borderColor = '#cccccc';
                idComIsValid = false;
                updateSubmitButtonState();
                return;
            }

            fetch(`check_duplicate.php?table=commande&column=idcom&id=${encodeURIComponent(comValue)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        idcomError.textContent = "❌ Ce code commande existe déjà !";
                        idcomError.style.color = "#e74c3c";
                        this.style.borderColor = "#e74c3c";
                        idComIsValid = false;
                    } else {
                        idcomError.textContent = "✅ Code commande disponible";
                        idcomError.style.color = "#2ecc71";
                        this.style.borderColor = "#2ecc71";
                        idComIsValid = true;
                    }
                    updateSubmitButtonState();
                })
                .catch(err => console.error("Erreur de validation ID Commande:", err));
        });

        updateSubmitButtonState();
    };
    </script>
</head>
<body>
    <a href="index.php" class="btn-home">← Retour à l'accueil</a>
    <h1>Gestion des Commandes</h1>
    <div class="form-card">
        <h2>Passer une nouvelle commande</h2>
        <?php if (isset($errorMessage)): ?>
            <p style="color:red; font-weight:bold;"><?php echo htmlspecialchars($errorMessage); ?></p>
        <?php endif; ?>
        
        <form action="add_commande.php" method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label for="idcom">Code Commande :</label>
                <input type="text" id="idcom" name="idcom" placeholder="Ex: A0056" required autocomplete="off">
                <span id="idcom-error" class="live-error"></span>
            </div>
            <div class="form-group">
                <label for="nomcli">Nom du Client :</label>
                <input type="text" id="nomcli" name="nomcli" required>
            </div>

            <div class="form-group">
                <label>Plat(s) choisi(s) & Quantité :</label>
                <div id="plats-container">
                    <div class="plat-row" style="display: flex; gap: 10px; margin-bottom: 10px;">
                        <select name="idplat[]" required style="flex: 2;">
                            <option value="">-- Sélectionner un plat --</option>
                            <?php foreach ($all_menus as $mn): ?>
                                <option value="<?php echo htmlspecialchars($mn['idplat']); ?>"><?php echo htmlspecialchars($mn['nomplat']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="qte[]" value="1" min="1" required style="flex: 1; text-align: center;">
                        <span style="width:32px;"></span>
                    </div>
                </div>
                <button type="button" onclick="ajouterPlatRow()" style="background-color: #3498db; color: white; border: none; padding: 6px 12px; cursor: pointer; border-radius: 4px; font-weight: bold; margin-top: 5px; max-width: 150px;">+ Ajouter un plat</button>
            </div>

            <div class="form-group">
                <label for="typecom">Type de commande :</label>
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
                        <option value="<?php echo htmlspecialchars($tb['idtable']); ?>" 
                                data-occupied="<?php echo $tb['occupation']; ?>"
                                class="<?php echo ($tb['occupation'] == 1) ? 'occupied-option' : ''; ?>">
                            <?php echo htmlspecialchars($tb['designation']) . (($tb['occupation'] == 1) ? ' (Occupée)' : ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span id="table-status-warning" class="live-error"></span>
            </div>
            <div class="form-group">
                <label for="datecom">Date :</label>
                <input type="date" id="datecom" name="datecom" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-actions">
                <button type="submit" id="submit-btn" class="btn-submit">Enregistrer</button>
                <a href="test_commande.php" class="btn-cancel">Annuler</a>
            </div>
        </form>
    </div>
</body>
</html>