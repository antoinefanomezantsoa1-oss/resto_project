<?php
include 'db_connect.php';

// 1. Handle Updating a Table IN-LINE (POST request from the row form)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_inline') {
    $idtable = $_POST['idtable'];
    $designation = $_POST['designation'];

    try {
        $query = "UPDATE table_resto SET designation = ? WHERE idtable = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$designation, $idtable]);

        echo "<script>window.location.href='test_table.php';</script>";
        exit();
    } catch (PDOException $e) {
        $errorMessage = "Erreur de modification : " . $e->getMessage();
    }
}

// 2. Fetch all current tables with AUTOMATIC live tracking state
try {
    $query = "SELECT t.idtable, t.designation, 
                     IF(COUNT(CASE WHEN c.statut = 'Non payé' THEN 1 END) > 0, 1, 0) as occupation
              FROM table_resto t
              LEFT JOIN commande c ON t.idtable = c.idtable
              GROUP BY t.idtable, t.designation
              ORDER BY t.idtable ASC";
    $stmt = $pdo->query($query);
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de chargement : " . $e->getMessage());
}

// Check if we are currently editing a row inline
$edit_id = $_GET['edit_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Tables</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-container {
            width: 100%;
            max-width: none !important;
            margin: 0 auto;
            box-sizing: border-box;
        }

        table.wide-table {
            max-width: none !important;
            width: 100% !important;
            table-layout: fixed !important;
        }

        /* Layout Blueprint */
        table.wide-table th:nth-child(1), table.wide-table td:nth-child(1) { width: 15%; }
        table.wide-table th:nth-child(2), table.wide-table td:nth-child(2) { width: 35%; }
        table.wide-table th:nth-child(3), table.wide-table td:nth-child(3) { width: 25%; text-align: left; }
        table.wide-table th:nth-child(4), table.wide-table td:nth-child(4) { width: 25%; text-align: left; }

        .row-actions-view, .row-actions-edit {
            display: inline-flex;
            align-items: center;
            gap: 15px;
        }

        /* HARD CRUSH GLOBAL ANIMATIONS IF WE ARE EDITING A ROW */
        <?php if ($edit_id !== null): ?>
        body, .dashboard-container, table, tr, td, h1, h2, a, button {
            animation: none !important;
            transition: none !important;
            opacity: 1 !important;
            transform: none !important;
        }
        <?php endif; ?>

        /* Inline Input Box Styling */
        table.wide-table input[type="text"] {
            background-color: rgba(20, 20, 20, 0.6);
            color: #ffffff;
            border: 1px solid rgba(241, 196, 15, 0.4);
            border-radius: 4px;
            padding: 6px 10px;
            width: 90%;
            box-sizing: border-box;
            animation: none !important;
            transition: none !important;
        }
        
        table.wide-table input[type="text"]:focus {
            outline: none;
            border-color: #f1c40f;
            background-color: rgba(30, 30, 30, 0.8);
        }
    </style>
    <script>
    document.addEventListener("DOMContentLoaded", () => {
        // If we are editing, instantly kill the load animation timer to prevent a flash
        <?php if ($edit_id !== null): ?>
            document.body.style.animation = "none";
            document.body.style.transition = "none";
        <?php else: ?>
            setTimeout(() => {
                document.body.style.animation = "none";
            }, 150); 
        <?php endif; ?>
        
        const scrollPos = sessionStorage.getItem("tableScrollPos");
        if (scrollPos) {
            window.scrollTo(0, parseInt(scrollPos));
            sessionStorage.removeItem("tableScrollPos");
        }
    });

    window.addEventListener("beforeunload", () => {
        sessionStorage.setItem("tableScrollPos", window.scrollY);
    });
    </script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const overlay = document.getElementById("delete-modal-overlay");
        const confirmBtn = document.getElementById("modal-confirm-btn");
        const cancelBtn = document.getElementById("modal-cancel-btn");
        let targetUrl = "";

        document.querySelectorAll(".delete-link").forEach(link => {
            link.addEventListener("click", function(event) {
                event.preventDefault(); 
                targetUrl = this.href;   
                overlay.style.display = "flex";
            });
        });

        cancelBtn.addEventListener("click", function() {
            overlay.style.display = "none";
            targetUrl = "";
        });

        confirmBtn.addEventListener("click", function() {
            if (targetUrl !== "") {
                window.location.href = targetUrl;
            }
        });

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
    
    <div id="delete-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.65); backdrop-filter: blur(8px); z-index: 9999; justify-content: center; align-items: center;">
        <div style="background-color: rgba(30, 25, 20, 0.95); padding: 35px; border-radius: 12px; border: 1px solid rgba(241, 196, 15, 0.3); box-shadow: 0 10px 30px rgba(0,0,0,0.6); max-width: 420px; width: 90%; text-align: center; font-family: 'Segoe UI', sans-serif;">
            <h3 style="color: #f1c40f; margin-top: 0; font-size: 22px; letter-spacing: 1px; text-transform: uppercase;">Confirmation</h3>
            <p style="color: #ffffff; font-size: 15px; margin-bottom: 30px; line-height: 1.5;">
                Êtes-vous sûr de vouloir supprimer cette table ? Cette action est irréversible.
            </p>
            <div style="display: flex; justify-content: center; gap: 15px;">
                <button id="modal-confirm-btn" style="padding: 12px 28px; background-color: #a62626; color: white; border: none; border-radius: 30px; font-weight: bold; cursor: pointer; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 4px 15px rgba(166, 38, 38, 0.3);">Oui, supprimer</button>
                <button id="modal-cancel-btn" style="padding: 12px 28px; background-color: transparent; color: rgba(255, 255, 255, 0.6); border: none; border-radius: 30px; font-weight: bold; cursor: pointer; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Annuler</button>
            </div>
        </div>
    </div>

    <h1>Gestion des Tables</h1>
    
    <?php if (isset($errorMessage)) echo "<p class='error-msg'>$errorMessage</p>"; ?>

    <div class="dashboard-container">
        <div class="action-bar">
            <a href="add_table.php" class="btn-ajouter">+ Ajouter une Nouvelle Table</a>
        </div>

        <div>
            <h2>Liste des Tables Resto</h2>
            <table class="wide-table">
                <thead>
                    <tr>
                        <th>Code Table</th>
                        <th>Désignation</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($tables) > 0): ?>
                        <?php foreach ($tables as $tb): ?>
                            
                            <?php if ($edit_id === $tb['idtable']): ?>
                                <tr style="animation: none !important; transition: none !important;">
                                    <td><strong><?php echo htmlspecialchars($tb['idtable']); ?></strong></td>
                                    <td>
                                        <form id="form-edit-<?php echo htmlspecialchars($tb['idtable']); ?>" action="test_table.php" method="POST" style="margin: 0; padding: 0;">
                                            <input type="hidden" name="action" value="update_inline">
                                            <input type="hidden" name="idtable" value="<?php echo htmlspecialchars($tb['idtable']); ?>">
                                            <input type="text" name="designation" value="<?php echo htmlspecialchars($tb['designation']); ?>" required>
                                        </form>
                                    </td>
                                    <td>
                                        <span style="color: <?php echo ($tb['occupation'] == 0) ? '#2ecc71' : '#e74c3c'; ?>; font-weight: bold; font-size: 14px;">
                                            <?php echo ($tb['occupation'] == 0) ? 'Libre' : 'Occupée'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="row-actions-edit">
                                            <button type="submit" form="form-edit-<?php echo htmlspecialchars($tb['idtable']); ?>" class="btn-save-inline">Enregistrer</button>
                                            <a href="test_table.php" class="btn-cancel-inline">Annuler</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($tb['idtable']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($tb['designation']); ?></td>
                                    <td>
                                        <span style="color: <?php echo ($tb['occupation'] == 0) ? '#2ecc71' : '#e74c3c'; ?>; font-weight: bold;">
                                            <?php echo ($tb['occupation'] == 0) ? 'Libre' : 'Occupée'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="row-actions-view">
                                            <a href="test_table.php?edit_id=<?php echo urlencode($tb['idtable']); ?>" class="btn-edit-link">Modifier</a>
                                            <a href="delete_table.php?idtable=<?php echo urlencode($tb['idtable']); ?>" class="delete-link btn-delete-icon">
                                                <img src="images/trash.png" alt="Supprimer">
                                            </a> 
                                        </div>                                     
                                    </td>
                                </tr>
                            <?php endif; ?>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; font-weight: bold;">Aucune table enregistrée.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>