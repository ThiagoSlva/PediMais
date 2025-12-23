<?php
include 'includes/config.php';

echo "Configurações:\n";
try {
    $stmt = $pdo->query("SELECT capa, logo, favicon FROM configuracoes LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "capa: " . ($config['capa'] ?? 'NULL') . "\n";
    echo "logo: " . ($config['logo'] ?? 'NULL') . "\n";
    echo "favicon: " . ($config['favicon'] ?? 'NULL') . "\n";
    
    // Check paths
    $capa_path = 'admin/' . ($config['capa'] ?? '');
    $logo_path = 'admin/' . ($config['logo'] ?? '');
    
    echo "\nPath checks:\n";
    echo "Full capa path: " . $capa_path . " - exists: " . (file_exists($capa_path) ? 'YES' : 'NO') . "\n";
    echo "Full logo path: " . $logo_path . " - exists: " . (file_exists($logo_path) ? 'YES' : 'NO') . "\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
