<?php
require 'includes/config.php';

echo "=== Colunas da tabela clientes ===\n";
$stmt = $pdo->query("DESCRIBE clientes");
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $r['Field'] . " - " . $r['Type'] . "\n";
}
