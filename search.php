<?php
include 'db_connect.php';

// Handle AJAX requests instantly before rendering the HTML page structure
if (isset($_GET['ajax_action'])) {
    $q = $_GET['q'] ?? '';
    
    if ($_GET['ajax_action'] === 'search_menu') {
        $query = "SELECT * FROM menu WHERE nomplat LIKE ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute(["%$q%"]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($results) > 0) {
            foreach ($results as $menu) {
                echo "<tr>";
                echo "<td><strong>" . htmlspecialchars($menu['idplat']) . "</strong></td>";
                echo "<td>" . htmlspecialchars($menu['nomplat']) . "</td>";
                echo "<td>" . htmlspecialchars($menu['pu']) . " Ar</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3'>Aucun plat ne correspond à votre recherche.</td></tr>";
        }
    }
    
    if ($_GET['ajax_action'] === 'search_client') {
        // JOIN commande and menu to get tables, dishes, and calculation details
        $query = "SELECT c.idcom, c.nomcli, c.idtable, c.datecom, m.nomplat 
                  FROM commande c 
                  LEFT JOIN menu m ON c.idplat = m.idplat 
                  WHERE c.nomcli LIKE ? 
                  ORDER BY c.datecom DESC";
                  
        $stmt = $pdo->prepare($query);
        $stmt->execute(["%$q%"]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($results) > 0) {
            foreach ($results as $cli) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($cli['idcom']) . "</td>";
                echo "<td><strong>" . htmlspecialchars($cli['nomcli']) . "</strong></td>";
                echo "<td>" . htmlspecialchars($cli['idtable'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($cli['nomplat'] ?? 'Inconnu') . "</td>";
                echo "<td>" . htmlspecialchars($cli['datecom']) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5'>Aucune commande trouvée pour ce client.</td></tr>";
        }
    }
    exit; // Stop executing right here for background updates
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche Avancée</title>
    <style>
        html { background-color: #19140f; }
        
        /* Layout Fix: Keeps columns statically sized even when empty to prevent structural jumping */
        .search-container {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        .search-card {
            flex: 1;
            min-width: 320px;
            display: flex;
            flex-direction: column;
        }
        .search-card.wide-card {
            flex: 1.5;
            min-width: 450px;
        }
        table {
            width: 100%;
            margin-top: 15px;
            table-layout: fixed; /* Prevents cell expansion shifts */
        }
    </style>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <a href="index.php" class="back-link">← Retour à l'accueil</a>
    <h1>Module de Recherche</h1>

    <div class="search-container">
        <div class="search-card">
            <h2>Recherche de Menu</h2>
            <div class="form-group">
                <label for="search_menu">Nom du plat :</label>
                <input type="text" id="search_menu" placeholder="Ex: Pizza, Jus..." autocomplete="off">
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 25%;">Code</th>
                        <th style="width: 50%;">Désignation</th>
                        <th style="width: 25%;">Prix Unitaire</th>
                    </tr>
                </thead>
                <tbody id="menu-target">
                    <tr><td colspan="3" style="color: rgba(255,255,255,0.4);">Tapez quelque chose pour commencer la recherche...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="search-card wide-card">
            <h2>Recherche de Client &amp; Commandes</h2>
            <div class="form-group">
                <label for="search_client">Nom du client :</label>
                <input type="text" id="search_client" placeholder="Ex: Maria, Toky..." autocomplete="off">
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 15%;">Code Com</th>
                        <th style="width: 25%;">Client</th>
                        <th style="width: 15%;">Table</th>
                        <th style="width: 25%;">Plat Commandé</th>
                        <th style="width: 20%;">Date</th>
                    </tr>
                </thead>
                <tbody id="client-target">
                    <tr><td colspan="5" style="color: rgba(255,255,255,0.4);">Tapez un nom pour voir ses détails de commande...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const menuInput = document.getElementById('search_menu');
        const clientInput = document.getElementById('search_client');
        const menuTarget = document.getElementById('menu-target');
        const clientTarget = document.getElementById('client-target');

        // Dynamic fetch layout engine
        function liveSearch(inputElement, targetBody, actionName) {
            inputElement.addEventListener('input', () => {
                const query = encodeURIComponent(inputElement.value.trim());
                
                if(query === "") {
                    if (actionName === 'search_menu') {
                        targetBody.innerHTML = `<tr><td colspan="3" style="color: rgba(255,255,255,0.4);">Tapez quelque chose pour commencer la recherche...</td></tr>`;
                    } else {
                        targetBody.innerHTML = `<tr><td colspan="5" style="color: rgba(255,255,255,0.4);">Tapez un nom pour voir ses détails de commande...</td></tr>`;
                    }
                    return;
                }

                fetch(`search.php?ajax_action=${actionName}&q=${query}`)
                    .then(res => res.text())
                    .then(html => {
                        // Injects perfectly without structural shifts or window flickers
                        targetBody.innerHTML = html;
                    });
            });
        }

        // Initialize live search listeners
        liveSearch(menuInput, menuTarget, 'search_menu');
        liveSearch(clientInput, clientTarget, 'search_client');
    });
    </script>

</body>
</html>