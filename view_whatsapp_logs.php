<?php
require_once 'includes/config.php';
header('Content-Type: text/plain; charset=utf-8');

echo "=== WHATSAPP LOGS ===\n\n";

try {
    $stmt = $pdo->query("SELECT * FROM whatsapp_logs ORDER BY id DESC LIMIT 10");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($logs as $log) {
        echo "ID: {$log['id']}\n";
        echo "Data: {$log['criado_em']}\n";
        echo "Telefone: {$log['telefone']}\n";
        echo "HTTP: {$log['http_code']}\n";
        echo "Mensagem: " . substr($log['mensagem'], 0, 100) . "\n";
        echo "Response: {$log['response']}\n";
        echo "---\n";
    }
    
    if (empty($logs)) {
        echo "Nenhum log encontrado.\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>
