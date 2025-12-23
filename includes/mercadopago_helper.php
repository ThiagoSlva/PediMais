<?php
class MercadoPagoHelper {
    private $access_token;
    private $public_key;
    private $sandbox;

    public function __construct($pdo) {
        $stmt = $pdo->query("SELECT * FROM mercadopago_config LIMIT 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($config) {
            $this->access_token = $config['access_token'];
            $this->public_key = $config['public_key'];
            $this->sandbox = $config['sandbox_mode'];
        }
    }

    public function isConfigured() {
        return !empty($this->access_token) && !empty($this->public_key);
    }

    public function createPayment($pedido_id, $valor, $cliente_email, $cliente_nome, $descricao, $prazo_minutos = 30) {
        if (!$this->isConfigured()) return ['error' => 'Mercado Pago não configurado'];

        $url = "https://api.mercadopago.com/v1/payments";
        
        // Calcular data de expiração
        $expiration_date = date('Y-m-d\TH:i:s.000P', strtotime("+$prazo_minutos minutes"));

        $data = [
            "transaction_amount" => (float)$valor,
            "description" => $descricao,
            "payment_method_id" => "pix",
            "payer" => [
                "email" => $cliente_email ?: 'cliente@email.com', // Email é obrigatório, usar dummy se vazio
                "first_name" => $cliente_nome,
            ],
            "date_of_expiration" => $expiration_date,
            "external_reference" => (string)$pedido_id
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->access_token,
            "X-Idempotency-Key: " . uniqid('', true)
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($http_code == 201 && isset($result['id'])) {
            return [
                'success' => true,
                'payment_id' => $result['id'],
                'qr_code' => $result['point_of_interaction']['transaction_data']['qr_code'],
                'qr_code_base64' => $result['point_of_interaction']['transaction_data']['qr_code_base64'],
                'ticket_url' => $result['point_of_interaction']['transaction_data']['ticket_url'],
                'status' => $result['status']
            ];
        } else {
            return [
                'success' => false, 
                'error' => isset($result['message']) ? $result['message'] : 'Erro desconhecido',
                'details' => $result
            ];
        }
    }

    public function getPaymentStatus($payment_id) {
        if (!$this->isConfigured()) return ['error' => 'Mercado Pago não configurado'];

        $url = "https://api.mercadopago.com/v1/payments/$payment_id";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->access_token
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            return json_decode($response, true);
        } else {
            return null;
        }
    }
}
?>