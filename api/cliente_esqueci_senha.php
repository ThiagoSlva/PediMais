<?php
/**
 * API: Esqueci a Senha - Cliente
 * Gera nova senha e envia via WhatsApp E Email para o cliente
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
    // Buscar cliente pelo email
    $stmt = $pdo->prepare("SELECT id, nome, email, telefone FROM clientes WHERE email = ? AND ativo = 1 LIMIT 1");
    $stmt->execute([$email]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cliente) {
        echo json_encode(['erro' => 'E-mail não encontrado ou conta inativa'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $telefone = $cliente['telefone'] ?? null;
    
    // Verificar se WhatsApp está ativo
    $stmt = $pdo->query("SELECT ativo FROM whatsapp_config LIMIT 1");
    $whats_config = $stmt->fetch(PDO::FETCH_ASSOC);
    $whatsapp_disponivel = !empty($telefone) && $whats_config && $whats_config['ativo'];
    
    // Gerar nova senha aleatória (8 caracteres alfanuméricos)
    $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $nova_senha = '';
    for ($i = 0; $i < 8; $i++) {
        $nova_senha .= $caracteres[random_int(0, strlen($caracteres) - 1)];
    }
    
    // Criar hash da senha
    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    
    // Atualizar senha no banco
    $stmt = $pdo->prepare("UPDATE clientes SET senha = ? WHERE id = ?");
    $stmt->execute([$senha_hash, $cliente['id']]);
    
    $resultados = [];
    
    // ========== ENVIAR VIA WHATSAPP ==========
    if ($whatsapp_disponivel) {
        $mensagem_whats = "🔐 *RECUPERAÇÃO DE SENHA*\n\n";
        $mensagem_whats .= "Olá, {$cliente['nome']}! 👋\n\n";
        $mensagem_whats .= "Sua nova senha é: *{$nova_senha}*\n\n";
        $mensagem_whats .= "⚠️ Recomendamos alterar esta senha após o login.\n\n";
        $mensagem_whats .= "Obrigado! 🙏";
        
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
            $emailHelper->setFromName('PediMais');
            
            $htmlEmail = EmailHelper::gerarHtmlRecuperacaoSenha($cliente['nome'], $nova_senha, 'cliente');
            $textoEmail = "Olá, {$cliente['nome']}!\n\nSua nova senha é: {$nova_senha}\n\nRecomendamos alterar esta senha após o login.";
            
            $resultados['email'] = $emailHelper->sendEmail(
                $cliente['email'],
                '🔐 Recuperação de Senha - PediMais',
                $htmlEmail,
                $textoEmail
            );
            
            if (!$resultados['email']) {
                error_log("Erro ao enviar email cliente: " . $emailHelper->getLastError());
            }
        } catch (Exception $e) {
            error_log("Erro ao enviar email cliente: " . $e->getMessage());
        }
    }
    
    // ========== RESPOSTA ==========
    if ($resultados['whatsapp'] || $resultados['email']) {
        $canais = [];
        if ($resultados['whatsapp']) {
            $telefone_masked = substr($telefone, 0, 2) . '****' . substr($telefone, -2);
            $canais[] = "WhatsApp ({$telefone_masked})";
        }
        if ($resultados['email']) {
            $email_parts = explode('@', $cliente['email']);
            $email_masked = substr($email_parts[0], 0, 2) . '***@' . $email_parts[1];
            $canais[] = "E-mail ({$email_masked})";
        }
        
        echo json_encode([
            'sucesso' => true,
            'mensagem' => "Nova senha enviada via " . implode(' e ', $canais)
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'erro' => 'Não foi possível enviar a senha. Verifique suas configurações de contato.'
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    error_log("Erro ao recuperar senha cliente: " . $e->getMessage());
    echo json_encode(['erro' => 'Erro ao processar solicitação'], JSON_UNESCAPED_UNICODE);
}
