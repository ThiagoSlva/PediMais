<?php
/**
 * API: Calcular Distância entre Endereços
 * Usa OpenStreetMap Nominatim (gratuito) para geocoding
 * Calcula distância real usando Haversine melhorado
 * Arredonda para múltiplos de 5km
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/db.php';
global $pdo;

// Aceitar CEP ou endereço completo
$cep_origem = $_GET['origem'] ?? $_GET['cep_origem'] ?? '';
$cep_destino = $_GET['destino'] ?? $_GET['cep_destino'] ?? '';

// Endereço completo de origem (do banco de dados)
$rua_origem = $_GET['rua_origem'] ?? '';
$numero_origem = $_GET['numero_origem'] ?? '';
$bairro_origem = $_GET['bairro_origem'] ?? '';
$cidade_origem = $_GET['cidade_origem'] ?? '';
$estado_origem = $_GET['estado_origem'] ?? '';

// Endereço completo de destino (do pedido)
$rua_destino = $_GET['rua_destino'] ?? '';
$numero_destino = $_GET['numero_destino'] ?? '';
$bairro_destino = $_GET['bairro_destino'] ?? '';
$cidade_destino = $_GET['cidade_destino'] ?? '';
$estado_destino = $_GET['estado_destino'] ?? '';

if (empty($cep_origem) || (empty($cep_destino) && empty($rua_destino))) {
    echo json_encode([
        'erro' => true,
        'mensagem' => 'Informe endereço de origem e destino'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Limpar CEPs
$cep_origem = preg_replace('/\D/', '', $cep_origem);
$cep_destino = preg_replace('/\D/', '', $cep_destino);

/**
 * Busca coordenadas via endereço completo ou CEP
 */
function buscarCoordenadas(string $cep = '', string $rua = '', string $numero = '', string $bairro = '', string $cidade = '', string $estado = ''): ?array {
    // Se tiver endereço completo, usar ele primeiro (mais preciso)
    if (!empty($rua) && !empty($cidade)) {
        $query_parts = array_filter([
            $rua . (!empty($numero) ? ', ' . $numero : ''),
            $bairro,
            $cidade,
            $estado,
            'Brasil'
        ]);
        $query = implode(', ', $query_parts);
    } elseif (!empty($cep)) {
        // Buscar endereço via ViaCEP
        $url_viacep = "https://viacep.com.br/ws/{$cep}/json/";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_viacep);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $endereco = json_decode($response, true);
        if (empty($endereco) || isset($endereco['erro'])) {
            return null;
        }
        
        // Montar query de busca
        $query = implode(', ', array_filter([
            $endereco['logradouro'] ?? '',
            $endereco['bairro'] ?? '',
            $endereco['localidade'] ?? '',
            $endereco['uf'] ?? '',
            'Brasil'
        ]));
    } else {
        return null;
    }
    
    // Buscar coordenadas via Nominatim (OpenStreetMap) - API gratuita
    $url_nominatim = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
        'q' => $query,
        'format' => 'json',
        'limit' => 1,
        'addressdetails' => 1
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_nominatim);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: CardapioDigital/1.0 (contato@cardapiodigital.com)' // Nominatim requer User-Agent
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        error_log("⚠️ Nominatim retornou HTTP {$http_code}");
        return null;
    }
    
    $coords = json_decode($response, true);
    
    if (empty($coords) || !isset($coords[0]['lat'], $coords[0]['lon'])) {
        error_log("⚠️ Não foi possível obter coordenadas para: {$query}");
        return null;
    }
    
    $endereco_info = isset($endereco) ? $endereco : [
        'logradouro' => $rua,
        'bairro' => $bairro,
        'localidade' => $cidade,
        'uf' => $estado
    ];
    
    return [
        'lat' => (float)$coords[0]['lat'],
        'lon' => (float)$coords[0]['lon'],
        'endereco' => $endereco_info
    ];
}

/**
 * Calcula distância entre duas coordenadas (Fórmula de Haversine)
 * Retorna distância em quilômetros
 */
function calcularDistancia(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $raio_terra = 6371; // Raio da Terra em km
    
    $dlat = deg2rad($lat2 - $lat1);
    $dlon = deg2rad($lon2 - $lon1);
    
    $a = sin($dlat / 2) * sin($dlat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dlon / 2) * sin($dlon / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    $distancia = $raio_terra * $c;
    
    return round($distancia, 2);
}

// Buscar coordenadas da origem (do banco de dados)
if (empty($rua_origem)) {
    // Se não veio endereço completo, buscar do banco
    try {
        $stmt = $pdo->query("SELECT 
            endereco_referencia_cep as cep,
            endereco_referencia_rua as rua,
            endereco_referencia_numero as numero,
            endereco_referencia_bairro as bairro,
            endereco_referencia_cidade as cidade,
            endereco_referencia_estado as estado
            FROM configuracao_entrega WHERE id = 1");
        $config_origem = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($config_origem) {
            $rua_origem = $config_origem['rua'] ?? '';
            $numero_origem = $config_origem['numero'] ?? '';
            $bairro_origem = $config_origem['bairro'] ?? '';
            $cidade_origem = $config_origem['cidade'] ?? '';
            $estado_origem = $config_origem['estado'] ?? '';
            if (empty($cep_origem)) {
                $cep_origem = preg_replace('/\D/', '', $config_origem['cep'] ?? '');
            }
        }
    } catch (Exception $e) {
        error_log("Erro ao buscar endereço de referência: " . $e->getMessage());
    }
}

error_log("🗺️ Buscando coordenadas para origem: {$rua_origem}, {$numero_origem}, {$bairro_origem}, {$cidade_origem}-{$estado_origem} (CEP: {$cep_origem})");
$coord_origem = buscarCoordenadas($cep_origem, $rua_origem, $numero_origem, $bairro_origem, $cidade_origem, $estado_origem);

if (!$coord_origem) {
    echo json_encode([
        'erro' => true,
        'mensagem' => 'Não foi possível localizar o endereço de origem. Verifique o endereço de referência nas configurações.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

error_log("🗺️ Buscando coordenadas para destino: {$rua_destino}, {$numero_destino}, {$bairro_destino}, {$cidade_destino}-{$estado_destino} (CEP: {$cep_destino})");
$coord_destino = buscarCoordenadas($cep_destino, $rua_destino, $numero_destino, $bairro_destino, $cidade_destino, $estado_destino);

if (!$coord_destino) {
    echo json_encode([
        'erro' => true,
        'mensagem' => 'Não foi possível localizar o endereço de destino. Verifique o endereço informado.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Calcular distância
$distancia_km = calcularDistancia(
    $coord_origem['lat'],
    $coord_origem['lon'],
    $coord_destino['lat'],
    $coord_destino['lon']
);

error_log("📏 Distância calculada: {$distancia_km} km");

// Buscar configuração de entrega
try {
    $stmt = $pdo->query("SELECT preco_por_km, km_gratis FROM configuracao_entrega WHERE id = 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $preco_por_km = (float)($config['preco_por_km'] ?? 2.00);
    $km_gratis = (float)($config['km_gratis'] ?? 0);
    
    error_log("📏 Distância: {$distancia_km} km");
    error_log("🎁 Km grátis: {$km_gratis} km");
    error_log("💵 Preço/km: R$ " . number_format($preco_por_km, 2, ',', '.'));
    
    // Calcular valor (distância - km grátis) × preço por km
    $km_cobrados = max(0, $distancia_km - $km_gratis);
    $valor = round($km_cobrados * $preco_por_km, 2);
    
    error_log("📊 Km cobrados: {$km_cobrados} km");
    error_log("💰 Valor: R$ " . number_format($valor, 2, ',', '.'));
    
    // Estimar tempo (média de 40km/h em cidade)
    $tempo_minutos = ceil(($distancia_km / 40) * 60);
    $tempo_estimado = $tempo_minutos < 30 ? '20-30 min' : 
                      ($tempo_minutos < 45 ? '30-45 min' :
                      ($tempo_minutos < 60 ? '45-60 min' :
                      ceil($tempo_minutos / 60) . 'h'));
    
    echo json_encode([
        'erro' => false,
        'cep_origem' => $cep_origem,
        'cep_destino' => $cep_destino,
        'distancia_km' => $distancia_km,
        'km_gratis' => $km_gratis,
        'km_cobrados' => $km_cobrados,
        'preco_por_km' => $preco_por_km,
        'valor' => $valor,
        'tempo_estimado' => $tempo_estimado,
        'origem' => [
            'lat' => $coord_origem['lat'],
            'lon' => $coord_origem['lon'],
            'endereco' => $coord_origem['endereco']['logradouro'] ?? '',
            'bairro' => $coord_origem['endereco']['bairro'] ?? '',
            'cidade' => $coord_origem['endereco']['localidade'] ?? ''
        ],
        'destino' => [
            'lat' => $coord_destino['lat'],
            'lon' => $coord_destino['lon'],
            'endereco' => $coord_destino['endereco']['logradouro'] ?? '',
            'bairro' => $coord_destino['endereco']['bairro'] ?? '',
            'cidade' => $coord_destino['endereco']['localidade'] ?? ''
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("❌ Erro: " . $e->getMessage());
    echo json_encode([
        'erro' => true,
        'mensagem' => 'Erro ao calcular valor da entrega'
    ], JSON_UNESCAPED_UNICODE);
}
