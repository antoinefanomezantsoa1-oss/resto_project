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
    $idreserv       = trim($_POST['idreserv']);
    $idtable        = $_POST['idtable'];
    $date_de_reserv = $_POST['date_de_reserv'];
    $date_reservee  = $_POST['date_reservee'];
    $nomcli         = trim($_POST['nomcli']);

    try {
        // 1. Check if the unique reservation ID already exists
        $idCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM reserver WHERE idreserv = ?");
        $idCheckStmt->execute([$idreserv]);
        if ($idCheckStmt->fetchColumn() > 0) {
            $errorMessage = "Erreur : Ce code reservation existe deja.";
        } else {
            // 2. OCCUPATION SAFETY GUARDRAIL: Check if table is currently physically occupied
            $statusQuery = "SELECT occupation FROM table_resto WHERE idtable = ? LIMIT 1";
            $statusStmt = $pdo->prepare($statusQuery);
            $statusStmt->execute([$idtable]);
            $isOccupied = $statusStmt->fetchColumn();

            if ($isOccupied == 1) {
                $errorMessage = "Erreur : Cette table est actuellement occupée dans la salle. Elle doit être payée/libérée avant d'être affectée.";
            } else {
                // 3. Strict hourly conflict check on the server side
                $conflictQuery = "SELECT COUNT(*) FROM reserver 
                                  WHERE idtable = ? 
                                  AND date_reservee >= DATE_SUB(?, INTERVAL 1 HOUR) 
                                  AND date_reservee <= DATE_ADD(?, INTERVAL 1 HOUR)";
                $conflictStmt = $pdo->prepare($conflictQuery);
                $conflictStmt->execute([$idtable, $date_reservee, $date_reservee]);
                
                if ($conflictStmt->fetchColumn() > 0) {
                    $errorMessage = "Erreur : Cette table est deja reservee a cette heure (creneau de 1 heure protege).";
                } else {
                    // Safe to save record and shift table occupancy state simultaneously
                    $pdo->beginTransaction();

                    // 4. Save the record if free
                    $insertQuery = "INSERT INTO reserver (idreserv, idtable, date_de_reserv, date_reservee, nomcli) VALUES (?, ?, ?, ?, ?)";
                    $insertStmt = $pdo->prepare($insertQuery);
                    $insertStmt->execute([$idreserv, $idtable, $date_de_reserv, $date_reservee, $nomcli]);
                    
                    // 5. Automatically switch table status flag to Occupied (1)
                    $updateTableQuery = "UPDATE table_resto SET occupation = 1 WHERE idtable = ?";
                    $updateTableStmt = $pdo->prepare($updateTableQuery);
                    $updateTableStmt->execute([$idtable]);

                    $pdo->commit();

                    header("Location: test_reservation.php");
                    exit();
                }
            }
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errorMessage = "Erreur d'ajout de la reservation : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter une Réservation</title>
    <link rel="stylesheet" href="style.css">
    <!--style>
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
        .btn-submit:disabled { background-color: #95a5a6; cursor: not-allowed; }
        .btn-cancel {
            color: #e74c3c;
            text-decoration: none;
            font-weight: bold;
            font-size: 15px;
        }
        .btn-cancel:hover { text-decoration: underline; }
        .error-msg { color: #e74c3c; font-weight: bold; margin-bottom: 15px; }
        .live-error { font-size: 13px; font-weight: bold; margin-top: 5px; }
    </style-->
    <script>
    function checkReservationConflict() {
        const idtable = document.getElementById('idtable').value;
        const date_reservee = document.getElementById('date_reservee').value;
        const errorContainer = document.getElementById('js-error-msg');
        const submitBtn = document.getElementById('submit-btn');

        // Only run check if both table and date time values are input
        if (!idtable || !date_reservee) {
            return;
        }

        // Send payload asynchronously to a background processing helper file
        fetch('check_conflict_ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'idtable=' + encodeURIComponent(idtable) + '&date_reservee=' + encodeURIComponent(date_reservee)
        })
        .then(response => response.json())
        .then(data => {
            if (data.conflict === true) {
                errorContainer.innerText = "Cette table est deja reservee a cette heure (creneau de 1 heure protege).";
                errorContainer.style.display = "block";
                submitBtn.disabled = true;
            } else {
                // Ensure we don't clear an block if the code entry verification has locked it down
                const codeError = document.getElementById('idreserv-error').textContent;
                if (!codeError.includes("existe")) {
                    errorContainer.innerText = "";
                    errorContainer.style.display = "none";
                    submitBtn.disabled = false;
                }
            }
        })
        .catch(error => console.error('Error running conflict validation:', error));
    }
    </script>
</head>
<body>

    <h1>Gestion des Réservations</h1>
    
    <div class="form-card">
        <h2>Créer une réservation</h2>
        
        <p id="js-error-msg" class="error-msg" style="display:none;"></p>
        <?php if (isset($errorMessage)) echo "<p class='error-msg'>$errorMessage</p>"; ?>
        
        <form action="add_reservation.php" method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="idreserv">Code Réservation :</label>
                <input type="text" id="idreserv" name="idreserv" placeholder="Ex: R001" required>
                <span id="idreserv-error" class="live-error"></span>
            </div>

            <div class="form-group">
                <label for="nomcli">Nom du Client :</label>
                <input type="text" id="nomcli" name="nomcli" placeholder="Nom du client" required>
            </div>

            <div class="form-group">
                <label for="idtable">Table à attribuer :</label>
                <select id="idtable" name="idtable" onchange="checkReservationConflict()" required>
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
                <input type="datetime-local" id="date_reservee" name="date_reservee" onchange="checkReservationConflict()" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" id="submit-btn" class="btn-submit">Enregistrer</button>
                <a href="test_reservation.php" class="btn-cancel">Annuler</a>
            </div>
        </form>
    </div>

    <script>
    document.getElementById('idreserv').addEventListener('input', function() {
        const idValue = this.value.trim();
        const errorSpan = document.getElementById('idreserv-error');
        const submitBtn = document.getElementById('submit-btn');

        if (idValue === '') {
            errorSpan.textContent = '';
            this.style.borderColor = '#cccccc';
            submitBtn.disabled = false;
            return;
        }

        fetch(`check_duplicate.php?table=reserver&column=idreserv&id=${encodeURIComponent(idValue)}`)
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    errorSpan.textContent = "❌ Ce code réservation existe déjà !";
                    errorSpan.style.color = "#e74c3c";
                    this.style.borderColor = "#e74c3c";
                    submitBtn.disabled = true;
                } else {
                    errorSpan.textContent = "✅ Code disponible";
                    errorSpan.style.color = "#2ecc71";
                    this.style.borderColor = "#2ecc71";
                    
                    // Only unlock if there isn't an active hourly conflict warning
                    const conflictMsg = document.getElementById('js-error-msg').innerText;
                    if (!conflictMsg) {
                        submitBtn.disabled = false;
                    }
                }
            })
            .catch(err => console.error("Erreur de validation :", err));
    });
    </script>
</body>
</html>