<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Restaurant - Tableau de Bord</title>
    <link rel="stylesheet" href="style.css"> 
</head>
<body>

    <div class="container">
        <h1>Gestion de Restaurant</h1>
        <p class="subtitle">Système d'administration et de suivi des opérations</p>
        
        <div class="menu-grid">
            <a href="search.php" class="menu-card">
                <div class="icon-container" style="margin-bottom: 15px;">
                    <img src="images/search.jpg" alt="Recherche" style="width: 65px; height: 65px; border-radius: 8px; object-fit: cover; border: 1px solid rgba(241, 196, 15, 0.2); display: inline-block; vertical-align: middle;">
                </div>
                <h3>Recherche</h3>
                <p>Rechercher rapidement des plats du menu ou des clients du restaurant.</p>
            </a>

            <a href="test_table.php" class="menu-card">
                <div class="icon-container" style="margin-bottom: 15px;">
                    <img src="images/tables.jpg" alt="Tables" style="width: 65px; height: 65px; border-radius: 8px; object-fit: cover; border: 1px solid rgba(241, 196, 15, 0.2); display: inline-block; vertical-align: middle;">
                </div>
                <h3>Tables</h3>
                <p>Gérer la désignation et le statut d'occupation des tables.</p>
            </a>

            <a href="test_menu.php" class="menu-card">
                <div class="icon-container" style="margin-bottom: 15px;">
                    <img src="images/menu.jpg" alt="Menu" style="width: 65px; height: 65px; border-radius: 8px; object-fit: cover; border: 1px solid rgba(241, 196, 15, 0.2); display: inline-block; vertical-align: middle;">
                </div>
                <h3>Menu</h3>
                <p>Administrer la carte des plats et les tarifs unitaires.</p>
            </a>

            <a href="test_commande.php" class="menu-card">
                <div class="icon-container" style="margin-bottom: 15px;">
                    <img src="images/commande.jpg" alt="Commandes" style="width: 65px; height: 65px; border-radius: 8px; object-fit: cover; border: 1px solid rgba(241, 196, 15, 0.2); display: inline-block; vertical-align: middle;">
                </div>
                <h3>Commandes</h3>
                <p>Prendre et modifier les commandes sur table ou à emporter.</p>
            </a>

            <a href="test_reservation.php" class="menu-card">
                <div class="icon-container" style="margin-bottom: 15px;">
                    <img src="images/reservation.jpg" alt="Réservations" style="width: 65px; height: 65px; border-radius: 8px; object-fit: cover; border: 1px solid rgba(241, 196, 15, 0.2); display: inline-block; vertical-align: middle;">
                </div>
                <h3>Réservations</h3>
                <p>Planifier et suivre les réservations de tables à l'avance.</p>
            </a>

            <a href="date_filter.php" class="menu-card">
                <div class="icon-container" style="margin-bottom: 15px;">
                    <img src="images/frequent.jpg" alt="Fréquentation" style="width: 65px; height: 65px; border-radius: 8px; object-fit: cover; border: 1px solid rgba(241, 196, 15, 0.2); display: inline-block; vertical-align: middle;">
                </div>
                <h3>Fréquentation Date</h3>
                <p>Lister les clients passés pour une date donnée ou entre deux dates.</p>
            </a>

            <a href="stats_dashboard.php" class="menu-card">
                <div class="icon-container" style="margin-bottom: 15px;">
                    <img src="images/stats.jpg" alt="Statistiques" style="width: 65px; height: 65px; border-radius: 8px; object-fit: cover; border: 1px solid rgba(241, 196, 15, 0.2); display: inline-block; vertical-align: middle;">
                </div>
                <h3>Statistiques</h3>
                <p>Recettes totales, histogramme des 6 derniers mois et top 10 des plats.</p>
            </a>
        </div>
    </div>

</body>
</html>