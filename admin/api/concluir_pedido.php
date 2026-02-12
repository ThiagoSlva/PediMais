<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    require_once '../../includes/security_headers.php';
    session_start();
}

// Check login via JSON response (don't redirect for API calls)
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado. Faça login novamente.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$pedido_id = $input['pedido_id'] ?? null;

if (!$pedido_id) {
    echo json_encode(['success' => false, 'error' => 'ID do pedido não informado']);
    exit;
}

try {
    // Verificar se coluna 'arquivado' existe, se não, criar
    $stmt = $pdo->query("SHOW COLUMNS FROM pedidos LIKE 'arquivado'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE pedidos ADD COLUMN arquivado TINYINT(1) DEFAULT 0");
        $pdo->exec("ALTER TABLE pedidos ADD COLUMN data_conclusao DATETIME NULL");
    }

    // Buscar dados do pedido
    $stmt = $pdo->prepare("SELECT p.*, c.telefone as cliente_telefone, c.nome as cliente_nome 
                           FROM pedidos p 
                           LEFT JOIN clientes c ON p.cliente_id = c.id 
                           WHERE p.id = ?");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        echo json_encode(['success' => false, 'error' => 'Pedido não encontrado']);
        exit;
    }

    // Atualizar pedido como concluído e arquivado
    $stmt = $pdo->prepare("UPDATE pedidos SET 
        status = 'finalizado',
        arquivado = 1,
        entregue = 1,
        data_conclusao = NOW(),
        atualizado_em = NOW()
        WHERE id = ?");
    $stmt->execute([$pedido_id]);

    // Enviar notificação WhatsApp de conclusão
    $whatsapp_result = ['success' => false, 'error' => 'Não enviado'];

    try {
        require_once '../../includes/whatsapp_helper.php';
        $whatsapp = new WhatsAppHelper($pdo);

        $telefone = $pedido['cliente_telefone'] ?? '';
        if (!empty($telefone)) {
            // 1) PRIMEIRO: Criar token de avaliação se sistema estiver ativo
            $stmt_config = $pdo->query("SELECT * FROM configuracao_avaliacoes LIMIT 1");
            $config_aval = $stmt_config->fetch(PDO::FETCH_ASSOC);

            if ($config_aval && $config_aval['ativo']) {
                try {
                    // Gerar token único para avaliação
                    $token = bin2hex(random_bytes(16));

                    // Inserir entrada de avaliação (usando nomes corretos das colunas)
                    // nome = cliente_nome, avaliacao = 0 (ainda não avaliou)
                    $stmt_ins = $pdo->prepare("INSERT IGNORE INTO avaliacoes (pedido_id, nome, cliente_nome, token, avaliacao) VALUES (?, ?, ?, ?, 0)");
                    $stmt_ins->execute([$pedido_id, $pedido['cliente_nome'], $pedido['cliente_nome'], $token]);

                    error_log("Token criado para pedido $pedido_id: $token");
                }
                catch (Exception $e) {
                    error_log("Erro ao criar token: " . $e->getMessage());
                }
            }

            // 2) Enviar mensagem de conclusão
            $whatsapp_result = $whatsapp->sendStatusUpdate($pedido, 'finalizado');
            error_log("Resultado WhatsApp para pedido $pedido_id: " . json_encode($whatsapp_result));

            // 3) Enviar link de avaliação como mensagem separada (clicável)
            if (isset($token) && !empty($token)) {
                sleep(1); // Pequeno delay entre mensagens
                $rating_link = SITE_URL . '/avaliar_pedido.php?token=' . $token;
                $link_msg = "⭐ Avalie seu pedido:\n" . $rating_link;
                $whatsapp->sendMessage($telefone, $link_msg);
            }
        }
    }
    catch (Exception $e) {
        error_log("Erro geral WhatsApp: " . $e->getMessage());
        $whatsapp_result = ['success' => false, 'error' => $e->getMessage()];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Pedido finalizado e arquivado com sucesso!',
        'whatsapp_enviado' => $whatsapp_result['success'] ?? false,
        'whatsapp_detalhes' => $whatsapp_result
    ]);


}
catch (PDOException $e) {
    error_log('Erro no banco ao concluir pedido: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao finalizar pedido. Tente novamente.']);
}
?>
