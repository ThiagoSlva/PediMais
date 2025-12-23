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

checkTable($pdo, 'configuracao_verificacao');
checkTable($pdo, 'verificacao_codigos'); // Log of sent codes?
checkTable($pdo, 'clientes'); // Check for verification columns
?>
