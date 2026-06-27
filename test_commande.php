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

        // Standard clean header redirect to avoid re-post prompt bugs
        header("Location: test_commande.php");
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
    <style>html { background-color: #19140f; }</style>
    <link rel="stylesheet" href="style.css">
    <script>
    document.addEventListener("DOMContentLoaded", () => {
        setTimeout(() => {
            document.body.style.animation = "none";
        }, 150); 
        
        // Retain view scroll alignment position after modifying values inline
        const scrollPos = sessionStorage.getItem("commandeScrollPos");
        if (scrollPos) {
            window.scrollTo(0, parseInt(scrollPos));
            sessionStorage.removeItem("commandeScrollPos");
        }
    });

    window.addEventListener("beforeunload", () => {
        sessionStorage.setItem("commandeScrollPos", window.scrollY);
    });
    </script>
    <script>
    function toggleTableSelectionInline() {
        const typeSelect = document.getElementById('inline_typecom');
        const tableSelect = document.getElementById('inline_idtable');
        
        if (typeSelect && tableSelect) {
            if (typeSelect.value === 'À emporter') {
                tableSelect.value = '';
                tableSelect.disabled = true;
                tableSelect.removeAttribute('required');
                tableSelect.style.backgroundColor = 'rgba(255,255,255,0.05)';
                tableSelect.style.color = 'rgba(255,255,255,0.2)';
            } else {
                tableSelect.disabled = false;
                tableSelect.setAttribute('required', 'required');
                tableSelect.style.backgroundColor = '#ffffff';
                tableSelect.style.color = '#333333';
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
                event.preventDefault(); 
                targetUrl = this.href;   
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

        // Hide modal if clicking outside the container dialog box
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
    <div id="delete-modal-overlay" style="
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.65);
    backdrop-filter: blur(8px); 
    z-index: 9999;
    justify-content: center;
    align-items: center;
">
    <div style="
        background-color: rgba(30, 25, 20, 0.95);
        padding: 35px;
        border-radius: 12px;
        border: 1px solid rgba(241, 196, 15, 0.3);
        box-shadow: 0 10px 30px rgba(0,0,0,0.6);
        max-width: 420px;
        width: 90%;
        text-align: center;
        font-family: 'Segoe UI', sans-serif;
    ">
        <h3 style="color: #f1c40f; margin-top: 0; font-size: 22px; letter-spacing: 1px; text-transform: uppercase;">Confirmation</h3>
        <p style="color: #ffffff; font-size: 15px; margin-bottom: 30px; line-height: 1.5;">
            Êtes-vous sûr de vouloir supprimer cet élément ? Cette action est irréversible.
        </p>
        
        <div style="display: flex; justify-content: center; gap: 15px;">
            <button id="modal-confirm-btn" style="
                padding: 12px 28px;
                background-color: #a62626;
                color: white;
                border: none;
                border-radius: 30px;
                font-weight: bold;
                cursor: pointer;
                font-size: 13px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                box-shadow: 0 4px 15px rgba(166, 38, 38, 0.3);
            ">Oui, supprimer</button>
            
            <button id="modal-cancel-btn" style="
                padding: 12px 28px;
                background-color: transparent;
                color: rgba(255, 255, 255, 0.6);
                border: none;
                border-radius: 30px;
                font-weight: bold;
                cursor: pointer;
                font-size: 13px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            ">Annuler</button>
        </div>
    </div>
</div>

    <a href="index.php" class="btn-home">← Retour à l'accueil</a>
    <h1>Gestion des Commandes</h1>
    <?php if (isset($errorMessage)) echo "<p class='error-msg'>$errorMessage</p>"; ?>

    <div class="dashboard-container">
        <div class="action-bar">
            <a href="add_commande.php" class="btn-ajouter">+ Ajouter une Nouvelle Commande</a>
        </div>

        <table class="wide-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Code Com</th>
                    <th style="width: 15%;">Client</th>
                    <th style="width: 18%;">Plat</th>
                    <th style="width: 12%;">Type</th>
                    <th style="width: 12%;">Table</th>
                    <th style="width: 13%;">Date Commande</th>
                    <th style="width: 20%;">Actions</th>
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
                                        <select name="idtable" id="inline_idtable" class="inline-select">
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
                                        <div class="row-actions-edit" style="display: flex; align-items: center; gap: 12px; white-space: nowrap;">
                                            <button type="submit" class="btn-save">Enregistrer</button>
                                            <a href="test_commande.php" class="btn-cancel">Annuler</a>
                                        </div>
                                    </td>
                                </tr>
                            </form>
                        <?php else: ?>
                            <tr>
                                <td>
                                    <a href="generate_pdf.php?id=<?php echo urlencode($com['idcom']); ?>" target="_blank" style="color: #f1c40f; font-weight: bold; text-decoration: none;" title="Cliquez pour voir la facture PDF">
                                        <?php echo htmlspecialchars($com['idcom']); ?> 📄
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($com['nomcli']); ?></td>
                                <td><?php echo htmlspecialchars($com['nomplat']); ?></td>
                                <td><?php echo htmlspecialchars($com['typecom']); ?></td>
                                <td><?php echo htmlspecialchars($com['designation'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($com['datecom']); ?></td>
                                <td>
                                    <div class="row-actions-view">
                                        <a href="test_commande.php?edit_id=<?php echo urlencode($com['idcom']); ?>" class="btn-edit">Modifier</a>
                                        <a href="delete_commande.php?idcom=<?php echo urlencode($com['idcom']); ?>" class="delete-link">
                                            <img src="images/trash.png" alt="Supprimer" style="width: 20px !important; height: 20px !important; display: inline-block; vertical-align: middle;">
                                        </a>
                                    </div>                                 
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