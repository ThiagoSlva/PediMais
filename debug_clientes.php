<?php
include 'includes/config.php';

$stmt = $pdo->query("SHOW COLUMNS FROM clientes");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo $col['Field'] . "\n";
}
?>
