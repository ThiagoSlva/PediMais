<?php
/**
 * OpenAI API Test Endpoint
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$api_key = $data['api_key'] ?? '';
$modelo = $data['modelo'] ?? 'gpt-4o-mini';

if (empty($api_key)) {
    echo json_encode(['success' => false, 'message' => 'API Key não fornecida']);
    exit;
}

// Validate model
$modelos_validos = ['gpt-4o-mini', 'gpt-4o', 'gpt-4-turbo', 'gpt-4-vision-preview', 'gpt-3.5-turbo'];
if (!in_array($modelo, $modelos_validos)) {
    $modelo = 'gpt-4o-mini';
}

// Test with a simple request to OpenAI
$url = "https://api.openai.com/v1/chat/completions";

$payload = [
    'model' => $modelo,
    'messages' => [
        [
            'role' => 'user',
            'content' => 'Responda apenas com: OK'
        ]
    ],
    'max_tokens' => 10
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message'])) {
        echo json_encode([
            'success' => true, 
            'message' => "Modelo {$modelo} funcionando corretamente!"
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Resposta inesperada da API'
        ]);
    }
} else {
    $error = json_decode($response, true);
    $error_msg = $error['error']['message'] ?? 'Erro desconhecido';
    echo json_encode([
        'success' => false, 
        'message' => "Erro ($http_code): $error_msg"
    ]);
}
