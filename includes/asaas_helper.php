<?php
/**
 * AsaasHelper - Classe para integração com API Asaas
 * Suporta pagamento via PIX estático
 */

class AsaasHelper {
    private $access_token;
    private $address_key;
    private $sandbox;
    private $pdo;
    private $config;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->loadConfig();
    }

    private function loadConfig() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM asaas_config LIMIT 1");
            $this->config = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($this->config) {
                $this->access_token = $this->config['access_token'];
                $this->address_key = $this->config['address_key'];
                $this->sandbox = $this->config['sandbox_mode'];
            }
        } catch (Exception $e) {
            $this->config = null;
        }
    }

    /**
     * Verifica se as credenciais estão configuradas
     */
    public function isConfigured() {
        return !empty($this->access_token) && !empty($this->address_key);
    }

    /**
     * Retorna a URL base da API (sandbox ou produção)
     */
    private function getApiUrl() {
        if ($this->sandbox) {
            return 'https://sandbox.asaas.com/api/v3';
        }
        return 'https://www.asaas.com/api/v3';
    }

    /**
     * Cria um pagamento PIX estático
     * 
     * @param int $pedido_id ID do pedido
     * @param float $valor Valor do pagamento
     * @param string $descricao Descrição do pagamento
     * @param int $prazo_minutos Prazo para pagamento (padrão: 30)
     * @return array
     */
    public function createPixPayment($pedido_id, $valor, $descricao, $prazo_minutos = null) {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Asaas não configurado'];
        }

        if (empty($this->address_key)) {
            error_log("Erro PIX Asaas - Chave PIX não configurada");
            return ['success' => false, 'error' => 'Chave PIX não configurada'];
        }

        $prazo = $prazo_minutos ?? ($this->config['prazo_pagamento_minutos'] ?? 30);
        
        $url = $this->getApiUrl() . '/pix/qrCodes/static';

        $data = [
            'addressKey' => $this->address_key,
            'description' => mb_substr($descricao, 0, 37), // Asaas limita a 37 caracteres
            'value' => (float)$valor
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: PediMais/1.0',
            'access_token: ' . $this->access_token
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);

        error_log("Asaas PIX Response (HTTP $http_code): " . $response);

        // Verificar erros de cURL
        if ($response === false || $curl_errno !== 0) {
            error_log("Erro cURL Asaas (errno $curl_errno): " . $curl_error);
            return [
                'success' => false, 
                'error' => 'Erro de conexão: ' . $curl_error,
                'curl_errno' => $curl_errno,
                'raw_response' => $response
            ];
        }

        // Verificar se resposta está vazia
        if (empty($response)) {
            error_log("Erro Asaas: Resposta vazia da API (HTTP $http_code)");
            return [
                'success' => false, 
                'error' => 'Resposta vazia da API (HTTP ' . $http_code . ')',
                'http_code' => $http_code
            ];
        }

        $result = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Erro JSON Asaas: " . json_last_error_msg() . " - Resposta bruta: " . substr($response, 0, 500));
            return [
                'success' => false, 
                'error' => 'Resposta inválida da API (HTTP ' . $http_code . ')',
                'json_error' => json_last_error_msg(),
                'raw_response' => substr($response, 0, 500),
                'http_code' => $http_code
            ];
        }

        if (isset($result['id'])) {
            error_log("PIX Asaas gerado com sucesso - ID: " . $result['id']);
            
            return [
                'success' => true,
                'payment_id' => $result['id'],
                'qr_code' => $result['payload'] ?? '',
                'qr_code_base64' => $result['encodedImage'] ?? '',
                'status' => 'pending'
            ];
        } else {
            $error = isset($result['errors']) ? $result['errors'][0]['description'] : 'Erro desconhecido';
            error_log("Erro PIX Asaas: " . $error);
            return ['success' => false, 'error' => $error, 'details' => $result];
        }
    }

    /**
     * Consulta as transferências/transações PIX recebidas
     * Usado para verificar se um pagamento foi confirmado
     * 
     * @param string $payment_id ID do QR Code estático
     * @return array|null
     */
    public function getPaymentStatus($payment_id) {
        if (!$this->isConfigured()) {
            return null;
        }

        // Para PIX estático, precisamos consultar as transferências recebidas
        // e verificar se alguma corresponde ao nosso payment_id
        $url = $this->getApiUrl() . '/pix/transactions';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: PediMais/1.0',
            'access_token: ' . $this->access_token
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            $result = json_decode($response, true);
            
            // Procurar por transação que corresponda ao nosso payment_id
            if (isset($result['data']) && is_array($result['data'])) {
                foreach ($result['data'] as $transaction) {
                    if (isset($transaction['originStaticQrCode']['id']) && 
                        $transaction['originStaticQrCode']['id'] === $payment_id) {
                        return [
                            'status' => 'approved',
                            'transaction_id' => $transaction['id'] ?? null,
                            'value' => $transaction['value'] ?? 0,
                            'paidAt' => $transaction['dateCreated'] ?? null
                        ];
                    }
                }
            }
            
            // Se não encontrou, ainda está pendente
            return ['status' => 'pending'];
        }

        error_log("Erro ao consultar status Asaas (HTTP $http_code): " . $response);
        return null;
    }

    /**
     * Consulta transferências PIX por valor e data (fallback)
     * Usado quando não conseguimos identificar pelo payment_id
     */
    public function findPaymentByValueAndDate($valor, $data_criacao) {
        if (!$this->isConfigured()) {
            return null;
        }

        $url = $this->getApiUrl() . '/pix/transactions';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: PediMais/1.0',
            'access_token: ' . $this->access_token
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            $result = json_decode($response, true);
            
            if (isset($result['data']) && is_array($result['data'])) {
                foreach ($result['data'] as $transaction) {
                    // Verificar valor e se foi após a data de criação
                    if (abs($transaction['value'] - $valor) < 0.01) {
                        $trans_date = strtotime($transaction['dateCreated']);
                        $pedido_date = strtotime($data_criacao);
                        
                        if ($trans_date >= $pedido_date) {
                            return [
                                'status' => 'approved',
                                'transaction_id' => $transaction['id'] ?? null,
                                'value' => $transaction['value'] ?? 0,
                                'paidAt' => $transaction['dateCreated'] ?? null
                            ];
                        }
                    }
                }
            }
        }

        return null;
    }
}
?>
