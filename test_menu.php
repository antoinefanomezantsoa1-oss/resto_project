<?php
include 'db_connect.php';

// 1. Handle Updating a Plat IN-LINE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_inline') {
    $idplat = $_POST['idplat'];
    $nomplat = $_POST['nomplat'];
    $pu = $_POST['pu'];

    try {
        $query = "UPDATE menu SET nomplat = ?, pu = ? WHERE idplat = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$nomplat, $pu, $idplat]);

        header("Location: test_menu.php");
        exit();
    } catch (PDOException $e) {
        $errorMessage = "Erreur de modification du plat : " . $e->getMessage();
    }
}

// 2. Fetch all current menu items from MariaDB
try {
    $query = "SELECT * FROM menu";
    $stmt = $pdo->query($query);
    $plats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de chargement du menu : " . $e->getMessage());
}

// Check if we are currently editing a row inline
$edit_id = $_GET['edit_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion du Menu</title>
    <style>html { background-color: #19140f; }</style>
    <link rel="stylesheet" href="style.css">
    <script>
    document.addEventListener("DOMContentLoaded", () => {
        setTimeout(() => {
            document.body.style.animation = "none";
        }, 150); 
        
        // Scroll memory behavior to stay at the exact same location after editing or refreshing
        const scrollPos = sessionStorage.getItem("menuScrollPos");
        if (scrollPos) {
            window.scrollTo(0, parseInt(scrollPos));
            sessionStorage.removeItem("menuScrollPos");
        }
    });

    window.addEventListener("beforeunload", () => {
        sessionStorage.setItem("menuScrollPos", window.scrollY);
    });
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
    <h1>Gestion du Menu (Plats)</h1>
    
    <?php if (isset($errorMessage)) echo "<p class='error-msg'>$errorMessage</p>"; ?>

    <div class="dashboard-container">
        
        <div class="action-bar">
            <a href="add_menu.php" class="btn-ajouter">+ Ajouter un Nouveau Plat</a>
        </div>

        <div>
            <h2>Liste des Plats</h2>
            <table class="wide-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Code Plat</th>
                        <th style="width: 35%;">Nom du Plat</th>
                        <th style="width: 20%;">Prix</th>
                        <th style="width: 30%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($plats) > 0): ?>
                        <?php foreach ($plats as $pl): ?>
                            
                            <?php if ($edit_id === $pl['idplat']): ?>
                                <form action="test_menu.php" method="POST">
                                    <input type="hidden" name="action" value="update_inline">
                                    <input type="hidden" name="idplat" value="<?php echo htmlspecialchars($pl['idplat']); ?>">
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($pl['idplat']); ?></strong></td>
                                        <td>
                                            <input type="text" name="nomplat" class="inline-input" value="<?php echo htmlspecialchars($pl['nomplat']); ?>" required>
                                        </td>
                                        <td>
                                            <input type="number" name="pu" step="0.01" class="inline-input" value="<?php echo htmlspecialchars($pl['pu']); ?>" required style="width: 110px;">
                                        </td>
                                        <td>
                                            <div class="row-actions-edit" style="display: flex; align-items: center; gap: 12px; white-space: nowrap;">
                                                <button type="submit" class="btn-save">Enregistrer</button>
                                                <a href="test_menu.php" class="btn-cancel">Annuler</a>
                                            </div>
                                        </td>
                                    </tr>
                                </form>
                            <?php else: ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($pl['idplat']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($pl['nomplat']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($pl['pu']); ?> Ar</strong></td>
                                    <td>
                                        <div class="row-actions-view">
                                            <a href="test_menu.php?edit_id=<?php echo urlencode($pl['idplat']); ?>" class="btn-edit" style="color: #f1c40f; font-weight: bold; text-decoration: none; font-size: 14px; transition: color 0.2s;"
       onmouseover="this.style.color='#fff'" 
       onmouseout="this.style.color='#f1c40f'">Modifier</a>
                                            <a href="delete_menu.php?idplat=<?php echo urlencode($pl['idplat']); ?>" class="delete-link">
                                                <img src="images/trash.png" alt="Supprimer" style="width: 20px !important; height: 20px !important; display: inline-block; vertical-align: middle;">
                                            </a>                                    
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; font-weight: bold;">Aucun plat enregistré dans le menu.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </div>

</body>
</html>