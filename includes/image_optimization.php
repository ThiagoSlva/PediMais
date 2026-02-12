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
function compressAndOptimizeImage($source, $destination, $quality = 75, $maxWidth = 1200, $maxHeight = 1200)
{
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
                return ['success' => false, 'error' => 'Formato não suportado', 'file' => null];
        }

        if (!$image) {
            return ['success' => false, 'error' => 'Falha ao carregar imagem', 'file' => null];
        }

        // Obter dimensões originais
        $width = imagesx($image);
        $height = imagesy($image);

        // Calcular novas dimensões mantendo proporção
        $newWidth = $width;
        $newHeight = $height;

        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = $width / $height;
            if ($width > $height) {
                $newWidth = $maxWidth;
                $newHeight = $maxWidth / $ratio;
            }
            else {
                $newHeight = $maxHeight;
                $newWidth = $maxHeight * $ratio;
            }
        }

        // Criar nova imagem redimensionada
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preservar transparência para PNG/WebP/GIF
        if ($source_format == 'png' || $source_format == 'webp' || $source_format == 'gif') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Copiar e redimensionar
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Salvar imagem otimizada (JPEG principal)
        $jpeg_file = $destination . '.jpg';
        imagejpeg($newImage, $jpeg_file, $quality);

        // Tentar salvar versão WebP também (para navegadores modernos)
        $webp_file = $destination . '.webp';
        if (function_exists('imagewebp')) {
            imagewebp($newImage, $webp_file, $quality); // WebP geralmente é menor
        }

        // Limpar memória
        imagedestroy($image);
        imagedestroy($newImage);

        // Calcular tamanho final e economia
        $final_size = filesize($jpeg_file);
        $compression_ratio = $original_size > 0 ? round((($original_size - $final_size) / $original_size) * 100, 1) : 0;

        // Normalizar separadores para compatibilidade Windows/Linux
        $destination = str_replace('\\', '/', $destination);

        // Retornar caminho relativo correto para web
        // O $destination pode ser absoluto (ex: /home/user/public/uploads/cat_123)
        // ou relativo (ex: uploads/cat_123)
        // O arquivo é SEMPRE salvo COM extensão .jpg, então precisa retornar COM extensão

        if (strpos($destination, '/') === 0 || (strlen($destination) > 1 && $destination[1] === ':')) {
            // É caminho absoluto (começa com / ou C:)
            // Extrair apenas a parte relativa a partir de 'uploads'
            if (strpos($destination, '/admin/uploads/') !== false) {
                // Trata admin/uploads/... - Prioridade para manter o path admin/
                $relative = 'admin/uploads/' . substr($destination, strpos($destination, '/admin/uploads/') + 15);
            }
            elseif (strpos($destination, '/uploads/') !== false) {
                // Trata /home/user/public_html/uploads/... e C:/.../uploads/...
                $relative = 'uploads/' . substr($destination, strpos($destination, '/uploads/') + 9);
            }
            else {
                // Fallback: tentar encontrar pasta do projeto
                $relative = basename($destination);
            }
            // Adicionar extensão para caminhos absolutos
            $best_file = $relative . '.jpg';
        }
        else {
            // Já é relativo
            $best_file = $destination . '.jpg';
        }

        return [
            'success' => true,
            'file' => $best_file,
            'original_size' => $original_size,
            'compressed_size' => $final_size,
            'compression_ratio' => $compression_ratio
        ];

    }
    catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage(), 'file' => null];
    }
}

/**
 * Renderiza uma imagem responsiva com fallback e WebP
 * 
 * @param string $imagePath - Caminho relativo da imagem (ex: uploads/produtos/foto.jpg)
 * @param string $alt - Texto alternativo
 * @param string $class - Classes CSS adicionais
 * @param string $style - Estilos CSS adicionais
 * @return string - HTML da tag picture ou img
 */
function renderOptimizedImage($imagePath, $alt = '', $class = '', $style = '')
{
    if (empty($imagePath)) {
        return '<img src="assets/images/sem-foto.jpg" alt="Sem imagem" class="' . $class . '" style="' . $style . '">';
    }

    // Remover extensão para verificar WebP
    $basePath = preg_replace('/\.(jpg|jpeg|png|gif)$/i', '', $imagePath);
    $webpPath = $basePath . '.webp';

    // Verificar se existe versão WebP (checagem física do arquivo seria ideal, mas custosa)
    // Assumimos que se foi gerado pelo sistema, existe.

    $html = '<picture>';
    $html .= '<source srcset="' . $webpPath . '" type="image/webp">';
    $html .= '<img src="' . $imagePath . '" alt="' . htmlspecialchars($alt) . '" class="' . $class . '" style="' . $style . '" loading="lazy">';
    $html .= '</picture>';

    return $html;
}

/**
 * Função utilitária para limpar imagens antigas (ex: uploads temporários)
 */
function cleanOldImages($directory, $days = 30)
{
    if (!is_dir($directory))
        return;

    $files = glob($directory . '*');
    $now = time();
    $day_sec = 60 * 60 * 24;
    $removed = 0;
    $freed_bytes = 0;

    foreach ($files as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) >= $days * $day_sec) {
                $size = filesize($file);
                if (unlink($file)) {
                    $removed++;
                    $freed_bytes += $size;
                }
            }
        }
    }

    return ['removed' => $removed, 'freed_bytes' => $freed_bytes];
}

/**
 * Formata bytes para tamanho legível (KB, MB)
 */
function formatBytes($bytes, $precision = 2)
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    // Uncomment one of the following alternatives
    $bytes /= pow(1024, $pow);
    // $bytes /= (1 << (10 * $pow)); 

    return round($bytes, $precision) . ' ' . $units[$pow];

}

/**
 * Otimiza uma imagem existente (sem mover)
 */
function optimizeImage($path, $basePath, $quality = 75, $maxWidth = 1200)
{
    if (!file_exists($path))
        return ['success' => false];

    // Check if webp already exists
    if (file_exists($basePath . '.webp'))
        return ['success' => true, 'saved_bytes' => 0];

    // Re-use logic from compressAndOptimizeImage but avoiding move
    // This is a simplified version just to generate WebP and optimize
    return compressAndOptimizeImage($path, $basePath, $quality, $maxWidth, 1200);
}
