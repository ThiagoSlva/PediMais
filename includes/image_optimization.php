<?php
/**
 * Image Optimization & Compression Functions
 * Funções para otimizar e comprimir imagens do sistema
 * 
 * Uso:
 * $result = compressAndOptimizeImage($sourcePath, $destinationPath, 75, 1200);
 */

/**
 * Comprime e otimiza uma imagem ao fazer upload
 * Redimensiona, converte para WebP e salva como JPEG/PNG
 * 
 * @param string $source - Caminho temporário do upload
 * @param string $destination - Caminho final (sem extensão)
 * @param int $quality - Qualidade (1-100, padrão 75)
 * @param int $maxWidth - Largura máxima (padrão 1200px)
 * @param int $maxHeight - Altura máxima (padrão 1200px)
 * @return array ['success' => bool, 'file' => 'path', 'original_size' => bytes, 'compressed_size' => bytes]
 */
function compressAndOptimizeImage($source, $destination, $quality = 75, $maxWidth = 1200, $maxHeight = 1200) {
    if (!file_exists($source)) {
        return ['success' => false, 'error' => 'Arquivo não encontrado', 'file' => null];
    }

    $original_size = filesize($source);
    
    try {
        // Verificar se é imagem válida
        $info = @getimagesize($source);
        if ($info === false) {
            return ['success' => false, 'error' => 'Arquivo não é uma imagem válida', 'file' => null];
        }

        // Detectar tipo MIME
        $mime = $info['mime'];
        
        // Carregar imagem com base no tipo
        switch ($mime) {
            case 'image/jpeg':
                $image = @imagecreatefromjpeg($source);
                $source_format = 'jpeg';
                break;
            case 'image/png':
                $image = @imagecreatefrompng($source);
                $source_format = 'png';
                break;
            case 'image/gif':
                $image = @imagecreatefromgif($source);
                $source_format = 'gif';
                break;
            case 'image/webp':
                $image = @imagecreatefromwebp($source);
                $source_format = 'webp';
                break;
            default:
                return ['success' => false, 'error' => 'Tipo de imagem não suportado', 'file' => null];
        }

        if (!$image) {
            return ['success' => false, 'error' => 'Erro ao processar imagem', 'file' => null];
        }

        // Obter dimensões originais
        $width = imagesx($image);
        $height = imagesy($image);
        $aspect_ratio = $width / $height;

        // Redimensionar se necessário
        if ($width > $maxWidth || $height > $maxHeight) {
            if ($width > $height) {
                // Paisagem
                $new_width = min($maxWidth, $width);
                $new_height = round($new_width / $aspect_ratio);
            } else {
                // Retrato
                $new_height = min($maxHeight, $height);
                $new_width = round($new_height * $aspect_ratio);
            }
            
            // Criar imagem redimensionada com melhor qualidade
            $resized = imagecreatetruecolor($new_width, $new_height);
            
            // Preservar transparência para PNG
            if ($source_format === 'png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }
            
            // Redimensionar com resampling de alta qualidade
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagedestroy($image);
            $image = $resized;
            $width = $new_width;
            $height = $new_height;
        }

        // Garantir que o diretório existe
        $dest_dir = dirname($destination);
        if (!is_dir($dest_dir)) {
            mkdir($dest_dir, 0777, true);
        }

        // Salvar como JPEG (principal, máxima compatibilidade)
        $jpeg_file = $destination . '.jpg';
        $jpeg_quality = max(60, $quality); // Mínimo 60% para JPEG
        
        if (!imagejpeg($image, $jpeg_file, $jpeg_quality)) {
            imagedestroy($image);
            return ['success' => false, 'error' => 'Erro ao salvar imagem JPEG', 'file' => null];
        }

        $compressed_size = filesize($jpeg_file);

        // Tentar salvar como WebP (melhor compressão, opcional)
        $webp_file = null;
        if (function_exists('imagewebp')) {
            $webp_file = $destination . '.webp';
            @imagewebp($image, $webp_file, $quality);
            
            // Se WebP ficou maior que JPEG, remover
            if (file_exists($webp_file) && filesize($webp_file) > $compressed_size) {
                unlink($webp_file);
                $webp_file = null;
            }
        }

        imagedestroy($image);

        // Retornar caminho relativo correto para web
        // O $destination pode ser absoluto (ex: /home/user/public/uploads/cat_123)
        // ou relativo (ex: uploads/cat_123)
        // O arquivo é SEMPRE salvo COM extensão .jpg, então precisa retornar COM extensão
        
        // Normalizar separadores para compatibilidade Windows/Linux
        $destination = str_replace('\\', '/', $destination);

        // Retornar caminho relativo correto para web
        // O $destination pode ser absoluto (ex: /home/user/public/uploads/cat_123)
        // ou relativo (ex: uploads/cat_123)
        // O arquivo é SEMPRE salvo COM extensão .jpg, então precisa retornar COM extensão
        
        if (strpos($destination, '/') === 0 || (strlen($destination) > 1 && $destination[1] === ':')) {
            // É caminho absoluto (começa com / ou C:)
            // Extrair apenas a parte relativa a partir de 'uploads'
             if (strpos($destination, '/uploads/') !== false) {
                 // Trata /home/user/public_html/uploads/... e C:/.../uploads/...
                 $relative = 'uploads/' . substr($destination, strpos($destination, '/uploads/') + 9);
             } elseif (strpos($destination, '/admin/uploads/') !== false) {
                // Trata /home/user/public_html/admin/uploads/... (DEPRECATED)
                 $relative = 'uploads/' . substr($destination, strpos($destination, '/admin/uploads/') + 15);
             } else {
                 // Fallback: tentar encontrar pasta do projeto
                 $relative = basename($destination);
             }
             // Adicionar extensão para caminhos absolutos
             $best_file = $relative . '.jpg';
        } else {
             // Já é relativo
             $best_file = $destination . '.jpg';
        }
        
        // Calcular economia
        $saved_bytes = max(0, $original_size - $compressed_size);
        $compression_ratio = round(($saved_bytes / $original_size) * 100);

        return [
            'success' => true,
            'file' => $best_file,
            'original_size' => $original_size,
            'compressed_size' => $compressed_size,
            'saved_bytes' => $saved_bytes,
            'compression_ratio' => $compression_ratio,
            'new_width' => $width,
            'new_height' => $height,
            'webp_available' => $webp_file ? true : false
        ];

    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage(), 'file' => null];
    }
}

/**
 * Comprime e redimensiona uma imagem
 * 
 * @param string $source - Caminho da imagem original
 * @param string $destination - Caminho para salvar (sem extensão)
 * @param int $quality - Qualidade JPEG (1-100, padrão 75)
 * @param int $maxWidth - Largura máxima em pixels (padrão 1200)
 * @return array ['success' => bool, 'files' => [paths], 'saved' => bytes]
 */
function optimizeImage($source, $destination, $quality = 75, $maxWidth = 1200) {
    if (!file_exists($source)) {
        return ['success' => false, 'error' => 'Arquivo não encontrado'];
    }

    $original_size = filesize($source);
    $saved_bytes = 0;
    $files_created = [];

    try {
        $info = @getimagesize($source);
        if (!$info) {
            return ['success' => false, 'error' => 'Arquivo não é uma imagem válida'];
        }

        // Detectar tipo de imagem
        $mime = $info['mime'];
        
        // Carregar imagem
        switch ($mime) {
            case 'image/jpeg':
                $image = @imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $image = @imagecreatefrompng($source);
                break;
            case 'image/gif':
                $image = @imagecreatefromgif($source);
                break;
            case 'image/webp':
                $image = @imagecreatefromwebp($source);
                break;
            default:
                return ['success' => false, 'error' => 'Tipo de imagem não suportado'];
        }

        if (!$image) {
            return ['success' => false, 'error' => 'Erro ao processar imagem'];
        }

        // Redimensionar se necessário
        $width = imagesx($image);
        $height = imagesy($image);
        
        if ($width > $maxWidth || $height > $maxWidth) {
            if ($width > $height) {
                $new_width = $maxWidth;
                $new_height = ($maxWidth / $width) * $height;
            } else {
                $new_height = $maxWidth;
                $new_width = ($maxWidth / $height) * $width;
            }
            
            $resized = imagecreatetruecolor($new_width, $new_height);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        // Salvar como WebP (mais compactado)
        if (function_exists('imagewebp')) {
            $webp_file = $destination . '.webp';
            if (imagewebp($image, $webp_file, $quality)) {
                $files_created[] = $webp_file;
                $saved_bytes += max(0, $original_size - filesize($webp_file));
            }
        }

        // Salvar como JPEG (compatibilidade)
        $jpg_file = $destination . '.jpg';
        if (imagejpeg($image, $jpg_file, $quality)) {
            $files_created[] = $jpg_file;
            $saved_bytes += max(0, $original_size - filesize($jpg_file));
        }

        // Salvar como PNG se original for PNG
        if ($mime === 'image/png') {
            $png_file = $destination . '.png';
            if (imagepng($image, $png_file, 8)) { // Nível 8 = máxima compressão
                $files_created[] = $png_file;
                $saved_bytes += max(0, $original_size - filesize($png_file));
            }
        }

        imagedestroy($image);

        return [
            'success' => true,
            'files' => $files_created,
            'original_size' => $original_size,
            'saved_bytes' => $saved_bytes,
            'new_width' => $new_width ?? $width,
            'new_height' => $new_height ?? $height
        ];

    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Gera tag picture com WebP + fallback
 * 
 * @param string $imagePath - Caminho da imagem (sem extensão)
 * @param string $alt - Texto alternativo
 * @param string $class - Classes CSS
 * @param string $loading - Tipo de loading ('lazy', 'eager')
 * @return string HTML picture tag
 */
function renderOptimizedImage($imagePath, $alt = '', $class = '', $loading = 'lazy') {
    $base = pathinfo($imagePath, PATHINFO_DIRNAME) . '/' . pathinfo($imagePath, PATHINFO_FILENAME);
    
    $html = '<picture>';
    
    // WebP
    if (file_exists($base . '.webp')) {
        $html .= '<source srcset="' . htmlspecialchars($base . '.webp') . '" type="image/webp">';
    }
    
    // JPEG fallback
    if (file_exists($base . '.jpg')) {
        $src = $base . '.jpg';
    } elseif (file_exists($imagePath)) {
        $src = $imagePath;
    } else {
        $src = 'admin/assets/images/sem-foto.jpg';
    }
    
    $html .= '<img ';
    $html .= 'loading="' . $loading . '" ';
    if ($class) {
        $html .= 'class="' . htmlspecialchars($class) . '" ';
    }
    $html .= 'src="' . htmlspecialchars($src) . '" ';
    $html .= 'alt="' . htmlspecialchars($alt) . '">';
    $html .= '</picture>';
    
    return $html;
}

/**
 * Limpa imagens antigas (otimização de disco)
 * 
 * @param string $directory - Diretório para limpar
 * @param int $days - Remover arquivos com mais de X dias (padrão 90)
 * @return array ['removed' => count, 'freed_bytes' => size]
 */
function cleanOldImages($directory, $days = 90) {
    if (!is_dir($directory)) {
        return ['error' => 'Diretório não encontrado'];
    }

    $removed = 0;
    $freed_bytes = 0;
    $time = time() - ($days * 24 * 60 * 60);

    $files = glob($directory . '/*');
    foreach ($files as $file) {
        if (is_file($file) && filemtime($file) < $time) {
            $freed_bytes += filesize($file);
            unlink($file);
            $removed++;
        }
    }

    return ['removed' => $removed, 'freed_bytes' => $freed_bytes];
}

/**
 * Retorna tamanho legível em bytes
 */
function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));

    return round($bytes, 2) . ' ' . $units[$pow];
}

?>
