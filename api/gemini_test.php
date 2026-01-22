<?php
/**
 * Gemini API Test Endpoint
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$api_key = $data['api_key'] ?? '';
$modelo = $data['modelo'] ?? 'gemini-2.0-flash';

if (empty($api_key)) {
    echo json_encode(['success' => false, 'message' => 'API Key não fornecida']);
    exit;
}

// Validate model name (prevent injection)
$modelos_validos = [
    'gemini-2.0-flash', 'gemini-2.0-flash-exp', 
    'gemini-1.5-flash', 'gemini-1.5-flash-latest',
    'gemini-1.5-pro', 'gemini-1.5-pro-latest',
    'gemini-pro', 'gemini-pro-vision'
];

if (!in_array($modelo, $modelos_validos)) {
    $modelo = 'gemini-2.0-flash';
}

// Test with a simple request to Gemini
$url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent?key=" . $api_key;

$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => 'Responda apenas com: OK']
            ]
        ]
    ]
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $result = json_decode($response, true);
    if (isset($result['candidates'][0]['content'])) {
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
