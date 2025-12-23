<?php
/**
 * API: Buscar Cliente por Telefone
 * Retorna dados completos do cliente para preenchimento automático no checkout
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/config.php';

// Aceita GET para facilitar uso
$telefone = $_GET['telefone'] ?? null;

if (!$telefone && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $telefone = $input['telefone'] ?? $_POST['telefone'] ?? null;
}

if (!$telefone) {
    echo json_encode(['sucesso' => false, 'erro' => 'Telefone não informado'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Limpar telefone (apenas números)
$telefone = preg_replace('/[^0-9]/', '', $telefone);

// Só buscar se tiver pelo menos 10 dígitos
if (strlen($telefone) < 10) {
    echo json_encode(['sucesso' => false, 'erro' => 'Telefone incompleto'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Buscar cliente pelo telefone
    $stmt = $pdo->prepare("
        SELECT 
            id, nome, email, telefone, 
            endereco_principal, cep, rua, numero, 
            complemento, bairro, cidade, estado,
            telefone_verificado
        FROM clientes 
        WHERE telefone = ? OR telefone = ?
        LIMIT 1
    ");
    
    // Tentar com e sem código de país
    $telefoneComDDD = $telefone;
    $telefoneSemPais = (strlen($telefone) > 10 && str_starts_with($telefone, '55')) 
        ? substr($telefone, 2) 
        : $telefone;
    
    $stmt->execute([$telefoneComDDD, $telefoneSemPais]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cliente) {
        echo json_encode([
            'sucesso' => true,
            'encontrado' => true,
            'cliente' => [
                'id' => (int)$cliente['id'],
                'nome' => $cliente['nome'],
                'email' => $cliente['email'] ?? '',
                'telefone' => $cliente['telefone'],
                'endereco_principal' => $cliente['endereco_principal'] ?? '',
                'cep' => $cliente['cep'] ?? '',
                'rua' => $cliente['rua'] ?? '',
                'numero' => $cliente['numero'] ?? '',
                'complemento' => $cliente['complemento'] ?? '',
                'bairro' => $cliente['bairro'] ?? '',
                'cidade' => $cliente['cidade'] ?? '',
                'estado' => $cliente['estado'] ?? '',
                'telefone_verificado' => (bool)($cliente['telefone_verificado'] ?? false)
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'sucesso' => true,
            'encontrado' => false,
            'cliente' => null
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Erro ao buscar cliente: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
