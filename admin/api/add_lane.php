<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../includes/auth.php';

// verificar_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$nome = $_POST['nome'] ?? '';
$cor = $_POST['cor'] ?? '#6c757d';

if (empty($nome)) {
    echo json_encode(['success' => false, 'error' => 'Nome da lane é obrigatório']);
    exit;
}

try {
    // Verificar se coluna 'acao' existe, se não, criar
    $stmt = $pdo->query("SHOW COLUMNS FROM kanban_lanes LIKE 'acao'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE kanban_lanes ADD COLUMN acao VARCHAR(50) DEFAULT NULL");
    }
    
    // Get max order
    $stmt = $pdo->query("SELECT MAX(ordem) FROM kanban_lanes");
    $maxOrdem = $stmt->fetchColumn();
    $ordem = $maxOrdem ? $maxOrdem + 1 : 1;

    $acao = $_POST['acao'] ?? null;
    
    $stmt = $pdo->prepare("INSERT INTO kanban_lanes (nome, cor, ordem, acao) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nome, $cor, $ordem, $acao]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
