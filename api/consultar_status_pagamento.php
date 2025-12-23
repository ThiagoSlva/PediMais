<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/mercadopago_helper.php';

$pedido_id = $_GET['pedido_id'] ?? 0;

if (!$pedido_id) {
    echo json_encode(['error' => 'ID do pedido não fornecido']);
    exit;
}

try {
    // Buscar pagamento associado
    $stmt = $pdo->prepare("SELECT payment_id, status FROM mercadopago_pagamentos WHERE pedido_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$pedido_id]);
    $pagamento = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pagamento) {
        $status_atual = $pagamento['status'];

        // Se ainda não estiver aprovado, verificar na API do MP
        if ($status_atual !== 'approved') {
            $mpHelper = new MercadoPagoHelper($pdo);
            $dados_mp = $mpHelper->getPaymentStatus($pagamento['payment_id']);

            if ($dados_mp && isset($dados_mp['status'])) {
                $novo_status = $dados_mp['status'];

                // Se status mudou, atualizar banco
                if ($novo_status !== $status_atual) {
                    $stmt_upd = $pdo->prepare("UPDATE mercadopago_pagamentos SET status = ?, atualizado_em = NOW() WHERE payment_id = ?");
                    $stmt_upd->execute([$novo_status, $pagamento['payment_id']]);
                    
                    // Se aprovado, atualizar status do pedido também se ainda estiver pendente
                    if ($novo_status === 'approved') {
                       // Opcional: Atualizar status do pedido para 'preparing' ou similar
                       // $pdo->exec("UPDATE pedidos SET status = 'preparando' WHERE id = $pedido_id AND status = 'pendente'");
                    }
                    
                    $status_atual = $novo_status;
                }
            }
        }

        // Mapear status para frontend
        $status_final = 'pendente';
        if ($status_atual === 'approved') $status_final = 'aprovado';
        
        echo json_encode(['status' => $status_final, 'mp_status' => $status_atual]);
    } else {
        echo json_encode(['status' => 'nao_encontrado']);
    }

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
