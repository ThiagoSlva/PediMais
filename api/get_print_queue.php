<?php
/**
 * API para obter fila de impressão de pedidos
 * Usado pelo CardapiX Desktop Printer
 */
header('Content-Type: application/json');
$allowed_origin = defined('SITE_URL') ? SITE_URL : '*';
header("Access-Control-Allow-Origin: $allowed_origin");
header('Access-Control-Allow-Headers: Authorization, Content-Type');

require_once '../includes/config.php';

// Verificar token de autenticação
$headers = getallheaders();
$token = $headers['Authorization'] ?? $_GET['token'] ?? '';

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token não fornecido']);
    exit;
}

// Validar token (simplificado - pode melhorar com JWT)
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE api_token = ? AND ativo = 1");
$stmt->execute([str_replace('Bearer ', '', $token)]);
$usuario = $stmt->fetch();

if (!$usuario) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token inválido']);
    exit;
}

try {
    // Buscar pedidos não impressos (últimas 24 horas)
    $sql = "SELECT p.*, fp.nome as forma_pagamento
            FROM pedidos p
            LEFT JOIN formas_pagamento fp ON p.forma_pagamento_id = fp.id
            WHERE p.impresso = 0 
            AND p.data_pedido >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND p.status != 'cancelado'
            ORDER BY p.data_pedido ASC";

    $stmt = $pdo->query($sql);
    $pedidos = [];

    while ($pedido = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Garantir campos opcionais
        $pedido['taxa_entrega'] = $pedido['taxa_entrega'] ?? 0;
        $pedido['troco_para'] = $pedido['troco_para'] ?? 0;

        // Buscar itens do pedido
        $stmt_itens = $pdo->prepare("SELECT pi.*, pr.nome as produto_nome
                                     FROM pedido_itens pi
                                     LEFT JOIN produtos pr ON pi.produto_id = pr.id
                                     WHERE pi.pedido_id = ?");
        $stmt_itens->execute([$pedido['id']]);
        $pedido['itens'] = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

        $pedidos[] = $pedido;
    }

    echo json_encode([
        'success' => true,
        'count' => count($pedidos),
        'pedidos' => $pedidos
    ]);


}
catch (Exception $e) {
    http_response_code(500);
    error_log('Erro get_print_queue: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro interno. Tente novamente.']);
}
