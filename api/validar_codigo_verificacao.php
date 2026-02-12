<?php
/**
 * API: Validar Código de Verificação
 * Verifica se o código informado é válido para o telefone
 */

header('Content-Type: application/json; charset=utf-8');
$allowed_origin = defined('SITE_URL') ? SITE_URL : '*';
header("Access-Control-Allow-Origin: $allowed_origin");
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['erro' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Receber dados
$input = json_decode(file_get_contents('php://input'), true);
$telefone = preg_replace('/[^0-9]/', '', $input['telefone'] ?? '');
$codigo = trim($input['codigo'] ?? '');

if (empty($telefone) || empty($codigo)) {
    echo json_encode(['erro' => 'Telefone e código são obrigatórios'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Buscar cliente pelo telefone
    $stmt = $pdo->prepare("SELECT id FROM clientes WHERE telefone = ? LIMIT 1");
    $stmt->execute([$telefone]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        echo json_encode(['erro' => 'Cliente não encontrado'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $cliente_id = $cliente['id'];

    // Verificar código válido e não expirado
    $stmt = $pdo->prepare("
        SELECT id FROM verificacao_codigos 
        WHERE cliente_id = ? AND codigo = ? AND expira_em > NOW() AND usado = 0
        LIMIT 1
    ");
    $stmt->execute([$cliente_id, $codigo]);
    $codigo_valido = $stmt->fetch();

    if ($codigo_valido) {
        // Marcar código como usado
        $pdo->prepare("UPDATE verificacao_codigos SET usado = 1 WHERE id = ?")->execute([$codigo_valido['id']]);

        // Marcar cliente como verificado
        $pdo->prepare("UPDATE clientes SET telefone_verificado = 1 WHERE id = ?")->execute([$cliente_id]);

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Telefone verificado com sucesso!'
        ], JSON_UNESCAPED_UNICODE);
    }
    else {
        echo json_encode(['erro' => 'Código inválido ou expirado'], JSON_UNESCAPED_UNICODE);
    }

}
catch (PDOException $e) {
    error_log("Erro ao validar código: " . $e->getMessage());
    echo json_encode(['erro' => 'Erro ao validar código'], JSON_UNESCAPED_UNICODE);
}