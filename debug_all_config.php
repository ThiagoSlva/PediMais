<?php
include 'includes/config.php';

$stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

echo "All config fields:\n";
foreach ($config as $key => $value) {
    echo "$key = " . ($value ?? 'NULL') . "\n";
}
?>
