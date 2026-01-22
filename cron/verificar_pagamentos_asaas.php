<?php
/**
 * Script para verificar status de pagamentos do Asaas
 * Deve ser executado via CRON a cada minuto
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/asaas_helper.php';
require_once __DIR__ . '/../includes/whatsapp_helper.php';

echo "Verificando pagamentos Asaas...\n";

$asaasHelper = new AsaasHelper($pdo);

if (!$asaasHelper->isConfigured()) {
    die("Asaas não configurado.\n");
}

// Buscar template de pagamento recebido (ID 150)
$stmt_template = $pdo->prepare("SELECT mensagem, ativo FROM whatsapp_mensagens WHERE id = 150 LIMIT 1");
$stmt_template->execute();
$template_pagamento = $stmt_template->fetch(PDO::FETCH_ASSOC);

// Inicializar WhatsApp Helper
$whatsapp = new WhatsAppHelper($pdo);

// Buscar pedidos pendentes com pagamento Asaas
$sql = "SELECT p.id as pedido_id, p.status as pedido_status, p.codigo_pedido, p.cliente_nome, 
               p.cliente_telefone, p.valor_total, p.data_pedido, ap.payment_id, ap.status as asaas_status,
               ap.criado_em as pagamento_criado_em
        FROM pedidos p
        JOIN asaas_pagamentos ap ON p.id = ap.pedido_id
        WHERE p.pagamento_online = 1 
        AND (p.status = 'pendente' OR p.status = '')
        AND ap.status = 'pending'";

try {
    $stmt = $pdo->query($sql);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro ao buscar pedidos: " . $e->getMessage() . "\n");
}

echo "Verificando " . count($pedidos) . " pedidos pendentes com Asaas...\n";

foreach ($pedidos as $pedido) {
    $payment_id = $pedido['payment_id'];
    $pedido_id = $pedido['pedido_id'];
    $valor = $pedido['valor_total'];
    $data_criacao = $pedido['pagamento_criado_em'];
    
    if (empty($payment_id)) continue;

    echo "Pedido #$pedido_id (Payment $payment_id): Verificando...\n";

    // Tentar encontrar pagamento pelo ID ou por valor/data
    $payment_info = $asaasHelper->getPaymentStatus($payment_id);
    
    if (!$payment_info) {
        // Fallback: buscar por valor e data
        $payment_info = $asaasHelper->findPaymentByValueAndDate($valor, $data_criacao);
    }

    if ($payment_info && $payment_info['status'] === 'approved') {
        echo "-> Pagamento APROVADO! Atualizando pedido...\n";
        
        // Atualizar status Asaas
        $stmt_upd_asaas = $pdo->prepare("UPDATE asaas_pagamentos SET status = 'approved', pago_em = NOW(), atualizado_em = NOW() WHERE pedido_id = ?");
        $stmt_upd_asaas->execute([$pedido_id]);
        
        // Atualizar pedido
        $stmt_upd_ped = $pdo->prepare("UPDATE pedidos SET status = 'em_andamento', pago = 1, em_preparo = 1, atualizado_em = NOW() WHERE id = ?");
        $stmt_upd_ped->execute([$pedido_id]);
        
        // Sincronizar Kanban
        try {
            sync_pedido_lane_by_status($pedido_id, 'em_andamento');
        } catch (Exception $e) {
            error_log("Erro ao sincronizar Kanban: " . $e->getMessage());
        }
        
        // Enviar mensagem WhatsApp
        if ($whatsapp->isConfigured() && $template_pagamento && $template_pagamento['ativo']) {
            $valor_formatado = number_format($pedido['valor_total'], 2, ',', '.');
            $mensagem = str_replace(
                ['{nome}', '{codigo_pedido}', '{valor}'],
                [$pedido['cliente_nome'], $pedido['codigo_pedido'], $valor_formatado],
                $template_pagamento['mensagem']
            );
            
            $telefone = $pedido['cliente_telefone'];
            if (!str_starts_with($telefone, '55')) {
                $telefone = '55' . $telefone;
            }
            
            $whatsapp->sendMessage($telefone, $mensagem);
            echo "-> Mensagem WhatsApp enviada para {$telefone}\n";
        }
        
        echo "-> Pedido #$pedido_id APROVADO e movido para em_andamento.\n";
    } else {
        echo "-> Status: pendente (aguardando pagamento)\n";
    }
}

echo "Verificação concluída.\n";
?>
