<?php
/**
 * Webhook do Asaas
 * Recebe notificações de pagamentos PIX
 */

declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/asaas_helper.php';
require_once __DIR__ . '/../includes/whatsapp_helper.php';

global $pdo;

// Log de recebimento
error_log("=====================================");
error_log("🔔 WEBHOOK ASAAS - " . date('Y-m-d H:i:s'));
error_log("=====================================");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconhecido'));

// Pegar dados do POST
$input = file_get_contents('php://input');
error_log("📦 Body recebido (" . strlen($input) . " bytes)");
error_log("📦 Body: " . $input);

$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("❌ Erro ao decodificar JSON: " . json_last_error_msg());
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido']);
    exit;
}

if (!$data) {
    error_log("❌ Webhook: Dados inválidos");
    http_response_code(400);
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

error_log("✅ Webhook Data: " . json_encode($data, JSON_PRETTY_PRINT));

// Asaas envia eventos com campo "event"
$event = $data['event'] ?? '';
$payment = $data['payment'] ?? null;

error_log("🔵 Event: {$event}");

// Eventos de pagamento PIX
if (in_array($event, ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED'])) {
    
    if (!$payment) {
        error_log("❌ Dados de pagamento não encontrados");
        http_response_code(400);
        echo json_encode(['error' => 'Payment data não encontrado']);
        exit;
    }

    $valor = $payment['value'] ?? 0;
    $status = strtolower($payment['status'] ?? '');
    $asaas_payment_id = $payment['id'] ?? '';
    
    error_log("💳 Payment ID: {$asaas_payment_id}, Valor: {$valor}, Status: {$status}");
    
    try {
        // Buscar pagamento no banco por valor (PIX estático não tem referência direta)
        // Primeiro tentamos pelo payment_id se existir
        $stmt = $pdo->prepare("
            SELECT ap.*, p.id as pedido_id, p.cliente_id, p.status as pedido_status, p.cliente_telefone, p.cliente_nome
            FROM asaas_pagamentos ap
            JOIN pedidos p ON ap.pedido_id = p.id
            WHERE ap.status = 'pending'
            AND ap.valor = :valor
            ORDER BY ap.criado_em DESC
            LIMIT 1
        ");
        $stmt->execute([':valor' => $valor]);
        $pagamento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pagamento) {
            error_log("⚠️ Pagamento não encontrado no banco para valor: {$valor}");
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Pagamento não encontrado']);
            exit;
        }
        
        $pedido_id = $pagamento['pedido_id'];
        error_log("🛒 Pedido ID: {$pedido_id}");
        
        // Atualizar status do pagamento Asaas
        $stmt = $pdo->prepare("
            UPDATE asaas_pagamentos 
            SET status = 'approved',
                payment_id = :payment_id,
                pago_em = NOW(),
                atualizado_em = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':payment_id' => $asaas_payment_id,
            ':id' => $pagamento['id']
        ]);
        
        error_log("✅ Status do pagamento Asaas atualizado no banco");
        
        // Buscar pedido antes de atualizar
        $stmt_pedido_antigo = $pdo->prepare("SELECT cliente_id, status FROM pedidos WHERE id = :id");
        $stmt_pedido_antigo->execute([':id' => $pedido_id]);
        $pedido_antigo = $stmt_pedido_antigo->fetch(PDO::FETCH_ASSOC);
        
        // Atualizar pedido para PAGO e EM_PREPARO
        $stmt = $pdo->prepare("
            UPDATE pedidos 
            SET pago = 1,
                status = 'em_andamento',
                em_preparo = 1
            WHERE id = :id
        ");
        $stmt->execute([':id' => $pedido_id]);
        error_log("✅ Pedido marcado como PAGO e EM_PREPARO");
        
        // Adicionar ponto de fidelidade se mudou para em_andamento
        if ($pedido_antigo && !empty($pedido_antigo['cliente_id']) && $pedido_antigo['status'] !== 'em_andamento') {
            require_once __DIR__ . '/../includes/functions.php';
            adicionar_ponto_fidelidade((int)$pedido_antigo['cliente_id'], $pedido_id);
        }
        
        // Sincronizar com Kanban
        try {
            require_once __DIR__ . '/../includes/functions.php';
            sync_pedido_lane_by_status($pedido_id, 'em_andamento');
            error_log("✅ Kanban sincronizado");
        } catch (Exception $e) {
            error_log("⚠️ Erro ao sincronizar Kanban: " . $e->getMessage());
        }
        
        // Buscar dados do pedido para WhatsApp
        $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = :id");
        $stmt->execute([':id' => $pedido_id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Enviar mensagem WhatsApp "Pagamento Recebido"
        try {
            error_log("🔍 WEBHOOK ASAAS: Verificando envio WhatsApp...");
            $whatsapp_config = $pdo->query("SELECT * FROM whatsapp_config WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            
            $usar_mp_whatsapp = isset($whatsapp_config['usar_mercadopago']) ? $whatsapp_config['usar_mercadopago'] : 1;
            
            if ($whatsapp_config && $whatsapp_config['ativo'] && $usar_mp_whatsapp) {
                // Buscar template de pagamento recebido (ID 150)
                $stmt_template = $pdo->prepare("SELECT mensagem, ativo FROM whatsapp_mensagens WHERE id = 150 LIMIT 1");
                $stmt_template->execute();
                $template = $stmt_template->fetch(PDO::FETCH_ASSOC);
                
                if ($template && $template['ativo']) {
                    // Substituir variáveis
                    $valor_formatado = number_format($pedido['valor_total'], 2, ',', '.');
                    $mensagem = str_replace(
                        ['{nome}', '{codigo_pedido}', '{valor}'],
                        [$pedido['cliente_nome'], $pedido['codigo_pedido'], $valor_formatado],
                        $template['mensagem']
                    );
                    
                    $telefone = $pedido['cliente_telefone'];
                    if ($telefone && !str_starts_with($telefone, '55')) {
                        $telefone = '55' . $telefone;
                    }
                    
                    if ($telefone) {
                        $whatsapp = new WhatsAppHelper($pdo);
                        $resultado = $whatsapp->sendMessage($telefone, $mensagem);
                        
                        if ($resultado['success']) {
                            error_log("✅ WEBHOOK ASAAS: WhatsApp enviado com sucesso!");
                        } else {
                            error_log("❌ WEBHOOK ASAAS: Erro ao enviar WhatsApp: " . ($resultado['error'] ?? 'Desconhecido'));
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("⚠️ WEBHOOK ASAAS: Erro ao enviar WhatsApp: " . $e->getMessage());
        }
        
        error_log("🎉 PROCESSAMENTO COMPLETO! Pedido #{$pedido_id} está na cozinha!");
        
        http_response_code(200);
        echo json_encode(['success' => true, 'pedido_id' => $pedido_id]);
        
    } catch (Exception $e) {
        error_log("❌ Erro no webhook Asaas: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    
} else {
    error_log("⚠️ Evento ignorado: {$event}");
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Evento ignorado']);
}

error_log("=====================================");
error_log("🏁 FIM DO WEBHOOK ASAAS");
error_log("=====================================");
?>
