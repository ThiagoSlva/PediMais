<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../includes/auth.php';

// verificar_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$pedido_id = $input['pedido_id'] ?? null;
$lane_id = $input['lane_id'] ?? null;

if (!$pedido_id || !$lane_id) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

try {
    // Buscar status atual antes de atualizar (para comparar se mudou)
    $stmtOld = $pdo->prepare("SELECT status FROM pedidos WHERE id = ?");
    $stmtOld->execute([$pedido_id]);
    $old_status = $stmtOld->fetchColumn();

    // Update Lane
    $stmt = $pdo->prepare("UPDATE pedidos SET lane_id = ? WHERE id = ?");
    $stmt->execute([$lane_id, $pedido_id]);

    // Get Lane info including action
    $stmtLane = $pdo->prepare("SELECT nome, acao FROM kanban_lanes WHERE id = ?");
    $stmtLane->execute([$lane_id]);
    $laneData = $stmtLane->fetch(PDO::FETCH_ASSOC);
    $laneName = strtolower($laneData['nome'] ?? '');
    $laneAcao = $laneData['acao'] ?? null;

    // Mapear lane para status e flags - PRIORIZAR ACAO SE DEFINIDA
    $status = 'pendente';
    $em_preparo = 0;
    $saiu_entrega = 0;
    $entregue = 0;
    $pago = null; // null = não alterar
    $executar_finalizacao = false;
    
    // Se a lane tem uma ação configurada, usar ela
    if (!empty($laneAcao)) {
        switch ($laneAcao) {
            case 'em_preparo':
                $status = 'em_andamento';
                $em_preparo = 1;
                break;
            case 'pronto':
                $status = 'pronto';
                $em_preparo = 1;
                break;
            case 'saiu_entrega':
                $status = 'saiu_entrega';
                $em_preparo = 1;
                $saiu_entrega = 1;
                break;
            case 'entregue':
                $status = 'concluido';
                $em_preparo = 1;
                $saiu_entrega = 1;
                $entregue = 1;
                break;
            case 'finalizar':
                $status = 'finalizado';
                $em_preparo = 1;
                $saiu_entrega = 1;
                $entregue = 1;
                $executar_finalizacao = true;
                break;
            case 'cancelar':
                $status = 'cancelado';
                break;
            default:
                // Ação desconhecida, manter pendente
                break;
        }
    } else {
        // Fallback: mapear por nome da lane (comportamento antigo)
        if (strpos($laneName, 'entregue') !== false || strpos($laneName, 'finalizado') !== false || strpos($laneName, 'concluido') !== false) {
            $status = 'concluido';
            $entregue = 1;
            $saiu_entrega = 1;
            $em_preparo = 1;
        } elseif (strpos($laneName, 'saiu') !== false || strpos($laneName, 'entrega') !== false) {
            $status = 'saiu_entrega';
            $saiu_entrega = 1;
            $em_preparo = 1;
        } elseif (strpos($laneName, 'pronto') !== false) {
            $status = 'pronto';
            $em_preparo = 1;
        } elseif (strpos($laneName, 'preparo') !== false) {
            $status = 'em_andamento';
            $em_preparo = 1;
        } elseif (strpos($laneName, 'novo') !== false || strpos($laneName, 'recebido') !== false) {
            $status = 'pendente';
            $em_preparo = 0;
        } elseif (strpos($laneName, 'cancelado') !== false) {
            $status = 'cancelado';
        }
    }

    // Atualizar status e flags
    if ($executar_finalizacao) {
        // Verificar se colunas de arquivamento existem
        $stmt = $pdo->query("SHOW COLUMNS FROM pedidos LIKE 'arquivado'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE pedidos ADD COLUMN arquivado TINYINT(1) DEFAULT 0");
            $pdo->exec("ALTER TABLE pedidos ADD COLUMN data_conclusao DATETIME NULL");
        }
        
        $sql = "UPDATE pedidos SET status = ?, em_preparo = ?, saiu_entrega = ?, entregue = ?, arquivado = 1, data_conclusao = NOW(), atualizado_em = NOW() WHERE id = ?";
        $pdo->prepare($sql)->execute([$status, $em_preparo, $saiu_entrega, $entregue, $pedido_id]);
    } else {
        $sql = "UPDATE pedidos SET status = ?, em_preparo = ?, saiu_entrega = ?, entregue = ?, atualizado_em = NOW() WHERE id = ?";
        $pdo->prepare($sql)->execute([$status, $em_preparo, $saiu_entrega, $entregue, $pedido_id]);
    }

    // Enviar notificação WhatsApp se o status mudou
    $whatsapp_result = ['success' => false, 'error' => 'Status não mudou'];
    if ($status !== $old_status) {
        try {
            require_once '../../includes/whatsapp_helper.php';
            $whatsapp = new WhatsAppHelper($pdo);
            
            // Buscar dados do pedido para notificação
            $stmt_pedido = $pdo->prepare("SELECT codigo_pedido, cliente_nome, cliente_telefone, valor_total, tipo_entrega FROM pedidos WHERE id = ?");
            $stmt_pedido->execute([$pedido_id]);
            $pedido = $stmt_pedido->fetch(PDO::FETCH_ASSOC);
            
            if ($pedido && !empty($pedido['cliente_telefone'])) {
                $whatsapp_result = $whatsapp->sendStatusUpdate($pedido, $status);
            } else {
                $whatsapp_result = ['success' => false, 'error' => 'Pedido sem telefone'];
            }
        } catch (Exception $e) {
            $whatsapp_result = ['success' => false, 'error' => 'Exceção: ' . $e->getMessage()];
        }
    }

    echo json_encode([
        'success' => true, 
        'status_updated' => $status,
        'whatsapp_enviado' => $whatsapp_result['success'] ?? false,
        'whatsapp_detalhes' => $whatsapp_result
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
