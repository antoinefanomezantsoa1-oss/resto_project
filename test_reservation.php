<?php
include 'db_connect.php';

// 1. Fetch available tables for inline dropdown selection
try {
    $tableStmt = $pdo->query("SELECT idtable, designation FROM table_resto");
    $all_tables = $tableStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de chargement : " . $e->getMessage());
}

// 2. Handle Updating a Reservation IN-LINE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_inline') {
    $idreserv       = $_POST['idreserv'];
    $idtable        = $_POST['idtable'];
    $date_de_reserv = $_POST['date_de_reserv'];
    $date_reservee  = $_POST['date_reservee'];
    $nomcli         = $_POST['nomcli'];

    try {
        $query = "UPDATE reserver SET idtable = ?, date_de_reserv = ?, date_reservee = ?, nomcli = ? WHERE idreserv = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$idtable, $date_de_reserv, $date_reservee, $nomcli, $idreserv]);

        echo "<script>window.location.href='test_reservation.php';</script>";
        exit();
    } catch (PDOException $e) {
        $errorMessage = "Erreur de modification de la réservation : " . $e->getMessage();
    }
}

// 3. Fetch current reservations with a JOIN to display readable table designations
try {
    $query = "SELECT r.*, t.designation FROM reserver r 
              INNER JOIN table_resto t ON r.idtable = t.idtable 
              ORDER BY r.date_reservee ASC";
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
        .btn-save { color: #2ecc71; background: none; border: none; font-weight: bold; font-size: 14px; cursor: pointer; }
        .btn-save:hover { text-decoration: underline; }
        .btn-edit { color: #3498db; text-decoration: none; font-weight: bold; }
        .btn-delete, .btn-cancel { color: #e74c3c; text-decoration: none; font-weight: bold; margin-left: 15px; }
        .btn-cancel { color: #7f8c8d; }
        .error-msg { color: #e74c3c; font-weight: bold; margin-bottom: 15px; }
    </style>
</head>
<body>

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
                                    <a href="javascript:void(0);" onclick="if(confirm('Annuler cette réservation ?')) { window.location.href='delete_reservation.php?id=<?php echo urlencode($res['idreserv']); ?>'; }" class="btn-delete">Annuler</a>
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