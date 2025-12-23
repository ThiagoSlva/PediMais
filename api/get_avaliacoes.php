<?php
include '../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Buscar avaliações com dados do cliente
    $sql = "SELECT a.*, c.nome AS cliente_nome 
            FROM avaliacoes a 
            LEFT JOIN pedidos p ON a.pedido_id = p.id 
            LEFT JOIN clientes c ON p.cliente_id = c.id 
            ORDER BY a.data_avaliacao DESC";
            
    $stmt = $pdo->query($sql);
    $avaliacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($avaliacoes);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar avaliações: ' . $e->getMessage()]);
}
?>