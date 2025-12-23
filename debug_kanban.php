<?php
require 'includes/config.php';

echo "<h2>Kanban Lanes:</h2>";
$stmt = $pdo->query("SELECT * FROM kanban_lanes ORDER BY ordem ASC");
$lanes = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($lanes);
echo "</pre>";

echo "<h2>Pedidos (primeiros 10):</h2>";
$stmt = $pdo->query("SELECT id, codigo_pedido, status, lane_id, status_pagamento, data_pedido FROM pedidos ORDER BY id DESC LIMIT 10");
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($pedidos);
echo "</pre>";

echo "<h2>Contagem por lane_id:</h2>";
$stmt = $pdo->query("SELECT lane_id, COUNT(*) as total FROM pedidos GROUP BY lane_id");
$counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($counts);
echo "</pre>";
