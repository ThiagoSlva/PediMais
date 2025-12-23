<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 1. Migration: Ensure fidelidade_resgates table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS fidelidade_resgates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT NOT NULL,
        data_resgate DATETIME DEFAULT CURRENT_TIMESTAMP,
        pontos_utilizados INT DEFAULT 0,
        pedido_origem_id INT DEFAULT NULL
    )");
} catch (PDOException $e) {
    // Ignore if exists
}

// 2. Validate Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['erro' => true, 'mensagem' => 'Método inválido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$cliente_telefone = isset($input['telefone']) ? preg_replace('/[^0-9]/', '', $input['telefone']) : '';

if (empty($cliente_telefone)) {
    echo json_encode(['erro' => true, 'mensagem' => 'Telefone do cliente não informado']);
    exit;
}

try {
    // 3. Get Client ID
    $stmt = $pdo->prepare("SELECT id, nome FROM clientes WHERE telefone = ? LIMIT 1");
    $stmt->execute([$cliente_telefone]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        echo json_encode(['erro' => true, 'mensagem' => 'Cliente não encontrado. Faça um pedido primeiro!']);
        exit;
    }

    $cliente_id = $cliente['id'];

    // 4. Get Loyalty Config
    $stmt = $pdo->query("SELECT * FROM fidelidade_config LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config || !$config['ativo']) {
        echo json_encode(['erro' => true, 'mensagem' => 'O programa de fidelidade não está ativo no momento.']);
        exit;
    }

    $pedidos_para_resgate = (int)$config['quantidade_pedidos'];

    // 5. Calculate Points (Orders)
    // Count completed orders
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE cliente_id = ? AND status = 'concluido'");
    $stmt->execute([$cliente_id]);
    $total_pedidos = $stmt->fetchColumn();

    // Count redemptions
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM fidelidade_resgates WHERE cliente_id = ?");
    $stmt->execute([$cliente_id]);
    $total_resgates = $stmt->fetchColumn();

    // Logic: Each redemption consumes 'pedidos_para_resgate' orders
    // Available Redemptions = floor(Total Orders / Required) - Redeemed
    $resgates_possiveis_total = floor($total_pedidos / $pedidos_para_resgate);
    $resgates_disponiveis = $resgates_possiveis_total - $total_resgates;

    if ($resgates_disponiveis <= 0) {
        $faltam = $pedidos_para_resgate - ($total_pedidos % $pedidos_para_resgate);
        echo json_encode([
            'erro' => true, 
            'mensagem' => "Você ainda não tem pontos suficientes. Faltam $faltam pedidos para ganhar uma recompensa!",
            'saldo' => $total_pedidos % $pedidos_para_resgate,
            'meta' => $pedidos_para_resgate
        ]);
        exit;
    }

    // 6. Get Available Rewards
    $stmt = $pdo->query("
        SELECT fp.*, p.nome, p.descricao, p.imagem 
        FROM fidelidade_produtos fp
        JOIN produtos p ON fp.produto_id = p.id
        WHERE fp.ativo = 1
    ");
    $premios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($premios)) {
        echo json_encode(['erro' => true, 'mensagem' => 'Não há prêmios disponíveis no momento.']);
        exit;
    }

    // If action is 'check', just return status
    if (isset($input['acao']) && $input['acao'] == 'check') {
        echo json_encode([
            'erro' => false,
            'disponivel' => true,
            'qtd_disponivel' => $resgates_disponiveis,
            'premios' => $premios
        ]);
        exit;
    }

    // If action is 'resgatar', process redemption (usually done during checkout, but here we just confirm eligibility)
    // The actual deduction happens when the order is placed with a reward.
    // For now, we return success and the list of rewards.
    
    echo json_encode([
        'erro' => false,
        'mensagem' => 'Recompensa disponível!',
        'premios' => $premios,
        'resgates_disponiveis' => $resgates_disponiveis
    ]);

} catch (Exception $e) {
    echo json_encode(['erro' => true, 'mensagem' => 'Erro ao processar fidelidade: ' . $e->getMessage()]);
}
?>
