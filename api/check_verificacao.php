<?php
/**
 * API: Verificar se cliente precisa de verificação
 * Retorna se o sistema está ativo e se o telefone já está verificado
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../includes/config.php';

$telefone = preg_replace('/[^0-9]/', '', $_GET['telefone'] ?? '');

if (empty($telefone)) {
    echo json_encode(['erro' => 'Telefone é obrigatório'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Verificar se sistema de verificação está ativo
    $stmt = $pdo->query("SELECT ativo, tempo_expiracao FROM configuracao_verificacao LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config || !$config['ativo']) {
        echo json_encode([
            'sucesso' => true,
            'verificacao_ativa' => false,
            'precisa_verificar' => false
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Verificar se cliente existe e está verificado
    $stmt = $pdo->prepare("SELECT id, telefone_verificado FROM clientes WHERE telefone = ? LIMIT 1");
    $stmt->execute([$telefone]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $precisa_verificar = true;
    
    if ($cliente && $cliente['telefone_verificado']) {
        $precisa_verificar = false;
    }
    
    echo json_encode([
        'sucesso' => true,
        'verificacao_ativa' => true,
        'precisa_verificar' => $precisa_verificar,
        'tempo_expiracao' => intval($config['tempo_expiracao'])
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log("Erro ao verificar status: " . $e->getMessage());
    echo json_encode(['erro' => 'Erro ao verificar status'], JSON_UNESCAPED_UNICODE);
}
