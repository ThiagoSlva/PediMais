<?php
require_once 'includes/config.php';

try {
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'whatsapp_logs'");
    if ($stmt->rowCount() == 0) {
        echo "Tabela whatsapp_logs nao existe\n";
        exit;
    }
    
    // Get columns
    $stmt = $pdo->query("DESCRIBE whatsapp_logs");
    echo "Colunas:\n";
    while ($row = $stmt->fetch()) {
        echo "  - " . $row['Field'] . "\n";
    }
    
    // Get logs 
    $stmt = $pdo->query("SELECT * FROM whatsapp_logs ORDER BY id DESC LIMIT 3");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n\nLogs:\n";
    print_r($logs);
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
