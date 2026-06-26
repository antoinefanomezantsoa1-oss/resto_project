<?php
include 'db_connect.php';

$search_menu = $_GET['search_menu'] ?? '';
$search_client = $_GET['search_client'] ?? '';

$menu_results = [];
$client_results = [];

// 1. Search Menu items using LIKE %...%
if (!empty($search_menu)) {
    try {
        $query = "SELECT * FROM menu WHERE nomplat LIKE ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute(["%$search_menu%"]);
        $menu_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errorMessage = "Erreur de recherche menu : " . $e->getMessage();
    }
}

// 2. Search Clients from orders using LIKE %...%
if (!empty($search_client)) {
    try {
        // We select unique client names matching the query
        $query = "SELECT DISTINCT nomcli FROM commande WHERE nomcli LIKE ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute(["%$search_client%"]);
        $client_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errorMessage = "Erreur de recherche client : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Recherche Avancée</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 40px; background-color: #f9f9f9; color: #333; }
        h1, h2 { color: #2c3e50; }
        .search-container { display: flex; gap: 30px; margin-bottom: 40px; flex-wrap: wrap; }
        .search-card { background-color: #ece7e7; padding: 25px; border-radius: 6px; border: 1px solid #6707b6; flex: 1; min-width: 300px; }
        .form-group { display: flex; flex-direction: column; margin-bottom: 15px; }
        .form-group label { font-weight: bold; margin-bottom: 8px; font-size: 14px; }
        .form-group input { padding: 10px; border: 1px solid #cccccc; border-radius: 4px; background-color: #fff; }
        .btn-search { padding: 10px 20px; background-color: #6707b6; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .btn-search:hover { background-color: #530494; }
        table { width: 100%; border-collapse: collapse; background-color: #ffffff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-radius: 5px; overflow: hidden; margin-top: 15px; }
        th, td { padding: 12px 15px; text-align: left; border: 1px solid #474141; }
        th { background-color: #021b31; color: #ffffff; }
        tbody tr { background-color: #ece7e7; }
        .back-link { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #7f8c8d; font-weight: bold; }
    </style>
</head>
<body>

    <a href="index.php" class="back-link">← Retour à l'accueil</a>
    <h1>Module de Recherche</h1>

    <div class="search-container">
        <div class="search-card">
            <h2>Recherche de Menu</h2>
            <form action="search.php" method="GET">
                <div class="form-group">
                    <label for="search_menu">Nom du plat :</label>
                    <input type="text" id="search_menu" name="search_menu" value="<?php echo htmlspecialchars($search_menu); ?>" placeholder="Ex: Pizza, Jus...">
                </div>
                <button type="submit" class="btn-search">Rechercher Plat</button>
            </form>

            <?php if (!empty($search_menu)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Désignation</th>
                            <th>Prix Unitaire</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($menu_results) > 0): ?>
                            <?php foreach ($menu_results as $menu): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($menu['idplat']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($menu['nomplat']); ?></td>
                                    <td><?php echo htmlspecialchars($menu['pu']); ?> Ar</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3">Aucun plat ne correspond à votre recherche.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="search-card">
            <h2>Recherche de Client</h2>
            <form action="search.php" method="GET">
                <div class="form-group">
                    <label for="search_client">Nom du client :</label>
                    <input type="text" id="search_client" name="search_client" value="<?php echo htmlspecialchars($search_client); ?>" placeholder="Ex: Maria, Toky...">
                </div>
                <button type="submit" class="btn-search">Rechercher Client</button>
            </form>

            <?php if (!empty($search_client)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nom du Client</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($client_results) > 0): ?>
                            <?php foreach ($client_results as $cli): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($cli['nomcli']); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td>Aucun client trouvé avec ce nom.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>