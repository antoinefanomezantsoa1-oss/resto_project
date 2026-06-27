<?php
include 'db_connect.php';

// 1. Fetch available tables for inline dropdown selection
try {
    $tableStmt = $pdo->query("SELECT idtable, designation FROM table_resto");
    $all_tables = $tableStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de chargement : " . $e->getMessage());
}

// 2. Handle Updating a Reservation IN-LINE with full safety checks
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_inline') {
    $idreserv       = $_POST['idreserv'];
    $idtable        = $_POST['idtable'];
    $date_de_reserv = $_POST['date_de_reserv'];
    $date_reservee  = $_POST['date_reservee'];
    $nomcli         = trim($_POST['nomcli']);

    try {
        // Strict hourly conflict check, explicitly excluding the current reservation row ID
        $conflictQuery = "SELECT COUNT(*) FROM reserver 
                          WHERE idtable = ? 
                          AND date_reservee >= DATE_SUB(?, INTERVAL 1 HOUR) 
                          AND date_reservee <= DATE_ADD(?, INTERVAL 1 HOUR)
                          AND idreserv != ?";
        $conflictStmt = $pdo->prepare($conflictQuery);
        $conflictStmt->execute([$idtable, $date_reservee, $date_reservee, $idreserv]);
        
        if ($conflictStmt->fetchColumn() > 0) {
            $errorMessage = "Erreur : Cette table est deja reservee a cette heure (creneau de 1 heure protege).";
        } else {
            // Update record if no conflicting schedules found
            $query = "UPDATE reserver SET idtable = ?, date_de_reserv = ?, date_reservee = ?, nomcli = ? WHERE idreserv = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$idtable, $date_de_reserv, $date_reservee, $nomcli, $idreserv]);

            header("Location: test_reservation.php");
            exit();
        }
    } catch (PDOException $e) {
        $errorMessage = "Erreur de modification de la réservation : " . $e->getMessage();
    }
}

// 3. Fetch current reservations with a JOIN - FIXED TO ORDER BY CODE SEQUENTIALLY
try {
    $query = "SELECT r.*, t.designation FROM reserver r 
              INNER JOIN table_resto t ON r.idtable = t.idtable 
              ORDER BY r.idreserv ASC";
    $stmt = $pdo->query($query);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de chargement : " . $e->getMessage());
}

$edit_id = $_GET['edit_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Réservations</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 40px; background-color: #f9f9f9; color: #333; }
        h1, h2 { color: #2c3e50; }
        .dashboard-container { display: flex; flex-direction: column; gap: 20px; }
        .top-bar { margin-bottom: 10px; }
        .btn-add-page { display: inline-block; padding: 10px 20px; background-color: #2ecc71; color: white; text-decoration: none; font-weight: bold; border-radius: 4px; font-size: 14px; }
        .btn-add-page:hover { background-color: #27ae60; }
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
        .btn-save:hover { text-decoration: underline; }
        .btn-edit { color: #3498db; text-decoration: none; font-weight: bold; }
        .btn-delete, .btn-cancel { color: #e74c3c; text-decoration: none; font-weight: bold; margin-left: 15px; }
        .btn-cancel { color: #7f8c8d; }
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
    <a href="index.php" class="btn-home">← Retour à l'accueil</a>
    <h1>Gestion des Réservations</h1>
    <?php if (isset($errorMessage)) echo "<p class='error-msg'>$errorMessage</p>"; ?>

    <div class="dashboard-container">
        <div class="top-bar">
            <a href="add_reservation.php" class="btn-add-page">+ Ajouter une Nouvelle Réservation</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">Code</th>
                    <th style="width: 25%;">Nom Client</th>
                    <th style="width: 25%;">Table Affectée</th>
                    <th style="width: 18%;">Fait le</th>
                    <th style="width: 17%;">Pour le</th>
                    <th style="width: 10%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($reservations) > 0): ?>
                    <?php foreach ($reservations as $res): ?>
                        
                        <?php if ($edit_id === $res['idreserv']): ?>
                            <form action="test_reservation.php" method="POST">
                                <input type="hidden" name="action" value="update_inline">
                                <input type="hidden" name="idreserv" value="<?php echo htmlspecialchars($res['idreserv']); ?>">
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($res['idreserv']); ?></strong></td>
                                    <td><input type="text" name="nomcli" class="inline-input" value="<?php echo htmlspecialchars($res['nomcli']); ?>" required></td>
                                    <td>
                                        <select name="idtable" class="inline-select" required>
                                            <?php foreach ($all_tables as $tb): ?>
                                                <option value="<?php echo htmlspecialchars($tb['idtable']); ?>" <?php if ($res['idtable'] === $tb['idtable']) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($tb['designation']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="datetime-local" name="date_de_reserv" class="inline-input" value="<?php echo date('Y-m-d\TH:i', strtotime($res['date_de_reserv'])); ?>" required></td>
                                    <td><input type="datetime-local" name="date_reservee" class="inline-input" value="<?php echo date('Y-m-d\TH:i', strtotime($res['date_reservee'])); ?>" required></td>
                                    <td>
                                        <button type="submit" class="btn-save">Enregistrer</button>
                                        <a href="test_reservation.php" class="btn-cancel">Annuler</a>
                                    </td>
                                </tr>
                            </form>
                        <?php else: ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($res['idreserv']); ?></strong></td>
                                <td><?php echo htmlspecialchars($res['nomcli']); ?></td>
                                <td><?php echo htmlspecialchars($res['designation']); ?></td>
                                <td><?php echo htmlspecialchars($res['date_de_reserv']); ?></td>
                                <td><?php echo htmlspecialchars($res['date_reservee']); ?></td>
                                <td>
                                    <a href="test_reservation.php?edit_id=<?php echo urlencode($res['idreserv']); ?>" class="btn-edit">Modifier</a>
                                    <a href="delete_reservation.php?idreserv=<?php echo urlencode($res['idreserv']); ?>" class="delete-link">Annuler</a>                                </td>
                            </tr>
                        <?php endif; ?>

                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; font-weight: bold;">Aucune réservation planifiée.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>