<?php
include 'includes/config.php';

$stmt = $pdo->query("SHOW COLUMNS FROM pedidos WHERE Field LIKE '%endereco%' OR Field LIKE '%cliente%' OR Field LIKE '%end_%'");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Address/client related columns in pedidos:\n";
foreach ($columns as $col) {
    echo "- " . $col['Field'] . "\n";
}

// Also check for a sample order
echo "\n\nSample order data:\n";
$stmt = $pdo->query("SELECT * FROM pedidos LIMIT 1");
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if ($order) {
    foreach ($order as $key => $value) {
        echo "$key: " . ($value ?? 'NULL') . "\n";
    }
}
?>
