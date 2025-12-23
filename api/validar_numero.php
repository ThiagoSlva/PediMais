<?php
include '../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido. Use POST.']);
    exit;
}

// Receber dados
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$telefone = $input['telefone'] ?? null;

if (!$telefone) {
    http_response_code(400);
    echo json_encode(['error' => 'Telefone é obrigatório.']);
    exit;
}

// Limpar telefone
$telefone = preg_replace('/[^0-9]/', '', $telefone);

try {
    // Verificar se o telefone existe
    $stmt = $pdo->prepare("SELECT id, nome, telefone_verificado FROM clientes WHERE telefone = ?");
    $stmt->execute([$telefone]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
        echo json_encode([
            'exists' => true,
            'verified' => (bool)$cliente['telefone_verificado'],
            'nome' => $cliente['nome']
        ]);
    } else {
        echo json_encode([
            'exists' => false,
            'verified' => false
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao validar número: ' . $e->getMessage()]);
}
?>