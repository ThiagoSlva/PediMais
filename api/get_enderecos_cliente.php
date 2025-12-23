<?php
/**
 * API para buscar endereços salvos do cliente
 * GET: ?telefone=11999999999
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/config.php';

try {
    $telefone = filter_input(INPUT_GET, 'telefone', FILTER_SANITIZE_STRING);
    
    if (!$telefone) {
        echo json_encode(['sucesso' => false, 'erro' => 'Telefone não informado']);
        exit;
    }
    
    // Limpar telefone (remover formatação)
    $telefone = preg_replace('/\D/', '', $telefone);
    
    // Buscar cliente pelo telefone
    $stmt = $pdo->prepare("SELECT id FROM clientes WHERE REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', '') = ?");
    $stmt->execute([$telefone]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cliente) {
        echo json_encode(['sucesso' => true, 'enderecos' => [], 'mensagem' => 'Cliente não encontrado']);
        exit;
    }
    
    // Buscar endereços do cliente
    $stmt = $pdo->prepare("
        SELECT id, apelido, cep, rua, numero, complemento, bairro, cidade, estado, principal
        FROM cliente_enderecos 
        WHERE cliente_id = ? 
        ORDER BY principal DESC, criado_em DESC
    ");
    $stmt->execute([$cliente['id']]);
    $enderecos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatar endereços para exibição
    foreach ($enderecos as &$end) {
        $end['endereco_completo'] = trim(
            ($end['rua'] ? $end['rua'] : '') .
            ($end['numero'] ? ', ' . $end['numero'] : '') .
            ($end['complemento'] ? ' - ' . $end['complemento'] : '') .
            ($end['bairro'] ? ' - ' . $end['bairro'] : '')
        );
        $end['principal'] = (bool)$end['principal'];
    }
    
    echo json_encode([
        'sucesso' => true,
        'cliente_id' => $cliente['id'],
        'enderecos' => $enderecos,
        'total' => count($enderecos)
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'erro' => 'Erro ao buscar endereços: ' . $e->getMessage()]);
}
