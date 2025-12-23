<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

check_cliente_auth();

$cliente = get_cliente_logado();
$pedido_id = (int)($_GET['id'] ?? 0);

if (!$pedido_id) {
    echo json_encode(['error' => 'ID do pedido não fornecido']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            id,
            codigo_pedido,
            status,
            pago,
            em_preparo,
            saiu_entrega,
            entregue,
            valor_total,
            tipo_entrega,
            data_pedido,
            atualizado_em
        FROM pedidos 
        WHERE id = :id AND cliente_id = :cliente_id
    ");
    $stmt->execute(['id' => $pedido_id, 'cliente_id' => $cliente['id']]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pedido) {
        echo json_encode(['error' => 'Pedido não encontrado']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'pedido' => $pedido
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar status do pedido: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar status']);
}




