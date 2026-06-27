<?php
include 'db_connect.php';

$query = $_GET['q'] ?? '';

try {
    
    $stmt = $pdo->prepare("SELECT idplat, nomplat, pu FROM menu WHERE nomplat LIKE ?");
    $stmt->execute(["%$query%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($results) > 0) {
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['idplat']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nomplat']) . "</td>";
            echo "<td>" . htmlspecialchars($row['pu']) . " Ar</td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='3' style='text-align:center;'>Aucun plat trouvé.</td></tr>";
    }
} catch (PDOException $e) {
    echo "<tr><td colspan='3'>Erreur : " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}
?>