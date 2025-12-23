<?php
require_once 'includes/config.php';

try {
    $tables = $pdo->query("SHOW TABLES LIKE '%pedido%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
        $columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
