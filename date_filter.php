<?php
include 'db_connect.php';

// Grab filtering parameters from GET request
$filter_mode = $_GET['filter_mode'] ?? 'single'; // 'single' or 'range'
$single_date = $_GET['single_date'] ?? '';
$start_date  = $_GET['start_date'] ?? '';
$end_date    = $_GET['end_date'] ?? '';

$clients = [];

try {
    if ($filter_mode === 'single' && !empty($single_date)) {
        // Mode 1: Exact Single Date Match (Using DATE() to strip times)
        $query = "SELECT c.*, m.nomplat, t.designation 
                  FROM commande c 
                  INNER JOIN menu m ON c.idplat = m.idplat 
                  LEFT JOIN table_resto t ON c.idtable = t.idtable 
                  WHERE DATE(c.datecom) = ?
                  ORDER BY c.nomcli ASC";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$single_date]);
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif ($filter_mode === 'range' && !empty($start_date) && !empty($end_date)) {
        // Mode 2: Between Two Dates Range (Using DATE() to strip times)
        $query = "SELECT c.*, m.nomplat, t.designation 
                  FROM commande c 
                  INNER JOIN menu m ON c.idplat = m.idplat 
                  LEFT JOIN table_resto t ON c.idtable = t.idtable 
                  WHERE DATE(c.datecom) BETWEEN ? AND ?
                  ORDER BY c.datecom ASC, c.nomcli ASC";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$start_date, $end_date]);
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $errorMessage = "Erreur de filtrage : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Suivi des Clients par Date</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 40px; background-color: #f9f9f9; color: #333; }
        h1, h2 { color: #2c3e50; }
        .back-link { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #7f8c8d; font-weight: bold; }
        .filter-container { background-color: #ece7e7; padding: 25px; border-radius: 6px; border: 1px solid #6707b6; margin-bottom: 30px; max-width: 650px; }
        .mode-selector { margin-bottom: 20px; font-weight: bold; }
        .mode-selector label { margin-right: 20px; cursor: pointer; }
        .form-row { display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: bold; margin-bottom: 8px; font-size: 14px; }
        .form-group input { padding: 10px; border: 1px solid #cccccc; border-radius: 4px; background-color: #fff; }
        .btn-filter { padding: 10px 25px; background-color: #6707b6; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; height: 41px; }
        .btn-filter:hover { background-color: #530494; }
        table { width: 100%; border-collapse: collapse; background-color: #ffffff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 5px; overflow: hidden; }
        th, td { padding: 12px 15px; text-align: left; border: 1px solid #474141; }
        th { background-color: #021b31; color: #ffffff; }
        tbody tr { background-color: #ece7e7; }
        tr:hover { background-color: #e2dbdb; }
        .empty-msg { text-align: center; font-weight: bold; padding: 20px; color: #7f8c8d; }
    </style>
    <script>
        function toggleFilterInputs() {
            const isSingle = document.getElementById('mode_single').checked;
            document.getElementById('wrapper_single').style.display = isSingle ? 'block' : 'none';
            document.getElementById('wrapper_range').style.display = isSingle ? 'none' : 'flex';
        }
    </script>
</head>
<body onload="toggleFilterInputs()">

    <a href="index.php" class="back-link">← Retour à l'accueil</a>
    <h1>Historique Fréquentation Clients</h1>

    <div class="filter-container">
        <h2>Options de Filtrage</h2>
        <?php if (isset($errorMessage)) echo "<p style='color:red;'>$errorMessage</p>"; ?>

        <form action="date_filter.php" method="GET">
            <div class="mode-selector">
                <label>
                    <input type="radio" id="mode_single" name="filter_mode" value="single" <?php if($filter_mode === 'single') echo 'checked'; ?> onchange="toggleFilterInputs()"> Date Unique
                </label>
                <label>
                    <input type="radio" id="mode_range" name="filter_mode" value="range" <?php if($filter_mode === 'range') echo 'checked'; ?> onchange="toggleFilterInputs()"> Entre Deux Dates
                </label>
            </div>

            <div class="form-row">
                <div id="wrapper_single" class="form-group" style="min-width: 200px;">
                    <label for="single_date">Choisir une Date :</label>
                    <input type="date" id="single_date" name="single_date" value="<?php echo htmlspecialchars($single_date); ?>">
                </div>

                <div id="wrapper_range" style="display:none; gap:20px; flex-grow:1;">
                    <div class="form-group" style="flex: 1;">
                        <label for="start_date">Date de Début :</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label for="end_date">Date de Fin :</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                </div>

                <button type="submit" class="btn-filter">Afficher la Liste</button>
            </div>
        </form>
    </div>

    <h2>Résultats de la Recherche</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Nom du Client</th>
                <th style="width: 15%;">Code Commande</th>
                <th style="width: 25%;">Plat Commandé</th>
                <th style="width: 15%;">Table Affectée</th>
                <th style="width: 20%;">Date du Passage</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($clients) > 0): ?>
                <?php foreach ($clients as $cli): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars(strtoupper($cli['nomcli'] ?? '')); ?></strong></td>
                        <td><?php echo htmlspecialchars($cli['idcom']); ?></td>
                        <td><?php echo htmlspecialchars($m_nomplat = $cli['nomplat'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($cli['designation'] ?? 'À emporter'); ?></td>
                        <td><?php echo htmlspecialchars($cli['datecom']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="empty-msg">Aucun client enregistré pour les critères sélectionnés.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>