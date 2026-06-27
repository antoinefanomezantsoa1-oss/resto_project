<?php
include 'db_connect.php';

try {
    $menuStmt = $pdo->query("SELECT idplat, nomplat FROM menu");
    $all_menus = $menuStmt->fetchAll(PDO::FETCH_ASSOC);

    // Pulling occupation along with ID and designation to build our dynamic frontend validation features
    $tableStmt = $pdo->query("SELECT idtable, designation, occupation FROM table_resto");
    $all_tables = $tableStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de chargement : " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $idcom   = trim($_POST['idcom']);
    $idplat  = $_POST['idplat'];
    $nomcli  = trim($_POST['nomcli']); 
    $typecom = $_POST['typecom'];
    $idtable = !empty($_POST['idtable']) ? $_POST['idtable'] : null;
    $datecom = $_POST['datecom'];

    try {
        // 1. Check if table is missing for dine-in orders
        if ($typecom === 'Sur table' && empty($idtable)) {
            $errorMessage = "Veuillez selectionner une table pour une commande 'Sur table'.";
        } 
        else {
            $tableBusy = false;

            if ($typecom === 'Sur table') {
                // Check the real-time status of the table in the table_resto table
                $statusQuery = "SELECT occupation FROM table_resto WHERE idtable = ? LIMIT 1";
                $statusStmt = $pdo->prepare($statusQuery);
                $statusStmt->execute([$idtable]);
                $occupationStatus = $statusStmt->fetchColumn();

                // If the table is physically occupied, find out who is sitting there
                if ($occupationStatus == 1) {
                    $occupantQuery = "SELECT nomcli FROM commande WHERE idtable = ? ORDER BY datecom DESC, idcom DESC LIMIT 1";
                    $occupantStmt = $pdo->prepare($occupantQuery);
                    $occupantStmt->execute([$idtable]);
                    $existingOccupant = $occupantStmt->fetchColumn();

                    // Block it only if a DIFFERENT client is trying to take it
                    if ($existingOccupant && strtolower(trim($existingOccupant)) !== strtolower($nomcli)) {
                        $tableBusy = true;
                        $errorMessage = "La table selectionnee est deja physiquement occupee par le client '" . htmlspecialchars($existingOccupant) . "'.";
                    }
                }
            }

            if (!$tableBusy) {
                // 3. Check if Code Commande already exists
                $checkQuery = "SELECT COUNT(*) FROM commande WHERE idcom = ?";
                $checkStmt = $pdo->prepare($checkQuery);
                $checkStmt->execute([$idcom]);
                $idExists = $checkStmt->fetchColumn();

                if ($idExists > 0) {
                    $errorMessage = "Le Code Commande '" . htmlspecialchars($idcom) . "' existe deja. Veuillez en entrer un autre.";
                } else {
                    // Start transaction to safely save order and lock table status simultaneously
                    $pdo->beginTransaction();

                    // 4. Safe to insert since table is free (or matches the same client)
                    $insertQuery = "INSERT INTO commande (idcom, idplat, nomcli, typecom, idtable, datecom) VALUES (?, ?, ?, ?, ?, ?)";
                    $insertStmt = $pdo->prepare($insertQuery);
                    $insertStmt->execute([$idcom, $idplat, $nomcli, $typecom, $idtable, $datecom]);

                    // 5. Automatically mark the table status as Occupied (1) in the database
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
    <!--style>
        body { font-family: 'Segoe UI', sans-serif; margin: 40px; background-color: #f9f9f9; color: #333; max-width: 500px; }
        h1 { color: #2c3e50; margin-top: 15px; }
        .btn-home { display: inline-block; text-decoration: none; color: #34495e; font-weight: bold; font-size: 14px; margin-bottom: 10px; transition: color 0.2s; }
        .btn-home:hover { color: #6707b6; }
        .form-card { background-color: #ece7e7; padding: 30px; border-radius: 6px; border: 1px solid #6707b6; }
        .form-group { display: flex; flex-direction: column; margin-bottom: 20px; }
        .form-group label { font-weight: bold; margin-bottom: 8px; color: #34495e; font-size: 14px; }
        .form-group input, .form-group select { padding: 10px; border: 1px solid #cccccc; border-radius: 4px; font-size: 15px; background-color: #ece7e7; color: #333; }
        .btn-submit { padding: 12px 25px; background-color: #2ecc71; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .btn-submit:disabled { background-color: #95a5a6; cursor: not-allowed; }
        .btn-cancel { color: #e74c3c; text-decoration: none; font-weight: bold; margin-left: 20px; }
        .live-error { font-size: 13px; font-weight: bold; margin-top: 5px; }
        .occupied-option { color: #7f8c8d; background-color: #dcdad6; }
    </style-->
    <link rel="stylesheet" href="style.css">
    <script>
    function toggleTableSelectionAdd() {
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

    function checkSelectedTableOccupancy() {
        const tableSelect = document.getElementById('idtable');
        const selectedOption = tableSelect.options[tableSelect.selectedIndex];
        const statusMsg = document.getElementById('table-status-warning');
        const submitBtn = document.getElementById('submit-btn');

        // Check the custom data-occupied flag we output inside the option markup
        if (selectedOption.getAttribute('data-occupied') === '1') {
            statusMsg.textContent = "❌ Cette table est déjà occupée dans la salle !";
            statusMsg.style.color = "#e74c3c";
            alert("Attention : Cette table est déjà occupée !");
            submitBtn.disabled = true;
        } else {
            statusMsg.textContent = "";
            // Only unlock if code confirmation hasn't clamped it down
            const idcomError = document.getElementById('idcom-error').textContent;
            if (!idcomError.includes("existe déjà")) {
                submitBtn.disabled = false;
            }
        }
    }

    window.onload = function() {
        toggleTableSelectionAdd();
        document.getElementById('idtable').addEventListener('change', checkSelectedTableOccupancy);
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
                <input type="text" id="idcom" name="idcom" placeholder="Ex: A0056" required>
                <span id="idcom-error" class="live-error"></span>
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
                <input type="date" id="datecom" name="datecom" required>
            </div>
            <div class="form-actions">
            <button type="submit" id="submit-btn" class="btn-submit">Enregistrer</button>
            <a href="test_commande.php" class="btn-cancel">Annuler</a>
            </div>

        </form>
    </div>

    <script>
    document.getElementById('idcom').addEventListener('input', function() {
        const idValue = this.value.trim();
        const errorSpan = document.getElementById('idcom-error');
        const submitBtn = document.getElementById('submit-btn');

        if (idValue === '') {
            errorSpan.textContent = '';
            this.style.borderColor = '#cccccc';
            submitBtn.disabled = false;
            return;
        }

        fetch(`check_duplicate.php?table=commande&column=idcom&id=${encodeURIComponent(idValue)}`)
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    errorSpan.textContent = "❌ Ce code commande existe déjà !";
                    errorSpan.style.color = "#e74c3c";
                    this.style.borderColor = "#e74c3c";
                    submitBtn.disabled = true;
                } else {
                    errorSpan.textContent = "✅ Code disponible";
                    errorSpan.style.color = "#2ecc71";
                    this.style.borderColor = "#2ecc71";
                    
                    // Only unlock if table warning isn't active
                    const tableWarning = document.getElementById('table-status-warning').textContent;
                    if (!tableWarning) {
                        submitBtn.disabled = false;
                    }
                }
            })
            .catch(err => console.error("Erreur de validation :", err));
    });
    </script>
</body>
</html>