<?php
/**
 * API de Upload de Foto de Perfil do Cliente
 */

header('Content-Type: application/json');

require_once '../../includes/config.php';
require_once '../includes/auth.php';

// Check authentication
if (!isset($_SESSION['cliente_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$cliente_id = $_SESSION['cliente_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => 'Arquivo muito grande (limite do servidor)',
        UPLOAD_ERR_FORM_SIZE => 'Arquivo muito grande',
        UPLOAD_ERR_PARTIAL => 'Upload incompleto',
        UPLOAD_ERR_NO_FILE => 'Nenhum arquivo enviado',
        UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária não encontrada',
        UPLOAD_ERR_CANT_WRITE => 'Erro ao gravar arquivo',
        UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão'
    ];
    
    $error_code = $_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE;
    $message = $error_messages[$error_code] ?? 'Erro no upload';
    
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$file = $_FILES['foto'];

// Validate file type
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime_type = $finfo->file($file['tmp_name']);

if (!in_array($mime_type, $allowed_types)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Formato inválido. Use JPG, PNG ou WebP.']);
    exit;
}

// Validate file size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Arquivo muito grande. Máximo 5MB.']);
    exit;
}

// Create uploads directory if not exists
$upload_dir = '../../uploads/clientes/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
if (empty($extension)) {
    $extensions = ['image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $extension = $extensions[$mime_type] ?? 'jpg';
}
$filename = 'cliente_' . $cliente_id . '_' . time() . '.' . $extension;
$filepath = $upload_dir . $filename;

// Process and resize image
try {
    // Load image based on type
    switch ($mime_type) {
        case 'image/jpeg':
        case 'image/jpg':
            $source = imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            $source = imagecreatefrompng($file['tmp_name']);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($file['tmp_name']);
            break;
        default:
            throw new Exception('Formato não suportado');
    }
    
    if (!$source) {
        throw new Exception('Erro ao processar imagem');
    }
    
    // Get original dimensions
    $orig_width = imagesx($source);
    $orig_height = imagesy($source);
    
    // Calculate new dimensions (max 400x400, maintaining aspect ratio and cropping to square)
    $size = min($orig_width, $orig_height);
    $x = ($orig_width - $size) / 2;
    $y = ($orig_height - $size) / 2;
    
    $new_size = 400;
    
    // Create new image
    $dest = imagecreatetruecolor($new_size, $new_size);
    
    // Preserve transparency for PNG
    if ($mime_type === 'image/png') {
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 255, 255, 255, 127);
        imagefilledrectangle($dest, 0, 0, $new_size, $new_size, $transparent);
    }
    
    // Resize and crop
    imagecopyresampled(
        $dest, $source,
        0, 0, $x, $y,
        $new_size, $new_size, $size, $size
    );
    
    // Save image as JPEG for consistency
    $final_filename = 'cliente_' . $cliente_id . '_' . time() . '.jpg';
    $final_filepath = $upload_dir . $final_filename;
    
    imagejpeg($dest, $final_filepath, 90);
    
    // Free memory
    imagedestroy($source);
    imagedestroy($dest);
    
    // Get old photo to delete
    $stmt = $pdo->prepare("SELECT foto_perfil FROM clientes WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $old_foto = $stmt->fetchColumn();
    
    // Update database
    $db_path = 'uploads/clientes/' . $final_filename;
    $stmt = $pdo->prepare("UPDATE clientes SET foto_perfil = ? WHERE id = ?");
    $stmt->execute([$db_path, $cliente_id]);
    
    // Delete old photo if exists
    if ($old_foto && file_exists('../../' . $old_foto)) {
        @unlink('../../' . $old_foto);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Foto atualizada com sucesso!',
        'foto_url' => $db_path
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao processar imagem: ' . $e->getMessage()]);
}
