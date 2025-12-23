<?php
require_once 'includes/config.php';
header('Content-Type: application/json');

// Verificar estrutura da tabela
$stmt = $pdo->query("SHOW COLUMNS FROM kanban_lanes");
$lanes_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Verificar lanes
$stmt = $pdo->query("SELECT id, nome, acao FROM kanban_lanes ORDER BY ordem");
$lanes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Verificar pedidos
$stmt = $pdo->query("SELECT id, status, lane_id, arquivado FROM pedidos ORDER BY id DESC LIMIT 5");
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'lanes_columns' => $lanes_columns,
    'lanes' => $lanes,
    'pedidos' => $pedidos
], JSON_PRETTY_PRINT);
