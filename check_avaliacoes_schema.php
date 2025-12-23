<?php
require_once 'includes/config.php';

try {
    $stmt = $pdo->query("DESCRIBE avaliacoes");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Table: avaliacoes\n";
    foreach ($columns as $col) {
        echo " - {$col['Field']} ({$col['Type']})\n";
    }
} catch (Exception $e) {
    echo "Table avaliacoes does not exist or error: " . $e->getMessage() . "\n";
}
?>
