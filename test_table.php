<?php
include 'db_connect.php';

// 1. Handle Updating a Table IN-LINE (POST request from the row form)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_inline') {
    $idtable = $_POST['idtable'];
    $designation = $_POST['designation'];
    $occupation = $_POST['occupation'];

    try {
        $query = "UPDATE table_resto SET designation = ?, occupation = ? WHERE idtable = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$designation, $occupation, $idtable]);

        echo "<script>window.location.href='test_table.php';</script>";
        exit();
    } catch (PDOException $e) {
        $errorMessage = "Erreur de modification : " . $e->getMessage();
    }
}

// 2. Fetch all current tables from MariaDB
try {
    $query = "SELECT * FROM table_resto";
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
    <style>html { background-color: #19140f; }</style>
    <link rel="stylesheet" href="style.css">
    <script>
    document.addEventListener("DOMContentLoaded", () => {
        setTimeout(() => {
            document.body.style.animation = "none";
        }, 150); 
        
        // 2. Scroll memory behavior to stay at the exact same location after editing or refreshing
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
    <h1>Gestion des Tables</h1>
    
    <?php if (isset($errorMessage)) echo "<p class='error-msg'>$errorMessage</p>"; ?>

    <div class="dashboard-container">
        
        <div class="action-bar">
            <a href="add_table.php" class="btn-ajouter">+ Ajouter une Nouvelle Table</a>
        </div>

        <div>
            <h2>Liste des Tables Resto</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 15%;">Numéro Table</th>
                        <th style="width: 45%;">Désignation</th>
                        <th style="width: 20%;">Statut</th>
                        <th style="width: 20%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($tables) > 0): ?>
                        <?php foreach ($tables as $tb): ?>
                            
                            <?php if ($edit_id === $tb['idtable']): ?>
                                <form action="test_table.php" method="POST">
                                    <input type="hidden" name="action" value="update_inline">
                                    <input type="hidden" name="idtable" value="<?php echo htmlspecialchars($tb['idtable']); ?>">
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($tb['idtable']); ?></strong></td>
                                        <td>
                                            <input type="text" name="designation" class="inline-input" value="<?php echo htmlspecialchars($tb['designation']); ?>" required>
                                        </td>
                                        <td>
                                            <select name="occupation" class="inline-select" required>
                                                <option value="0" <?php if ($tb['occupation'] == 0) echo 'selected'; ?>>Libre</option>
                                                <option value="1" <?php if ($tb['occupation'] == 1) echo 'selected'; ?>>Occupée</option>
                                            </select>
                                        </td>
                                        <td>
                                            <div class="row-actions-edit">
                                                <button type="submit" class="btn-save">Enregistrer</button>
                                                <a href="test_table.php" class="btn-cancel">Annuler</a>
                                            </div>
                                        </td>
                                    </tr>
                                </form>
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
                                            <a href="test_table.php?edit_id=<?php echo urlencode($tb['idtable']); ?>" class="btn-edit">Modifier</a>
                                            <a href="delete_table.php?idtable=<?php echo urlencode($tb['idtable']); ?>" class="delete-link">
                                                <img src="images/trash.png" alt="Supprimer" style="width: 20px !important; height: 20px !important; display: inline-block; vertical-align: middle;">
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