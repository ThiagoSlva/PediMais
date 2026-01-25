<?php
/**
 * API: Finalizar Pedido Completo
 * Processa o pedido do checkout completo com todos os dados
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Receber dados JSON
$input = file_get_contents('php://input');
$dados = json_decode($input, true);

if (!$dados) {
    echo json_encode(['erro' => 'Dados inválidos'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar se a loja está aberta
if (!loja_aberta()) {
    // Buscar mensagem personalizada
    try {
        $stmt_msg = $pdo->query("SELECT mensagem_fechado FROM configuracao_horarios LIMIT 1");
        $config_horario = $stmt_msg->fetch(PDO::FETCH_ASSOC);
        $mensagem = $config_horario['mensagem_fechado'] ?? 'Estamos fechados no momento.';
    } catch (Exception $e) {
        $mensagem = 'Estamos fechados no momento.';
    }
    
    echo json_encode([
        'erro' => $mensagem,
        'loja_fechada' => true
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar se precisa de verificação de primeiro pedido
$cliente_telefone_temp = preg_replace('/[^0-9]/', '', $dados['telefone'] ?? '');
if (!empty($cliente_telefone_temp)) {
    try {
        // Verificar se sistema de verificação está ativo
        $stmt_verif = $pdo->query("SELECT ativo FROM configuracao_verificacao LIMIT 1");
        $config_verif = $stmt_verif->fetch(PDO::FETCH_ASSOC);
        
        if ($config_verif && $config_verif['ativo']) {
            // Verificar se cliente existe e está verificado
            $stmt_cliente = $pdo->prepare("SELECT telefone_verificado FROM clientes WHERE telefone = ? LIMIT 1");
            $stmt_cliente->execute([$cliente_telefone_temp]);
            $cliente_check = $stmt_cliente->fetch(PDO::FETCH_ASSOC);
            
            // Se cliente não existe ou não está verificado
            if (!$cliente_check || !$cliente_check['telefone_verificado']) {
                echo json_encode([
                    'erro' => 'Verificação necessária',
                    'precisa_verificar' => true,
                    'mensagem' => 'Por favor, verifique seu telefone para fazer o primeiro pedido.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
    } catch (Exception $e) {
        // Se houver erro na verificação, permitir pedido
        error_log("Erro ao verificar telefone: " . $e->getMessage());
    }
}

try {
    $pdo->beginTransaction();

    // 1. Dados do Cliente
    $cliente_nome = trim($dados['nome'] ?? '');
    $cliente_telefone = preg_replace('/[^0-9]/', '', $dados['telefone'] ?? '');
    $cliente_email = trim($dados['email'] ?? '');
    $cliente_endereco = trim($dados['endereco'] ?? '');
    
    if (empty($cliente_nome) || empty($cliente_telefone)) {
        echo json_encode(['erro' => 'Nome e telefone são obrigatórios'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Verificar se cliente já existe pelo telefone
    $stmt = $pdo->prepare("SELECT id FROM clientes WHERE telefone = ? LIMIT 1");
    $stmt->execute([$cliente_telefone]);
    $cliente_existente = $stmt->fetch();
    $cliente_novo = false;
    
    // Obter dados detalhados do endereço
    $endereco_detalhado = $dados['endereco_detalhado'] ?? null;

    if ($cliente_existente) {
        $cliente_id = $cliente_existente['id'];
        // Atualizar dados do cliente incluindo endereço detalhado
        if ($endereco_detalhado && !empty($endereco_detalhado['rua'])) {
            $stmt = $pdo->prepare("UPDATE clientes SET nome = ?, email = ?, endereco_principal = ?, 
                cep = ?, rua = ?, numero = ?, bairro = ?, cidade = ?, estado = ?, complemento = ?
                WHERE id = ?");
            $stmt->execute([
                $cliente_nome, $cliente_email, $cliente_endereco,
                $endereco_detalhado['cep'] ?? '',
                $endereco_detalhado['rua'] ?? '',
                $endereco_detalhado['numero'] ?? '',
                $endereco_detalhado['bairro'] ?? '',
                $endereco_detalhado['cidade'] ?? '',
                $endereco_detalhado['uf'] ?? '',
                $endereco_detalhado['complemento'] ?? '',
                $cliente_id
            ]);
        } else {
            $stmt = $pdo->prepare("UPDATE clientes SET nome = ?, email = ?, endereco_principal = ? WHERE id = ?");
            $stmt->execute([$cliente_nome, $cliente_email, $cliente_endereco, $cliente_id]);
        }
    } else {
        // Criar novo cliente com endereço detalhado
        if ($endereco_detalhado && !empty($endereco_detalhado['rua'])) {
            $stmt = $pdo->prepare("INSERT INTO clientes (nome, telefone, email, endereco_principal, 
                cep, rua, numero, bairro, cidade, estado, complemento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $cliente_nome, $cliente_telefone, $cliente_email, $cliente_endereco,
                $endereco_detalhado['cep'] ?? '',
                $endereco_detalhado['rua'] ?? '',
                $endereco_detalhado['numero'] ?? '',
                $endereco_detalhado['bairro'] ?? '',
                $endereco_detalhado['cidade'] ?? '',
                $endereco_detalhado['uf'] ?? '',
                $endereco_detalhado['complemento'] ?? ''
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO clientes (nome, telefone, email, endereco_principal) VALUES (?, ?, ?, ?)");
            $stmt->execute([$cliente_nome, $cliente_telefone, $cliente_email, $cliente_endereco]);
        }
        $cliente_id = $pdo->lastInsertId();
        $cliente_novo = true;
    }

    // 1.5. Salvar endereço detalhado na tabela cliente_enderecos
    // - Para cliente NOVO em delivery: salvar automaticamente o primeiro endereço
    // - Para cliente existente: salvar apenas se checkbox marcado
    $salvar_endereco = $dados['salvar_endereco'] ?? false;
    // $endereco_detalhado já foi definido acima na linha ~91
    $tipo_entrega = $dados['tipo_entrega'] ?? 'balcao';
    
    // Cliente novo + delivery = salvar automaticamente
    $deve_salvar = false;
    if ($cliente_novo && $tipo_entrega === 'delivery' && !empty($cliente_endereco)) {
        $deve_salvar = true;
        // Montar endereco_detalhado a partir dos dados disponíveis se não vier do frontend
        if (!$endereco_detalhado) {
            // Tentar extrair do endereço completo ou usar dados do request
            $endereco_detalhado = [
                'apelido' => 'Casa',
                'cep' => '',
                'rua' => '',
                'numero' => '',
                'complemento' => '',
                'bairro' => '',
                'cidade' => '',
                'uf' => ''
            ];
        }
    } elseif ($salvar_endereco && $endereco_detalhado) {
        $deve_salvar = true;
    }
    
    if ($deve_salvar && $endereco_detalhado && !empty($endereco_detalhado['rua'])) {
        try {
            // Verificar se esse endereço já existe para o cliente
            $stmt = $pdo->prepare("
                SELECT id FROM cliente_enderecos 
                WHERE cliente_id = ? AND rua = ? AND numero = ? 
                LIMIT 1
            ");
            $stmt->execute([
                $cliente_id, 
                $endereco_detalhado['rua'] ?? '', 
                $endereco_detalhado['numero'] ?? ''
            ]);
            
            if (!$stmt->fetch()) {
                // Endereço não existe, criar
                $stmt = $pdo->prepare("
                    INSERT INTO cliente_enderecos 
                    (cliente_id, apelido, cep, rua, numero, complemento, bairro, cidade, uf, principal) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $cliente_id,
                    $endereco_detalhado['apelido'] ?? 'Casa',
                    $endereco_detalhado['cep'] ?? '',
                    $endereco_detalhado['rua'] ?? '',
                    $endereco_detalhado['numero'] ?? '',
                    $endereco_detalhado['complemento'] ?? '',
                    $endereco_detalhado['bairro'] ?? '',
                    $endereco_detalhado['cidade'] ?? '',
                    $endereco_detalhado['uf'] ?? '',
                    $cliente_novo ? 1 : 0  // Marcar como principal se for o primeiro
                ]);
            }
        } catch (Exception $e) {
            error_log("Erro ao salvar endereço: " . $e->getMessage());
        }
    }

    // 2. Dados do Pedido
    $tipo_entrega = $dados['tipo_entrega'] ?? 'balcao';
    $forma_pagamento_id = (int)($dados['forma_pagamento_id'] ?? 1);
    $taxa_entrega = floatval($dados['taxa_entrega'] ?? 0);
    $observacoes = trim($dados['observacoes'] ?? '');
    $carrinho = $dados['carrinho'] ?? [];

    if (empty($carrinho)) {
        echo json_encode(['erro' => 'Carrinho vazio'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Calcular valor total
    $valor_subtotal = 0;
    foreach ($carrinho as $item) {
        $preco_item = floatval($item['preco'] ?? 0);
        $quantidade = (int)($item['quantidade'] ?? 1);
        
        // Adicionais
        if (!empty($item['adicionais'])) {
            foreach ($item['adicionais'] as $adicional) {
                $preco_item += floatval($adicional['preco'] ?? 0);
            }
        }
        
        $valor_subtotal += $preco_item * $quantidade;
    }
    
    $valor_total = $valor_subtotal + $taxa_entrega;

    // Gerar código do pedido
    $codigo_pedido = strtoupper(substr(md5(uniqid() . time()), 0, 8));

    // Buscar forma de pagamento para verificar se é online
    $stmt = $pdo->prepare("SELECT tipo FROM formas_pagamento WHERE id = ?");
    $stmt->execute([$forma_pagamento_id]);
    $forma_pagamento = $stmt->fetch();

    // Verificar qual gateway está ativo
    $gateway_ativo = 'none';
    try {
        $stmt_gateway = $pdo->query("SELECT gateway_ativo FROM gateway_settings LIMIT 1");
        $gateway_settings = $stmt_gateway->fetch();
        $gateway_ativo = $gateway_settings['gateway_ativo'] ?? 'none';
    } catch (PDOException $e) {
        // Tabela pode não existir ainda, verificar config antiga do MP
        $stmt_mp_check = $pdo->query("SELECT ativo FROM mercadopago_config LIMIT 1");
        $mp_cfg = $stmt_mp_check->fetch();
        if ($mp_cfg && $mp_cfg['ativo']) {
            $gateway_ativo = 'mercadopago';
        }
    }

    // Lógica Híbrida: Aceitar tipo 'mercadopago' OU tipo 'pix' (se gateway ativo)
    $pagamento_online = 0;
    
    if ($forma_pagamento && $gateway_ativo !== 'none') {
        if ($forma_pagamento['tipo'] === 'mercadopago' || $forma_pagamento['tipo'] === 'pix') {
            $pagamento_online = 1;
        }
    }

    // DEBUG TEMPORÁRIO
    error_log("💰 Finalizando Pedido - FormaID: $forma_pagamento_id, Tipo: " . ($forma_pagamento['tipo'] ?? 'null') . ", Online: $pagamento_online");

    // Buscar primeira lane do Kanban
    $stmt_lane = $pdo->query("SELECT id FROM kanban_lanes ORDER BY ordem ASC LIMIT 1");
    $first_lane = $stmt_lane->fetch();
    $lane_id = $first_lane ? $first_lane['id'] : 1;

    // Inserir pedido
    $sql_pedido = "INSERT INTO pedidos (
        cliente_id, codigo_pedido, cliente_nome, cliente_telefone, cliente_endereco,
        valor_total, tipo_entrega, forma_pagamento_id,
        observacoes, status, data_pedido, pagamento_online, lane_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente', NOW(), ?, ?)";

    $stmt = $pdo->prepare($sql_pedido);
    $stmt->execute([
        $cliente_id, $codigo_pedido, $cliente_nome, $cliente_telefone, $cliente_endereco,
        $valor_total, $tipo_entrega, $forma_pagamento_id,
        $observacoes, $pagamento_online, $lane_id
    ]);
    $pedido_id = $pdo->lastInsertId();

    // 3. Inserir Itens do Pedido
    foreach ($carrinho as $item) {
        $produto_id = (int)($item['id'] ?? 0);
        $produto_nome = $item['nome'] ?? '';
        $quantidade = (int)($item['quantidade'] ?? 1);
        $preco_unitario = floatval($item['preco'] ?? 0);
        $obs_item = $item['observacoes'] ?? '';

        $stmt_item = $pdo->prepare("INSERT INTO pedido_itens (pedido_id, produto_id, produto_nome, quantidade, preco_unitario, observacoes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_item->execute([$pedido_id, $produto_id, $produto_nome, $quantidade, $preco_unitario, $obs_item]);
        $item_id = $pdo->lastInsertId();

        // Inserir adicionais se houver
        if (!empty($item['adicionais'])) {
            foreach ($item['adicionais'] as $adicional) {
                $stmt_adic = $pdo->prepare("INSERT INTO pedido_item_adicionais (pedido_item_id, nome, preco) VALUES (?, ?, ?)");
                $stmt_adic->execute([$item_id, $adicional['nome'] ?? '', floatval($adicional['preco'] ?? 0)]);
            }
        }
    }

    // 4. Processar Pagamento Online (Gateway dinâmico)
    $qr_code_data = null;
    if ($pagamento_online) {
        $descricao = "Pedido #$codigo_pedido - $cliente_nome";
        
        if ($gateway_ativo === 'mercadopago') {
            // Usar Mercado Pago
            require_once '../includes/mercadopago_helper.php';
            $mpHelper = new MercadoPagoHelper($pdo);
            
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

                // Atualizar pedido com QR Code
                $stmt_upd = $pdo->prepare("UPDATE pedidos SET qr_code_base64 = ? WHERE id = ?");
                $stmt_upd->execute([$payment_result['qr_code_base64'], $pedido_id]);

                $qr_code_data = [
                    'qr_code' => $payment_result['qr_code'],
                    'qr_code_base64' => $payment_result['qr_code_base64'],
                    'ticket_url' => $payment_result['ticket_url']
                ];
            } else {
                error_log("Erro ao gerar PIX Mercado Pago para pedido $pedido_id: " . $payment_result['error']);
            }
            
        } elseif ($gateway_ativo === 'asaas') {
            // Usar Asaas
            require_once '../includes/asaas_helper.php';
            $asaasHelper = new AsaasHelper($pdo);
            
            $payment_result = $asaasHelper->createPixPayment($pedido_id, $valor_total, $descricao);
            
            if ($payment_result['success']) {
                // Salvar dados do pagamento
                $stmt_asaas_log = $pdo->prepare("INSERT INTO asaas_pagamentos (pedido_id, payment_id, qr_code, qr_code_base64, status, valor, expiracao) VALUES (?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))");
                $stmt_asaas_log->execute([
                    $pedido_id,
                    $payment_result['payment_id'],
                    $payment_result['qr_code'],
                    $payment_result['qr_code_base64'],
                    $payment_result['status'],
                    $valor_total
                ]);

                // Atualizar pedido com QR Code
                $stmt_upd = $pdo->prepare("UPDATE pedidos SET qr_code_base64 = ? WHERE id = ?");
                $stmt_upd->execute([$payment_result['qr_code_base64'], $pedido_id]);

                $qr_code_data = [
                    'qr_code' => $payment_result['qr_code'],
                    'qr_code_base64' => $payment_result['qr_code_base64'],
                    'ticket_url' => null
                ];
                
                error_log("✅ PIX Asaas gerado com sucesso para pedido $pedido_id");
            } else {
                error_log("❌ Erro ao gerar PIX Asaas para pedido $pedido_id: " . ($payment_result['error'] ?? 'Erro desconhecido'));
                if (isset($payment_result['details'])) {
                    error_log("📋 Detalhes do erro: " . json_encode($payment_result['details']));
                }
            }
        }
    }

    $pdo->commit();

    // 5. Enviar Notificações WhatsApp (após commit)
    $whatsapp_result = null;
    try {
        require_once '../includes/whatsapp_helper.php';
        $whatsapp = new WhatsAppHelper($pdo);
        
        // Dados do pedido para notificação
        $pedido_notif = [
            'id' => $pedido_id,
            'codigo_pedido' => $codigo_pedido,
            'cliente_nome' => $cliente_nome,
            'cliente_telefone' => $cliente_telefone,
            'cliente_endereco' => $cliente_endereco,
            'valor_total' => $valor_total,
            'valor_produtos' => $valor_subtotal,
            'valor_entrega' => $taxa_entrega,
            'tipo_entrega' => $tipo_entrega,
            'forma_pagamento_id' => $forma_pagamento_id,
            'observacoes' => $observacoes,
            'troco_para' => $dados['troco_para'] ?? 0
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
        error_log("Erro WhatsApp: " . $e->getMessage());
        $whatsapp_result = false;
    }

    // Retornar sucesso
    echo json_encode([
        'sucesso' => true,
        'codigo' => $codigo_pedido,
        'pedido_id' => $pedido_id,
        'pagamento_online' => (bool)$pagamento_online,
        'pix_data' => $qr_code_data,
        'whatsapp_enviado' => $whatsapp_result
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erro ao finalizar pedido: " . $e->getMessage());
    echo json_encode([
        'erro' => 'Erro ao processar pedido: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
