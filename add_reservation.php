<?php
include 'db_connect.php';

// Fetch current tables to feed into our drop selection form input
try {
    $tableStmt = $pdo->query("SELECT idtable, designation FROM table_resto");
    $all_tables = $tableStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de chargement des tables : " . $e->getMessage());
}

// Process data extraction from post array parameters matching the project specs
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $idreserv       = $_POST['idreserv'];
    $idtable        = $_POST['idtable'];
    $date_de_reserv = $_POST['date_de_reserv'];
    $date_reservee  = $_POST['date_reservee'];
    $nomcli         = $_POST['nomcli'];

    try {
        $insertQuery = "INSERT INTO reserver (idreserv, idtable, date_de_reserv, date_reservee, nomcli) VALUES (?, ?, ?, ?, ?)";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([$idreserv, $idtable, $date_de_reserv, $date_reservee, $nomcli]);
        
        header("Location: test_reservation.php");
        exit();
    } catch (PDOException $e) {
        $errorMessage = "Erreur d'ajout de la réservation : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter une Réservation</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 40px; 
            background-color: #f9f9f9; 
            color: #333333; 
            max-width: 500px;
        }
        h1, h2 { color: #2c3e50; }
        .form-card { 
            background-color: #ece7e7; 
            padding: 30px; 
            border-radius: 6px; 
            border: 1px solid #6707b6; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .form-group { 
            display: flex; 
            flex-direction: column; 
            margin-bottom: 20px;
        }
        .form-group label { 
            font-weight: bold; 
            margin-bottom: 8px; 
            color: #34495e; 
            font-size: 14px; 
        }
        .form-group input, .form-group select { 
            padding: 10px; 
            border: 1px solid #cccccc; 
            border-radius: 4px; 
            font-size: 15px; 
            background-color: #ece7e7;
            color: #333333;
        }
        .form-group input:focus, .form-group select:focus {
            outline: 2px solid #6707b6;
        }
        .button-group {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-top: 25px;
        }
        .btn-submit { 
            padding: 12px 25px; 
            background-color: #2ecc71; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            font-weight: bold; 
            cursor: pointer; 
            font-size: 15px; 
        }
        .btn-submit:hover { background-color: #27ae60; }
        .btn-cancel {
            color: #e74c3c;
            text-decoration: none;
            font-weight: bold;
            font-size: 15px;
        }
        .btn-cancel:hover { text-decoration: underline; }
        .error-msg { color: #e74c3c; font-weight: bold; margin-bottom: 15px; }
    </style>
</head>
<body>

    <h1>Gestion des Réservations</h1>
    
    <div class="form-card">
        <h2>Créer une réservation</h2>
        <?php if (isset($errorMessage)) echo "<p class='error-msg'>$errorMessage</p>"; ?>
        
        <form action="add_reservation.php" method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="idreserv">Code Réservation :</label>
                <input type="text" id="idreserv" name="idreserv" placeholder="Ex: R001" required>
            </div>

            <div class="form-group">
                <label for="nomcli">Nom du Client :</label>
                <input type="text" id="nomcli" name="nomcli" placeholder="Nom du client" required>
            </div>

            <div class="form-group">
                <label for="idtable">Table à attribuer :</label>
                <select id="idtable" name="idtable" required>
                    <option value="">-- Choisir une table --</option>
                    <?php foreach ($all_tables as $tb): ?>
                        <option value="<?php echo htmlspecialchars($tb['idtable']); ?>">
                            <?php echo htmlspecialchars($tb['designation']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="date_de_reserv">Date de Prise (Fait le) :</label>
                <input type="datetime-local" id="date_de_reserv" name="date_de_reserv" required>
            </div>

            <div class="form-group">
                <label for="date_reservee">Date Réservée (Pour le) :</label>
                <input type="datetime-local" id="date_reservee" name="date_reservee" required>
            </div>
            
            <div class="button-group">
                <button type="submit" class="btn-submit">Enregistrer</button>
                <a href="test_reservation.php" class="btn-cancel">Annuler</a>
            </div>
        </form>
    </div>

</body>
</html>