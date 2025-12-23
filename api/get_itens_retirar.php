<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

try {
    // Verificar se tabela existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'itens_retirar'");
    if ($stmt->rowCount() == 0) {
        echo json_encode(['success' => true, 'itens' => []]);
        exit;
    }
    
    // Buscar itens ativos
    $stmt = $pdo->query("SELECT id, nome FROM itens_retirar WHERE ativo = 1 ORDER BY ordem ASC, nome ASC");
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'itens' => $itens]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => true, 'itens' => []]);
}
?>
