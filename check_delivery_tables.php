<?php
require_once 'includes/config.php';

try {
    // Check if 'entregador_id' exists in 'pedidos'
    $colunas = $pdo->query("SHOW COLUMNS FROM pedidos LIKE 'entregador_id'")->fetchAll();
    if (count($colunas) == 0) {
        $pdo->exec("ALTER TABLE pedidos ADD COLUMN entregador_id INT DEFAULT NULL");
        echo "Column 'entregador_id' added to 'pedidos'.\n";
    }

    echo "Delivery tables checked/created successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
