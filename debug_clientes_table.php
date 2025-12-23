<?php
require_once 'includes/config.php';

echo "<h2>Estrutura da tabela clientes</h2>";
$stmt = $pdo->query("DESCRIBE clientes");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th></tr>";
foreach ($cols as $col) {
    echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td></tr>";
}
echo "</table>";
