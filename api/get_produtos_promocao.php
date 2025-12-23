<?php
include '../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Buscar produtos em promoção (preço promocional > 0)
    $stmt = $pdo->query("SELECT * FROM produtos WHERE preco_promocional > 0 AND ativo = 1 ORDER BY ordem ASC");
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($produtos);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar produtos em promoção: ' . $e->getMessage()]);
}
?>