<?php
require_once 'includes/config.php';

function test_url($url, $method = 'GET', $data = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($method == 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $http_code, 'body' => $response];
}

$base_url = SITE_URL . '/api';

echo "Testing API Endpoints...\n\n";

// 1. Get Pizzas
echo "1. Testing get_pizzas_disponiveis.php... ";
$res = test_url($base_url . '/get_pizzas_disponiveis.php');
echo "HTTP " . $res['code'] . "\n";
echo "Response: " . substr($res['body'], 0, 100) . "...\n\n";

// 2. Loyalty (Check)
echo "2. Testing resgatar_fidelidade.php (Check)... ";
$res = test_url($base_url . '/resgatar_fidelidade.php', 'POST', ['telefone' => '99999999999', 'acao' => 'check']);
echo "HTTP " . $res['code'] . "\n";
echo "Response: " . $res['body'] . "\n\n";

// 3. Checkout (Empty)
echo "3. Testing checkout.php (Empty)... ";
$res = test_url($base_url . '/checkout.php', 'POST', []);
echo "HTTP " . $res['code'] . "\n";
echo "Response: " . $res['body'] . "\n\n";

?>
