<?php
include 'db_connect.php';

// 1. Handle Updating a Table IN-LINE (POST request from the row form)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_inline') {
    $idtable = $_POST['idtable'];
    $designation = $_POST['designation'];
    $occupation = $_POST['occupation'];

    try {
        $query = "UPDATE table_resto SET designation = ?, occupation = ? WHERE idtable = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$designation, $occupation, $idtable]);

        echo "<script>window.location.href='test_table.php';</script>";
        exit();
    } catch (PDOException $e) {
        $errorMessage = "Erreur de modification : " . $e->getMessage();
    }
}

// 2. Fetch all current tables from MariaDB
try {
    $query = "SELECT * FROM table_resto";
    $stmt = $pdo->query($query);
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de chargement : " . $e->getMessage());
}

// Check if we are currently editing a row inline
$edit_id = $_GET['edit_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Tables</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 40px; 
            background-color: #f9f9f9; 
            color: #333333; 
        }
        h1, h2 { color: #2c3e50; }
        .dashboard-container { display: flex; flex-direction: column; gap: 20px; }
        
        .top-bar {
            display: block;
            margin-bottom: 10px;
        }

        /* Pretty green button to go to another page for addition */
        .btn-add-page {
            display: inline-block;
            padding: 10px 20px;
            background-color: #2ecc71;
            color: white;
            text-decoration: none;
            font-weight: bold;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn-add-page:hover { background-color: #27ae60; }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            background-color: #ffffff; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
            border-radius: 5px; 
            overflow: hidden; 
        }
        th, td { 
            padding: 12px 15px; 
            text-align: left; 
            border: 1px solid #474141; 
        }
        th { 
            background-color: #021b31; 
            color: #ffffff; 
            font-weight: 600; 
        }
        tbody tr { background-color: #ece7e7; }
        tr:hover { background-color: #e2dbdb; }

        /* Inline Form Inputs layout styling */
        .inline-input {
            padding: 6px 10px;
            border: 1px solid #cccccc;
            border-radius: 4px;
            font-size: 14px;
            background-color: #ffffff;
            color: #333333;
            width: 90%;
            box-sizing: border-box;
        }
        .inline-select {
            padding: 6px 10px;
            border: 1px solid #cccccc;
            border-radius: 4px;
            font-size: 14px;
            background-color: #ffffff;
            color: #333333;
        }

        .btn-save { color: #2ecc71; background: none; border: none; font-weight: bold; font-size: 14px; cursor: pointer; padding: 0; }
        .btn-save:hover { text-decoration: underline; }
        .btn-edit { color: #3498db; text-decoration: none; font-weight: bold; }
        .btn-delete { color: #e74c3c; text-decoration: none; font-weight: bold; margin-left: 15px; }
        .btn-cancel { color: #7f8c8d; text-decoration: none; font-weight: bold; margin-left: 15px; }
        .error-msg { color: #e74c3c; font-weight: bold; margin-bottom: 15px; }
    </style>
</head>
<body>

    <h1>Gestion des Tables</h1>
    
    <?php if (isset($errorMessage)) echo "<p class='error-msg'>$errorMessage</p>"; ?>

    <div class="dashboard-container">
        
        <div class="top-bar">
            <a href="add_table.php" class="btn-add-page">+ Ajouter une Nouvelle Table</a>
        </div>

        <div>
            <h2>Liste des Tables Resto</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 15%;">Numéro Table</th>
                        <th style="width: 45%;">Désignation</th>
                        <th style="width: 20%;">Statut</th>
                        <th style="width: 20%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($tables) > 0): ?>
                        <?php foreach ($tables as $tb): ?>
                            
                            <?php if ($edit_id === $tb['idtable']): ?>
                                <form action="test_table.php" method="POST">
                                    <input type="hidden" name="action" value="update_inline">
                                    <input type="hidden" name="idtable" value="<?php echo htmlspecialchars($tb['idtable']); ?>">
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($tb['idtable']); ?></strong></td>
                                        <td>
                                            <input type="text" name="designation" class="inline-input" value="<?php echo htmlspecialchars($tb['designation']); ?>" required>
                                        </td>
                                        <td>
                                            <select name="occupation" class="inline-select" required>
                                                <option value="0" <?php if ($tb['occupation'] == 0) echo 'selected'; ?>>Libre</option>
                                                <option value="1" <?php if ($tb['occupation'] == 1) echo 'selected'; ?>>Occupée</option>
                                            </select>
                                        </td>
                                        <td>
                                            <button type="submit" class="btn-save">Enregistrer</button>
                                            <a href="test_table.php" class="btn-cancel">Annuler</a>
                                        </td>
                                    </tr>
                                </form>
                            <?php else: ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($tb['idtable']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($tb['designation']); ?></td>
                                    <td>
                                        <span style="color: <?php echo ($tb['occupation'] == 0) ? '#2ecc71' : '#e74c3c'; ?>; font-weight: bold;">
                                            <?php echo ($tb['occupation'] == 0) ? 'Libre' : 'Occupée'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="test_table.php?edit_id=<?php echo urlencode($tb['idtable']); ?>" class="btn-edit">Modifier</a>
                                        <a href="javascript:void(0);" onclick="if(confirm('Supprimer cette table ?')) { window.location.href='delete_table.php?id=<?php echo urlencode($tb['idtable']); ?>'; }" class="btn-delete">Supprimer</a>
                                    </td>
                                </tr>
                            <?php endif; ?>

                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; font-weight: bold;">Aucune table enregistrée.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </div>

</body>
</html>