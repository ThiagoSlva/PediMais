<?php
require_once 'includes/config.php';

$tables = ['configuracao_entrega', 'cidades', 'bairros'];

foreach ($tables as $table) {
    echo "Table: $table\n";
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo " - {$col['Field']} ({$col['Type']})\n";
        }
    } catch (Exception $e) {
        echo " - Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
?>
