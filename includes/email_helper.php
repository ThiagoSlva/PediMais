<?php
/**
 * Email Helper - SMTP Email Sending
 * Envia emails via SMTP usando configurações do .env
 */

class EmailHelper {
    private $smtp_host;
    private $smtp_port;
    private $smtp_user;
    private $smtp_pass;
    private $from_email;
    private $from_name;
    private $last_error;
    
    public function __construct() {
        // Carregar configurações de email
        if (file_exists(__DIR__ . '/email_config.php')) {
            require_once __DIR__ . '/email_config.php';
        }
        
        // Configurações SMTP via constantes (definidas em email_config.php)
        $this->smtp_host = defined('EMAIL_SMTP_HOST') ? EMAIL_SMTP_HOST : 'mail.shopdix.com.br';
        $this->smtp_port = defined('EMAIL_SMTP_PORT') ? EMAIL_SMTP_PORT : 465;
        $this->smtp_user = defined('EMAIL_SMTP_USER') ? EMAIL_SMTP_USER : 'atendimento@shopdix.com.br';
        $this->smtp_pass = defined('EMAIL_SMTP_PASSWORD') ? EMAIL_SMTP_PASSWORD : '';
        $this->from_email = $this->smtp_user;
        $this->from_name = defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : 'PediMais';
        $this->last_error = '';
    }
    
    /**
     * Define a senha SMTP
     */
    public function setPassword($password) {
        $this->smtp_pass = $password;
        return $this;
    }
    
    /**
     * Define o nome do remetente
     */
    public function setFromName($name) {
        $this->from_name = $name;
        return $this;
    }
    
    /**
     * Retorna o último erro
     */
    public function getLastError() {
        return $this->last_error;
    }
    
    /**
     * Envia email via SMTP com SSL
     */
    public function sendEmail($to, $subject, $htmlBody, $textBody = null) {
        if (empty($this->smtp_pass)) {
            $this->last_error = 'Senha SMTP não configurada';
            return false;
        }
        
        if (empty($to) || empty($subject)) {
            $this->last_error = 'Destinatário e assunto são obrigatórios';
            return false;
        }
        
        // Fallback para texto simples se não fornecido
        if ($textBody === null) {
            $textBody = strip_tags($htmlBody);
        }
        
        // Gerar boundary e Message-ID únicos
        $boundary = md5(uniqid(time()));
        $messageId = '<' . md5(uniqid(time())) . '@' . parse_url($this->smtp_host, PHP_URL_HOST) . '>';
        
        // Data no formato RFC 2822
        $date = date('r');
        
        // Remover emojis do assunto (podem causar problemas com spam filters)
        $subject_clean = $this->removeEmojis($subject);
        
        // Construir cabeçalhos completos (ordem importa para spam filters)
        $headers = [
            "Date: {$date}",
            "Message-ID: {$messageId}",
            "From: {$this->from_name} <{$this->from_email}>",
            "Reply-To: {$this->from_email}",
            "To: {$to}",
            "Subject: {$subject_clean}",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            "X-Priority: 3",
            "X-Mailer: PediMais Mailer/1.0"
        ];
        
        // Construir corpo do email (multipart)
        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= quoted_printable_encode($textBody) . "\r\n\r\n";
        
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= quoted_printable_encode($htmlBody) . "\r\n\r\n";
        
        $body .= "--{$boundary}--";
        
        // Tentar enviar via socket SMTP
        try {
            $result = $this->sendViaSMTP($to, $subject_clean, $body, $headers);
            return $result;
        } catch (Exception $e) {
            $this->last_error = $e->getMessage();
            error_log("Erro ao enviar email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove emojis do texto (alguns servidores rejeitam)
     */
    private function removeEmojis($text) {
        return preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F1E0}-\x{1F1FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $text);
    }
    
    /**
     * Envia via SMTP socket direto
     */
    private function sendViaSMTP($to, $subject, $body, $headers) {
        // Abrir conexão SSL
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        $socket = @stream_socket_client(
            "ssl://{$this->smtp_host}:{$this->smtp_port}",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$socket) {
            throw new Exception("Não foi possível conectar ao servidor SMTP: $errstr ($errno)");
        }
        
        // Ler resposta de boas-vindas
        $response = $this->getResponse($socket);
        if (substr($response, 0, 3) != '220') {
            fclose($socket);
            throw new Exception("Falha na conexão SMTP: $response");
        }
        
        // EHLO
        $this->sendCommand($socket, "EHLO " . gethostname());
        $response = $this->getResponse($socket);
        
        // AUTH LOGIN
        $this->sendCommand($socket, "AUTH LOGIN");
        $response = $this->getResponse($socket);
        if (substr($response, 0, 3) != '334') {
            fclose($socket);
            throw new Exception("Falha na autenticação SMTP: $response");
        }
        
        // Enviar usuário (base64)
        $this->sendCommand($socket, base64_encode($this->smtp_user));
        $response = $this->getResponse($socket);
        
        // Enviar senha (base64)
        $this->sendCommand($socket, base64_encode($this->smtp_pass));
        $response = $this->getResponse($socket);
        if (substr($response, 0, 3) != '235') {
            fclose($socket);
            throw new Exception("Autenticação SMTP falhou: $response");
        }
        
        // MAIL FROM
        $this->sendCommand($socket, "MAIL FROM:<{$this->from_email}>");
        $response = $this->getResponse($socket);
        if (substr($response, 0, 3) != '250') {
            fclose($socket);
            throw new Exception("MAIL FROM falhou: $response");
        }
        
        // RCPT TO
        $this->sendCommand($socket, "RCPT TO:<{$to}>");
        $response = $this->getResponse($socket);
        if (substr($response, 0, 3) != '250') {
            fclose($socket);
            throw new Exception("RCPT TO falhou: $response");
        }
        
        // DATA
        $this->sendCommand($socket, "DATA");
        $response = $this->getResponse($socket);
        if (substr($response, 0, 3) != '354') {
            fclose($socket);
            throw new Exception("DATA falhou: $response");
        }
        
        // Construir mensagem completa (headers já contém To e Subject)
        $message = implode("\r\n", $headers) . "\r\n\r\n";
        $message .= $body . "\r\n.\r\n";
        
        fwrite($socket, $message);
        $response = $this->getResponse($socket);
        if (substr($response, 0, 3) != '250') {
            fclose($socket);
            throw new Exception("Envio da mensagem falhou: $response");
        }
        
        // QUIT
        $this->sendCommand($socket, "QUIT");
        fclose($socket);
        
        return true;
    }
    
    private function sendCommand($socket, $command) {
        fwrite($socket, $command . "\r\n");
    }
    
    private function getResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            // Se a linha começa com código e espaço, é a última linha
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }
        return trim($response);
    }
    
    /**
     * Gera HTML bonito para email de recuperação de senha
     */
    public static function gerarHtmlRecuperacaoSenha($nome, $nova_senha, $tipo = 'cliente') {
        $titulo = $tipo === 'admin' ? 'Painel Administrativo' : 'PediMais';
        
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background-color: #ffffff;">
        <tr>
            <td style="background: linear-gradient(135deg, #9C27B0 0%, #7B1FA2 100%); padding: 30px; text-align: center;">
                <h1 style="color: #ffffff; margin: 0; font-size: 24px;">🔐 Recuperação de Senha</h1>
                <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 14px;">' . $titulo . '</p>
            </td>
        </tr>
        <tr>
            <td style="padding: 30px;">
                <p style="color: #333; font-size: 16px; margin: 0 0 20px 0;">Olá, <strong>' . htmlspecialchars($nome) . '</strong>!</p>
                
                <p style="color: #666; font-size: 14px; line-height: 1.6; margin: 0 0 20px 0;">
                    Recebemos uma solicitação de recuperação de senha para sua conta. 
                    Sua nova senha é:
                </p>
                
                <div style="background: #f8f9fa; border: 2px dashed #9C27B0; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0;">
                    <p style="color: #666; font-size: 12px; margin: 0 0 10px 0; text-transform: uppercase;">Nova Senha</p>
                    <p style="color: #9C27B0; font-size: 28px; font-weight: bold; margin: 0; letter-spacing: 2px;">' . htmlspecialchars($nova_senha) . '</p>
                </div>
                
                <p style="color: #e74c3c; font-size: 14px; margin: 20px 0;">
                    ⚠️ <strong>Importante:</strong> Recomendamos alterar esta senha após o primeiro login.
                </p>
                
                <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                
                <p style="color: #999; font-size: 12px; margin: 0; text-align: center;">
                    Se você não solicitou esta recuperação, ignore este e-mail.<br>
                    Sua senha anterior permanecerá inalterada.
                </p>
            </td>
        </tr>
        <tr>
            <td style="background-color: #f8f9fa; padding: 20px; text-align: center;">
                <p style="color: #999; font-size: 12px; margin: 0;">
                    Este é um e-mail automático, não responda.<br>
                    © ' . date('Y') . ' ' . $titulo . '
                </p>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
}
