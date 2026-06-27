<?php
include 'db_connect.php';

try {
    // 1. Recette totale accumulée - Changed to m.pu
    $totalQuery = "SELECT SUM(m.pu) as total_recette 
                   FROM commande c 
                   JOIN menu m ON c.idplat = m.idplat";
    $totalStmt = $pdo->query($totalQuery);
    $totalRecette = $totalStmt->fetchColumn() ?? 0;

    // 2. Histogramme des recettes - Changed to m.pu
    $historyQuery = "SELECT DATE_FORMAT(c.datecom, '%Y-%m') as mois, SUM(m.pu) as montant
                     FROM commande c
                     JOIN menu m ON c.idplat = m.idplat
                     WHERE c.datecom >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                     GROUP BY DATE_FORMAT(c.datecom, '%Y-%m')
                     ORDER BY mois ASC";
    $historyStmt = $pdo->query($historyQuery);
    $chartData = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Liste des 10 plats les plus vendus - Changed to m.pu
    $topPlatsQuery = "SELECT m.nomplat, COUNT(c.idplat) as total_ventes, SUM(m.pu) as total_genere
                      FROM commande c
                      JOIN menu m ON c.idplat = m.idplat
                      GROUP BY c.idplat
                      ORDER BY total_ventes DESC
                      LIMIT 10";
    $topPlatsStmt = $pdo->query($topPlatsQuery);
    $topPlats = $topPlatsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur de calcul statistique : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de Bord Statistique</title>
    <link rel="stylesheet" href="style.css">

    <!--style>
        body { font-family: 'Segoe UI', sans-serif; margin: 40px; background-color: #f9f9f9; color: #333; }
        .btn-home { display: inline-block; text-decoration: none; color: #34495e; font-weight: bold; margin-bottom: 20px; }
        .kpi-card { background-color: #021b31; color: white; padding: 25px; border-radius: 6px; inline-size: fit-content; margin-bottom: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .kpi-card h2 { margin: 0; font-size: 16px; color: #bdc3c7; text-transform: uppercase; }
        .kpi-card p { margin: 10px 0 0 0; font-size: 32px; font-weight: bold; color: #2ecc71; }
        
        .grid-layout { display: flex; flex-direction: column; gap: 40px; }
        
/* Replace your chart styles with this exact block */
        .chart-container { 
            background: white; 
            padding: 25px; 
            border-radius: 6px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
        }
        .histogram { 
            display: flex; 
            align-items: flex-end; 
            justify-content: flex-start; /* Aligns them nicely to the left */
            gap: 40px; /* Space between different month blocks */
            height: 250px; 
            padding-top: 30px; 
            border-bottom: 2px solid #333; 
            margin-bottom: 15px; 
        }
        .bar-group { 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            width: 70px; /* Explicit width for the column area */
            height: 100%; /* Keeps the scale accurate relative to the 250px container */
            justify-content: flex-end;
        }
        .bar { 
            width: 35px; /* Perfect sleek bar width */
            background-color: #3498db; 
            border-radius: 4px 4px 0 0; 
            transition: height 0.5s ease; 
            position: relative; 
        }
        .bar:hover { 
            background-color: #2980b9; 
        }
        .bar-val { 
            position: absolute; 
            top: -25px; 
            left: 50%; 
            transform: translateX(-50%); 
            font-weight: bold; 
            font-size: 12px; 
            color: #2c3e50; 
            white-space: nowrap; 
        }
        .bar-label { 
            margin-top: 8px; 
            font-weight: bold; 
            font-size: 13px; 
            color: #7f8c8d; 
        }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #2c3e50; color: white; }
        tr:hover { background-color: #f1f2f6; }
        .rank { font-weight: bold; color: #e67e22; }
    </style-->
</head>
<body>

    <a href="index.php" class="btn-home">← Retour à l'accueil</a>
    <h1>Analyse des Performances du Restaurant</h1>

    <div class="kpi-card">
        <h2>Recette Totale Accumulée</h2>
        <p><?php echo number_format($totalRecette, 2, ',', ' '); ?> Ar</p>
    </div>

    <div class="grid-layout">
        
        <div class="chart-container">
            <h2>Histogramme des Recettes (6 derniers mois)</h2>
            <div class="histogram">
                <?php 
                // Determine scale divisor based on max calculation to balance container dimensions gracefully
                $maxRevenue = max(array_column($chartData, 'montant') ?: [1]);
                foreach ($chartData as $data): 
                    $heightPercentage = ($data['montant'] / $maxRevenue) * 100;
                ?>
                    <div class="bar-group">
                        <div class="bar" style="height: <?php echo $heightPercentage; ?>%;">
                            <span class="bar-val"><?php echo number_format($data['montant'], 0, '', ' '); ?></span>
                        </div>
                        <div class="bar-label"><?php echo htmlspecialchars($data['mois']); ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if(empty($chartData)): ?>
                    <p style="text-align: center; width: 100%; color: #95a5a6;">Aucune donnée financière disponible pour les 6 derniers mois.</p>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <h2>Top 10 des Plats les Plus Vendus</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 10%;">Rang</th>
                        <th style="width: 50%;">Nom du Plat</th>
                        <th style="width: 20%;">Quantité Vendue</th>
                        <th style="width: 20%;">Chiffre d'Affaires</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank = 1; foreach ($topPlats as $plat): ?>
                        <tr>
                            <td class="rank">#<?php echo $rank++; ?></td>
                            <td><strong><?php echo htmlspecialchars($plat['nomplat']); ?></strong></td>
                            <td><?php echo htmlspecialchars($plat['total_ventes']); ?> commandes</td>
                            <td><?php echo number_format($plat['total_genere'], 2, ',', ' '); ?> Ar</td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($topPlats)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">Aucune vente enregistrée pour le moment.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>