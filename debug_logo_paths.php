<?php
include 'includes/config.php';

$stmt = $pdo->query("SELECT logo, favicon, capa FROM configuracoes LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "logo: " . ($row['logo'] ?? 'NULL') . "\n";
echo "favicon: " . ($row['favicon'] ?? 'NULL') . "\n";
echo "capa: " . ($row['capa'] ?? 'NULL') . "\n";
?>
