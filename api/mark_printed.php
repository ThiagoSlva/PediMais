<?php
/**
 * API para marcar pedido como impresso
 * Usado pelo CardapiX Desktop Printer
 */
header('Content-Type: application/json');
$allowed_origin = defined('SITE_URL') ? SITE_URL : '*';
header("Access-Control-Allow-Origin: $allowed_origin");
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../includes/config.php';

// Verificar token de autenticação
$headers = getallheaders();
$token = $headers['Authorization'] ?? '';

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token não fornecido']);
    exit;
}

// Validar token
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE api_token = ? AND ativo = 1");
$stmt->execute([str_replace('Bearer ', '', $token)]);
$usuario = $stmt->fetch();

if (!$usuario) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token inválido']);
    exit;
}

// Obter dados do POST
$input = json_decode(file_get_contents('php://input'), true);
$pedido_id = $input['pedido_id'] ?? $_POST['pedido_id'] ?? null;

if (!$pedido_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'pedido_id é obrigatório']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE pedidos SET impresso = 1, data_impressao = NOW() WHERE id = ?");
    $stmt->execute([$pedido_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Pedido marcado como impresso']);
    }
    else {
        echo json_encode(['success' => false, 'error' => 'Pedido não encontrado']);
    }


}
catch (Exception $e) {
    http_response_code(500);
    error_log('Erro mark_printed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro interno. Tente novamente.']);
}
