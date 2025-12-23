<?php
/**
 * Download de imagem da web para produtos
 * Baixa a imagem e salva localmente
 */

header('Content-Type: application/json; charset=utf-8');

include '../includes/config.php';

// Receber URL da imagem
$image_url = isset($_POST['url']) ? trim($_POST['url']) : '';
$prefix = isset($_POST['prefix']) ? trim($_POST['prefix']) : 'prod_';

if (empty($image_url)) {
    echo json_encode(['success' => false, 'error' => 'URL da imagem não informada']);
    exit;
}

// Validar URL
if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
    // Verificar se é data URL (base64)
    if (strpos($image_url, 'data:image/') !== 0) {
        echo json_encode(['success' => false, 'error' => 'URL inválida']);
        exit;
    }
}

// Diretório de upload
$upload_dir = __DIR__ . '/../admin/uploads/produtos/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$filename = '';

try {
    // Verificar se é data URL (base64)
    if (strpos($image_url, 'data:image/') === 0) {
        // Extrair dados base64
        if (preg_match('/^data:image\/(\w+);base64,(.*)$/', $image_url, $matches)) {
            $extension = $matches[1];
            if ($extension === 'jpeg') $extension = 'jpg';
            
            $data = base64_decode($matches[2]);
            $filename = $prefix . time() . '_' . rand(1000, 9999) . '.' . $extension;
            
            if (file_put_contents($upload_dir . $filename, $data) === false) {
                throw new Exception('Erro ao salvar imagem base64');
            }
        }
    } else {
        // Download da URL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $image_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $image_content = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        
        if ($http_code !== 200 || empty($image_content)) {
            throw new Exception('Não foi possível baixar a imagem (HTTP ' . $http_code . ')');
        }
        
        // Verificar se é realmente uma imagem
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_buffer($finfo, $image_content);
        finfo_close($finfo);
        
        $allowed_types = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg', 
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];
        
        if (!isset($allowed_types[$mime_type])) {
            throw new Exception('Tipo de arquivo não suportado: ' . $mime_type);
        }
        
        $extension = $allowed_types[$mime_type];
        $filename = $prefix . time() . '_' . rand(1000, 9999) . '.' . $extension;
        
        if (file_put_contents($upload_dir . $filename, $image_content) === false) {
            throw new Exception('Erro ao salvar imagem');
        }
        
        // Verificar se salvou corretamente
        if (!file_exists($upload_dir . $filename) || filesize($upload_dir . $filename) < 100) {
            @unlink($upload_dir . $filename);
            throw new Exception('Arquivo salvo está corrompido');
        }
    }
    
    // Retornar sucesso
    $relative_path = 'admin/uploads/produtos/' . $filename;
    
    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'path' => $relative_path,
        'full_url' => '/' . $relative_path
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
