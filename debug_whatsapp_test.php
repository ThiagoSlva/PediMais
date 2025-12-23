<?php
// Debug WhatsApp Integration
require_once 'includes/config.php';
require_once 'includes/whatsapp_helper.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG WHATSAPP ===\n\n";

// 1. Verificar configuração
echo "1. CONFIGURAÇÃO:\n";
$stmt = $pdo->query("SELECT * FROM whatsapp_config LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

if ($config) {
    echo "   - ID: " . ($config['id'] ?? 'N/A') . "\n";
    echo "   - Ativo: " . ($config['ativo'] ? 'SIM' : 'NÃO') . "\n";
    echo "   - Base URL: " . ($config['base_url'] ?? 'VAZIO') . "\n";
    echo "   - API Key: " . (empty($config['apikey']) ? 'VAZIO' : 'DEFINIDO (****)') . "\n";
    echo "   - Instance Name: " . ($config['instance_name'] ?? 'VAZIO') . "\n";
    echo "   - Enviar Comprovante: " . ($config['enviar_comprovante'] ? 'SIM' : 'NÃO') . "\n";
    echo "   - Notificar Status: " . ($config['notificar_status_pedido'] ? 'SIM' : 'NÃO') . "\n";
    echo "   - WhatsApp Estabelecimento: " . ($config['whatsapp_estabelecimento'] ?? 'VAZIO') . "\n";
} else {
    echo "   ERRO: Nenhuma configuração encontrada!\n";
}

echo "\n";

// 2. Verificar helper
echo "2. WHATSAPP HELPER:\n";
$whatsapp = new WhatsAppHelper($pdo);
echo "   - isConfigured(): " . ($whatsapp->isConfigured() ? 'SIM' : 'NÃO') . "\n";
echo "   - shouldSendOrderNotification(): " . ($whatsapp->shouldSendOrderNotification() ? 'SIM' : 'NÃO') . "\n";
echo "   - shouldSendStatusNotification(): " . ($whatsapp->shouldSendStatusNotification() ? 'SIM' : 'NÃO') . "\n";

echo "\n";

// 3. Ver logs recentes
echo "3. LOGS RECENTES:\n";
try {
    $stmt = $pdo->query("SELECT * FROM whatsapp_logs ORDER BY id DESC LIMIT 5");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($logs) {
        foreach ($logs as $log) {
            echo "   [{$log['criado_em']}] Tel: {$log['telefone']} - HTTP: {$log['http_code']}\n";
            echo "   Msg: " . substr($log['mensagem'], 0, 50) . "...\n";
            echo "   Resp: " . substr($log['response'], 0, 100) . "\n\n";
        }
    } else {
        echo "   Nenhum log encontrado.\n";
    }
} catch (Exception $e) {
    echo "   Tabela whatsapp_logs não existe ainda.\n";
}

echo "\n";

// 4. Testar envio (apenas se configurado)
echo "4. TESTE DE ENVIO:\n";
if ($whatsapp->isConfigured() && !empty($config['whatsapp_estabelecimento'])) {
    echo "   Tentando enviar mensagem de teste para: {$config['whatsapp_estabelecimento']}\n";
    
    $result = $whatsapp->sendMessage($config['whatsapp_estabelecimento'], "🧪 Teste de integração WhatsApp - PedeMais");
    
    echo "   Resultado:\n";
    echo "   - Success: " . ($result['success'] ? 'SIM' : 'NÃO') . "\n";
    echo "   - HTTP Code: " . ($result['http_code'] ?? 'N/A') . "\n";
    echo "   - Error: " . ($result['error'] ?? 'Nenhum') . "\n";
    echo "   - Response: " . json_encode($result['response'] ?? []) . "\n";
} else {
    echo "   SKIP: WhatsApp não configurado corretamente ou sem número do estabelecimento.\n";
}

echo "\n=== FIM DEBUG ===\n";
?>
