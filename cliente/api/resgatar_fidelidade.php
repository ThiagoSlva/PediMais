<?php
/**
 * API de Resgate de Fidelidade do Cliente
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
$produto_id = $data['produto_id'] ?? null;

if (!$produto_id) {
    echo json_encode(['success' => false, 'message' => 'Produto não especificado']);
    exit;
}

try {
    // Get fidelity config
    $stmt = $pdo->query("SELECT * FROM fidelidade_config WHERE id = 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config || !$config['ativo']) {
        echo json_encode(['success' => false, 'message' => 'Programa de fidelidade não está ativo']);
        exit;
    }
    
    $pontos_necessarios = $config['quantidade_pedidos'];
    
    // Check available points
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM fidelidade_pontos WHERE cliente_id = ? AND status = 'ativo'");
    $stmt->execute([$cliente_id]);
    $pontos_ativos = $stmt->fetchColumn();
    
    if ($pontos_ativos < $pontos_necessarios) {
        echo json_encode([
            'success' => false, 
            'message' => "Pontos insuficientes. Você tem $pontos_ativos pontos, mas precisa de $pontos_necessarios."
        ]);
        exit;
    }
    
    // Verify product is available for redemption
    $stmt = $pdo->prepare("
        SELECT fp.*, p.nome as produto_nome, p.preco
        FROM fidelidade_produtos fp
        JOIN produtos p ON fp.produto_id = p.id
        WHERE fp.produto_id = ? AND fp.ativo = 1
    ");
    $stmt->execute([$produto_id]);
    $recompensa = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$recompensa) {
        echo json_encode(['success' => false, 'message' => 'Recompensa não disponível']);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Mark points as used (oldest first)
    $stmt = $pdo->prepare("
        SELECT id FROM fidelidade_pontos 
        WHERE cliente_id = ? AND status = 'ativo'
        ORDER BY criado_em ASC
        LIMIT " . (int)$pontos_necessarios . "
    ");
    $stmt->execute([$cliente_id]);
    $pontos_para_usar = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($pontos_para_usar) < $pontos_necessarios) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Erro ao contabilizar pontos']);
        exit;
    }
    
    // Update points status
    $placeholders = implode(',', array_fill(0, count($pontos_para_usar), '?'));
    $stmt = $pdo->prepare("UPDATE fidelidade_pontos SET status = 'resgatado' WHERE id IN ($placeholders)");
    $stmt->execute($pontos_para_usar);
    
    // Create redemption record
    $stmt = $pdo->prepare("
        INSERT INTO fidelidade_resgates (cliente_id, pontos_usados, status)
        VALUES (?, ?, 'resgatado')
    ");
    $stmt->execute([$cliente_id, $pontos_necessarios]);
    $resgate_id = $pdo->lastInsertId();
    
    // Create redemption item record
    $stmt = $pdo->prepare("
        INSERT INTO fidelidade_resgate_itens (resgate_id, produto_id, quantidade)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$resgate_id, $produto_id, $recompensa['quantidade']]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Recompensa "' . $recompensa['produto_nome'] . '" resgatada com sucesso! Fale com a loja para receber.',
        'resgate_id' => $resgate_id
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao processar resgate: ' . $e->getMessage()]);
}
