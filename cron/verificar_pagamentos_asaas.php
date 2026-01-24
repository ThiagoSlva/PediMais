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
    echo "  -> Valor: R$ $valor, Data criação: $data_criacao\n";

    // Tentar encontrar pagamento pelo ID ou por valor/data
    $payment_info = $asaasHelper->getPaymentStatus($payment_id);
    
    echo "  -> Resultado getPaymentStatus: " . json_encode($payment_info) . "\n";
    
    if (!$payment_info || $payment_info['status'] !== 'approved') {
        // Fallback: buscar por valor e data
        echo "  -> Tentando fallback por valor/data...\n";
        $payment_info = $asaasHelper->findPaymentByValueAndDate($valor, $data_criacao);
        echo "  -> Resultado findPaymentByValueAndDate: " . json_encode($payment_info) . "\n";
    }

    if ($payment_info && $payment_info['status'] === 'approved') {
        echo "-> Pagamento APROVADO! Atualizando pedido...\n";
        
        // Atualizar status Asaas
        $stmt_upd_asaas = $pdo->prepare("UPDATE asaas_pagamentos SET status = 'approved', pago_em = NOW(), atualizado_em = NOW() WHERE pedido_id = ?");
        $stmt_upd_asaas->execute([$pedido_id]);
        
        // Atualizar pedido
        $stmt_upd_ped = $pdo->prepare("UPDATE pedidos SET status = 'em_andamento', pago = 1, em_preparo = 1, atualizado_em = NOW() WHERE id = ?");
        $stmt_upd_ped->execute([$pedido_id]);
        
        // Sincronizar Kanban (se função existir)
        try {
            if (function_exists('sync_pedido_lane_by_status')) {
                sync_pedido_lane_by_status($pedido_id, 'em_andamento');
                echo "-> Kanban sincronizado\n";
            }
        } catch (Exception $e) {
            echo "-> Erro Kanban: " . $e->getMessage() . "\n";
        }
        
        // Enviar mensagem WhatsApp
        echo "-> Verificando WhatsApp...\n";
        echo "   whatsapp.isConfigured: " . ($whatsapp->isConfigured() ? 'SIM' : 'NAO') . "\n";
        echo "   template_pagamento: " . ($template_pagamento ? 'EXISTE' : 'NAO EXISTE') . "\n";
        echo "   template_ativo: " . (isset($template_pagamento['ativo']) && $template_pagamento['ativo'] ? 'SIM' : 'NAO') . "\n";
        
        if ($whatsapp->isConfigured() && $template_pagamento && $template_pagamento['ativo']) {
            $valor_formatado = number_format($pedido['valor_total'], 2, ',', '.');
            $mensagem = str_replace(
                ['{nome}', '{codigo_pedido}', '{valor}'],
                [$pedido['cliente_nome'], $pedido['codigo_pedido'], $valor_formatado],
                $template_pagamento['mensagem']
            );
            
            $telefone = $pedido['cliente_telefone'];
            // Limpar telefone (remover caracteres não numéricos)
            $telefone = preg_replace('/\D/', '', $telefone);
            if (!str_starts_with($telefone, '55')) {
                $telefone = '55' . $telefone;
            }
            
            echo "   Telefone: $telefone\n";
            echo "   Mensagem: " . substr($mensagem, 0, 50) . "...\n";
            
            $resultado = $whatsapp->sendMessage($telefone, $mensagem);
            
            if (isset($resultado['success']) && $resultado['success']) {
                echo "-> ✅ Mensagem WhatsApp enviada para {$telefone}\n";
            } else {
                echo "-> ❌ Erro ao enviar WhatsApp: " . ($resultado['error'] ?? json_encode($resultado)) . "\n";
            }
        } else {
            echo "-> ⚠️ WhatsApp não configurado ou template desativado\n";
        }
        
        echo "-> Pedido #$pedido_id APROVADO e movido para em_andamento.\n";
    } else {
        echo "-> Status: pendente (aguardando pagamento)\n";
    }
}

echo "Verificação concluída.\n";
?>
