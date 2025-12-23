<?php
require 'includes/config.php';
require 'includes/whatsapp_helper.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG COMPLETO DO WHATSAPP ===\n\n";

// 1. Verificar configuração
echo "1. CONFIGURAÇÃO WHATSAPP:\n";
$stmt = $pdo->query("SELECT * FROM whatsapp_config LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);
echo "   - ativo: " . ($config['ativo'] ?? 'N/A') . "\n";
echo "   - base_url: " . ($config['base_url'] ?? 'N/A') . "\n";
echo "   - instance_name: " . ($config['instance_name'] ?? 'N/A') . "\n";
echo "   - apikey: " . (empty($config['apikey']) ? 'NÃO DEFINIDA' : 'DEFINIDA (*****)') . "\n";
echo "   - notificar_status_pedido: " . ($config['notificar_status_pedido'] ?? 'N/A') . "\n";
echo "   - enviar_comprovante: " . ($config['enviar_comprovante'] ?? 'N/A') . "\n";
echo "\n";

// 2. Verificar se helper está OK
echo "2. TESTE DO HELPER:\n";
$whatsapp = new WhatsAppHelper($pdo);
echo "   - isConfigured(): " . ($whatsapp->isConfigured() ? 'SIM' : 'NÃO') . "\n";
echo "   - shouldSendStatusNotification(): " . ($whatsapp->shouldSendStatusNotification() ? 'SIM' : 'NÃO') . "\n";
echo "\n";

// 3. Verificar templates ativos
echo "3. TEMPLATES DE STATUS (deve mostrar os ativos):\n";
$stmt = $pdo->query("SELECT id, titulo, ativo FROM whatsapp_mensagens WHERE id IN (5,6,7,8,9,10)");
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "   - ID " . $r['id'] . ": " . $r['titulo'] . " [" . ($r['ativo'] ? 'ATIVO' : 'INATIVO') . "]\n";
}
echo "\n";

// 4. Buscar um pedido para testar
echo "4. PEDIDO PARA TESTE:\n";
$stmt = $pdo->query("SELECT id, codigo_pedido, cliente_nome, cliente_telefone, valor_total, tipo_entrega, status FROM pedidos ORDER BY id DESC LIMIT 1");
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);
if ($pedido) {
    echo "   - ID: " . $pedido['id'] . "\n";
    echo "   - Código: " . $pedido['codigo_pedido'] . "\n";
    echo "   - Cliente: " . $pedido['cliente_nome'] . "\n";
    echo "   - Telefone: " . $pedido['cliente_telefone'] . "\n";
    echo "   - Status atual: " . $pedido['status'] . "\n";
    echo "\n";
    
    // 5. Simular envio para status em_andamento
    echo "5. SIMULANDO ENVIO (status: em_andamento):\n";
    $result = $whatsapp->sendStatusUpdate($pedido, 'em_andamento');
    echo "   - success: " . ($result['success'] ? 'SIM' : 'NÃO') . "\n";
    echo "   - error: " . ($result['error'] ?? 'nenhum') . "\n";
    if (isset($result['http_code'])) {
        echo "   - http_code: " . $result['http_code'] . "\n";
    }
    if (isset($result['response'])) {
        echo "   - response: " . print_r($result['response'], true) . "\n";
    }
} else {
    echo "   - NENHUM PEDIDO ENCONTRADO!\n";
}

echo "\n=== FIM DO DEBUG ===\n";
