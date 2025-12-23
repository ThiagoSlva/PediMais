<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../includes/auth.php';

// verificar_login();

$telefone = $_GET['telefone'] ?? '';

if (empty($telefone)) {
    echo json_encode(['success' => false, 'error' => 'Telefone missing']);
    exit;
}

try {
    // Remove non-digits
    $telefoneClean = preg_replace('/\D/', '', $telefone);

    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE telefone LIKE ? OR telefone LIKE ? LIMIT 1");
    $stmt->execute(["%{$telefoneClean}%", "%{$telefone}%"]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
        echo json_encode(['success' => true, 'cliente' => $cliente]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cliente não encontrado']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
