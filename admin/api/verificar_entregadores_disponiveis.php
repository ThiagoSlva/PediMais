<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../includes/auth.php';

// verificar_login();

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE nivel = 'entregador' AND ativo = 1");
    $count = $stmt->fetchColumn();

    echo json_encode(['success' => true, 'disponiveis' => $count > 0, 'total' => $count]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
