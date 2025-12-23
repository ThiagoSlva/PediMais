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
$lane_id = $input['lane_id'] ?? null;

if (!$lane_id) {
    echo json_encode(['success' => false, 'error' => 'Lane ID missing']);
    exit;
}

try {
    // Move orders to the first available lane (usually 'Novos')
    $stmt = $pdo->prepare("SELECT id FROM kanban_lanes WHERE id != ? ORDER BY ordem ASC LIMIT 1");
    $stmt->execute([$lane_id]);
    $fallbackLaneId = $stmt->fetchColumn();

    if ($fallbackLaneId) {
        $pdo->prepare("UPDATE pedidos SET lane_id = ? WHERE lane_id = ?")->execute([$fallbackLaneId, $lane_id]);
    }

    // Delete Lane
    $stmt = $pdo->prepare("DELETE FROM kanban_lanes WHERE id = ?");
    $stmt->execute([$lane_id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
