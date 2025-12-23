<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../includes/auth.php';

// verificar_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$pedido_id = $input['pedido_id'] ?? null;
$entregador_id = $input['entregador_id'] ?? null;

if (!$pedido_id || !$entregador_id) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE pedidos SET entregador_id = ?, saiu_entrega = 1 WHERE id = ?");
    $stmt->execute([$entregador_id, $pedido_id]);

    // Optionally move to "Saiu para Entrega" lane
    $stmtLane = $pdo->prepare("SELECT id FROM kanban_lanes WHERE nome LIKE '%Saiu%' OR nome LIKE '%Entrega%' LIMIT 1");
    $stmtLane->execute();
    $laneId = $stmtLane->fetchColumn();

    if ($laneId) {
        $pdo->prepare("UPDATE pedidos SET lane_id = ? WHERE id = ?")->execute([$laneId, $pedido_id]);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
