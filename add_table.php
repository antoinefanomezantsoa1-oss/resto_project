<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $idtable = $_POST['idtable'];
    $designation = $_POST['designation'];
    $occupation = $_POST['occupation'];

    try {
        $insertQuery = "INSERT INTO table_resto (idtable, designation, occupation) VALUES (?, ?, ?)";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([$idtable, $designation, $occupation]);
        
        header("Location: test_table.php");
        exit();
    } catch (PDOException $e) {
        $errorMessage = "Erreur d'ajout de la table : Ce code table existe déjà";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter une Table</title>
    <style>html { background-color: #19140f; }</style>
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
</head>
<body>

    <h1>Gestion des Tables</h1>
    
    <div class="form-card">
        <h2>Ajouter une nouvelle table</h2>
        <?php if (isset($errorMessage)) echo "<p class='error-msg'>$errorMessage</p>"; ?>
        
        <form action="add_table.php" method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="idtable">Numéro de Table :</label>
                <input type="text" id="idtable" name="idtable" placeholder="Ex: T1" required>
                <span id="idtable-error" class="live-error"></span>
            </div>

            <div class="form-group">
                <label for="designation">Désignation :</label>
                <input type="text" id="designation" name="designation" placeholder="Ex: Table Fenêtre" required>
            </div>

            <div class="form-group">
                <label for="occupation">Statut initial :</label>
                <select id="occupation" name="occupation" required>
                    <option value="0">Libre</option>
                    <option value="1">Occupée</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" id="submit-btn" class="btn-submit">Enregistrer</button>
                <a href="test_table.php" class="btn-cancel">Annuler</a>
            </div>
        </form>
    </div>

    <script>
    document.getElementById('idtable').addEventListener('input', function() {
        const idValue = this.value.trim();
        const errorSpan = document.getElementById('idtable-error');
        const submitBtn = document.getElementById('submit-btn');

        if (idValue === '') {
            errorSpan.textContent = '';
            this.style.borderColor = '#cccccc';
            submitBtn.disabled = false;
            return;
        }

        fetch(`check_duplicate.php?table=table_resto&column=idtable&id=${encodeURIComponent(idValue)}`)
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    errorSpan.textContent = "❌ Ce numéro de table existe déjà !";
                    errorSpan.style.color = "#e74c3c";
                    this.style.borderColor = "#e74c3c";
                    submitBtn.disabled = true;
                } else {
                    errorSpan.textContent = "✅ Numéro disponible";
                    errorSpan.style.color = "#2ecc71";
                    this.style.borderColor = "#2ecc71";
                    submitBtn.disabled = false;
                }
            })
            .catch(err => console.error("Erreur de validation :", err));
    });
    </script>
</body>
</html>