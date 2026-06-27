<?php
// Ensure no hidden spaces or HTML errors corrupt the json output
header('Content-Type: application/json');

// Include your connection file
include 'db_connect.php';

$table = $_GET['table'] ?? '';
$column = $_GET['column'] ?? '';
$id = $_GET['id'] ?? '';

if (empty($table) || empty($column) || empty($id)) {
    echo json_encode(['exists' => false]);
    exit();
}

// Map your forms' exact table names to their true primary key columns
$allowed = [
    'reserver' => 'idreserv',
    'commande' => 'idcom',
    'menu' => 'idplat',
    'table_resto' => 'idtable'
];

// Security validation check
if (!array_key_exists($table, $allowed) || $allowed[$table] !== $column) {
    echo json_encode(['exists' => false, 'error' => 'Invalid table or column configuration']);
    exit();
}

try {
    // Run the check against your database
    $query = "SELECT COUNT(*) FROM `$table` WHERE `$column` = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id]);
    $exists = $stmt->fetchColumn() > 0;

    echo json_encode(['exists' => $exists]);
} catch (PDOException $e) {
    echo json_encode(['exists' => false, 'error' => $e->getMessage()]);
}