<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../includes/auth.php';

// verificar_login();

$input = json_decode(file_get_contents('php://input'), true);
$pedido_id = $input['pedido_id'] ?? $_GET['pedido_id'] ?? null;

if (!$pedido_id) {
    echo json_encode(['success' => false, 'error' => 'Pedido ID missing']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT tipo_entrega FROM pedidos WHERE id = ?");
    $stmt->execute([$pedido_id]);
    $tipo = $stmt->fetchColumn();

    echo json_encode(['success' => true, 'tipo_entrega' => $tipo]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
