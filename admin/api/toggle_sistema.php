<?php
header('Content-Type: application/json');
include '../../includes/config.php';
include '../includes/auth.php';

// Verificar se é admin
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Receber dados JSON
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['tipo']) || !isset($data['ativo'])) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

$tipo = $data['tipo'];
$ativo = (int)$data['ativo'];

try {
    if ($tipo === 'entregadores') {
        // Atualizar whatsapp_config
        $stmt = $pdo->prepare("UPDATE whatsapp_config SET sistema_entregadores_ativo = :ativo");
        $stmt->execute([':ativo' => $ativo]);
        
        $mensagem = $ativo ? 'Sistema de entregadores ATIVADO' : 'Sistema de entregadores DESATIVADO';
        echo json_encode(['success' => true, 'mensagem' => $mensagem]);
        
    } elseif ($tipo === 'estabelecimento') {
        // Atualizar configuracao_horarios
        // aberto_manual: 1 = aberto, 0 = fechado, NULL = automático (mas o toggle força manual)
        $stmt = $pdo->prepare("UPDATE configuracao_horarios SET aberto_manual = :ativo");
        $stmt->execute([':ativo' => $ativo]);
        
        $mensagem = $ativo ? 'Estabelecimento ABERTO manualmente' : 'Estabelecimento FECHADO manualmente';
        echo json_encode(['success' => true, 'mensagem' => $mensagem]);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Tipo desconhecido']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro no banco de dados: ' . $e->getMessage()]);
}