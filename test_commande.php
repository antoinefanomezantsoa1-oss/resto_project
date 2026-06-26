<?php
include 'db_connect.php';

// 1. Fetch menus and tables for dynamic dropdown selections
try {
    $menuStmt = $pdo->query("SELECT idplat, nomplat FROM menu");
    $all_menus = $menuStmt->fetchAll(PDO::FETCH_ASSOC);

    $tableStmt = $pdo->query("SELECT idtable, designation FROM table_resto");
    $all_tables = $tableStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de chargement des relations : " . $e->getMessage());
}

// 2. Handle Updating an Order IN-LINE matching PDF keys
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_inline') {
    $idcom   = $_POST['idcom'];
    $idplat  = $_POST['idplat'];
    $nomcli  = $_POST['nomcli'];
    $typecom = $_POST['typecom'];
    $idtable = !empty($_POST['idtable']) ? $_POST['idtable'] : null;
    $datecom = $_POST['datecom'];

    try {
        $query = "UPDATE commande SET idplat = ?, nomcli = ?, typecom = ?, idtable = ?, datecom = ? WHERE idcom = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$idplat, $nomcli, $typecom, $idtable, $datecom, $idcom]);

        echo "<script>window.location.href='test_commande.php';</script>";
        exit();
    } catch (PDOException $e) {
        $errorMessage = "Erreur de modification de la commande : " . $e->getMessage();
    }
}

// 3. Fetch all current orders using double INNER JOIN
try {
    $query = "SELECT c.*, m.nomplat, t.designation 
              FROM commande c 
              INNER JOIN menu m ON c.idplat = m.idplat 
              LEFT JOIN table_resto t ON c.idtable = t.idtable 
              ORDER BY c.datecom DESC";
    $stmt = $pdo->query($query);
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de chargement des commandes : " . $e->getMessage());
}

$edit_id = $_GET['edit_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Commandes</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 40px; background-color: #f9f9f9; color: #333; }
        h1, h2 { color: #2c3e50; }
        .dashboard-container { display: flex; flex-direction: column; gap: 20px; }
        .top-bar { margin-bottom: 10px; }
        .btn-add-page { display: inline-block; padding: 10px 20px; background-color: #2ecc71; color: white; text-decoration: none; font-weight: bold; border-radius: 4px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; background-color: #ffffff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 5px; overflow: hidden; }
        th, td { padding: 12px 15px; text-align: left; border: 1px solid #474141; }
        th { background-color: #021b31; color: #ffffff; font-weight: 600; }
        tbody tr { background-color: #ece7e7; }
        tr:hover { background-color: #e2dbdb; }
        .inline-input, .inline-select { padding: 6px 10px; border: 1px solid #cccccc; border-radius: 4px; font-size: 14px; background-color: #ffffff; color: #333; box-sizing: border-box; width: 95%; }
        .btn-save { color: #2ecc71; background: none; border: none; font-weight: bold; font-size: 14px; cursor: pointer; }
        .btn-edit { color: #3498db; text-decoration: none; font-weight: bold; }
        .btn-delete, .btn-cancel { color: #e74c3c; text-decoration: none; font-weight: bold; margin-left: 15px; }
        .btn-cancel { color: #7f8c8d; }
        .error-msg { color: #e74c3c; font-weight: bold; margin-bottom: 15px; }
    </style>
    <script>
    function toggleTableSelectionInline() {
        const typeSelect = document.getElementById('inline_typecom');
        const tableSelect = document.getElementById('inline_idtable');
        
        // Only execute if the inline row form is currently open on the screen
        if (typeSelect && tableSelect) {
            if (typeSelect.value === 'À emporter') {
                tableSelect.value = '';
                tableSelect.disabled = true;
                tableSelect.style.backgroundColor = '#dcdad6';
            } else {
                tableSelect.disabled = false;
                tableSelect.style.backgroundColor = '#ffffff';
            }
        }
    }

    // Automatically trigger checks when the page runs an active edit block
    document.addEventListener("DOMContentLoaded", toggleTableSelectionInline);
    </script>
</head>
<body>

    <h1>Gestion des Commandes</h1>
    <?php if (isset($errorMessage)) echo "<p class='error-msg'>$errorMessage</p>"; ?>

    <div class="dashboard-container">
        <div class="top-bar">
            <a href="add_commande.php" class="btn-add-page">+ Ajouter une Nouvelle Commande</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">Code Com</th>
                    <th style="width: 15%;">Client</th>
                    <th style="width: 20%;">Plat</th>
                    <th style="width: 10%;">Type</th>
                    <th style="width: 15%;">Table</th>
                    <th style="width: 15%;">Date Commande</th>
                    <th style="width: 10%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($commandes) > 0): ?>
                    <?php foreach ($commandes as $com): ?>
                        <?php if ($edit_id === $com['idcom']): ?>
                            <form action="test_commande.php" method="POST">
                                <input type="hidden" name="action" value="update_inline">
                                <input type="hidden" name="idcom" value="<?php echo htmlspecialchars($com['idcom']); ?>">
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($com['idcom']); ?></strong></td>
                                    <td><input type="text" name="nomcli" class="inline-input" value="<?php echo htmlspecialchars($com['nomcli']); ?>" required></td>
                                    <td>
                                        <select name="idplat" class="inline-select" required>
                                            <?php foreach ($all_menus as $mn): ?>
                                                <option value="<?php echo htmlspecialchars($mn['idplat']); ?>" <?php if ($com['idplat'] === $mn['idplat']) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($mn['nomplat']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <!-- Added class and onchange handler -->
                                        <select name="typecom" id="inline_typecom" class="inline-select" onchange="toggleTableSelectionInline()" required>
                                            <option value="Sur table" <?php if ($com['typecom'] === 'Sur table') echo 'selected'; ?>>Sur table</option>
                                            <option value="À emporter" <?php if ($com['typecom'] === 'À emporter') echo 'selected'; ?>>À emporter</option>
                                        </select>
                                    </td>
                                    <td>
                                        <!-- Added id attribute -->
                                        <select name="idtable" id="inline_idtable" class="inline-select" required>
                                            <option value="">-- Aucune --</option>
                                            <?php foreach ($all_tables as $tb): ?>
                                                <option value="<?php echo htmlspecialchars($tb['idtable']); ?>" <?php if ($com['idtable'] === $tb['idtable']) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($tb['designation']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="date" name="datecom" class="inline-input" value="<?php echo htmlspecialchars($com['datecom']); ?>" required></td>
                                    <td>
                                        <button type="submit" class="btn-save">Enregistrer</button>
                                        <a href="test_commande.php" class="btn-cancel">Annuler</a>
                                        
                                    </td>
                                </tr>
                            </form>
                        <?php else: ?>
                            <tr>
                                <td>
                                    <a href="generate_pdf.php?id=<?php echo urlencode($com['idcom']); ?>" target="_blank" style="color: #6707b6; font-weight: bold; text-decoration: none;" title="Cliquez pour voir la facture PDF">
                                        <?php echo htmlspecialchars($com['idcom']); ?> 📄
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($com['nomcli']); ?></td>
                                <td><?php echo htmlspecialchars($com['nomplat']); ?></td>
                                <td><?php echo htmlspecialchars($com['typecom']); ?></td>
                                <td><?php echo htmlspecialchars($com['designation'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($com['datecom']); ?></td>
                                <td>
                                    <a href="test_commande.php?edit_id=<?php echo urlencode($com['idcom']); ?>" class="btn-edit">Modifier</a><br>
                                    <a href="javascript:void(0);" onclick="if(confirm('Supprimer cette commande ?')) { window.location.href='delete_commande.php?id=<?php echo urlencode($com['idcom']); ?>'; }" class="btn-delete">Supprimer</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align: center; font-weight: bold;">Aucune commande en cours.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>