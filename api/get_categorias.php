<?php
include '../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Buscar categorias ativas ordenadas
    $stmt = $pdo->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY ordem ASC");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($categorias);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar categorias: ' . $e->getMessage()]);
}
?>