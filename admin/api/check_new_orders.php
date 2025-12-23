<?php
header('Content-Type: application/json');
include '../../includes/config.php';
include '../includes/auth.php';

// Verificar se é admin
if (!is_admin_logged_in()) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

try {
    // Contar pedidos pendentes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pedidos WHERE status = 'pendente'");
    $total_pendentes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Buscar últimos pedidos pendentes para a lista
    $stmt = $pdo->query("SELECT id, codigo_pedido, cliente_nome, valor_total, data_pedido, mesa_numero, tipo_entrega 
                         FROM pedidos 
                         WHERE status = 'pendente' 
                         ORDER BY data_pedido DESC 
                         LIMIT 10");
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatar dados para o frontend
    $lista_pedidos = [];
    foreach ($pedidos as $p) {
        // Calcular tempo decorrido
        $data_pedido = new DateTime($p['data_pedido']);
        $agora = new DateTime();
        $diff = $agora->diff($data_pedido);
        
        $tempo_decorrido = '';
        if ($diff->h > 0) $tempo_decorrido .= $diff->h . 'h ';
        $tempo_decorrido .= $diff->i . 'min';

        $lista_pedidos[] = [
            'id' => $p['id'],
            'codigo' => $p['codigo_pedido'],
            'cliente' => $p['cliente_nome'],
            'total' => number_format($p['valor_total'], 2, ',', '.'),
            'mesa' => $p['mesa_numero'],
            'tipo_entrega' => $p['tipo_entrega'],
            'tempo_decorrido' => $tempo_decorrido
        ];
    }

    // Verificar se há NOVOS pedidos (criados nos últimos 15 segundos) para o popup
    $stmt = $pdo->query("SELECT id, cliente_nome, valor_total, mesa_numero 
                         FROM pedidos 
                         WHERE status = 'pendente' 
                         AND data_pedido >= DATE_SUB(NOW(), INTERVAL 15 SECOND)");
    $novos_pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $novos_formatados = [];
    foreach ($novos_pedidos as $np) {
        $novos_formatados[] = [
            'id' => $np['id'],
            'cliente' => $np['cliente_nome'],
            'total' => number_format($np['valor_total'], 2, ',', '.'),
            'mesa' => $np['mesa_numero']
        ];
    }

    echo json_encode([
        'success' => true,
        'total_pendentes' => $total_pendentes,
        'lista_pedidos' => $lista_pedidos,
        'has_new' => count($novos_formatados) > 0,
        'novos_pedidos' => $novos_formatados
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro no banco de dados']);
}