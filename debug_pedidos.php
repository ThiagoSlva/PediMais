<?php
require_once 'includes/config.php';

try {
    echo "Table: pedidos\n";
    $columns = $pdo->query("DESCRIBE pedidos")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
