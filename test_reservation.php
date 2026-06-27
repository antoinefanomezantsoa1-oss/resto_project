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
    <style>html { background-color: #19140f; }</style>
    <link rel="stylesheet" href="style.css">
    <script>
    document.addEventListener("DOMContentLoaded", () => {
        setTimeout(() => {
            document.body.style.animation = "none";
        }, 150); 
        
        // Scroll memory behavior to stay at the exact same location after editing or refreshing
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
    <h1>Gestion des Réservations</h1>
    <?php if (isset($errorMessage)) echo "<p class='error-msg'>$errorMessage</p>"; ?>

    <div class="dashboard-container">
        <div class="action-bar">
            <a href="add_reservation.php" class="btn-ajouter">+ Ajouter une Nouvelle Réservation</a>
        </div>

        <table class="wide-table">
            <thead>
                <tr>
                    <th style="width: 12%;">Code Res</th>
                    <th style="width: 20%;">Client</th>
                    <th style="width: 18%;">Table</th>
                    <th style="width: 15%;">Date Réservation</th>
                    <th style="width: 15%;">Heure</th>
                    <th style="width: 20%;">Actions</th>
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
                                        <div class="row-actions-edit" style="display: flex; align-items: center; gap: 12px; white-space: nowrap;">
                                            <button type="submit" class="btn-save">Enregistrer</button>
                                            <a href="test_reservation.php" class="btn-cancel">Annuler</a>
                                        </div>
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
                                    <div class="row-actions-view">
                                        <a href="test_reservation.php?edit_id=<?php echo urlencode($res['idreserv']); ?>" class="btn-edit">Modifier</a>
                                        <a href="delete_reservation.php?idreserv=<?php echo urlencode($res['idreserv']); ?>" class="delete-link">
                                            <img src="images/trash.png" alt="Supprimer" style="width: 20px !important; height: 20px !important; display: inline-block; vertical-align: middle;">
                                        </a>  
                                    </div>
                                </td>
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