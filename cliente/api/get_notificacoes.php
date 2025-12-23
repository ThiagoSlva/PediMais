<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

check_cliente_auth();

$cliente = get_cliente_logado();
$cliente_id = (int)$cliente['id'];
$ultima_atualizacao = $_GET['last_update'] ?? null;

try {
    // Buscar contagem de notificações não lidas
    $stmt_count = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM cliente_notificacoes
        WHERE cliente_id = :cliente_id AND lida = 0
    ");
    $stmt_count->execute([':cliente_id' => $cliente_id]);
    $count_result = $stmt_count->fetch(PDO::FETCH_ASSOC);
    $nao_lidas = (int)($count_result['total'] ?? 0);
    
    // Buscar notificações não lidas (novas)
    $query = "
        SELECT 
            id,
            pedido_id,
            tipo,
            titulo,
            mensagem,
            lida,
            criada_em
        FROM cliente_notificacoes
        WHERE cliente_id = :cliente_id AND lida = 0
    ";
    
    $params = ['cliente_id' => $cliente_id];
    
    // Se há last_update, buscar apenas notificações criadas após essa data
    if ($ultima_atualizacao) {
        $query .= " AND criada_em > :last_update";
        $params['last_update'] = $ultima_atualizacao;
    }
    
    $query .= " ORDER BY criada_em DESC LIMIT 20";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $notificacoes_novas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obter timestamp da última notificação ou usar o atual
    $timestamp = date('Y-m-d H:i:s');
    if (!empty($notificacoes_novas) && isset($notificacoes_novas[0]['criada_em'])) {
        $timestamp = $notificacoes_novas[0]['criada_em'];
    } elseif ($ultima_atualizacao) {
        $timestamp = $ultima_atualizacao;
    }
    
    echo json_encode([
        'sucesso' => true,
        'nao_lidas' => $nao_lidas,
        'novas' => $notificacoes_novas,
        'timestamp' => $timestamp
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar notificações: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao buscar notificações']);
}
