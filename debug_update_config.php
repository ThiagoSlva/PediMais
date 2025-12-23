<?php
include 'includes/config.php';

try {
    $stmt = $pdo->prepare("UPDATE configuracoes SET capa = ?, logo = ?, favicon = ? WHERE id = 1");
    $stmt->execute([
        'uploads/config/capa.png',
        'uploads/config/logo.png',
        'uploads/config/favicon.ico'
    ]);
    echo "Configuration updated successfully!\n";
    
    // Verify
    $stmt = $pdo->query("SELECT capa, logo, favicon FROM configuracoes LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($config);
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
