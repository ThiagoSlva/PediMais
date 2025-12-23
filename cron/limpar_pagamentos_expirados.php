<?php
// Script para limpar pagamentos expirados do Mercado Pago
// Deve ser executado via CRON a cada minuto

// Ajustar caminho se necessário
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mercadopago_helper.php';
require_once __DIR__ . '/../includes/whatsapp_helper.php';

echo "Iniciando limpeza de pagamentos expirados...\n";
echo "DB Name: " . DB_NAME . "\n";

$mpHelper = new MercadoPagoHelper($pdo);
$whatsapp = new WhatsAppHelper($pdo);

// Buscar pedidos pendentes (ou status vazio) com pagamento online expirado
// E que NÃO estejam aprovados
$sql = "SELECT p.id as pedido_id, p.codigo_pedido, p.cliente_nome, p.cliente_telefone, 
               p.valor_total, p.data_pedido, p.tipo_entrega,
               mp.payment_id, mp.status as mp_status, mp.expiracao
        FROM pedidos p
        JOIN mercadopago_pagamentos mp ON p.id = mp.pedido_id
        WHERE p.pagamento_online = 1 
        AND (p.status = 'pendente' OR p.status = '')
        AND mp.status != 'approved'
        AND mp.status != 'cancelled'
        AND mp.expiracao < NOW()";

try {
    $stmt = $pdo->query($sql);
    $expirados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Encontrados " . count($expirados) . " pagamentos expirados.\n";

    foreach ($expirados as $pedido) {
        $pedido_id = $pedido['pedido_id'];
        $payment_id = $pedido['payment_id'];
        $codigo = $pedido['codigo_pedido'];
        
        echo "Processando Pedido #{$pedido_id} (Payment {$payment_id})...\n";

        // 1. Atualizar status no Mercado Pago (Local)
        $stmt_mp = $pdo->prepare("UPDATE mercadopago_pagamentos SET status = 'cancelled', atualizado_em = NOW() WHERE payment_id = ?");
        $stmt_mp->execute([$payment_id]);
        
        // 2. Atualizar status do Pedido
        $stmt_ped = $pdo->prepare("UPDATE pedidos SET status = 'cancelado', atualizado_em = NOW() WHERE id = ?");
        $stmt_ped->execute([$pedido_id]);

        echo "-> Pedido cancelado no banco de dados.\n";

        // 3. Tentar cancelar na API do Mercado Pago (Opcional, mas recomendado para invalidar QR Code)
        /* 
           Se quiser implementar cancelamento na API:
           $mpHelper->cancelPayment($payment_id); 
           (Assumindo que o método existe ou seria criado. Por enquanto, limpa localmente.)
        */

        // 4. Enviar Notificação WhatsApp (Template de Cancelamento - ID 10)
        if ($whatsapp->shouldSendStatusNotification()) {
            $result = $whatsapp->sendStatusUpdate($pedido, 'cancelado');
            if ($result['success']) {
                echo "-> Notificação de cancelamento enviada para {$pedido['cliente_telefone']}.\n";
            } else {
                echo "-> Falha ao enviar notificação: " . ($result['error'] ?? 'Erro desconhecido') . "\n";
            }
        }

        echo "---------------------------------------------------\n";
    }

} catch (PDOException $e) {
    echo "Erro ao consultar banco de dados: " . $e->getMessage() . "\n";
}

echo "Limpeza concluída.\n";
?>