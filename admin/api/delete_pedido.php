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
    // Delete items first (if cascade delete is not set)
    $pdo->prepare("DELETE FROM pedido_itens WHERE pedido_id = ?")->execute([$pedido_id]); // Assuming table name
    
    $stmt = $pdo->prepare("DELETE FROM pedidos WHERE id = ?");
    $stmt->execute([$pedido_id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
