<?php
require_once 'includes/config.php';

echo "<h2>Diagnóstico e Correção de Pedidos</h2>";

// 1. Ver estado atual dos pedidos
echo "<h3>1. Estado atual dos pedidos:</h3>";
$stmt = $pdo->query("SELECT id, status, lane_id, arquivado FROM pedidos ORDER BY id DESC");
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($pedidos);
echo "</pre>";

// 2. Verificar se coluna status permite NULL
echo "<h3>2. Estrutura da coluna status:</h3>";
$stmt = $pdo->query("SHOW COLUMNS FROM pedidos LIKE 'status'");
$col = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($col);
echo "</pre>";

// 3. Corrigir todos os pedidos com status vazio para 'pendente' como fallback
echo "<h3>3. Corrigindo pedidos com status vazio:</h3>";
$sql = "UPDATE pedidos SET status = 'pendente' WHERE status IS NULL OR status = ''";
$count1 = $pdo->exec($sql);
echo "Pedidos com status vazio corrigidos para 'pendente': $count1<br>";

// 4. Corrigir pedidos arquivados para 'finalizado'
echo "<h3>4. Corrigindo pedidos arquivados:</h3>";
$sql = "UPDATE pedidos SET status = 'finalizado' WHERE arquivado = 1";
$count2 = $pdo->exec($sql);
echo "Pedidos arquivados atualizados para 'finalizado': $count2<br>";

// 5. Ver estado depois da correção
echo "<h3>5. Estado dos pedidos APÓS correção:</h3>";
$stmt = $pdo->query("SELECT id, status, lane_id, arquivado FROM pedidos ORDER BY id DESC");
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($pedidos);
echo "</pre>";

echo "<h3>✅ Correção concluída! Atualize a página de pedidos.</h3>";
