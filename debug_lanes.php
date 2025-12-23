<?php
require 'includes/config.php';

echo "=== Lanes do Kanban ===\n";
$stmt = $pdo->query("SELECT * FROM kanban_lanes ORDER BY ordem");
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $r['id'] . ": " . $r['nome'] . "\n";
}

echo "\n=== Status possíveis de pedidos ===\n";
$stmt = $pdo->query("SELECT DISTINCT status FROM pedidos");
while($r = $stmt->fetch(PDO::FETCH_COLUMN)) {
    echo "- " . $r . "\n";
}
