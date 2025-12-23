<?php
include '../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['categoria_id']) || empty($_GET['categoria_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID da categoria não fornecido.']);
    exit;
}

$categoria_id = $_GET['categoria_id'];

try {
    // Buscar produtos da categoria
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE categoria_id = ? AND ativo = 1 ORDER BY ordem ASC");
    $stmt->execute([$categoria_id]);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($produtos);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar produtos: ' . $e->getMessage()]);
}
?>