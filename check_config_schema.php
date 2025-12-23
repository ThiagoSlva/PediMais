<?php
require_once 'includes/config.php';

try {
    $stmt = $pdo->query("DESCRIBE config");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Table: config\n";
    foreach ($columns as $col) {
        echo " - {$col['Field']} ({$col['Type']})\n";
    }
} catch (Exception $e) {
    echo "Table config does not exist or error: " . $e->getMessage() . "\n";
}
?>
