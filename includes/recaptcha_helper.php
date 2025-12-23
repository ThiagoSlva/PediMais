<?php
// recaptcha_helper.php - Recreated
// Handles Google reCAPTCHA verification

class ReCaptchaHelper {
    private $pdo;
    private $config;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadConfig();
    }

    private function loadConfig() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM configuracao_recaptcha LIMIT 1");
            $this->config = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->config = null;
        }
    }

    public function isEnabled($context = 'login_cliente') {
        if (!$this->config || !$this->config['ativo']) return false;
        
        // Check specific context if column exists
        // Assuming columns like 'ativo_login_admin', 'ativo_login_cliente', etc.
        // Based on walkthrough, these columns exist.
        $column = 'ativo_' . $context;
        return isset($this->config[$column]) && $this->config[$column];
    }

    public function getSiteKey() {
        return $this->config['site_key'] ?? '';
    }

    public function verify($response) {
        if (!$this->isEnabled()) return true; // If disabled, always pass

        $secret = $this->config['secret_key'];
        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
        
        $data = [
            'secret' => $secret,
            'response' => $response
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($verifyUrl, false, $context);
        $json = json_decode($result, true);

        return isset($json['success']) && $json['success'];
    }
}
?>
