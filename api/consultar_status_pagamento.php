<?php
/**
 * API: Consultar Status de Pagamento PIX
 * Endpoint utilizado pelo front-end (polling) para verificar se o pagamento foi aprovado.
 * Suporta Mercado Pago e Asaas.
 */
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/functions.php';

$pedido_id = $_GET['pedido_id'] ?? 0;

if (!$pedido_id) {
    echo json_encode(['error' => 'ID do pedido não fornecido']);
    exit;
}

try {
    // Verificar se o pedido já foi pago (consulta rápida ao DB primeiro)
    $stmt_pedido = $pdo->prepare("SELECT id, status, pagamento_online, pago, cliente_id FROM pedidos WHERE id = ?");
    $stmt_pedido->execute([$pedido_id]);
    $pedido = $stmt_pedido->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        echo json_encode(['status' => 'nao_encontrado']);
        exit;
    }

    // Se pedido já está pago ou já saiu de pendente, retornar imediatamente
    if ($pedido['pago'] || !in_array($pedido['status'], ['pendente', ''])) {
        $status_final = 'pendente';
        if ($pedido['pago'] || $pedido['status'] === 'em_andamento')
            $status_final = 'aprovado';
        elseif ($pedido['status'] === 'cancelado')
            $status_final = 'cancelado';

        echo json_encode(['status' => $status_final]);
        exit;
    }

    // --- Pedido ainda pendente, consultar gateway ---
    $status_final = 'pendente';
    $gateway_consultado = false;

    // 1. Tentar Mercado Pago
    $stmt_mp = $pdo->prepare("SELECT payment_id, status FROM mercadopago_pagamentos WHERE pedido_id = ? ORDER BY id DESC LIMIT 1");
    $stmt_mp->execute([$pedido_id]);
    $pagamento_mp = $stmt_mp->fetch(PDO::FETCH_ASSOC);

    if ($pagamento_mp && !empty($pagamento_mp['payment_id'])) {
        $gateway_consultado = true;

        // Se já está aprovado no banco local
        if ($pagamento_mp['status'] === 'approved') {
            $status_final = 'aprovado';
        }
        else {
            // Consultar API do Mercado Pago em tempo real
            require_once '../includes/mercadopago_helper.php';
            $mpHelper = new MercadoPagoHelper($pdo);
            $dados_mp = $mpHelper->getPaymentStatus($pagamento_mp['payment_id']);

            if ($dados_mp && isset($dados_mp['status'])) {
                $novo_status = $dados_mp['status'];

                // Se status mudou, atualizar banco
                if ($novo_status !== $pagamento_mp['status']) {
                    $stmt_upd = $pdo->prepare("UPDATE mercadopago_pagamentos SET status = ?, atualizado_em = NOW() WHERE payment_id = ?");
                    $stmt_upd->execute([$novo_status, $pagamento_mp['payment_id']]);
                }

                if ($novo_status === 'approved') {
                    $status_final = 'aprovado';
                }
            }
        }
    }

    // 2. Tentar Asaas (se não encontrou no Mercado Pago)
    if (!$gateway_consultado) {
        $stmt_asaas = $pdo->prepare("SELECT payment_id, status FROM asaas_pagamentos WHERE pedido_id = ? ORDER BY id DESC LIMIT 1");
        $stmt_asaas->execute([$pedido_id]);
        $pagamento_asaas = $stmt_asaas->fetch(PDO::FETCH_ASSOC);

        if ($pagamento_asaas && !empty($pagamento_asaas['payment_id'])) {
            $gateway_consultado = true;

            if (in_array($pagamento_asaas['status'], ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'])) {
                $status_final = 'aprovado';
            }
            else {
                // Consultar API do Asaas em tempo real
                require_once '../includes/asaas_helper.php';
                $asaasHelper = new AsaasHelper($pdo);
                $dados_asaas = $asaasHelper->getPaymentStatus($pagamento_asaas['payment_id']);

                if ($dados_asaas && isset($dados_asaas['status'])) {
                    $novo_status = $dados_asaas['status'];

                    // Se status mudou, atualizar banco
                    if ($novo_status !== $pagamento_asaas['status']) {
                        $stmt_upd = $pdo->prepare("UPDATE asaas_pagamentos SET status = ?, atualizado_em = NOW() WHERE payment_id = ?");
                        $stmt_upd->execute([$novo_status, $pagamento_asaas['payment_id']]);
                    }

                    if (in_array($novo_status, ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'])) {
                        $status_final = 'aprovado';
                    }
                }
            }
        }
    }

    // 3. Se pagamento foi aprovado, ATUALIZAR O PEDIDO
    if ($status_final === 'aprovado' && ($pedido['status'] === 'pendente' || $pedido['status'] === '')) {
        // Atualizar pedido para pago + em_andamento
        $stmt_upd_ped = $pdo->prepare("UPDATE pedidos SET status = 'em_andamento', pago = 1, em_preparo = 1, atualizado_em = NOW() WHERE id = ? AND (status = 'pendente' OR status = '')");
        $stmt_upd_ped->execute([$pedido_id]);

        // Sincronizar lane do Kanban
        if (function_exists('sync_pedido_lane_by_status')) {
            try {
                sync_pedido_lane_by_status($pedido_id, 'em_andamento');
            }
            catch (Exception $e) {
                error_log("Erro ao sincronizar Kanban no polling: " . $e->getMessage());
            }
        }

        // Adicionar ponto de fidelidade
        if (!empty($pedido['cliente_id']) && function_exists('adicionar_ponto_fidelidade')) {
            try {
                adicionar_ponto_fidelidade((int)$pedido['cliente_id'], $pedido_id);
            }
            catch (Exception $e) {
                error_log("Erro ao adicionar fidelidade no polling: " . $e->getMessage());
            }
        }

        // Enviar WhatsApp de pagamento recebido
        try {
            require_once '../includes/whatsapp_helper.php';
            $whatsapp = new WhatsAppHelper($pdo);

            if ($whatsapp->isConfigured()) {
                // Buscar dados atualizados do pedido
                $stmt_ped_full = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
                $stmt_ped_full->execute([$pedido_id]);
                $pedido_full = $stmt_ped_full->fetch(PDO::FETCH_ASSOC);

                if ($pedido_full) {
                    $whatsapp->sendStatusUpdate($pedido_full, 'em_andamento');
                }
            }
        }
        catch (Exception $e) {
            error_log("Erro ao enviar WhatsApp no polling: " . $e->getMessage());
        }

        error_log("✅ Polling: Pedido #{$pedido_id} APROVADO e atualizado para em_andamento");
    }

    echo json_encode(['status' => $status_final]);

}
catch (Exception $e) {
    error_log("Erro no polling de pagamento: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>
