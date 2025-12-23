<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../includes/auth.php';

try {
    $stmt = $pdo->query("SELECT impressao_automatica FROM configuracoes LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'impressao_automatica' => (bool)($config['impressao_automatica'] ?? false)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'impressao_automatica' => false,
        'error' => $e->getMessage()
    ]);
}
