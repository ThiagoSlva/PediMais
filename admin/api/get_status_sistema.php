<?php
header('Content-Type: application/json');
include '../../includes/config.php';
include '../includes/auth.php';

// Verificar se é admin
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

try {
    // Buscar status entregadores
    $entregadores_ativo = 0;
    try {
        $stmt = $pdo->query("SELECT sistema_entregadores_ativo FROM whatsapp_config LIMIT 1");
        $whatsapp_config = $stmt->fetch(PDO::FETCH_ASSOC);
        $entregadores_ativo = $whatsapp_config ? (int)($whatsapp_config['sistema_entregadores_ativo'] ?? 0) : 0;
    } catch (PDOException $e) {
        // Column might not exist
    }

    // Buscar status estabelecimento
    $estabelecimento_aberto = 0;
    try {
        $stmt = $pdo->query("SELECT aberto_manual FROM configuracao_horarios LIMIT 1");
        $horarios_config = $stmt->fetch(PDO::FETCH_ASSOC);
        $estabelecimento_aberto = $horarios_config && $horarios_config['aberto_manual'] == 1 ? 1 : 0;
    } catch (PDOException $e) {
        // Column might not exist
    }

    echo json_encode([
        'success' => true,
        'entregadores_ativo' => $entregadores_ativo,
        'estabelecimento_aberto' => $estabelecimento_aberto
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro no banco de dados']);
}