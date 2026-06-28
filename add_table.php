<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {
    $idtable = trim($_POST['idtable']);
    $designation = trim($_POST['designation']);
    $occupation = 0; // Automatically force new entries to default as free/empty

    try {
        $insertQuery = "INSERT INTO table_resto (idtable, designation, occupation) VALUES (?, ?, ?)";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([$idtable, $designation, $occupation]);
        
        header("Location: test_table.php");
        exit();
    } catch (PDOException $e) {
        $errorMessage = "Erreur d'ajout de la table : Ce code table ou cette désignation existe déjà";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter une Table</title>
    <style>
        html { background-color: #19140f; }
        
        .live-error {
            font-size: 13px;
            display: block;
            margin-top: 6px;
            font-weight: bold;
        }

        /* Styles for the button when it's locked */
        button.btn-submit:disabled {
            background-color: #555 !important;
            color: #aaa !important;
            cursor: not-allowed !important;
            opacity: 0.6;
            box-shadow: none !important;
        }
    </style>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <a href="index.php" class="btn-home">← Retour à l'accueil</a>

    <h1>Gestion des Tables</h1>
    
    <div class="form-card">
        <h2>Ajouter une nouvelle table</h2>
        <?php if (isset($errorMessage)) echo "<p class='error-msg'>$errorMessage</p>"; ?>
        
        <form action="add_table.php" method="POST" id="add-table-form">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="idtable">Numéro de Table :</label>
                <input type="text" id="idtable" name="idtable" placeholder="Ex: T1" required autocomplete="off">
                <span id="idtable-error" class="live-error"></span>
            </div>

            <div class="form-group">
                <label for="designation">Désignation :</label>
                <input type="text" id="designation" name="designation" placeholder="Ex: Table numéro 1" required autocomplete="off">
                <span id="designation-error" class="live-error"></span>
            </div>
            
            <div class="form-actions">
                <button type="submit" id="submit-btn" class="btn-submit">Enregistrer</button>
                <a href="test_table.php" class="btn-cancel">Annuler</a>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const idInput = document.getElementById('idtable');
        const designationInput = document.getElementById('designation');
        const idError = document.getElementById('idtable-error');
        const designationError = document.getElementById('designation-error');
        const submitBtn = document.getElementById('submit-btn');

        // State tracking to handle form lock safely
        let idIsValid = false;
        let designationIsValid = false;

        function updateSubmitButtonState() {
            if (idIsValid && designationIsValid) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        // 1. Live Validation for ID Table
        idInput.addEventListener('input', function() {
            const idValue = this.value.trim();

            if (idValue === '') {
                idError.textContent = '';
                this.style.borderColor = '#cccccc';
                idIsValid = false;
                updateSubmitButtonState();
                return;
            }

            fetch(`check_duplicate.php?table=table_resto&column=idtable&id=${encodeURIComponent(idValue)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        idError.textContent = "❌ Ce numéro de table existe déjà !";
                        idError.style.color = "#e74c3c";
                        this.style.borderColor = "#e74c3c";
                        idIsValid = false;
                    } else {
                        idError.textContent = "✅ Numéro disponible";
                        idError.style.color = "#2ecc71";
                        this.style.borderColor = "#2ecc71";
                        idIsValid = true;
                    }
                    updateSubmitButtonState();
                })
                .catch(err => console.error("Erreur de validation ID:", err));
        });

        // 2. Live Validation for Designation Naming
        designationInput.addEventListener('input', function() {
            const desValue = this.value.trim();

            if (desValue === '') {
                designationError.textContent = '';
                this.style.borderColor = '#cccccc';
                designationIsValid = false;
                updateSubmitButtonState();
                return;
            }

            fetch(`check_duplicate.php?table=table_resto&column=designation&id=${encodeURIComponent(desValue)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        designationError.textContent = "❌ Cette désignation existe déjà !";
                        designationError.style.color = "#e74c3c";
                        this.style.borderColor = "#e74c3c";
                        designationIsValid = false;
                    } else {
                        designationError.textContent = "✅ Désignation disponible";
                        designationError.style.color = "#2ecc71";
                        this.style.borderColor = "#2ecc71";
                        designationIsValid = true;
                    }
                    updateSubmitButtonState();
                })
                .catch(err => console.error("Erreur de validation Désignation:", err));
        });

        // Lock form initially on empty start
        updateSubmitButtonState();
    });
    </script>
</body>
</html>