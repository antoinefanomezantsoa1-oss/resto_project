<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $idplat = $_POST['idplat'];
    $nomplat = $_POST['nomplat'];
    $pu = floatval($_POST['pu']); // Convert to float for clean numeric parsing

    // BACKEND GUARDRAIL: Block prices less than 0
    if ($pu < 0) {
        $errorMessage = "Erreur : Le prix unitaire ne peut pas être négatif.";
    } else {
        try {
            $insertQuery = "INSERT INTO menu (idplat, nomplat, pu) VALUES (?, ?, ?)";
            $insertStmt = $pdo->prepare($insertQuery);
            $insertStmt->execute([$idplat, $nomplat, $pu]);
            
            header("Location: test_menu.php");
            exit();
        } catch (PDOException $e) {
            $errorMessage = "Erreur d'ajout du plat : Ne pas utiliser le meme code plat";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un Plat au Menu</title>
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
        .form-group input { 
            padding: 10px; 
            border: 1px solid #cccccc; 
            border-radius: 4px; 
            font-size: 15px; 
            background-color: #ece7e7;
            color: #333333;
        }
        .form-group input:focus {
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
    </style>
</head>
<body>

    <h1>Gestion du Menu</h1>
    
    <div class="form-card">
        <h2>Ajouter un nouveau plat</h2>
        <?php if (isset($errorMessage)) echo "<p class='error-msg'>$errorMessage</p>"; ?>
        
        <form action="add_menu.php" method="POST">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="idplat">Code du Plat :</label>
                <input type="text" id="idplat" name="idplat" placeholder="Ex: P1" required>
                <span id="idplat-error" class="live-error"></span>
            </div>

            <div class="form-group">
                <label for="nomplat">Nom du Plat :</label>
                <input type="text" id="nomplat" name="nomplat" placeholder="Ex: Pizza Quatre Fromages" required>
            </div>

            <div class="form-group">
                <label for="pu">Prix (en Ar) :</label>
                <input type="number" id="pu" name="pu" step="0.01" min="0" placeholder="Ex: 25000" required>
            </div>
            
            <div class="button-group">
                <button type="submit" id="submit-btn" class="btn-submit">Enregistrer</button>
                <a href="test_menu.php" class="btn-cancel">Annuler</a>
            </div>
        </form>
    </div>

    <script>
    document.getElementById('idplat').addEventListener('input', function() {
        const idValue = this.value.trim();
        const errorSpan = document.getElementById('idplat-error');
        const submitBtn = document.getElementById('submit-btn');

        if (idValue === '') {
            errorSpan.textContent = '';
            this.style.borderColor = '#cccccc';
            submitBtn.disabled = false;
            return;
        }

        fetch(`check_duplicate.php?table=menu&column=idplat&id=${encodeURIComponent(idValue)}`)
            .then(response => response.json())
            .then(data => {
                if (data.exists) {
                    errorSpan.textContent = "❌ Ce code plat existe déjà !";
                    errorSpan.style.color = "#e74c3c";
                    this.style.borderColor = "#e74c3c";
                    submitBtn.disabled = true;
                } else {
                    errorSpan.textContent = "✅ Code disponible";
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