<?php
/**
 * API para Repetir Pedido
 * Adiciona os itens de um pedido anterior ao carrinho
 */

header('Content-Type: application/json');

require_once '../../includes/config.php';
require_once '../includes/auth.php';

// Check authentication
if (!isset($_SESSION['cliente_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$cliente_id = $_SESSION['cliente_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['pedido_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID do pedido não informado']);
    exit;
}

$pedido_id = (int) $data['pedido_id'];

try {
    // Verify order belongs to client
    $stmt = $pdo->prepare("SELECT id FROM pedidos WHERE id = ? AND cliente_id = ?");
    $stmt->execute([$pedido_id, $cliente_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Pedido não encontrado']);
        exit;
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT pi.produto_id, pi.quantidade, pi.observacoes, p.ativo as produto_ativo, p.nome as produto_nome
        FROM pedido_itens pi
        LEFT JOIN produtos p ON pi.produto_id = p.id
        WHERE pi.pedido_id = ?
    ");
    $stmt->execute([$pedido_id]);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($itens)) {
        echo json_encode(['success' => false, 'message' => 'Pedido não possui itens']);
        exit;
    }
    
    // Initialize cart if not exists
    if (!isset($_SESSION['carrinho'])) {
        $_SESSION['carrinho'] = [];
    }
    
    $itens_adicionados = 0;
    $itens_indisponiveis = [];
    
    foreach ($itens as $item) {
        // Check if product is still available
        if ($item['produto_id'] && $item['produto_ativo']) {
            $_SESSION['carrinho'][] = [
                'produto_id' => $item['produto_id'],
                'quantidade' => $item['quantidade'],
                'observacoes' => $item['observacoes'] ?? '',
                'adicionais' => []
            ];
            $itens_adicionados++;
        } else {
            $itens_indisponiveis[] = $item['produto_nome'];
        }
    }
    
    if ($itens_adicionados > 0) {
        $message = "$itens_adicionados itens adicionados ao carrinho!";
        if (!empty($itens_indisponiveis)) {
            $message .= " Alguns itens não estão mais disponíveis.";
        }
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'itens_adicionados' => $itens_adicionados,
            'indisponiveis' => $itens_indisponiveis
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Nenhum item disponível para adicionar ao carrinho'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
