<?php
/**
 * Webhook do Mercado Pago
 * Recebe notificações de pagamentos aprovados/rejeitados
 */

declare(strict_types = 1)
;
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mercadopago_helper.php';
require_once __DIR__ . '/../includes/whatsapp_helper.php';

global $pdo;

// Log de recebimento
error_log("=====================================");
error_log("🔔 WEBHOOK MERCADO PAGO - " . date('Y-m-d H:i:s'));
error_log("=====================================");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconhecido'));

// Pegar dados do POST
$input = file_get_contents('php://input');
error_log("📦 Body recebido (" . strlen($input) . " bytes)");

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

// Mercado Pago envia notificações do tipo "payment"
$type = $data['type'] ?? '';
$action = $data['action'] ?? '';

error_log("🔵 Type: {$type}, Action: {$action}");

if ($type === 'payment' && in_array($action, ['payment.created', 'payment.updated'])) {
    $payment_id = $data['data']['id'] ?? null;

    if (!$payment_id) {
        error_log("❌ Payment ID não encontrado");
        http_response_code(400);
        echo json_encode(['error' => 'Payment ID não encontrado']);
        exit;
    }

    error_log("💳 Payment ID: {$payment_id}");

    // Consultar pagamento no Mercado Pago
    $mp = new MercadoPagoHelper($pdo);
    $result = $mp->getPaymentStatus($payment_id);

    if (!$result) {
        error_log("❌ Erro ao consultar pagamento: Falha na comunicação com API");
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao consultar pagamento']);
        exit;
    }

    // O helper retorna array com status se sucesso, ou null se falha
    // O formato retornado pelo helper é direto o body da resposta do MP
    $status = $result['status'] ?? 'unknown'; # Helper retorna o array do MP direto

    error_log("📊 Status do pagamento: {$status}");

    // Buscar pagamento no banco
    try {
        $stmt = $pdo->prepare("SELECT * FROM mercadopago_pagamentos WHERE payment_id = :payment_id");
        $stmt->execute([':payment_id' => $payment_id]);
        $pagamento = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pagamento) {
            error_log("⚠️ Pagamento não encontrado no banco");
            http_response_code(404);
            echo json_encode(['error' => 'Pagamento não encontrado']);
            exit;
        }

        $pedido_id = $pagamento['pedido_id'];
        error_log("🛒 Pedido ID: {$pedido_id}");

        // Atualizar status do pagamento
        $stmt = $pdo->prepare("
            UPDATE mercadopago_pagamentos 
            SET status = :status,
                pago_em = :pago_em,
                atualizado_em = NOW()
            WHERE payment_id = :payment_id
        ");

        $stmt->execute([
            ':status' => $status,
            ':pago_em' => $status === 'approved' ? date('Y-m-d H:i:s') : null,
            ':payment_id' => $payment_id
        ]);

        error_log("✅ Status do pagamento atualizado no banco");

        // Se foi APROVADO, processar pedido
        if ($status === 'approved') {
            error_log("🎉 PAGAMENTO APROVADO! Processando pedido...");

            // Buscar pedido antes de atualizar
            $stmt_pedido_antigo = $pdo->prepare("SELECT cliente_id, status FROM pedidos WHERE id = :id");
            $stmt_pedido_antigo->execute([':id' => $pedido_id]);
            $pedido_antigo = $stmt_pedido_antigo->fetch(PDO::FETCH_ASSOC);

            // 1. Atualizar pedido para PAGO e EM_PREPARO
            // Status correto do ENUM: 'em_andamento'
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
                if (function_exists('adicionar_ponto_fidelidade')) {
                    adicionar_ponto_fidelidade((int)$pedido_antigo['cliente_id'], $pedido_id);
                }
            }

            // 2. Sincronizar com Kanban (mover para lane "Em Preparo")
            try {
                require_once __DIR__ . '/../includes/functions.php';
                if (function_exists('sync_pedido_lane_by_status')) {
                    sync_pedido_lane_by_status($pedido_id, 'em_andamento');
                    error_log("✅ Kanban sincronizado");
                }
            }
            catch (Exception $e) {
                error_log("⚠️ Erro ao sincronizar Kanban: " . $e->getMessage());
            }

            // 3. Buscar dados do pedido
            $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = :id");
            $stmt->execute([':id' => $pedido_id]);
            $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

            // 4. Enviar mensagem WhatsApp "Pagamento Recebido"
            try {
                error_log("🔍 WEBHOOK: Verificando envio WhatsApp...");
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
                        // Limpar telefone
                        $telefone = preg_replace('/\D/', '', $telefone);
                        if ($telefone && !str_starts_with($telefone, '55')) {
                            $telefone = '55' . $telefone;
                        }

                        if ($telefone) {
                            error_log("📤 WEBHOOK: Enviando mensagem...");
                            $whatsapp = new WhatsAppHelper($pdo);
                            $resultado = $whatsapp->sendMessage($telefone, $mensagem);

                            error_log("📡 WEBHOOK: Resultado: " . json_encode($resultado));

                            if ($resultado['success']) {
                                error_log("✅ WEBHOOK: WhatsApp enviado com sucesso!");
                            }
                            else {
                                error_log("❌ WEBHOOK: Erro ao enviar: " . ($resultado['error'] ?? 'Desconhecido'));
                            }
                        }
                    }
                }
            }
            catch (Exception $e) {
                error_log("⚠️ WEBHOOK: Erro ao enviar WhatsApp: " . $e->getMessage());
            }

            error_log("🎉 PROCESSAMENTO COMPLETO! Pedido #{$pedido_id} está na cozinha!");
        }
        else {
            error_log("⏳ Status: {$status} - Aguardando aprovação");
        }

        http_response_code(200);
        echo json_encode(['success' => true, 'status' => $status]);

    }
    catch (Exception $e) {
        error_log("❌ Erro no webhook: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode(['error' => 'Erro interno do servidor']);
    }
}
else {
    error_log("⚠️ Tipo de notificação ignorado: {$type}");
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Notificação ignorada']);
}

error_log("=====================================");
error_log("🏁 FIM DO WEBHOOK");
error_log("=====================================");
