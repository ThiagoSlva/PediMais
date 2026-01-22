<?php
/**
 * AI Menu Analysis API
 * Supports both Google Gemini and OpenAI for analyzing menu images
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

// Get AI config
$ai_config = $pdo->query("SELECT * FROM ai_config WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

if (!$ai_config) {
    echo json_encode(['success' => false, 'message' => 'Configuração de IA não encontrada']);
    exit;
}

if (!$ai_config['ativo']) {
    echo json_encode(['success' => false, 'message' => 'Integração de IA está desativada']);
    exit;
}

$provider = $ai_config['provider'] ?? 'gemini';

// Check if image was uploaded
if (!isset($_FILES['menu_image']) || $_FILES['menu_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Nenhuma imagem enviada']);
    exit;
}

$image_file = $_FILES['menu_image'];
$allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

if (!in_array($image_file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de imagem não suportado']);
    exit;
}

// Read image and convert to base64
$image_data = file_get_contents($image_file['tmp_name']);
$base64_image = base64_encode($image_data);
$mime_type = $image_file['type'];

// Build the prompt
$prompt = <<<PROMPT
Analise esta imagem de cardápio e extraia TODOS os pratos/itens listados.

Para cada item, identifique:
1. Nome do prato
2. Descrição (se houver)
3. Preços para cada tamanho disponível (M = Médio, G = Grande, P = Pequeno, etc.)

IMPORTANTE:
- Se houver preços diferentes para tamanhos (M, G, P), liste CADA tamanho com seu preço
- M significa "Médio", G significa "Grande", P significa "Pequeno"
- Se só houver um preço, considere como tamanho único

Retorne SOMENTE um JSON válido no seguinte formato (sem markdown, sem explicações):
{
    "items": [
        {
            "nome": "Nome do Prato",
            "descricao": "Descrição se houver",
            "tamanhos": [
                {"tamanho": "M", "preco": 25.00},
                {"tamanho": "G", "preco": 30.00}
            ]
        }
    ]
}

Se o prato só tiver um preço sem tamanho especificado:
{
    "nome": "Nome do Prato",
    "descricao": "",
    "tamanhos": [
        {"tamanho": "Único", "preco": 25.00}
    ]
}
PROMPT;

// Call the appropriate API based on provider
if ($provider === 'openai') {
    $result = callOpenAI($ai_config, $base64_image, $mime_type, $prompt);
} else {
    $result = callGemini($ai_config, $base64_image, $mime_type, $prompt);
}

echo json_encode($result);

/**
 * Call Google Gemini API
 */
function callGemini($config, $base64_image, $mime_type, $prompt) {
    $api_key = $config['gemini_api_key'] ?? '';
    $modelo = $config['gemini_modelo'] ?? 'gemini-2.0-flash';
    
    if (empty($api_key)) {
        return ['success' => false, 'message' => 'API Key do Gemini não configurada'];
    }
    
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent?key=" . $api_key;
    
    $payload = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt],
                    [
                        'inline_data' => [
                            'mime_type' => $mime_type,
                            'data' => $base64_image
                        ]
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.1,
            'topP' => 0.8,
            'maxOutputTokens' => 8192
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 120,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        return ['success' => false, 'message' => 'Erro de conexão: ' . $curl_error];
    }
    
    if ($http_code !== 200) {
        $error = json_decode($response, true);
        $error_msg = $error['error']['message'] ?? 'Erro desconhecido';
        return ['success' => false, 'message' => "Erro Gemini ($http_code): $error_msg"];
    }
    
    $result = json_decode($response, true);
    
    if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        return ['success' => false, 'message' => 'Resposta inválida do Gemini'];
    }
    
    return parseAIResponse($result['candidates'][0]['content']['parts'][0]['text']);
}

/**
 * Call OpenAI API
 */
function callOpenAI($config, $base64_image, $mime_type, $prompt) {
    $api_key = $config['openai_api_key'] ?? '';
    $modelo = $config['openai_modelo'] ?? 'gpt-4o-mini';
    
    if (empty($api_key)) {
        return ['success' => false, 'message' => 'API Key do OpenAI não configurada'];
    }
    
    $url = "https://api.openai.com/v1/chat/completions";
    
    $payload = [
        'model' => $modelo,
        'messages' => [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $prompt
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => "data:{$mime_type};base64,{$base64_image}"
                        ]
                    ]
                ]
            ]
        ],
        'max_tokens' => 4096,
        'temperature' => 0.1
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
        CURLOPT_TIMEOUT => 120,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        return ['success' => false, 'message' => 'Erro de conexão: ' . $curl_error];
    }
    
    if ($http_code !== 200) {
        $error = json_decode($response, true);
        $error_msg = $error['error']['message'] ?? 'Erro desconhecido';
        return ['success' => false, 'message' => "Erro OpenAI ($http_code): $error_msg"];
    }
    
    $result = json_decode($response, true);
    
    if (!isset($result['choices'][0]['message']['content'])) {
        return ['success' => false, 'message' => 'Resposta inválida do OpenAI'];
    }
    
    return parseAIResponse($result['choices'][0]['message']['content']);
}

/**
 * Parse AI response and extract menu items
 */
function parseAIResponse($text_response) {
    // Clean the response - remove markdown code blocks if present
    $text_response = preg_replace('/```json\s*/', '', $text_response);
    $text_response = preg_replace('/```\s*/', '', $text_response);
    $text_response = trim($text_response);
    
    // Try to parse JSON
    $parsed = json_decode($text_response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Try to extract JSON from the response
        if (preg_match('/\{[\s\S]*\}/', $text_response, $matches)) {
            $parsed = json_decode($matches[0], true);
        }
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false, 
                'message' => 'Não foi possível interpretar a resposta da IA',
                'raw_response' => $text_response
            ];
        }
    }
    
    // Validate structure
    if (!isset($parsed['items']) || !is_array($parsed['items'])) {
        return [
            'success' => false,
            'message' => 'Formato de resposta inválido',
            'parsed' => $parsed
        ];
    }
    
    return [
        'success' => true,
        'message' => 'Cardápio analisado com sucesso!',
        'items' => $parsed['items'],
        'total_items' => count($parsed['items'])
    ];
}
