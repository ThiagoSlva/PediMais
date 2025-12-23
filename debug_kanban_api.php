<?php
require_once 'includes/config.php';

// Testar o API de Kanban diretamente
try {
    echo "=== Testando get_kanban_snapshot.php ===\n\n";
    
    // Simular o que a API faz
    $stmt = $pdo->query("SELECT * FROM kanban_lanes ORDER BY ordem ASC");
    $lanes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Lanes encontradas: " . count($lanes) . "\n\n";
    
    foreach ($lanes as $lane) {
        echo "Lane: " . $lane['nome'] . " (ID: " . $lane['id'] . ")\n";
        
        $sql = "SELECT p.*, c.nome as cliente_nome, c.telefone as cliente_telefone, 
                       COALESCE(c.endereco_principal, '') as cliente_endereco
                FROM pedidos p
                LEFT JOIN clientes c ON p.cliente_id = c.id
                WHERE p.lane_id = ?
                ORDER BY p.id DESC";
        
        $stmtOrders = $pdo->prepare($sql);
        $stmtOrders->execute([$lane['id']]);
        $pedidos = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);
        
        echo "  Pedidos: " . count($pedidos) . "\n";
        
        foreach ($pedidos as $p) {
            echo "    - ID: " . $p['id'] . ", Cliente: " . ($p['cliente_nome'] ?? 'N/A') . "\n";
            // Verificar campos que podem causar erro
            echo "      pago: " . var_export($p['pago'] ?? null, true) . ", ";
            echo "em_preparo: " . var_export($p['em_preparo'] ?? null, true) . ", ";
            echo "saiu_entrega: " . var_export($p['saiu_entrega'] ?? null, true) . ", ";
            echo "entregue: " . var_export($p['entregue'] ?? null, true) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString();
}
