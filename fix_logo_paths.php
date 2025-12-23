<?php
include 'includes/config.php';

// Get current values
$stmt = $pdo->query("SELECT logo, favicon, capa FROM configuracoes LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Current values:\n";
echo "logo: " . ($row['logo'] ?? 'NULL') . "\n";
echo "favicon: " . ($row['favicon'] ?? 'NULL') . "\n";
echo "capa: " . ($row['capa'] ?? 'NULL') . "\n\n";

// Fix paths by removing the uploads/config/ prefix
$logo = $row['logo'] ?? '';
$favicon = $row['favicon'] ?? '';
$capa = $row['capa'] ?? '';

// Remove duplicated prefix
$logo = preg_replace('/^uploads\/config\//', '', $logo);
$favicon = preg_replace('/^uploads\/config\//', '', $favicon);
$capa = preg_replace('/^uploads\/config\//', '', $capa);

echo "Fixed values:\n";
echo "logo: $logo\n";
echo "favicon: $favicon\n";
echo "capa: $capa\n\n";

// Update database
$stmt = $pdo->prepare("UPDATE configuracoes SET logo = ?, favicon = ?, capa = ? WHERE id = 1");
$stmt->execute([$logo, $favicon, $capa]);

echo "Database updated!\n";
?>
