<?php
require_once 'includes/config.php';

try {
    // 1. Check/Create kanban_lanes table
    $pdo->exec("CREATE TABLE IF NOT EXISTS kanban_lanes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(50) NOT NULL,
        cor VARCHAR(20) DEFAULT '#6c757d',
        ordem INT DEFAULT 0,
        ativo TINYINT DEFAULT 1
    )");

    // 2. Insert default lanes if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM kanban_lanes");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO kanban_lanes (nome, cor, ordem) VALUES 
            ('Novos Pedidos', '#3b82f6', 1),
            ('Em Preparo', '#f59e0b', 2),
            ('Pronto / Aguardando', '#10b981', 3),
            ('Saiu para Entrega', '#6366f1', 4),
            ('Entregue / Finalizado', '#64748b', 5)
        ");
        echo "Default lanes created.\n";
    }

    // 3. Check if 'lane_id' exists in 'pedidos'
    $colunas = $pdo->query("SHOW COLUMNS FROM pedidos LIKE 'lane_id'")->fetchAll();
    if (count($colunas) == 0) {
        $pdo->exec("ALTER TABLE pedidos ADD COLUMN lane_id INT DEFAULT 1");
        echo "Column 'lane_id' added to 'pedidos'.\n";
        
        // Update existing orders to lane 1 (Novos) or 5 (Entregue) based on status
        $pdo->exec("UPDATE pedidos SET lane_id = 5 WHERE status = 'concluido' OR entregue = 1");
        $pdo->exec("UPDATE pedidos SET lane_id = 1 WHERE lane_id IS NULL OR lane_id = 0");
    }

    echo "Kanban tables checked/created successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
