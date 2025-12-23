<?php
require_once 'includes/config.php';
require_once 'includes/mercadopago_helper.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== TESTE MERCADO PAGO PIX ===\n\n";

// Check config
$stmt = $pdo->query("SELECT ativo, sandbox_mode, access_token, public_key FROM mercadopago_config LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

echo "CONFIG:\n";
echo "  Ativo: " . ($config['ativo'] ? 'SIM' : 'NAO') . "\n";
echo "  Sandbox: " . ($config['sandbox_mode'] ? 'SIM' : 'NAO') . "\n";
echo "  Access Token: " . (empty($config['access_token']) ? 'VAZIO' : 'DEFINIDO (****)') . "\n";
echo "  Public Key: " . (empty($config['public_key']) ? 'VAZIO' : 'DEFINIDO (****)') . "\n\n";

$mp = new MercadoPagoHelper($pdo);
echo "isConfigured: " . ($mp->isConfigured() ? 'SIM' : 'NAO') . "\n\n";

// Try to create a test payment
if ($mp->isConfigured()) {
    echo "Criando pagamento de teste...\n";
    
    $result = $mp->createPayment(
        99999, // pedido_id fake
        1.00, // R$ 1,00 de teste
        'teste@teste.com',
        'Cliente Teste',
        'Pedido Teste PedeMais'
    );
    
    echo "Resultado:\n";
    echo "  Success: " . ($result['success'] ? 'SIM' : 'NAO') . "\n";
    
    if ($result['success']) {
        echo "  Payment ID: " . $result['payment_id'] . "\n";
        echo "  QR Code (copia e cola): " . substr($result['qr_code'], 0, 50) . "...\n";
        echo "  Ticket URL: " . $result['ticket_url'] . "\n";
    } else {
        echo "  Error: " . ($result['error'] ?? 'Unknown') . "\n";
        echo "  Details: " . json_encode($result['details'] ?? []) . "\n";
    }
}

echo "\n=== FIM TESTE ===\n";
?>
