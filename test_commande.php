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

// 3. Fetch all current orders using double INNER JOIN - FIXED TO ORDER BY CODE SEQUENTIALLY
try {
    $query = "SELECT c.*, m.nomplat, t.designation 
              FROM commande c 
              INNER JOIN menu m ON c.idplat = m.idplat 
              LEFT JOIN table_resto t ON c.idtable = t.idtable 
              ORDER BY c.idcom ASC";
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
        .btn-home { 
            display: inline-block; 
            text-decoration: none; 
            color: #34495e; 
            font-weight: bold; 
            font-size: 14px; 
            margin-bottom: 15px; 
            transition: color 0.2s; 
        }
        .btn-home:hover { color: #6707b6; }
        .btn-save { color: #2ecc71; background: none; border: none; font-weight: bold; font-size: 14px; cursor: pointer; }
        .btn-edit { color: #3498db; text-decoration: none; font-weight: bold; }
        .btn-delete, .btn-cancel { color: #e74c3c; text-decoration: none; font-weight: bold; margin-left: 15px; }
        .btn-cancel { color: #7f8c8d; }
        .delete-link {
        display: inline-block;
        padding: 6px 14px;
        background-color: #e74c3c; /* Red color matching your cancel choices */
        color: white !important; /* Forces the text to stay white instead of blue */
        text-decoration: none; /* Removes the ugly default underline */
        border-radius: 4px;
        font-weight: bold;
        font-size: 14px;
        transition: background-color 0.2s ease;
        }
        .delete-link:hover {
            background-color: #c0392b; /* Darker red on hover */
            text-decoration: none; /* Keeps underline gone during hover */
        }
        .error-msg { color: #e74c3c; font-weight: bold; margin-bottom: 15px; }
    </style>
    <script>
    function toggleTableSelectionInline() {
        const typeSelect = document.getElementById('inline_typecom');
        const tableSelect = document.getElementById('inline_idtable');
        
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

    document.addEventListener("DOMContentLoaded", toggleTableSelectionInline);
    </script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const overlay = document.getElementById("delete-modal-overlay");
        const confirmBtn = document.getElementById("modal-confirm-btn");
        const cancelBtn = document.getElementById("modal-cancel-btn");
        let targetUrl = "";

        // Intercept all delete link button clicks
        document.querySelectorAll(".delete-link").forEach(link => {
            link.addEventListener("click", function(event) {
                event.preventDefault(); // Stop the page from immediately navigating away
                targetUrl = this.href;   // Cache the PHP deletion destination path
                
                // Unroll the gorgeous blurred UI overlay window
                overlay.style.display = "flex";
            });
        });

        // If the user backs out, tuck it away safely
        cancelBtn.addEventListener("click", function() {
            overlay.style.display = "none";
            targetUrl = "";
        });

        // If they approve, drop into the target route script natively
        confirmBtn.addEventListener("click", function() {
            if (targetUrl !== "") {
                window.location.href = targetUrl;
            }
        });

        // Optional: Hide modal if clicking outside the container dialog box
        overlay.addEventListener("click", function(event) {
            if (event.target === overlay) {
                overlay.style.display = "none";
                targetUrl = "";
            }
        });
    });
    </script>
</head>
<body>
    <a href="index.php" class="btn-home">← Retour à l'accueil</a>
    <div id="delete-modal-overlay" style="
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(5px); /* Blurs the restaurant background data */
    z-index: 9999;
    justify-content: center;
    align-items: center;
">
    <div style="
        background-color: #f7f4f4;
        padding: 30px;
        border-radius: 8px;
        border: 2px solid #6707b6;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        max-width: 400px;
        width: 90%;
        text-align: center;
        font-family: 'Segoe UI', sans-serif;
    ">
        <h3 style="color: #2c3e50; margin-top: 0; font-size: 20px;">Confirmation</h3>
        <p style="color: #333333; font-size: 15px; margin-bottom: 25px;">
            Êtes-vous sûr de vouloir supprimer cet élément ?
        </p>
        
        <div style="display: flex; justify-content: center; gap: 15px;">
            <button id="modal-confirm-btn" style="
                padding: 10px 25px;
                background-color: #e74c3c;
                color: white;
                border: none;
                border-radius: 4px;
                font-weight: bold;
                cursor: pointer;
                font-size: 15px;
            ">Oui, supprimer</button>
            
            <button id="modal-cancel-btn" style="
                padding: 10px 25px;
                background-color: #95a5a6;
                color: white;
                border: none;
                border-radius: 4px;
                font-weight: bold;
                cursor: pointer;
                font-size: 15px;
            ">Annuler</button>
        </div>
    </div>
</div>
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
                                        <select name="typecom" id="inline_typecom" class="inline-select" onchange="toggleTableSelectionInline()" required>
                                            <option value="Sur table" <?php if ($com['typecom'] === 'Sur table') echo 'selected'; ?>>Sur table</option>
                                            <option value="À emporter" <?php if ($com['typecom'] === 'À emporter') echo 'selected'; ?>>À emporter</option>
                                        </select>
                                    </td>
                                    <td>
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
                                    <a href="delete_commande.php?idcom=<?php echo urlencode($com['idcom']); ?>" class="delete-link">Supprimer</a>                                </td>
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