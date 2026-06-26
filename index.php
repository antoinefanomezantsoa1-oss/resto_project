<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Restaurant - Tableau de Bord</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 40px; 
            background-color: #f9f9f9; 
            color: #333333; 
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .container {
            max-width: 900px;
            width: 100%;
            text-align: center;
        }

        h1 { 
            color: #2c3e50; 
            margin-bottom: 10px;
            font-size: 2.5rem;
        }

        .subtitle {
            color: #7f8c8d;
            font-size: 1.1rem;
            margin-bottom: 40px;
        }
        
        .menu-grid { 
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .menu-card { 
            background-color: #ece7e7; 
            padding: 30px 20px; 
            border-radius: 8px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); 
            border: 1px solid #6707b6; 
            text-decoration: none;
            color: #333333;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(103, 7, 182, 0.15);
            background-color: #e2dbdb;
        }

        .menu-card .icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .menu-card h3 {
            margin: 0 0 10px 0;
            color: #021b31;
            font-size: 1.3rem;
        }

        .menu-card p {
            margin: 0;
            font-size: 0.9rem;
            color: #666666;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Gestion de Restaurant</h1>
        <p class="subtitle">Système d'administration et de suivi des opérations</p>
        
        <div class="menu-grid">
            <a href="search.php" class="menu-card" style="border-color: #3498db;">
                <div class="icon">🔍</div>
                <h3>Recherche</h3>
                <p>Rechercher rapidement des plats du menu ou des clients du restaurant.</p>
            </a>
            <a href="test_table.php" class="menu-card">
                <div class="icon">🪑</div>
                <h3>Tables</h3>
                <p>Gérer la désignation et le statut d'occupation des tables.</p>
            </a>
            
            <a href="test_menu.php" class="menu-card">
                <div class="icon">🍽️</div>
                <h3>Menu</h3>
                <p>Administrer la carte des plats et les tarifs unitaires.</p>
            </a>
            
            <a href="test_commande.php" class="menu-card">
                <div class="icon">📝</div>
                <h3>Commandes</h3>
                <p>Prendre et modifier les commandes sur table ou à emporter.</p>
            </a>
            
            <a href="test_reservation.php" class="menu-card">
                <div class="icon">📅</div>
                <h3>Réservations</h3>
                <p>Planifier et suivre les réservations de tables à l'avance.</p>
            </a>
            <a href="date_filter.php" class="menu-card" style="border-color: #e67e22;">
                <div class="icon">📅</div>
                <h3>Fréquentation Date</h3>
                <p>Lister les clients passés pour une date donnée ou entre deux dates.</p>
            </a>
        </div>
    </div>

</body>
</html>