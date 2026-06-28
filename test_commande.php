<?php
include 'db_connect.php';

try {
    // Fetch aggregated orders and preserve their payment status
    $query = "SELECT c.idcom, c.nomcli, c.typecom, c.idtable, c.datecom, c.statut, t.designation,
                     GROUP_CONCAT(CONCAT(m.nomplat, ' (x', c.qte, ')') SEPARATOR ', ') as plats_groupes
              FROM commande c 
              INNER JOIN menu m ON c.idplat = m.idplat 
              LEFT JOIN table_resto t ON c.idtable = t.idtable 
              GROUP BY c.idcom, c.nomcli, c.typecom, c.idtable, c.datecom, c.statut, t.designation
              ORDER BY c.idcom ASC";
    $stmt = $pdo->query($query);
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de chargement des commandes : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Commandes</title>
    <link rel="stylesheet" href="style.css">
    <style>
        html { background-color: #19140f; }
        
        /* Badges styling */
        .plats-badge {
            background-color: rgba(241, 196, 15, 0.08);
            border-left: 3px solid #f1c40f;
            padding: 6px 10px;
            font-size: 13px;
            color: #e0e0e0;
            border-radius: 0 4px 4px 0;
            line-height: 1.4;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }
        .status-unpaid {
            background-color: rgba(231, 76, 60, 0.15);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        .status-paid {
            background-color: rgba(46, 204, 113, 0.15);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .action-cell {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }

    /* FIXED: Explicitly force text contrast with !important on hover/active states */
    .btn-action-payer {
        background-color: #f1c40f;
        color: #19140f !important;
        padding: 5px 12px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
        text-decoration: none;
        border-radius: 4px;
        transition: all 0.2s ease;
        display: inline-block;
    }
    .btn-action-payer:hover {
        background-color: #f39c12 !important;
        color: #19140f !important; /* Forces text to stay dark */
        transform: translateY(-1px);
    }
    </style>
 <script>
document.addEventListener("DOMContentLoaded", function() {
    // FIXED: Capture the scroll position from the URL query parameter after redirecting
    const urlParams = new URLSearchParams(window.location.search);
    const scrollY = urlParams.get('scroll_y');
    if (scrollY) {
        window.scrollTo(0, parseInt(scrollY));
    }

    const overlay = document.getElementById("custom-modal-overlay");
    const modalTitle = document.getElementById("modal-title");
    const modalText = document.getElementById("modal-text");
    const confirmBtn = document.getElementById("modal-confirm-btn");
    const cancelBtn = document.getElementById("modal-cancel-btn");
    let targetUrl = "";

    // Handle Deletions
    document.querySelectorAll(".delete-link").forEach(link => {
        link.addEventListener("click", function(event) {
            event.preventDefault(); 
            targetUrl = this.href;   
            modalTitle.textContent = "Suppression";
            modalTitle.style.color = "#a62626";
            modalText.textContent = "Êtes-vous sûr de vouloir supprimer définitivement cette commande ?";
            confirmBtn.style.backgroundColor = "#a62626";
            overlay.style.display = "flex";
        });
    });

    // FIXED: Dynamically inject current scroll position into the payment link on click
    document.querySelectorAll(".payer-link").forEach(link => {
        link.addEventListener("click", function(event) {
            event.preventDefault();
            // Append current window scroll coordinates right onto the script query string
            targetUrl = this.href + "&scroll_y=" + Math.round(window.scrollY);
            
            modalTitle.textContent = "Règlement Facture";
            modalTitle.style.color = "#f1c40f";
            modalText.textContent = "Confirmer le règlement de l'addition et libérer la table correspondante ?";
            confirmBtn.style.backgroundColor = "#27ae60";
            overlay.style.display = "flex";
        });
    });

    cancelBtn.addEventListener("click", () => { overlay.style.display = "none"; });
    confirmBtn.addEventListener("click", () => { if (targetUrl) window.location.href = targetUrl; });
});
</script>
</head>
<body>
    <div id="custom-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); backdrop-filter: blur(6px); z-index: 9999; justify-content: center; align-items: center;">
        <div style="background-color: rgba(25, 20, 15, 0.98); padding: 35px; border-radius: 8px; border: 1px solid rgba(241, 196, 15, 0.2); max-width: 400px; width: 90%; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.8);">
            <h3 id="modal-title" style="margin-top: 0; font-size: 20px; letter-spacing: 1px; text-transform: uppercase;">Confirmation</h3>
            <p id="modal-text" style="color: #ffffff; font-size: 14px; margin-bottom: 25px; line-height: 1.5;"></p>
            <div style="display: flex; justify-content: center; gap: 15px;">
                <button id="modal-confirm-btn" style="padding: 10px 24px; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; text-transform: uppercase; font-size: 12px;">Confirmer</button>
                <button id="modal-cancel-btn" style="padding: 10px 24px; background-color: transparent; color: rgba(255, 255, 255, 0.5); border: none; cursor: pointer; text-transform: uppercase; font-size: 12px;">Annuler</button>
            </div>
        </div>
    </div>

    <a href="index.php" class="btn-home">← Retour à l'accueil</a>
    <h1>Gestion des Commandes</h1>

    <div class="dashboard-container">
        <div class="action-bar">
            <a href="add_commande.php" class="btn-ajouter">+ Ajouter une Nouvelle Commande</a>
        </div>

        <table class="wide-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Code Com</th>
                    <th style="width: 15%;">Client</th>
                    <th style="width: 28%;">Plat(s) Commandé(s)</th>
                    <th style="width: 10%;">Type</th>
                    <th style="width: 10%;">Table</th>
                    <th style="width: 11%;">Date</th>
                    <th style="width: 11%;">Statut</th>
                    <th style="width: 15%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($commandes) > 0): ?>
                    <?php foreach ($commandes as $com): ?>
                        <tr>
                            <td>
                                <a href="generate_pdf.php?id=<?php echo urlencode($com['idcom']); ?>" target="_blank" style="color: #f1c40f; font-weight: bold; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                                    <?php echo htmlspecialchars($com['idcom']); ?>
                                    <img src="images/pdf.png" alt="PDF" style="width: 18px; height: 18px;">
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($com['nomcli']); ?></td>
                            <td><div class="plats-badge"><?php echo htmlspecialchars($com['plats_groupes']); ?></div></td>
                            <td><?php echo htmlspecialchars($com['typecom']); ?></td>
                            <td><?php echo htmlspecialchars($com['designation'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($com['datecom']); ?></td>
                            <td>
                                <span class="status-badge <?php echo ($com['statut'] === 'Payé') ? 'status-paid' : 'status-unpaid'; ?>">
                                    <?php echo htmlspecialchars($com['statut']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-cell">
                                    <?php if ($com['statut'] === 'Non payé'): ?>
                                        <a href="payer_commande.php?idcom=<?php echo urlencode($com['idcom']); ?>&idtable=<?php echo urlencode($com['idtable']); ?>" class="btn-action-payer payer-link">Payer</a>
                                    <?php else: ?>
                                        <span style="font-size: 11px; color: #2ecc71; font-weight: bold; text-transform: uppercase; padding: 5px 8px;">Réglé</span>
                                    <?php endif; ?>

                                    <a href="edit_commande.php?id=<?php echo urlencode($com['idcom']); ?>" class="btn-edit">Modifier</a>
                                    
                                    <a href="delete_commande.php?idcom=<?php echo urlencode($com['idcom']); ?>" class="delete-link">
                                        <img src="images/trash.png" alt="Supprimer" style="width: 18px; height: 18px; vertical-align: middle;">
                                    </a>
                                </div>                                 
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align: center; font-weight: bold;">Aucune commande en cours.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>