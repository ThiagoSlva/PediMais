<?php
// whatsapp_helper.php - Evolution API Integration
// Handles WhatsApp messaging via Evolution API

class WhatsAppHelper {
    private $pdo;
    private $config;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadConfig();
    }

    private function loadConfig() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM whatsapp_config LIMIT 1");
            $this->config = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            $this->config = [];
        }
    }

    public function isConfigured() {
        return !empty($this->config['base_url']) 
            && !empty($this->config['apikey']) 
            && !empty($this->config['instance_name'])
            && ($this->config['ativo'] ?? false);
    }

    public function sendMessage($phone, $message) {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'WhatsApp não configurado'];
        }

        // Remove non-digits
        $phone = preg_replace('/\D/', '', $phone);
        
        // Add country code if missing (assuming BR +55)
        if (strlen($phone) <= 11) {
            $phone = '55' . $phone;
        }

        // Evolution API endpoint
        $url = rtrim($this->config['base_url'], '/') . '/message/sendText/' . urlencode($this->config['instance_name']);
        
        // Evolution API v2 format
        $body = [
            "number" => $phone,
            "text" => $message,
            "delay" => 1200
        ];

        $headers = [
            "Content-Type: application/json",
            "apikey: " . $this->config['apikey']
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Log the attempt
        $this->logMessage($phone, $message, $httpCode, $response);

        return [
            'success' => $httpCode == 200 || $httpCode == 201,
            'response' => json_decode($response, true),
            'error' => $error,
            'http_code' => $httpCode
        ];
    }

    private function logMessage($phone, $message, $httpCode, $response) {
        try {
            // Use existing whatsapp_logs table structure
            $status = ($httpCode == 200 || $httpCode == 201) ? 'enviado' : 'erro';
            $erro = ($httpCode != 200 && $httpCode != 201) ? "HTTP $httpCode: " . substr($response, 0, 500) : null;
            
            $stmt = $this->pdo->prepare("INSERT INTO whatsapp_logs (cliente_telefone, status, erro, resposta_api, enviado_em) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$phone, $status, $erro, substr($response, 0, 1000)]);
        } catch (Exception $e) {
            // Silently fail logging - table might have different structure
        }
    }

    // Check if order notification should be sent
    public function shouldSendOrderNotification() {
        return $this->isConfigured() && ($this->config['enviar_comprovante'] ?? false);
    }

    // Check if status change notification should be sent
    public function shouldSendStatusNotification() {
        return $this->isConfigured() && ($this->config['notificar_status_pedido'] ?? false);
    }

    // Send order confirmation to customer
    public function sendOrderConfirmation($pedido) {
        if (!$this->shouldSendOrderNotification()) {
            return ['success' => false, 'error' => 'Notificação de pedido desativada'];
        }

        $telefone = $pedido['cliente_telefone'];
        $codigo = $pedido['codigo_pedido'];
        $nome = $pedido['cliente_nome'];
        $total = number_format($pedido['valor_total'], 2, ',', '.');
        $tipo = $pedido['tipo_entrega'] == 'delivery' ? '🛵 Delivery' : '🏪 Retirada no balcão';
        
        // Buscar itens do pedido
        $itens_texto = '';
        $adicionais_texto = '';
        try {
            $pedido_id = $pedido['id'] ?? null;
            if ($pedido_id) {
                // Buscar itens
                $stmt_itens = $this->pdo->prepare("SELECT * FROM pedido_itens WHERE pedido_id = ?");
                $stmt_itens->execute([$pedido_id]);
                $itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($itens)) {
                    $itens_texto = "🛒 *Itens do Pedido:*\n";
                    foreach ($itens as $item) {
                        $qtd = $item['quantidade'];
                        $nome_item = $item['produto_nome'];
                        $preco_item = number_format($item['preco_unitario'] * $qtd, 2, ',', '.');
                        $itens_texto .= "• {$qtd}x {$nome_item} - R$ {$preco_item}\n";
                        
                        // Observação do item
                        if (!empty($item['observacoes'])) {
                            $itens_texto .= "   📝 _{$item['observacoes']}_\n";
                        }
                        
                        // Buscar adicionais deste item
                        $stmt_adicionais = $this->pdo->prepare("SELECT * FROM pedido_item_adicionais WHERE pedido_item_id = ?");
                        $stmt_adicionais->execute([$item['id']]);
                        $adicionais = $stmt_adicionais->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (!empty($adicionais)) {
                            foreach ($adicionais as $adicional) {
                                $preco_ad = $adicional['preco'] > 0 ? " (+R$ " . number_format($adicional['preco'], 2, ',', '.') . ")" : '';
                                $itens_texto .= "   ➕ {$adicional['nome']}{$preco_ad}\n";
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Continua sem os itens
        }
        
        // Buscar forma de pagamento
        $forma_pagamento = 'Não informada';
        try {
            $forma_id = $pedido['forma_pagamento_id'] ?? null;
            if ($forma_id) {
                $stmt_fp = $this->pdo->prepare("SELECT nome FROM formas_pagamento WHERE id = ?");
                $stmt_fp->execute([$forma_id]);
                $fp = $stmt_fp->fetch(PDO::FETCH_ASSOC);
                if ($fp) {
                    $forma_pagamento = $fp['nome'];
                }
            }
        } catch (Exception $e) {
            // Usa valor padrão
        }
        
        // Valores
        $valor_produtos = isset($pedido['valor_produtos']) ? number_format($pedido['valor_produtos'], 2, ',', '.') : null;
        $valor_entrega = isset($pedido['valor_entrega']) && $pedido['valor_entrega'] > 0 
            ? number_format($pedido['valor_entrega'], 2, ',', '.') 
            : null;
        
        // Troco
        $troco = '';
        if (!empty($pedido['troco_para']) && $pedido['troco_para'] > 0) {
            $troco_valor = number_format($pedido['troco_para'], 2, ',', '.');
            $troco = "\n💵 *Troco para:* R$ {$troco_valor}";
        }

        // Montar mensagem
        $mensagem = "🎉 *Pedido Confirmado!*\n\n";
        $mensagem .= "Olá, *{$nome}*!\n\n";
        $mensagem .= "Seu pedido *#{$codigo}* foi recebido com sucesso!\n\n";
        
        // Itens do pedido
        if (!empty($itens_texto)) {
            $mensagem .= $itens_texto . "\n";
        }
        
        // Valores detalhados
        $mensagem .= "━━━━━━━━━━━━━━━━\n";
        if ($valor_produtos) {
            $mensagem .= "🧾 *Subtotal:* R$ {$valor_produtos}\n";
        }
        if ($valor_entrega) {
            $mensagem .= "🚚 *Taxa de Entrega:* R$ {$valor_entrega}\n";
        }
        $mensagem .= "💰 *Total:* R$ {$total}\n";
        $mensagem .= "━━━━━━━━━━━━━━━━\n\n";
        
        // Tipo de entrega
        $mensagem .= "📦 *Tipo:* {$tipo}\n";
        
        // Endereço (se delivery)
        if ($pedido['tipo_entrega'] == 'delivery' && !empty($pedido['cliente_endereco'])) {
            $mensagem .= "📍 *Endereço:* {$pedido['cliente_endereco']}\n";
        }
        
        // Forma de pagamento
        $mensagem .= "💳 *Pagamento:* {$forma_pagamento}{$troco}\n";
        
        // Telefone
        if (!empty($telefone)) {
            $telefone_formatado = $this->formatarTelefone($telefone);
            $mensagem .= "📱 *Telefone:* {$telefone_formatado}\n";
        }
        
        // Observações gerais do pedido
        if (!empty($pedido['observacoes'])) {
            $mensagem .= "\n📝 *Observações:* {$pedido['observacoes']}\n";
        }
        
        $mensagem .= "\nVocê receberá atualizações sobre o status do seu pedido.\n\n";
        $mensagem .= "Obrigado pela preferência! 😊";

        return $this->sendMessage($telefone, $mensagem);
    }
    
    // Formatar telefone para exibição
    private function formatarTelefone($telefone) {
        $telefone = preg_replace('/\D/', '', $telefone);
        // Remove código do país se presente
        if (strlen($telefone) > 11 && substr($telefone, 0, 2) == '55') {
            $telefone = substr($telefone, 2);
        }
        // Formata (XX) XXXXX-XXXX ou (XX) XXXX-XXXX
        if (strlen($telefone) == 11) {
            return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
        } elseif (strlen($telefone) == 10) {
            return '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
        }
        return $telefone;
    }

    // Send status change notification using templates from database
    public function sendStatusUpdate($pedido, $novo_status) {
        if (!$this->shouldSendStatusNotification()) {
            return ['success' => false, 'error' => 'Notificação de status desativada'];
        }

        $telefone = $pedido['cliente_telefone'] ?? '';
        if (empty($telefone)) {
            return ['success' => false, 'error' => 'Telefone do cliente não informado'];
        }

        // Mapeamento de status do sistema para ID do template no banco
        // IDs conforme tabela whatsapp_mensagens
        $status_template_map = [
            'pendente' => 5,        // Status: Pendente (inativo)
            'em_andamento' => 6,    // Status: Em Preparo
            'pronto' => 7,          // Status: Pronto
            'saiu_entrega' => 8,    // Status: Saiu para Entrega
            'concluido' => 9,       // Status: Entregue
            'finalizado' => 12,     // Status: Pedido Concluído/Finalizado (exclusivo)
            'cancelado' => 10       // Status: Cancelado
        ];

        // Verificar se o status tem template mapeado
        if (!isset($status_template_map[$novo_status])) {
            return ['success' => false, 'error' => 'Status não tem template mapeado: ' . $novo_status];
        }

        $template_id = $status_template_map[$novo_status];

        // Buscar template do banco
        try {
            $stmt = $this->pdo->prepare("SELECT mensagem, ativo FROM whatsapp_mensagens WHERE id = ? LIMIT 1");
            $stmt->execute([$template_id]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$template) {
                return ['success' => false, 'error' => 'Template ID ' . $template_id . ' não encontrado'];
            }

            if (!$template['ativo']) {
                return ['success' => false, 'error' => 'Template ID ' . $template_id . ' está inativo'];
            }

            // Preparar variáveis para substituição
            $codigo = $pedido['codigo_pedido'] ?? '';
            $nome = $pedido['cliente_nome'] ?? 'Cliente';
            $valor = number_format($pedido['valor_total'] ?? 0, 2, ',', '.');
            $tipo = ($pedido['tipo_entrega'] ?? '') == 'delivery' ? 'Delivery' : 'Retirada';
            $data_pedido = date('d/m/Y H:i');
            
            // Buscar tempos configurados pelo usuário
            $tempo_preparo = $this->config['tempo_preparo_padrao'] ?? 30;
            $tempo_entrega = $this->config['tempo_entrega_padrao'] ?? 40;
            
            // Gerar link de avaliação se aplicável (para status concluido/finalizado)
            $rating_link = '';
            if (in_array($novo_status, ['concluido', 'finalizado'])) {
                // Verificar se sistema de avaliação está ativo
                try {
                    $stmt_av = $this->pdo->query("SELECT ativo FROM configuracao_avaliacoes LIMIT 1");
                    $av_config = $stmt_av->fetch(PDO::FETCH_ASSOC);
                    
                    if ($av_config && $av_config['ativo']) {
                        // Buscar token existente ou criar novo
                        $pedido_id = $pedido['id'] ?? null;
                        if ($pedido_id) {
                            $stmt_token = $this->pdo->prepare("SELECT token FROM avaliacoes WHERE pedido_id = ? LIMIT 1");
                            $stmt_token->execute([$pedido_id]);
                            $existing = $stmt_token->fetch(PDO::FETCH_ASSOC);
                            
                            if ($existing) {
                                $rating_link = SITE_URL . '/avaliar_pedido.php?token=' . $existing['token'];
                            }
                        }
                    }
                } catch (Exception $e) {
                    // Silently fail rating link generation
                }
            }

            // Substituir variáveis no template
            $mensagem = str_replace(
                ['{nome}', '{codigo_pedido}', '{codigo}', '{valor}', '{total}', '{tipo_entrega}', '{data_pedido}', '{telefone}', '{tempo_preparo}', '{tempo_entrega}', '{link}'],
                [$nome, $codigo, $codigo, $valor, $valor, $tipo, $data_pedido, $telefone, $tempo_preparo, $tempo_entrega, $rating_link],
                $template['mensagem']
            );

            // Enviar mensagem
            return $this->sendMessage($telefone, $mensagem);

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erro ao processar template: ' . $e->getMessage()];
        }
    }

    // Send to establishment owner
    public function notifyEstablishment($pedido) {
        $whatsapp_estabelecimento = $this->config['whatsapp_estabelecimento'] ?? '';
        
        if (empty($whatsapp_estabelecimento)) {
            return ['success' => false, 'error' => 'WhatsApp do estabelecimento não configurado'];
        }

        $codigo = $pedido['codigo_pedido'];
        $nome = $pedido['cliente_nome'];
        $telefone = $pedido['cliente_telefone'] ?? '';
        $total = number_format($pedido['valor_total'], 2, ',', '.');
        $tipo = $pedido['tipo_entrega'] == 'delivery' ? '🛵 Delivery' : '🏪 Retirada';
        
        // Buscar itens do pedido
        $itens_texto = '';
        try {
            $pedido_id = $pedido['id'] ?? null;
            if ($pedido_id) {
                $stmt_itens = $this->pdo->prepare("SELECT * FROM pedido_itens WHERE pedido_id = ?");
                $stmt_itens->execute([$pedido_id]);
                $itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($itens)) {
                    $itens_texto = "\n🛒 *ITENS:*\n";
                    foreach ($itens as $item) {
                        $qtd = $item['quantidade'];
                        $nome_item = $item['produto_nome'];
                        $preco_item = number_format($item['preco_unitario'] * $qtd, 2, ',', '.');
                        $itens_texto .= "• {$qtd}x {$nome_item} - R$ {$preco_item}\n";
                        
                        // Observação do item
                        if (!empty($item['observacoes'])) {
                            $itens_texto .= "   📝 _{$item['observacoes']}_\n";
                        }
                        
                        // Buscar adicionais
                        $stmt_adicionais = $this->pdo->prepare("SELECT * FROM pedido_item_adicionais WHERE pedido_item_id = ?");
                        $stmt_adicionais->execute([$item['id']]);
                        $adicionais = $stmt_adicionais->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (!empty($adicionais)) {
                            foreach ($adicionais as $adicional) {
                                $preco_ad = $adicional['preco'] > 0 ? " (+R$ " . number_format($adicional['preco'], 2, ',', '.') . ")" : '';
                                $itens_texto .= "   ➕ {$adicional['nome']}{$preco_ad}\n";
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Continua sem os itens
        }
        
        // Buscar forma de pagamento
        $forma_pagamento = 'N/A';
        try {
            $forma_id = $pedido['forma_pagamento_id'] ?? null;
            if ($forma_id) {
                $stmt_fp = $this->pdo->prepare("SELECT nome FROM formas_pagamento WHERE id = ?");
                $stmt_fp->execute([$forma_id]);
                $fp = $stmt_fp->fetch(PDO::FETCH_ASSOC);
                if ($fp) {
                    $forma_pagamento = $fp['nome'];
                }
            }
        } catch (Exception $e) {
            // Usa valor padrão
        }
        
        // Valores detalhados
        $valor_produtos = isset($pedido['valor_produtos']) ? number_format($pedido['valor_produtos'], 2, ',', '.') : null;
        $valor_entrega = isset($pedido['valor_entrega']) && $pedido['valor_entrega'] > 0 
            ? number_format($pedido['valor_entrega'], 2, ',', '.') 
            : null;
        
        // Troco
        $troco = '';
        if (!empty($pedido['troco_para']) && $pedido['troco_para'] > 0) {
            $troco_valor = number_format($pedido['troco_para'], 2, ',', '.');
            $troco = "\n💵 *Troco para:* R$ {$troco_valor}";
        }

        // Montar mensagem
        $mensagem = "🔔 *NOVO PEDIDO!*\n";
        $mensagem .= "━━━━━━━━━━━━━━━━━━\n\n";
        $mensagem .= "📋 *Pedido:* #{$codigo}\n";
        $mensagem .= "🕐 *Horário:* " . date('H:i') . "\n\n";
        
        // Dados do cliente
        $mensagem .= "👤 *Cliente:* {$nome}\n";
        if (!empty($telefone)) {
            $telefone_formatado = $this->formatarTelefone($telefone);
            $mensagem .= "📱 *Telefone:* {$telefone_formatado}\n";
        }
        
        // Tipo e endereço
        $mensagem .= "\n📦 *Tipo:* {$tipo}\n";
        if ($pedido['tipo_entrega'] == 'delivery' && !empty($pedido['cliente_endereco'])) {
            $mensagem .= "📍 *Endereço:* {$pedido['cliente_endereco']}\n";
        }
        
        // Itens do pedido
        if (!empty($itens_texto)) {
            $mensagem .= $itens_texto;
        }
        
        // Valores
        $mensagem .= "\n━━━━━━━━━━━━━━━━━━\n";
        if ($valor_produtos) {
            $mensagem .= "🧾 *Subtotal:* R$ {$valor_produtos}\n";
        }
        if ($valor_entrega) {
            $mensagem .= "🚚 *Taxa Entrega:* R$ {$valor_entrega}\n";
        }
        $mensagem .= "💰 *TOTAL:* R$ {$total}\n";
        $mensagem .= "━━━━━━━━━━━━━━━━━━\n";
        
        // Pagamento
        $mensagem .= "\n💳 *Pagamento:* {$forma_pagamento}{$troco}\n";
        
        // Observações gerais
        if (!empty($pedido['observacoes'])) {
            $mensagem .= "\n📝 *Obs:* {$pedido['observacoes']}\n";
        }
        
        $mensagem .= "\n✅ Acesse o painel para gerenciar!";

        return $this->sendMessage($whatsapp_estabelecimento, $mensagem);
    }

    // Send PIX payment details to customer (2 messages)
    public function sendPixPayment($pedido, $pix_data, $prazo_minutos = 30) {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'WhatsApp não configurado'];
        }

        $telefone = $pedido['cliente_telefone'];
        $pix_copia_cola = $pix_data['qr_code'] ?? '';

        if (empty($pix_copia_cola)) {
            return ['success' => false, 'error' => 'Código PIX não disponível'];
        }

        // Preparar variáveis para substituição
        $nome = $pedido['cliente_nome'] ?? '';
        $codigo = $pedido['codigo_pedido'] ?? '';
        $valor = number_format($pedido['valor_total'] ?? 0, 2, ',', '.');
        $data_pedido = date('d/m/Y H:i');

        // Buscar template do banco (ID 149 - Aguardando Pagamento)
        try {
            $stmt = $this->pdo->prepare("SELECT mensagem, ativo FROM whatsapp_mensagens WHERE id = 149 LIMIT 1");
            $stmt->execute();
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($template && $template['ativo']) {
                // Usar template EXATO do banco com substituição de variáveis
                $mensagem1 = str_replace(
                    ['{nome}', '{telefone}', '{codigo_pedido}', '{valor}', '{minutos}', '{data_pedido}'],
                    [$nome, $telefone, $codigo, $valor, $prazo_minutos, $data_pedido],
                    $template['mensagem']
                );
            } else {
                // Template inativo - não enviar
                return ['success' => false, 'error' => 'Template de mensagem inativo'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erro ao buscar template: ' . $e->getMessage()];
        }

        // MENSAGEM 1: Template personalizado
        $result1 = $this->sendMessage($telefone, $mensagem1);
        
        // Pequeno delay entre mensagens (1 segundo)
        sleep(1);
        
        // MENSAGEM 2: Apenas o código PIX (fácil de copiar)
        $mensagem2 = $pix_copia_cola;
        $result2 = $this->sendMessage($telefone, $mensagem2);

        return [
            'success' => $result1['success'] && $result2['success'],
            'msg1' => $result1,
            'msg2' => $result2
        ];
    }

    // Check if Mercado Pago PIX WhatsApp is enabled
    public function shouldSendPixNotification() {
        return $this->isConfigured() && ($this->config['usar_mercadopago'] ?? false);
    }
}
?>
