<?php
require 'includes/config.php';

echo "<h2>Corrigindo pedidos sem lane_id...</h2>";

// 1. Buscar a primeira lane (Novos Pedidos)
$stmt = $pdo->query("SELECT id FROM kanban_lanes ORDER BY ordem ASC LIMIT 1");
$lane = $stmt->fetch();

if (!$lane) {
    echo "ERRO: Nenhuma lane encontrada!";
    exit;
}

$lane_id = $lane['id'];
echo "Lane inicial encontrada: ID = $lane_id<br><br>";

// 2. Atualizar todos os pedidos que não têm lane_id
$stmt = $pdo->prepare("UPDATE pedidos SET lane_id = ? WHERE lane_id IS NULL OR lane_id = 0");
$stmt->execute([$lane_id]);
$updated = $stmt->rowCount();

echo "Pedidos atualizados: $updated<br><br>";

// 3. Verificar resultado
echo "<h2>Contagem após correção:</h2>";
$stmt = $pdo->query("SELECT lane_id, COUNT(*) as total FROM pedidos GROUP BY lane_id");
$counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($counts);
echo "</pre>";

echo "<br><br><a href='admin/pedidos_kanban.php'>Ir para o Kanban</a>";
