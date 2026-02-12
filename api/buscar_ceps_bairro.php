<?php
/**
 * API: Buscar CEPs por Bairro e Cidade
 * Busca CEPs de um bairro específico via ViaCEP
 */

declare(strict_types = 1)
;
header('Content-Type: application/json; charset=utf-8');
$allowed_origin = defined('SITE_URL') ? SITE_URL : '*';
header("Access-Control-Allow-Origin: $allowed_origin");

$bairro = $_GET['bairro'] ?? '';
$cidade = $_GET['cidade'] ?? '';
$uf = $_GET['uf'] ?? '';

if (empty($bairro) || empty($cidade) || empty($uf)) {
    echo json_encode([
        'erro' => true,
        'mensagem' => 'Parâmetros insuficientes. Informe bairro, cidade e UF.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Sanitizar inputs
$bairro = trim($bairro);
$cidade = trim($cidade);
$uf = strtoupper(trim($uf));

// Validar UF
if (strlen($uf) !== 2) {
    echo json_encode([
        'erro' => true,
        'mensagem' => 'UF inválida. Use 2 letras (ex: SP, RJ)'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Buscar no ViaCEP
$url = "https://viacep.com.br/ws/{$uf}/{$cidade}/{$bairro}/json/";
error_log("🔍 Buscando CEPs: {$url}");

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    error_log("❌ Erro cURL: {$error}");
    echo json_encode([
        'erro' => true,
        'mensagem' => 'Erro ao buscar CEPs. Tente novamente.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($httpCode !== 200) {
    error_log("❌ HTTP {$httpCode}: {$response}");
    echo json_encode([
        'erro' => true,
        'mensagem' => 'Erro ao buscar CEPs (HTTP ' . $httpCode . ')'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("❌ JSON inválido: " . json_last_error_msg());
    echo json_encode([
        'erro' => true,
        'mensagem' => 'Resposta inválida da API'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ViaCEP retorna array de CEPs ou objeto com erro
if (isset($data['erro']) && $data['erro'] === true) {
    echo json_encode([
        'erro' => true,
        'mensagem' => 'Nenhum CEP encontrado para este bairro/cidade.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Filtrar apenas CEPs únicos
$ceps = [];
if (is_array($data)) {
    foreach ($data as $endereco) {
        if (!empty($endereco['cep'])) {
            $cep = str_replace('-', '', $endereco['cep']);
            $ceps[$cep] = [
                'cep' => $endereco['cep'],
                'logradouro' => $endereco['logradouro'] ?? '',
                'complemento' => $endereco['complemento'] ?? '',
                'bairro' => $endereco['bairro'] ?? $bairro,
                'localidade' => $endereco['localidade'] ?? $cidade,
                'uf' => $endereco['uf'] ?? $uf
            ];
        }
    }
}

$ceps = array_values($ceps); // Reindexar

error_log("✅ Encontrados " . count($ceps) . " CEPs para {$bairro}/{$cidade}-{$uf}");

echo json_encode([
    'erro' => false,
    'bairro' => $bairro,
    'cidade' => $cidade,
    'uf' => $uf,
    'total' => count($ceps),
    'ceps' => $ceps
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
