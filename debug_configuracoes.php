<?php
require 'includes/config.php';
$stmt = $pdo->query("DESCRIBE configuracoes");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}
?>
