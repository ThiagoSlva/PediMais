<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../includes/auth.php';

verificar_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$lane_id = $input['lane_id'] ?? null;
$nome = trim($input['nome'] ?? '');
$cor = trim($input['cor'] ?? '#6c757d');
$ordem = intval($input['ordem'] ?? 0);

if (!$lane_id) {
    echo json_encode(['success' => false, 'error' => 'ID da lane não informado']);
    exit;
}

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
    
    $acao = isset($input['acao']) ? $input['acao'] : null;
    
    $stmt = $pdo->prepare("UPDATE kanban_lanes SET nome = ?, cor = ?, ordem = ?, acao = ? WHERE id = ?");
    $stmt->execute([$nome, $cor, $ordem, $acao, $lane_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Lane atualizada com sucesso!'
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro no banco: ' . $e->getMessage()]);
}
?>
