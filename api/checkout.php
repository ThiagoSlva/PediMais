<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/mercadopago_helper.php';

// Receber dados JSON
$input = file_get_contents('php://input');
$dados = json_decode($input, true);

if (!$dados) {
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Dados do Cliente
    $cliente_nome = $dados['cliente']['nome'];
    $cliente_telefone = preg_replace('/[^0-9]/', '', $dados['cliente']['telefone']);
    $cliente_endereco = $dados['cliente']['endereco']; 
    
    // Se endereço for objeto, formatar
    if (is_array($cliente_endereco)) {
        $end_str = $cliente_endereco['rua'] . ', ' . $cliente_endereco['numero'];
        if (!empty($cliente_endereco['bairro'])) $end_str .= ' - ' . $cliente_endereco['bairro'];
        if (!empty($cliente_endereco['complemento'])) $end_str .= ' (' . $cliente_endereco['complemento'] . ')';
        $cliente_endereco = $end_str;
    }

    // Verificar se cliente já existe pelo telefone
    $stmt = $pdo->prepare("SELECT id, email FROM clientes WHERE telefone = ? LIMIT 1");
    $stmt->execute([$cliente_telefone]);
    $cliente_existente = $stmt->fetch();

    $cliente_email = '';
    if ($cliente_existente) {
        $cliente_id = $cliente_existente['id'];
        $cliente_email = $cliente_existente['email'];
        // Atualizar dados se necessário
        $stmt = $pdo->prepare("UPDATE clientes SET nome = ?, endereco_principal = ? WHERE id = ?");
        $stmt->execute([$cliente_nome, $cliente_endereco, $cliente_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO clientes (nome, telefone, endereco_principal) VALUES (?, ?, ?)");
        $stmt->execute([$cliente_nome, $cliente_telefone, $cliente_endereco]);
        $cliente_id = $pdo->lastInsertId();
    }

    // 2. Criar Pedido
    $codigo_pedido = strtoupper(substr(md5(uniqid()), 0, 8));
    $valor_total = $dados['total'];
    $tipo_entrega = $dados['tipo_entrega']; // 'delivery' ou 'retirada'
    $forma_pagamento = $dados['pagamento']; // 'pix', 'dinheiro', 'cartao'
    $observacoes = isset($dados['observacoes']) ? $dados['observacoes'] : '';
    $troco_para = isset($dados['troco_para']) ? $dados['troco_para'] : null;
    $mesa_numero = isset($dados['mesa']) ? $dados['mesa'] : null; // Suporte a mesa

    // Mapear forma de pagamento para ID
    $stmt_fp = $pdo->prepare("SELECT id FROM formas_pagamento WHERE tipo = ? LIMIT 1");
    $stmt_fp->execute([$forma_pagamento]);
    $fp = $stmt_fp->fetch();
    $forma_pagamento_id = $fp ? $fp['id'] : 1; 

    $pagamento_online = 0;
    if ($forma_pagamento == 'pix') {
        // Verificar se PIX Online está ativo
        $stmt_mp = $pdo->query("SELECT ativo FROM mercadopago_config LIMIT 1");
        $mp_config = $stmt_mp->fetch();
        if ($mp_config && $mp_config['ativo']) {
            $pagamento_online = 1;
        }
    }

    // Buscar primeira lane do Kanban para novos pedidos
    $stmt_lane = $pdo->query("SELECT id FROM kanban_lanes ORDER BY ordem ASC LIMIT 1");
    $first_lane = $stmt_lane->fetch();
    $lane_id = $first_lane ? $first_lane['id'] : 1;

    $sql_pedido = "INSERT INTO pedidos (
        cliente_id, codigo_pedido, cliente_nome, cliente_telefone, cliente_endereco, 
        valor_total, tipo_entrega, forma_pagamento_id, troco_para, observacoes, status, data_pedido, mesa_numero, pagamento_online, lane_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente', NOW(), ?, ?, ?)";

    $stmt = $pdo->prepare($sql_pedido);
    $stmt->execute([
        $cliente_id, $codigo_pedido, $cliente_nome, $cliente_telefone, $cliente_endereco,
        $valor_total, $tipo_entrega, $forma_pagamento_id, $troco_para, $observacoes, $mesa_numero, $pagamento_online, $lane_id
    ]);
    $pedido_id = $pdo->lastInsertId();

    // 3. Itens do Pedido
    foreach ($dados['itens'] as $item) {
        $produto_id = $item['id'];
        $quantidade = $item['quantidade'];
        $preco_unitario = $item['preco'];
        $obs_item = isset($item['observacoes']) ? $item['observacoes'] : '';
        $nome_produto = $item['nome']; 

        $stmt_item = $pdo->prepare("INSERT INTO pedido_itens (pedido_id, produto_id, produto_nome, quantidade, preco_unitario, observacoes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_item->execute([$pedido_id, $produto_id, $nome_produto, $quantidade, $preco_unitario, $obs_item]);
        $pedido_item_id = $pdo->lastInsertId();

        // Opções/Adicionais do Item
        if (isset($item['adicionais']) && is_array($item['adicionais'])) {
            foreach ($item['adicionais'] as $add) {
                $stmt_add = $pdo->prepare("INSERT INTO pedido_item_opcoes (pedido_item_id, opcao_id, quantidade, preco_adicional) VALUES (?, ?, 1, ?)");
                $stmt_add->execute([$pedido_item_id, $add['id'], $add['preco']]);
            }
        }
    }

    // 4. Processar Pagamento Online (Mercado Pago)
    $qr_code_data = null;
    if ($pagamento_online) {
        $mpHelper = new MercadoPagoHelper($pdo);
        $descricao = "Pedido #$codigo_pedido - $cliente_nome";
        
        $payment_result = $mpHelper->createPayment($pedido_id, $valor_total, $cliente_email, $cliente_nome, $descricao);
        
        if ($payment_result['success']) {
            // Salvar dados do pagamento
            $stmt_mp_log = $pdo->prepare("INSERT INTO mercadopago_pagamentos (pedido_id, payment_id, qr_code, qr_code_base64, ticket_url, status, valor, expiracao) VALUES (?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))");
            $stmt_mp_log->execute([
                $pedido_id,
                $payment_result['payment_id'],
                $payment_result['qr_code'],
                $payment_result['qr_code_base64'],
                $payment_result['ticket_url'],
                $payment_result['status'],
                $valor_total
            ]);

            // Atualizar pedido com QR Code (opcional, mas útil)
            $stmt_upd = $pdo->prepare("UPDATE pedidos SET qr_code_base64 = ? WHERE id = ?");
            $stmt_upd->execute([$payment_result['qr_code_base64'], $pedido_id]);

            $qr_code_data = [
                'qr_code' => $payment_result['qr_code'],
                'qr_code_base64' => $payment_result['qr_code_base64'],
                'ticket_url' => $payment_result['ticket_url']
            ];
        } else {
            // Se falhar o pagamento, mas o pedido foi criado, podemos retornar erro ou aviso.
            // Vamos logar o erro mas manter o pedido como pendente (sem pagamento online)
            // Ou lançar exceção para desfazer tudo? Melhor desfazer para o cliente tentar de novo.
            throw new Exception("Erro ao gerar PIX: " . $payment_result['error']);
        }
    }

    $pdo->commit();

    // 5. Enviar Notificações WhatsApp (após commit para garantir que o pedido existe)
    $whatsapp_result = null;
    try {
        require_once '../includes/whatsapp_helper.php';
        $whatsapp = new WhatsAppHelper($pdo);
        
        // Dados do pedido para notificação
        $pedido_notif = [
            'codigo_pedido' => $codigo_pedido,
            'cliente_nome' => $cliente_nome,
            'cliente_telefone' => $cliente_telefone,
            'valor_total' => $valor_total,
            'tipo_entrega' => $tipo_entrega
        ];
        
        // Se for pagamento PIX online, enviar código copia e cola para o cliente
        if ($pagamento_online && $qr_code_data && $whatsapp->shouldSendPixNotification()) {
            $whatsapp->sendPixPayment($pedido_notif, $qr_code_data, 30);
        } else {
            // Enviar apenas comprovante normal para pedidos sem PIX online
            $whatsapp->sendOrderConfirmation($pedido_notif);
        }
        
        // Notificar estabelecimento
        $whatsapp->notifyEstablishment($pedido_notif);
        
        $whatsapp_result = true;
    } catch (Exception $e) {
        // Não falhar o checkout por causa do WhatsApp
        $whatsapp_result = false;
    }

    echo json_encode([
        'success' => true,
        'pedido_id' => $pedido_id,
        'codigo_pedido' => $codigo_pedido,
        'mensagem' => 'Pedido realizado com sucesso!',
        'pagamento_online' => $pagamento_online,
        'pix_data' => $qr_code_data,
        'whatsapp_enviado' => $whatsapp_result
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['error' => 'Erro ao processar pedido: ' . $e->getMessage()]);
}
?>
