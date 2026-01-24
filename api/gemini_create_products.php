<?php
/**
 * API to create products from AI analysis
 * Now includes automatic image search for each product
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

$categoria_nome = trim($data['categoria_nome'] ?? '');
$items = $data['items'] ?? [];
$buscar_imagens = $data['buscar_imagens'] ?? true;

if (empty($categoria_nome)) {
    echo json_encode(['success' => false, 'message' => 'Nome da categoria é obrigatório']);
    exit;
}

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Nenhum item para criar']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Search for category image if enabled (use category name or first product name)
    $categoria_imagem = null;
    // Default image path (relative to site root, as expected by frontend)
    $default_category_image = 'admin/uploads/categorias/default_category.png';
    
    if ($buscar_imagens) {
        // Try with category name first
        $categoria_imagem = searchAndDownloadCategoryImage($categoria_nome);
        
        // If not found and we have items, try with first product name
        if (!$categoria_imagem && !empty($items[0]['nome'])) {
            $categoria_imagem = searchAndDownloadCategoryImage($items[0]['nome']);
        }
    }
    
    // Use default image if none found
    if (!$categoria_imagem) {
        $categoria_imagem = $default_category_image;
    }
    
    // Create category with image
    $stmt = $pdo->prepare("INSERT INTO categorias (nome, imagem, ativo) VALUES (?, ?, 1)");
    $stmt->execute([$categoria_nome, $categoria_imagem]);
    $categoria_id = $pdo->lastInsertId();
    
    $produtos_criados = 0;
    $opcoes_criadas = 0;
    $imagens_encontradas = 0;
    $categoria_com_imagem = $categoria_imagem ? true : false;
    
    foreach ($items as $item) {
        $nome = trim($item['nome'] ?? '');
        $descricao = trim($item['descricao'] ?? '');
        $tamanhos = $item['tamanhos'] ?? [];
        
        if (empty($nome) || empty($tamanhos)) {
            continue;
        }
        
        // Sort sizes to get the smallest price as base
        usort($tamanhos, function($a, $b) {
            return $a['preco'] <=> $b['preco'];
        });
        
        $preco_base = $tamanhos[0]['preco'];
        $tamanho_base = $tamanhos[0]['tamanho'];
        
        // Search for image if enabled
        $imagem_path = null;
        if ($buscar_imagens) {
            $imagem_path = searchAndDownloadImage($nome);
            if ($imagem_path) {
                $imagens_encontradas++;
            }
        }
        
        // Create product with base price and image
        $stmt = $pdo->prepare("
            INSERT INTO produtos (categoria_id, nome, descricao, preco, imagem_path, disponivel, ativo, ordem) 
            VALUES (?, ?, ?, ?, ?, 1, 1, 0)
        ");
        $stmt->execute([$categoria_id, $nome, $descricao, $preco_base, $imagem_path]);
        $produto_id = $pdo->lastInsertId();
        $produtos_criados++;
        
        // If there are multiple sizes, create grupo_adicional with items
        if (count($tamanhos) > 1) {
            // Create a group for this product's sizes
            $grupo_nome = "Tamanho - " . $nome;
            $stmt = $pdo->prepare("
                INSERT INTO grupos_adicionais (nome, tipo_escolha, minimo_escolha, maximo_escolha, obrigatorio, ordem, ativo) 
                VALUES (?, 'unico', 1, 1, 1, 0, 1)
            ");
            $stmt->execute([$grupo_nome]);
            $grupo_id = $pdo->lastInsertId();
            
            // Link group to product
            $stmt = $pdo->prepare("
                INSERT INTO produto_grupos (produto_id, grupo_id, ordem) 
                VALUES (?, ?, 0)
            ");
            $stmt->execute([$produto_id, $grupo_id]);
            
            // Create group items for each size
            $ordem_item = 0;
            foreach ($tamanhos as $tam) {
                $tamanho_nome = $tam['tamanho'];
                $preco_adicional = $tam['preco'] - $preco_base;
                
                // Map size codes to full names
                $tamanho_labels = [
                    'P' => 'Pequeno (P)',
                    'M' => 'Médio (M)',
                    'G' => 'Grande (G)',
                    'GG' => 'Extra Grande (GG)',
                    'Único' => 'Tamanho Único'
                ];
                
                $tamanho_label = $tamanho_labels[$tamanho_nome] ?? $tamanho_nome;
                
                $stmt = $pdo->prepare("
                    INSERT INTO grupo_adicional_itens (grupo_id, nome, preco_adicional, ordem, ativo) 
                    VALUES (?, ?, ?, ?, 1)
                ");
                $stmt->execute([$grupo_id, $tamanho_label, $preco_adicional, $ordem_item]);
                $opcoes_criadas++;
                $ordem_item++;
            }
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Importação concluída!",
        'categoria_id' => $categoria_id,
        'categoria_nome' => $categoria_nome,
        'produtos_criados' => $produtos_criados,
        'opcoes_criadas' => $opcoes_criadas,
        'imagens_encontradas' => $imagens_encontradas,
        'categoria_com_imagem' => $categoria_com_imagem
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao criar produtos: ' . $e->getMessage()
    ]);
}

/**
 * Search for an image and download it locally
 */
function searchAndDownloadImage($product_name) {
    // Try Bing first
    $images = searchBingImages($product_name);
    
    // Fallback to Unsplash
    if (empty($images)) {
        $images = getUnsplashImages($product_name);
    }
    
    if (empty($images)) {
        return null;
    }
    
    // Try to download the first valid image
    foreach ($images as $image) {
        $url = $image['url'];
        $local_path = downloadImage($url, $product_name, 'produtos');
        if ($local_path) {
            return $local_path;
        }
    }
    
    return null;
}

/**
 * Search for a category image and download it locally
 */
function searchAndDownloadCategoryImage($category_name) {
    // Try Bing with food category keywords
    $images = searchBingImages($category_name . ' comida menu');
    
    // Fallback to Unsplash
    if (empty($images)) {
        $images = getUnsplashImages($category_name);
    }
    
    if (empty($images)) {
        return null;
    }
    
    // Try to download the first valid image
    foreach ($images as $image) {
        $url = $image['url'];
        $local_path = downloadImage($url, $category_name, 'categorias');
        if ($local_path) {
            return $local_path;
        }
    }
    
    return null;
}

/**
 * Download image from URL and save locally
 */
function downloadImage($url, $item_name, $type = 'produtos') {
    $upload_dir = __DIR__ . '/../admin/uploads/' . $type . '/';
    
    // Create directory if not exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $extension = 'jpg';
    if (preg_match('/\.(jpg|jpeg|png|webp|gif)/i', $url, $matches)) {
        $ext = strtolower($matches[1]);
        $extension = ($ext === 'jpeg') ? 'jpg' : $ext;
    }
    
    $prefix = $type === 'categorias' ? 'cat_' : 'prod_';
    $filename = $prefix . time() . '_' . mt_rand(1000, 9999) . '.' . $extension;
    $full_path = $upload_dir . $filename;
    // Both need full path from site root
    $relative_path = 'admin/uploads/' . $type . '/' . $filename;
    
    // Download the image
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Accept: image/webp,image/apng,image/*,*/*;q=0.8',
        ]
    ]);
    
    $image_data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    
    if ($http_code !== 200 || empty($image_data)) {
        return null;
    }
    
    // Verify it's actually an image
    if (strpos($content_type, 'image') === false) {
        return null;
    }
    
    // Check if it's a valid image (at least 5KB to avoid placeholders)
    if (strlen($image_data) < 5000) {
        return null;
    }
    
    // Save the file
    if (file_put_contents($full_path, $image_data)) {
        return $relative_path;
    }
    
    return null;
}

/**
 * Search images on Bing
 */
function searchBingImages($query) {
    $images = [];
    $search_query = urlencode($query . ' comida prato');
    $bing_url = "https://www.bing.com/images/search?q={$search_query}&first=1&count=10&qft=+filterui:photo-photo";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $bing_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
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
        preg_match_all('/murl&quot;:&quot;(https?:\/\/[^&]+)&quot;/i', $html, $matches);
        
        if (!empty($matches[1])) {
            $urls = array_unique($matches[1]);
            $count = 0;
            foreach ($urls as $url) {
                if ($count >= 5) break;
                
                $decoded_url = html_entity_decode($url);
                $decoded_url = str_replace('\\u002f', '/', $decoded_url);
                
                if (preg_match('/\.(jpg|jpeg|png|webp)/i', $decoded_url) && 
                    strpos($decoded_url, 'placeholder') === false &&
                    strpos($decoded_url, 'via.placeholder') === false) {
                    $images[] = ['url' => $decoded_url];
                    $count++;
                }
            }
        }
    }
    
    return $images;
}

/**
 * Fallback: Unsplash Source CDN
 */
function getUnsplashImages($query) {
    $search_terms = urlencode($query . ' food');
    return [
        ['url' => "https://source.unsplash.com/600x400/?{$search_terms}&sig=" . mt_rand(1, 100)]
    ];
}
