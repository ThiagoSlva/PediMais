<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../includes/auth.php';

// verificar_login();

try {
    // Fetch active orders (not concluded/cancelled)
    $sql = "SELECT p.*, c.nome as cliente_nome 
            FROM pedidos p
            LEFT JOIN clientes c ON p.cliente_id = c.id
            WHERE p.status != 'concluido' AND p.status != 'cancelado'
            ORDER BY p.id DESC";
    
    $stmt = $pdo->query($sql);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'pedidos' => $pedidos]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
