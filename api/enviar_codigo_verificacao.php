<?php
/**
 * API: Enviar Código de Verificação
 * Gera e envia código de 6 dígitos para o WhatsApp do cliente
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/config.php';
require_once '../includes/whatsapp_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['erro' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$telefone = preg_replace('/[^0-9]/', '', $input['telefone'] ?? '');
$nome = trim($input['nome'] ?? 'Cliente');
$endereco_detalhado = $input['endereco_detalhado'] ?? null; // Novo: aceitar dados do endereço

if (empty($telefone)) {
    echo json_encode(['erro' => 'Telefone é obrigatório'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Verificar se verificação está ativa
    $stmt = $pdo->query("SELECT * FROM configuracao_verificacao LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config || !$config['ativo']) {
        echo json_encode(['erro' => 'Sistema de verificação está desativado'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Buscar ou criar cliente
    $stmt = $pdo->prepare("SELECT id, telefone_verificado FROM clientes WHERE telefone = ? LIMIT 1");
    $stmt->execute([$telefone]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cliente && $cliente['telefone_verificado']) {
        echo json_encode([
            'sucesso' => true,
            'ja_verificado' => true,
            'mensagem' => 'Este telefone já está verificado'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Se cliente não existe, criar
    $cliente_novo = false;
    if (!$cliente) {
        $stmt = $pdo->prepare("INSERT INTO clientes (nome, telefone, telefone_verificado) VALUES (?, ?, 0)");
        $stmt->execute([$nome, $telefone]);
        $cliente_id = $pdo->lastInsertId();
        $cliente_novo = true;
    } else {
        $cliente_id = $cliente['id'];
    }
    
    // DEBUG: Log address data received
    error_log("📍 VERIFICAÇÃO - Cliente ID: $cliente_id, Novo: " . ($cliente_novo ? 'SIM' : 'NÃO'));
    error_log("📍 VERIFICAÇÃO - Endereço recebido: " . json_encode($endereco_detalhado));
    
    // Salvar endereço automaticamente para cliente novo OU existente sem endereços
    if ($endereco_detalhado && !empty($endereco_detalhado['rua'])) {
        // Verificar se cliente já tem algum endereço salvo
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cliente_enderecos WHERE cliente_id = ?");
        $stmt->execute([$cliente_id]);
        $tem_endereco = $stmt->fetchColumn() > 0;
        
        error_log("📍 VERIFICAÇÃO - Cliente tem endereço: " . ($tem_endereco ? 'SIM' : 'NÃO'));
        
        // Salvar se cliente novo OU se não tem nenhum endereço
        if ($cliente_novo || !$tem_endereco) {
            try {
                // Verificar duplicata
                $stmt = $pdo->prepare("SELECT id FROM cliente_enderecos WHERE cliente_id = ? AND rua = ? AND numero = ? LIMIT 1");
                $stmt->execute([$cliente_id, $endereco_detalhado['rua'] ?? '', $endereco_detalhado['numero'] ?? '']);
                
                if (!$stmt->fetch()) {
                    $stmt = $pdo->prepare("
                        INSERT INTO cliente_enderecos 
                        (cliente_id, apelido, cep, rua, numero, complemento, bairro, cidade, uf, principal) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
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
                        $endereco_detalhado['uf'] ?? ''
                    ]);
                    error_log("📍 VERIFICAÇÃO - Endereço SALVO com sucesso! ID: " . $pdo->lastInsertId());
                } else {
                    error_log("📍 VERIFICAÇÃO - Endereço já existe, não duplicado.");
                }
            } catch (Exception $e) {
                error_log("❌ VERIFICAÇÃO - Erro ao salvar endereço: " . $e->getMessage());
            }
        }
    } else {
        error_log("📍 VERIFICAÇÃO - Sem dados de endereço para salvar");
    }
    
    // Gerar código de 6 dígitos
    $codigo = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Tempo de expiração
    $tempo_minutos = intval($config['tempo_expiracao'] ?? 5);
    $expira_em = date('Y-m-d H:i:s', strtotime("+$tempo_minutos minutes"));
    
    // Invalidar códigos anteriores do mesmo cliente
    $pdo->prepare("UPDATE verificacao_codigos SET usado = 1 WHERE cliente_id = ? AND usado = 0")->execute([$cliente_id]);
    
    // Salvar novo código
    $stmt = $pdo->prepare("INSERT INTO verificacao_codigos (cliente_id, codigo, expira_em, usado) VALUES (?, ?, ?, 0)");
    $stmt->execute([$cliente_id, $codigo, $expira_em]);
    
    // Preparar mensagem
    $mensagem = $config['mensagem_codigo'];
    if (empty($mensagem)) {
        $mensagem = "🔐 *Código de Verificação*\n\nSeu código de verificação é: *{codigo}*\n\nEste código expira em {tempo} minutos.\nDigite este código para finalizar seu primeiro pedido.";
    }
    
    $mensagem = str_replace('{codigo}', $codigo, $mensagem);
    $mensagem = str_replace('{tempo}', $tempo_minutos, $mensagem);
    
    // Enviar via WhatsApp
    $whatsapp = new WhatsAppHelper($pdo);
    $resultado = $whatsapp->sendMessage($telefone, $mensagem);
    
    if (isset($resultado['success']) && $resultado['success']) {
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Código enviado para o WhatsApp!',
            'tempo_expiracao' => $tempo_minutos
        ], JSON_UNESCAPED_UNICODE);
    } else {
        $erro_msg = $resultado['error'] ?? 'Falha ao enviar código via WhatsApp.';
        error_log("Erro WhatsApp: " . $erro_msg);
        echo json_encode([
            'erro' => 'Falha ao enviar código via WhatsApp. Verifique a conexão.'
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (PDOException $e) {
    error_log("Erro PDO ao enviar código: " . $e->getMessage());
    
    // Check if it's a missing column error and try to add it
    if (strpos($e->getMessage(), 'telefone_verificado') !== false) {
        try {
            $pdo->exec("ALTER TABLE clientes ADD COLUMN telefone_verificado TINYINT(1) DEFAULT 0");
            echo json_encode(['erro' => 'Tabela atualizada, tente novamente.'], JSON_UNESCAPED_UNICODE);
        } catch (Exception $ex) {
            echo json_encode(['erro' => 'Erro ao processar cliente'], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode(['erro' => 'Erro ao processar solicitação: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    error_log("Erro ao enviar código de verificação: " . $e->getMessage());
    echo json_encode(['erro' => 'Erro ao enviar WhatsApp: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
