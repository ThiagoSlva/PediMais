<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['cliente_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado. Faça login.']);
    exit;
}

$cliente_id = $_SESSION['cliente_id'];

try {
    // Buscar pedidos do cliente
    $stmt = $pdo->prepare("SELECT id, data_pedido, valor_total, status, pagamento_online, codigo_pedido FROM pedidos WHERE cliente_id = ? ORDER BY id DESC");
    $stmt->execute([$cliente_id]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($pedidos);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar pedidos: ' . $e->getMessage()]);
}
?>