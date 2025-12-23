<?php
include '../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

$pedido_id = $_GET['id'] ?? $_GET['pedido_id'] ?? null;

if (!$pedido_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do pedido é obrigatório.']);
    exit;
}

try {
    // Verificar status do pagamento
    $stmt = $pdo->prepare("SELECT id, status, pagamento_online FROM pedidos WHERE id = ?");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pedido) {
        echo json_encode([
            'id' => $pedido['id'],
            'status' => $pedido['status'],
            'pagamento_online' => $pedido['pagamento_online']
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Pedido não encontrado.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao verificar pagamento: ' . $e->getMessage()]);
}
?>