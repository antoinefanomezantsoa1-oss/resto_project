<?php
include 'db_connect.php';

// 1. Load active tables for the dropdown selector options
try {
    $tableStmt = $pdo->query("SELECT idtable, designation FROM table_resto");
    $all_tables = $tableStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de chargement des tables : " . $e->getMessage());
}

// 2. Fetch the target reservation record to pre-populate text fields
if (isset($_GET['id'])) {
    $idreserv = $_GET['id'];

    $query = "SELECT * FROM reserver WHERE idreserv = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$idreserv]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$res) {
        echo "Réservation non trouvée";
        exit();
    }
}

// 3. Process the form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $idreserv       = $_POST['idreserv'];
    $idtable        = $_POST['idtable'];
    $date_de_reserv = $_POST['date_de_reserv'];
    $date_reservee  = $_POST['date_reservee'];
    $nomcli         = $_POST['nomcli'];

    try {
        $query = "UPDATE reserver 
                  SET idtable = ?, date_de_reserv = ?, date_reservee = ?, nomcli = ?
                  WHERE idreserv = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$idtable, $date_de_reserv, $date_reservee, $nomcli, $idreserv]);

        header("Location: test_reservation.php");
        exit();
    } catch (PDOException $e) {
        echo "Erreur lors de la modification de la réservation : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modification de la Réservation</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 40px; 
            background-color: #f9f9f9; 
            color: #333333; 
            max-width: 600px;
        }
        h1, h2 { color: #2c3e50; }
        
        .form-card { 
            background-color: #ece7e7; 
            padding: 30px; 
            border-radius: 6px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
            border: 1px solid #6707b6; 
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
            padding: 10px 12px; 
            border: 1px solid #cccccc; 
            border-radius: 4px; 
            font-size: 15px; 
            background-color: #ece7e7;
            color: #333333;
            width: 100%;
            box-sizing: border-box;
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
            color: #ffffff; 
            border: none; 
            border-radius: 4px; 
            font-weight: bold; 
            cursor: pointer; 
            font-size: 15px; 
        }
        .btn-submit:hover { background-color: #27ae60; }
        
        .btn-cancel {
            padding: 11px 20px;
            background-color: #7f8c8d;
            color: #ffffff;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 15px;
            font-weight: bold;
        }
        .btn-cancel:hover { background-color: #95a5a6; }
    </style>
</head>
<body>

    <h1>Gestion des Réservations</h1>
    
    <div class="form-card">
        <h2>Modifier la réservation : <?php echo htmlspecialchars($res['idreserv'] ?? ''); ?></h2>
        
        <form action="edit_reservation.php?id=<?php echo urlencode($res['idreserv'] ?? ''); ?>" method="POST">
            <input type="hidden" name="idreserv" value="<?php echo htmlspecialchars($res['idreserv'] ?? ''); ?>">
            
            <div class="form-group">
                <label for="nomcli">Nom du Client :</label>
                <input type="text" id="nomcli" name="nomcli" value="<?php echo htmlspecialchars($res['nomcli'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="idtable">Table réservée :</label>
                <select id="idtable" name="idtable" required>
                    <?php foreach ($all_tables as $tb): ?>
                        <option value="<?php echo htmlspecialchars($tb['idtable']); ?>" <?php if (($res['idtable'] ?? '') === $tb['idtable']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($tb['designation']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="date_de_reserv">Fait le :</label>
                <input type="datetime-local" id="date_de_reserv" name="date_de_reserv" value="<?php echo isset($res['date_de_reserv']) ? date('Y-m-d\TH:i', strtotime($res['date_de_reserv'])) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="date_reservee">Pour le :</label>
                <input type="datetime-local" id="date_reservee" name="date_reservee" value="<?php echo isset($res['date_reservee']) ? date('Y-m-d\TH:i', strtotime($res['date_reservee'])) : ''; ?>" required>
            </div>
            
            <div class="button-group">
                <button type="submit" class="btn-submit">Mettre à jour</button>
                <a href="test_reservation.php" class="btn-cancel">Annuler</a>
            </div>
        </form>
    </div>

</body>
</html>