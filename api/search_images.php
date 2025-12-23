<?php
/**
 * Busca de imagens na web para produtos
 * Usa Google Custom Search (emulação via scraping)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Termo de busca
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query)) {
    echo json_encode(['success' => false, 'error' => 'Termo de busca não informado']);
    exit;
}

$images = [];

// Tentar buscar no Bing
$images = searchBingImages($query);

// Se Bing falhar, tentar Pexels (sem API key - scraping)
if (empty($images)) {
    $images = searchPexels($query);
}

// Se ainda não encontrou, usar Unsplash Source (CDN direto - sempre funciona)
if (empty($images)) {
    $images = getUnsplashImages($query);
}

echo json_encode([
    'success' => true,
    'query' => $query,
    'count' => count($images),
    'images' => $images
]);

/**
 * Busca imagens no Bing
 */
function searchBingImages($query) {
    $images = [];
    $search_query = urlencode($query . ' food dish');
    $bing_url = "https://www.bing.com/images/search?q={$search_query}&first=1&count=30&qft=+filterui:photo-photo";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $bing_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
        ],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && !empty($html)) {
        // Método 1: Extrair URLs do JSON embutido
        preg_match_all('/murl&quot;:&quot;(https?:\/\/[^&]+)&quot;/i', $html, $matches);
        
        if (!empty($matches[1])) {
            $urls = array_unique($matches[1]);
            $count = 0;
            foreach ($urls as $url) {
                if ($count >= 20) break;
                
                $decoded_url = html_entity_decode($url);
                $decoded_url = str_replace('\\u002f', '/', $decoded_url);
                
                // Filtrar apenas imagens válidas e evitar placeholders
                if (preg_match('/\.(jpg|jpeg|png|webp)/i', $decoded_url) && 
                    strpos($decoded_url, 'placeholder') === false &&
                    strpos($decoded_url, 'picsum') === false &&
                    strpos($decoded_url, 'via.placeholder') === false) {
                    $images[] = [
                        'url' => $decoded_url,
                        'thumb' => $decoded_url,
                        'title' => $query . ' - Imagem ' . ($count + 1)
                    ];
                    $count++;
                }
            }
        }
    }
    
    return $images;
}

/**
 * Busca imagens no Pexels (scraping)
 */
function searchPexels($query) {
    $images = [];
    $search_query = urlencode($query);
    $pexels_url = "https://www.pexels.com/search/{$search_query}/";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $pexels_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    $html = curl_exec($ch);
    curl_close($ch);
    
    if (!empty($html)) {
        // Extrair URLs de imagens do Pexels
        preg_match_all('/https:\/\/images\.pexels\.com\/photos\/[^\s"\']+/i', $html, $matches);
        
        if (!empty($matches[0])) {
            $urls = array_unique($matches[0]);
            $count = 0;
            foreach ($urls as $url) {
                if ($count >= 20) break;
                
                // Limpar URL e pegar versão menor
                $clean_url = preg_replace('/\?.*$/', '', $url);
                if (strpos($clean_url, '.jpeg') !== false || strpos($clean_url, '.png') !== false) {
                    $images[] = [
                        'url' => $clean_url . '?auto=compress&cs=tinysrgb&w=600',
                        'thumb' => $clean_url . '?auto=compress&cs=tinysrgb&w=200',
                        'title' => $query . ' - Pexels ' . ($count + 1)
                    ];
                    $count++;
                }
            }
        }
    }
    
    return $images;
}

/**
 * Fallback: Unsplash Source CDN (sempre funciona, sem API key)
 */
function getUnsplashImages($query) {
    $images = [];
    $search_terms = urlencode($query);
    
    // Gerar várias imagens do Unsplash Source
    for ($i = 1; $i <= 20; $i++) {
        // Cada URL com sig diferente retorna imagem diferente
        $images[] = [
            'url' => "https://source.unsplash.com/600x400/?{$search_terms}&sig={$i}",
            'thumb' => "https://source.unsplash.com/200x150/?{$search_terms}&sig={$i}",
            'title' => $query . ' - Unsplash ' . $i
        ];
    }
    
    return $images;
}
