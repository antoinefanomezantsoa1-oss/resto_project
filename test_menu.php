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

        echo "<script>window.location.href='test_menu.php';</script>";
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
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 40px; 
            background-color: #f9f9f9; 
            color: #333333; 
        }
        h1, h2 { color: #2c3e50; }
        .dashboard-container { display: flex; flex-direction: column; gap: 20px; }
        
        .top-bar {
            display: block;
            margin-bottom: 10px;
        }

        .btn-add-page {
            display: inline-block;
            padding: 10px 20px;
            background-color: #2ecc71;
            color: white;
            text-decoration: none;
            font-weight: bold;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn-add-page:hover { background-color: #27ae60; }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            background-color: #ffffff; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
            border-radius: 5px; 
            overflow: hidden; 
        }
        th, td { 
            padding: 12px 15px; 
            text-align: left; 
            border: 1px solid #474141; 
        }
        th { 
            background-color: #021b31; 
            color: #ffffff; 
            font-weight: 600; 
        }
        tbody tr { background-color: #ece7e7; }
        tr:hover { background-color: #e2dbdb; }

        .inline-input {
            padding: 6px 10px;
            border: 1px solid #cccccc;
            border-radius: 4px;
            font-size: 14px;
            background-color: #ffffff;
            color: #333333;
            width: 95%;
            box-sizing: border-box;
        }
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
        .btn-save { color: #2ecc71; background: none; border: none; font-weight: bold; font-size: 14px; cursor: pointer; padding: 0; }
        .btn-save:hover { text-decoration: underline; }
        .btn-edit { color: #3498db; text-decoration: none; font-weight: bold; }
        .btn-delete { color: #e74c3c; text-decoration: none; font-weight: bold; margin-left: 15px; }
        .btn-cancel { color: #7f8c8d; text-decoration: none; font-weight: bold; margin-left: 15px; }
        .error-msg { color: #e74c3c; font-weight: bold; margin-bottom: 15px; }
    </style>
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
        background-color: #ece7e7;
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
    <h1>Gestion du Menu (Plats)</h1>
    
    <?php if (isset($errorMessage)) echo "<p class='error-msg'>$errorMessage</p>"; ?>

    <div class="dashboard-container">
        
        <div class="top-bar">
            <a href="add_menu.php" class="btn-add-page">+ Ajouter un Nouveau Plat</a>
        </div>

        <div>
            <h2>Liste des Plats</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 20%;">Code Plat</th>
                        <th style="width: 20%;">Nom du Plat</th>
                        <th style="width: 20%;">Prix</th>
                        <th style="width: 40%;">Actions</th>
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
                                            <input type="number" name="pu" step="0.01" class="inline-input" value="<?php echo htmlspecialchars($pl['pu']); ?>" required style="width: 90px;">
                                        </td>
                                        <td>
                                            <button type="submit" class="btn-save">Enregistrer</button>
                                            <a href="test_menu.php" class="btn-cancel">Annuler</a>
                                        </td>
                                    </tr>
                                </form>
                            <?php else: ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($pl['idplat']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($pl['nomplat']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($pl['pu']); ?> Ar</strong></td>
                                    <td>
                                        <a href="test_menu.php?edit_id=<?php echo urlencode($pl['idplat']); ?>" class="btn-edit">Modifier</a>
                                        <a href="delete_menu.php?idplat=<?php echo urlencode($pl['idplat']); ?>" class="delete-link">Supprimer</a>                                    </td>
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