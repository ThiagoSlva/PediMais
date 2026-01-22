<?php
/**
 * Script para confirmar manualmente um pagamento Asaas (para testes)
 * USO: php confirmar_pagamento_manual.php <pedido_id>
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/whatsapp_helper.php';

if (!isset($argv[1])) {
    echo "Uso: php confirmar_pagamento_manual.php <pedido_id>\n";
    echo "Exemplo: php confirmar_pagamento_manual.php 17\n";
    exit(1);
}

$pedido_id = (int)$argv[1];

echo "Confirmando pagamento do pedido #$pedido_id...\n";

try {
    // Verificar se pedido existe
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pedido) {
        echo "❌ Pedido não encontrado!\n";
        exit(1);
    }
    
    echo "Pedido: #{$pedido['codigo_pedido']} - {$pedido['cliente_nome']}\n";
    echo "Status atual: {$pedido['status']}, Pago: " . ($pedido['pago'] ? 'Sim' : 'Não') . "\n";
    
    // Atualizar pagamento Asaas (se existir)
    $stmt = $pdo->prepare("UPDATE asaas_pagamentos SET status = 'approved', pago_em = NOW() WHERE pedido_id = ?");
    $stmt->execute([$pedido_id]);
    echo "✅ Status do pagamento Asaas atualizado\n";
    
    // Atualizar pedido
    $stmt = $pdo->prepare("UPDATE pedidos SET pago = 1, status = 'em_andamento', em_preparo = 1 WHERE id = ?");
    $stmt->execute([$pedido_id]);
    echo "✅ Pedido marcado como PAGO e EM_PREPARO\n";
    
    // Sincronizar Kanban (se função existir)
    try {
        if (function_exists('sync_pedido_lane_by_status')) {
            sync_pedido_lane_by_status($pedido_id, 'em_andamento');
            echo "✅ Kanban sincronizado\n";
        }
    } catch (Exception $e) {
        echo "⚠️ Kanban: " . $e->getMessage() . "\n";
    }
    
    // Adicionar ponto de fidelidade (se função existir)
    if (!empty($pedido['cliente_id']) && function_exists('adicionar_ponto_fidelidade')) {
        adicionar_ponto_fidelidade((int)$pedido['cliente_id'], $pedido_id);
        echo "✅ Ponto de fidelidade adicionado\n";
    }
    
    // Enviar WhatsApp de confirmação
    $whatsapp = new WhatsAppHelper($pdo);
    $whatsapp_config = $pdo->query("SELECT * FROM whatsapp_config WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if ($whatsapp_config && $whatsapp_config['ativo']) {
        // Buscar template de pagamento recebido (ID 150)
        $stmt = $pdo->prepare("SELECT mensagem, ativo FROM whatsapp_mensagens WHERE id = 150 LIMIT 1");
        $stmt->execute();
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($template && $template['ativo']) {
            $valor_formatado = number_format($pedido['valor_total'], 2, ',', '.');
            $mensagem = str_replace(
                ['{nome}', '{codigo_pedido}', '{valor}'],
                [$pedido['cliente_nome'], $pedido['codigo_pedido'], $valor_formatado],
                $template['mensagem']
            );
            
            $telefone = $pedido['cliente_telefone'];
            if (!str_starts_with($telefone, '55')) {
                $telefone = '55' . preg_replace('/\D/', '', $telefone);
            }
            
            $resultado = $whatsapp->sendMessage($telefone, $mensagem);
            if ($resultado['success']) {
                echo "✅ WhatsApp de confirmação enviado para $telefone\n";
            } else {
                echo "⚠️ Erro ao enviar WhatsApp: " . ($resultado['error'] ?? 'Desconhecido') . "\n";
            }
        }
    }
    
    echo "\n🎉 Pagamento do pedido #$pedido_id confirmado com sucesso!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
?>
