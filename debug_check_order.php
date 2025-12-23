<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$codigo = '9BBDCC64'; // From user screenshot
echo "Debugging Order: $codigo\n";

// Check Pedidos
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE codigo_pedido = ?");
$stmt->execute([$codigo]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    echo "Order not found in 'pedidos' table.\n";
    exit;
}

echo "Order Found: ID {$pedido['id']}\n";
echo "Status: {$pedido['status']}\n";
echo "Pagamento Online: {$pedido['pagamento_online']}\n";
echo "Forma Pagamento ID: {$pedido['forma_pagamento_id']}\n";

// Check MercadoPago Pagamentos
$stmt = $pdo->prepare("SELECT * FROM mercadopago_pagamentos WHERE pedido_id = ?");
$stmt->execute([$pedido['id']]);
$mp_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Mercado Pago Entries: " . count($mp_payments) . "\n";
foreach ($mp_payments as $mp) {
    echo " - Payment ID: {$mp['payment_id']}, Status: {$mp['status']}\n";
}

// Check Join Query EXACTLY AS IN CRON
$codigo = '9BBDCC64';
$stmt = $pdo->prepare("SELECT id, status, pagamento_online FROM pedidos WHERE codigo_pedido = ?");
$stmt->execute([$codigo]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$p) die("Order not found\n");

echo "ID: " . var_export($p['id'], true) . "\n";
echo "Status: " . var_export($p['status'], true) . "\n";
echo "Online: " . var_export($p['pagamento_online'], true) . "\n";

$stmt = $pdo->prepare("SELECT pedido_id, status FROM mercadopago_pagamentos WHERE pedido_id = ?");
$stmt->execute([$p['id']]);
$mps = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "MP Rows: " . count($mps) . "\n";
if (count($mps) > 0) {
    echo "MP[0][pedido_id]: " . var_export($mps[0]['pedido_id'], true) . "\n";
    echo "MP[0][status]: " . var_export($mps[0]['status'], true) . "\n";
}

$sql = "SELECT p.id
        FROM pedidos p
        JOIN mercadopago_pagamentos mp ON p.id = mp.pedido_id
        WHERE p.pagamento_online = 1 
        AND p.status = 'pendente'
        AND p.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$p['id']]);
$res = $stmt->fetchAll();

echo "JOIN Result Count: " . count($res) . "\n";
if (count($res) == 0) {
    echo "Why 0?\n";
    // Check Status Match
    if ($p['status'] !== 'pendente') echo " - Status '{$p['status']}' != 'pendente'\n";
    // Check Online Match
    if ($p['pagamento_online'] != 1) echo " - Online '{$p['pagamento_online']}' != 1\n";
}
?>
