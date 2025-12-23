<?php
declare(strict_types=1);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (!isset($_GET['cep'])) {
    echo json_encode(['erro' => 'CEP não fornecido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$cep = preg_replace('/[^0-9]/', '', $_GET['cep']);

if (strlen($cep) !== 8) {
    echo json_encode(['erro' => 'CEP inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $url = "https://viacep.com.br/ws/{$cep}/json/";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        echo json_encode(['erro' => 'Erro ao consultar CEP'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['erro'])) {
        echo json_encode(['erro' => 'CEP não encontrado'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    echo json_encode([
        'cep' => $data['cep'] ?? '',
        'logradouro' => $data['logradouro'] ?? '',
        'complemento' => $data['complemento'] ?? '',
        'bairro' => $data['bairro'] ?? '',
        'localidade' => $data['localidade'] ?? '',
        'uf' => $data['uf'] ?? '',
        'ibge' => $data['ibge'] ?? '',
        'gia' => $data['gia'] ?? '',
        'ddd' => $data['ddd'] ?? '',
        'siafi' => $data['siafi'] ?? ''
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Erro ao buscar CEP: " . $e->getMessage());
    echo json_encode(['erro' => 'Erro ao consultar CEP'], JSON_UNESCAPED_UNICODE);
}
?>
