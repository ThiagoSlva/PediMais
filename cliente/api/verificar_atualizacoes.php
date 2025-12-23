<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

// Pode receber pedido_id ou verificar todos os pedidos do cliente
$pedido_id = $_GET['pedido_id'] ?? $_GET['id'] ?? null;
$last_status = $_GET['last_status'] ?? null;

// Se não tem pedido_id, verifica pedidos do cliente logado
if (!$pedido_id && !isset($_SESSION['cliente_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Pedido ID é obrigatório ou faça login.']);
    exit;
}

try {
    if ($pedido_id) {
        // Verificar status de um pedido específico
        $stmt = $pdo->prepare("SELECT id, status, data_pedido FROM pedidos WHERE id = ?");
        $stmt->execute([$pedido_id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pedido) {
            $changed = ($last_status !== null && $last_status !== $pedido['status']);
            echo json_encode([
                'id' => $pedido['id'],
                'status' => $pedido['status'],
                'data_pedido' => $pedido['data_pedido'],
                'changed' => $changed
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Pedido não encontrado.']);
        }
    } else {
        // Verificar últimos pedidos do cliente logado
        $cliente_id = $_SESSION['cliente_id'];
        $stmt = $pdo->prepare("SELECT id, status, data_pedido FROM pedidos WHERE cliente_id = ? ORDER BY id DESC LIMIT 10");
        $stmt->execute([$cliente_id]);
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'pedidos' => $pedidos,
            'total' => count($pedidos)
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao verificar atualizações: ' . $e->getMessage()]);
}
?>