<?php
require_once 'includes/config.php';
header('Content-Type: application/json');

// Corrigir pedidos com status vazio ou incorreto
$pdo->exec("UPDATE pedidos SET status = 'finalizado' WHERE arquivado = 1 AND (status IS NULL OR status = '' OR status = 'pendente')");
$affected1 = $pdo->query("SELECT ROW_COUNT()")->fetchColumn();

// Corrigir pedidos arquivados que ainda não têm status finalizado
$pdo->exec("UPDATE pedidos SET status = 'finalizado', entregue = 1, saiu_entrega = 1, em_preparo = 1 WHERE arquivado = 1");
$affected2 = $pdo->query("SELECT ROW_COUNT()")->fetchColumn();

// Verificar resultado
$stmt = $pdo->query("SELECT id, status, lane_id, arquivado, entregue FROM pedidos ORDER BY id DESC LIMIT 5");
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'message' => "Pedidos com status vazio corrigidos: $affected1, Pedidos arquivados atualizados: $affected2",
    'pedidos_atualizados' => $pedidos
], JSON_PRETTY_PRINT);
