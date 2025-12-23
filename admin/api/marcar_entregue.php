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

if (!$pedido_id) {
    echo json_encode(['success' => false, 'error' => 'Pedido ID missing']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE pedidos SET entregue = 1, status = 'concluido' WHERE id = ?");
    $stmt->execute([$pedido_id]);

    // Optionally move to "Entregue" lane if it exists
    $stmtLane = $pdo->prepare("SELECT id FROM kanban_lanes WHERE nome LIKE '%Entregue%' OR nome LIKE '%Finalizado%' LIMIT 1");
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
