<?php
// Script para verificar status de pagamentos do Mercado Pago
// Deve ser executado via CRON a cada minuto

// Ajustar caminho se necessário, dependendo de onde o cron é chamado
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mercadopago_helper.php';
require_once __DIR__ . '/../includes/whatsapp_helper.php';

$mpHelper = new MercadoPagoHelper($pdo);

if (!$mpHelper->isConfigured()) {
    die("Mercado Pago não configurado.\n");
}

// Função para substituir variáveis no template
function substituirVariaveis($template, $pedido) {
    $valor = number_format($pedido['valor_total'], 2, ',', '.');
    $data = date('d/m/Y H:i', strtotime($pedido['data_pedido'] ?? 'now'));
    
    $replacements = [
        '{nome}' => $pedido['cliente_nome'] ?? '',
        '{telefone}' => $pedido['cliente_telefone'] ?? '',
        '{codigo_pedido}' => $pedido['codigo_pedido'] ?? '',
        '{valor}' => $valor,
        '{data_pedido}' => $data
    ];
    
    return str_replace(array_keys($replacements), array_values($replacements), $template);
}

// Buscar template de pagamento recebido (ID 150)
$stmt_template = $pdo->prepare("SELECT mensagem, ativo FROM whatsapp_mensagens WHERE id = 150 LIMIT 1");
$stmt_template->execute();
$template_pagamento = $stmt_template->fetch(PDO::FETCH_ASSOC);

// Inicializar WhatsApp Helper
$whatsapp = new WhatsAppHelper($pdo);

// Buscar pedidos pendentes com pagamento online
// status = 'pendente' (ou vazio) AND pagamento_online = 1
$sql = "SELECT p.id as pedido_id, p.status as pedido_status, p.codigo_pedido, p.cliente_nome, 
               p.cliente_telefone, p.valor_total, p.data_pedido, mp.payment_id, mp.status as mp_status
        FROM pedidos p
        JOIN mercadopago_pagamentos mp ON p.id = mp.pedido_id
        WHERE p.pagamento_online = 1 
        AND (p.status = 'pendente' OR p.status = '')";

$stmt = $pdo->query($sql);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Verificando " . count($pedidos) . " pedidos pendentes...\n";

foreach ($pedidos as $pedido) {
    $payment_id = $pedido['payment_id'];
    $pedido_id = $pedido['pedido_id'];
    
    if (empty($payment_id)) continue;

    $payment_info = $mpHelper->getPaymentStatus($payment_id);

    if ($payment_info) {
        $novo_status_mp = $payment_info['status'];
        $status_mudou = ($novo_status_mp != $pedido['mp_status']);
        $precisa_aprovar = ($novo_status_mp == 'approved' && ($pedido['pedido_status'] == 'pendente' || $pedido['pedido_status'] == ''));
        
        echo "Pedido #$pedido_id (Payment $payment_id): Status MP Atual: $novo_status_mp | DB: {$pedido['mp_status']}\n";

        // Se status mudou ou se precisa aprovar o pedido (sincronizar)
        if ($status_mudou || $precisa_aprovar) {
            
            // Atualizar status MP se mudou
            if ($status_mudou) {
                $stmt_upd_mp = $pdo->prepare("UPDATE mercadopago_pagamentos SET status = ?, atualizado_em = NOW() WHERE payment_id = ?");
                $stmt_upd_mp->execute([$novo_status_mp, $payment_id]);
                echo "-> Status MP atualizado para $novo_status_mp\n";
            }

            // Se aprovado e pedido ainda pendente
            if ($precisa_aprovar) {
                // Status correto do ENUM: 'em_andamento' (não existe 'em_preparo')
                $stmt_upd_ped = $pdo->prepare("UPDATE pedidos SET status = 'em_andamento', pago = 1, atualizado_em = NOW() WHERE id = ?");
                $stmt_upd_ped->execute([$pedido_id]);
                
                // Enviar mensagem WhatsApp de pagamento recebido
                if ($whatsapp->isConfigured() && $template_pagamento && $template_pagamento['ativo']) {
                    $mensagem = substituirVariaveis($template_pagamento['mensagem'], $pedido);
                    $whatsapp->sendMessage($pedido['cliente_telefone'], $mensagem);
                    echo "-> Mensagem WhatsApp enviada para {$pedido['cliente_telefone']}\n";
                }
                
                echo "-> Pedido #$pedido_id APROVADO e movido para em_andamento.\n";
            }
            // Se cancelado ou rejeitado e status mudou
            elseif ($status_mudou && ($novo_status_mp == 'cancelled' || $novo_status_mp == 'rejected')) {
                // Cancelar pedido
                $stmt_upd_ped = $pdo->prepare("UPDATE pedidos SET status = 'cancelado', atualizado_em = NOW() WHERE id = ?");
                $stmt_upd_ped->execute([$pedido_id]);
                echo "-> Pedido #$pedido_id CANCELADO (Pagamento $novo_status_mp).\n";
            }
        }
    } else {
        echo "Erro ao consultar pagamento $payment_id\n";
    }
}

echo "Verificação concluída.\n";
?>