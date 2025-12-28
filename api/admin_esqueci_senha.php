<?php
/**
 * API: Esqueci a Senha - Painel Admin
 * Gera nova senha e envia via WhatsApp E Email para o estabelecimento
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/config.php';
require_once '../includes/whatsapp_helper.php';
require_once '../includes/email_helper.php';

// Carregar configurações de email (se existir)
if (file_exists('../includes/email_config.php')) {
    require_once '../includes/email_config.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['erro' => 'Método não permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');

if (empty($email)) {
    echo json_encode(['erro' => 'E-mail é obrigatório'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Buscar usuário pelo email
    $stmt = $pdo->prepare("SELECT id, nome, email FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        echo json_encode(['erro' => 'E-mail não encontrado ou usuário inativo'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Buscar telefone do estabelecimento na configuração do WhatsApp
    $stmt = $pdo->query("SELECT whatsapp_estabelecimento, ativo FROM whatsapp_config LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $whatsapp_disponivel = $config && !empty($config['whatsapp_estabelecimento']) && $config['ativo'];
    $telefone = $whatsapp_disponivel ? $config['whatsapp_estabelecimento'] : null;
    
    // Gerar nova senha aleatória (8 caracteres alfanuméricos)
    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $nova_senha = '';
    for ($i = 0; $i < 8; $i++) {
        $nova_senha .= $caracteres[random_int(0, strlen($caracteres) - 1)];
    }
    
    // Criar hash da senha
    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    
    // Atualizar senha no banco
    $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
    $stmt->execute([$senha_hash, $usuario['id']]);
    
    $resultados = [];
    
    // ========== ENVIAR VIA WHATSAPP ==========
    if ($whatsapp_disponivel) {
        $mensagem_whats = "🔐 *RECUPERAÇÃO DE SENHA*\n\n";
        $mensagem_whats .= "📧 *E-mail:* {$usuario['email']}\n";
        $mensagem_whats .= "👤 *Usuário:* {$usuario['nome']}\n\n";
        $mensagem_whats .= "🔑 *Nova Senha:* {$nova_senha}\n\n";
        $mensagem_whats .= "⚠️ Recomendamos alterar esta senha após o login.\n\n";
        $mensagem_whats .= "Acesse o painel administrativo para entrar.";
        
        $whatsapp = new WhatsAppHelper($pdo);
        $resultado_whats = $whatsapp->sendMessage($telefone, $mensagem_whats);
        $resultados['whatsapp'] = isset($resultado_whats['success']) && $resultado_whats['success'];
    } else {
        $resultados['whatsapp'] = false;
    }
    
    // ========== ENVIAR VIA EMAIL ==========
    $resultados['email'] = false;
    if (defined('EMAIL_SMTP_PASSWORD') && EMAIL_SMTP_PASSWORD !== 'SUA_SENHA_AQUI') {
        try {
            $emailHelper = new EmailHelper();
            $emailHelper->setPassword(EMAIL_SMTP_PASSWORD);
            $emailHelper->setFromName('PediMais Admin');
            
            $htmlEmail = EmailHelper::gerarHtmlRecuperacaoSenha($usuario['nome'], $nova_senha, 'admin');
            $textoEmail = "Olá, {$usuario['nome']}!\n\nSua nova senha é: {$nova_senha}\n\nRecomendamos alterar esta senha após o login.";
            
            $resultados['email'] = $emailHelper->sendEmail(
                $usuario['email'],
                '🔐 Recuperação de Senha - Painel Admin',
                $htmlEmail,
                $textoEmail
            );
            
            if (!$resultados['email']) {
                error_log("Erro ao enviar email: " . $emailHelper->getLastError());
            }
        } catch (Exception $e) {
            error_log("Erro ao enviar email admin: " . $e->getMessage());
        }
    }
    
    // ========== RESPOSTA ==========
    if ($resultados['whatsapp'] || $resultados['email']) {
        $canais = [];
        if ($resultados['whatsapp']) {
            $telefone_masked = substr($telefone, 0, 4) . '****' . substr($telefone, -2);
            $canais[] = "WhatsApp ({$telefone_masked})";
        }
        if ($resultados['email']) {
            $canais[] = "E-mail ({$usuario['email']})";
        }
        
        echo json_encode([
            'sucesso' => true,
            'mensagem' => "Nova senha enviada via " . implode(' e ', $canais)
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'erro' => 'Não foi possível enviar a senha. Verifique as configurações de WhatsApp e Email.'
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    error_log("Erro ao recuperar senha admin: " . $e->getMessage());
    echo json_encode(['erro' => 'Erro ao processar solicitação'], JSON_UNESCAPED_UNICODE);
}
