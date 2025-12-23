<?php
require_once 'includes/config.php';
require_once 'includes/whatsapp_helper.php';

header('Content-Type: text/plain; charset=utf-8');

$whatsapp = new WhatsAppHelper($pdo);

echo "isConfigured: " . ($whatsapp->isConfigured() ? 'SIM' : 'NAO') . "\n";
echo "shouldSendOrderNotification: " . ($whatsapp->shouldSendOrderNotification() ? 'SIM' : 'NAO') . "\n\n";

// Load config to check what's set
$stmt = $pdo->query("SELECT base_url, instance_name, whatsapp_estabelecimento, enviar_comprovante, ativo FROM whatsapp_config LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($config);

// Try sending a test message
if ($whatsapp->isConfigured() && !empty($config['whatsapp_estabelecimento'])) {
    echo "\n\nEnviando mensagem de teste...\n";
    $result = $whatsapp->sendMessage($config['whatsapp_estabelecimento'], "Teste PedeMais - " . date('H:i:s'));
    echo "Success: " . ($result['success'] ? 'SIM' : 'NAO') . "\n";
    echo "HTTP Code: " . ($result['http_code'] ?? 'N/A') . "\n";
    echo "Error: " . ($result['error'] ?? 'none') . "\n";
    echo "Response: " . json_encode($result['response'] ?? []) . "\n";
}
?>
