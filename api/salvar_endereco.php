<?php
/**
 * API para salvar endereço do cliente
 * POST: cliente_id ou telefone + dados do endereço
 */

header('Content-Type: application/json; charset=utf-8');
$allowed_origin = defined('SITE_URL') ? SITE_URL : '*';
header("Access-Control-Allow-Origin: $allowed_origin");
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        echo json_encode(['sucesso' => false, 'erro' => 'Dados não recebidos']);
        exit;
    }

    // Identificar cliente
    $cliente_id = null;

    if (!empty($input['cliente_id'])) {
        $cliente_id = (int)$input['cliente_id'];
    }
    elseif (!empty($input['telefone'])) {
        $telefone = preg_replace('/\D/', '', $input['telefone']);
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', '') = ?");
        $stmt->execute([$telefone]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        $cliente_id = $cliente ? $cliente['id'] : null;
    }

    if (!$cliente_id) {
        echo json_encode(['sucesso' => false, 'erro' => 'Cliente não identificado']);
        exit;
    }

    // Dados do endereço
    $apelido = isset($input['apelido']) ? trim($input['apelido']) : null;
    $cep = isset($input['cep']) ? preg_replace('/\D/', '', $input['cep']) : null;
    $rua = isset($input['rua']) ? trim($input['rua']) : null;
    $numero = isset($input['numero']) ? trim($input['numero']) : null;
    $complemento = isset($input['complemento']) ? trim($input['complemento']) : null;
    $bairro = isset($input['bairro']) ? trim($input['bairro']) : null;
    $cidade = isset($input['cidade']) ? trim($input['cidade']) : null;
    $estado = isset($input['estado']) ? trim($input['estado']) : null;
    $principal = !empty($input['principal']) ? 1 : 0;

    // Validar campos mínimos
    if (!$rua || !$bairro) {
        echo json_encode(['sucesso' => false, 'erro' => 'Rua e bairro são obrigatórios']);
        exit;
    }

    // Se marcado como principal, desmarcar outros
    if ($principal) {
        $stmt = $pdo->prepare("UPDATE cliente_enderecos SET principal = 0 WHERE cliente_id = ?");
        $stmt->execute([$cliente_id]);
    }

    // Verificar se é atualização ou novo
    $endereco_id = !empty($input['endereco_id']) ? (int)$input['endereco_id'] : null;

    if ($endereco_id) {
        // Atualizar existente
        $stmt = $pdo->prepare("
            UPDATE cliente_enderecos SET 
                apelido = ?, cep = ?, rua = ?, numero = ?, 
                complemento = ?, bairro = ?, cidade = ?, estado = ?, principal = ?
            WHERE id = ? AND cliente_id = ?
        ");
        $stmt->execute([$apelido, $cep, $rua, $numero, $complemento, $bairro, $cidade, $estado, $principal, $endereco_id, $cliente_id]);

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Endereço atualizado com sucesso',
            'endereco_id' => $endereco_id
        ]);
    }
    else {
        // Inserir novo
        $stmt = $pdo->prepare("
            INSERT INTO cliente_enderecos 
                (cliente_id, apelido, cep, rua, numero, complemento, bairro, cidade, estado, principal)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$cliente_id, $apelido, $cep, $rua, $numero, $complemento, $bairro, $cidade, $estado, $principal]);

        $novo_id = $pdo->lastInsertId();

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Endereço salvo com sucesso',
            'endereco_id' => $novo_id
        ]);
    }


}
catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao salvar endereço: ' . $e->getMessage()]);
}
