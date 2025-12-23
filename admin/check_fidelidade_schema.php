<?php
include 'includes/config.php';

function checkTable($pdo, $tableName) {
    try {
        $stmt = $pdo->query("DESCRIBE $tableName");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Table '$tableName' exists. Columns: " . implode(", ", $columns) . "\n";
    } catch (PDOException $e) {
        echo "Table '$tableName' does not exist.\n";
    }
}

checkTable($pdo, 'fidelidade_config');
checkTable($pdo, 'fidelidade_produtos');
checkTable($pdo, 'clientes'); // Check if there are loyalty columns here
?>
