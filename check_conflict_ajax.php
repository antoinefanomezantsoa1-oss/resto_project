<?php
include 'db_connect.php';

$idtable = $_POST['idtable'] ?? '';
$date_reservee = $_POST['date_reservee'] ?? '';

$response = ['conflict' => false];

if (!empty($idtable)) {
    try {
        // 1. FIRST GUARDRAIL: Check if the table is physically occupied right now
        $statusStmt = $pdo->prepare("SELECT occupation FROM table_resto WHERE idtable = ? LIMIT 1");
        $statusStmt->execute([$idtable]);
        $isOccupied = $statusStmt->fetchColumn();

        if ($isOccupied == 1) {
            $response['conflict'] = true;
        } 
        // 2. SECOND GUARDRAIL: Run the hourly protected time slot check if it isn't occupied
        elseif (!empty($date_reservee)) {
            $conflictQuery = "SELECT COUNT(*) FROM reserver 
                              WHERE idtable = ? 
                              AND date_reservee >= DATE_SUB(?, INTERVAL 1 HOUR) 
                              AND date_reservee <= DATE_ADD(?, INTERVAL 1 HOUR)";
            $conflictStmt = $pdo->prepare($conflictQuery);
            $conflictStmt->execute([$idtable, $date_reservee, $date_reservee]);

            if ($conflictStmt->fetchColumn() > 0) {
                $response['conflict'] = true;
            }
        }
    } catch (PDOException $e) {
        // Fail silently or handle error log
    }
}

header('Content-Type: application/json');
echo json_encode($response);