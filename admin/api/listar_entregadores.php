<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../includes/auth.php';

// verificar_login();

try {
    // Fetch users with level 'entregador'
    $stmt = $pdo->query("SELECT id, nome FROM usuarios WHERE nivel = 'entregador' AND ativo = 1 ORDER BY nome ASC");
    $entregadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'entregadores' => $entregadores]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
