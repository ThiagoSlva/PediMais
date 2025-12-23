<?php
include '../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido. Use POST.']);
    exit;
}

// Receber dados (JSON ou Form Data)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$pedido_id = $input['pedido_id'] ?? null;
$nota = $input['nota'] ?? null;
$comentario = $input['comentario'] ?? '';
$nome = $input['nome'] ?? null; // Nome do cliente, opcional se já tiver no pedido, mas vamos salvar se vier

if (!$pedido_id || !$nota) {
    http_response_code(400);
    echo json_encode(['error' => 'Pedido ID e Nota são obrigatórios.']);
    exit;
}

try {
    // Verificar se o pedido existe
    $stmt = $pdo->prepare("SELECT id FROM pedidos WHERE id = ?");
    $stmt->execute([$pedido_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Pedido não encontrado.']);
        exit;
    }

    // Verificar se já existe avaliação para este pedido (opcional, mas boa prática)
    $stmt = $pdo->prepare("SELECT id FROM avaliacoes WHERE pedido_id = ?");
    $stmt->execute([$pedido_id]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Este pedido já foi avaliado.']);
        exit;
    }

    // Inserir avaliação
    // Colunas: pedido_id, nome, avaliacao, descricao, ativo, data_avaliacao
    $stmt = $pdo->prepare("INSERT INTO avaliacoes (pedido_id, nome, avaliacao, descricao, ativo, data_avaliacao) VALUES (?, ?, ?, ?, 1, NOW())");
    $stmt->execute([$pedido_id, $nome, $nota, $comentario]);

    echo json_encode(['success' => true, 'message' => 'Avaliação salva com sucesso!']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao salvar avaliação: ' . $e->getMessage()]);
}
?>