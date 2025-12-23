<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

check_cliente_auth();

$cliente = get_cliente_logado();
$ultima_atualizacao = $_GET['last_update'] ?? null;

try {
    $query = "
        SELECT 
            id,
            codigo_pedido,
            status,
            pago,
            em_preparo,
            saiu_entrega,
            entregue,
            valor_total,
            tipo_entrega,
            data_pedido,
            atualizado_em
        FROM pedidos 
        WHERE cliente_id = :cliente_id
    ";
    
    $params = ['cliente_id' => $cliente['id']];
    
    // Filtrar apenas pedidos atualizados após última verificação
    if ($ultima_atualizacao) {
        $query .= " AND atualizado_em > :last_update";
        $params['last_update'] = $ultima_atualizacao;
    }
    
    $query .= " ORDER BY atualizado_em DESC LIMIT 50";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Usar o timestamp do último pedido retornado, ou manter o anterior se não houver pedidos novos
    $timestamp = date('Y-m-d H:i:s');
    
    if (!empty($pedidos) && isset($pedidos[0]['atualizado_em'])) {
        // Usar o timestamp do pedido mais recente retornado
        $timestamp = $pedidos[0]['atualizado_em'];
    } elseif ($ultima_atualizacao) {
        // Se não há pedidos novos, manter o timestamp anterior para evitar loop
        $timestamp = $ultima_atualizacao;
    }
    
    echo json_encode([
        'success' => true,
        'pedidos' => $pedidos,
        'timestamp' => $timestamp
    ]);
    
} catch (PDOException $e) {
    error_log("Erro ao buscar pedidos do cliente: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar pedidos']);
}




