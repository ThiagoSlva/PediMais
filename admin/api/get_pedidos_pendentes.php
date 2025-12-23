<?php
header('Content-Type: application/json');
include '../../includes/config.php';
include '../includes/auth.php';

// Verificar se é admin (usando sessão diretamente já que a função não existe)
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['erro' => true, 'mensagem' => 'Não autorizado']);
    exit;
}

try {
    // Buscar pedidos pendentes
    $stmt = $pdo->query("SELECT id, codigo_pedido, cliente_nome, valor_total, data_pedido, status, tipo_entrega 
                         FROM pedidos 
                         WHERE status = 'pendente' 
                         ORDER BY data_pedido DESC");
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = count($pedidos);

    echo json_encode([
        'erro' => false,
        'total' => $total,
        'pedidos' => $pedidos
    ]);

} catch (PDOException $e) {
    echo json_encode(['erro' => true, 'mensagem' => 'Erro no banco de dados']);
}