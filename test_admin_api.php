<?php
require_once 'includes/config.php';

function testUrl($url, $postData = null) {
    global $site_url; // Assuming defined in config.php, otherwise use localhost
    $baseUrl = 'http://localhost:8000/admin/api/';
    $fullUrl = $baseUrl . $url;
    
    $ch = curl_init($fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($postData) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $httpCode, 'response' => $response];
}

echo "Testing Admin APIs...\n";

// 1. Test get_kanban_snapshot
echo "1. Testing get_kanban_snapshot.php... ";
$res = testUrl('get_kanban_snapshot.php');
$json = json_decode($res['response'], true);
if ($res['code'] == 200 && isset($json['success']) && $json['success']) {
    echo "OK (Lanes: " . count($json['lanes']) . ")\n";
} else {
    echo "FAIL. Response: " . substr($res['response'], 0, 100) . "...\n";
}

// 2. Test get_pedidos_realtime
echo "2. Testing get_pedidos_realtime.php... ";
$res = testUrl('get_pedidos_realtime.php');
$json = json_decode($res['response'], true);
if ($res['code'] == 200 && isset($json['success']) && $json['success']) {
    echo "OK (Pedidos: " . count($json['pedidos']) . ")\n";
} else {
    echo "FAIL. Response: " . substr($res['response'], 0, 100) . "...\n";
}

// 3. Test verificar_entregadores_disponiveis
echo "3. Testing verificar_entregadores_disponiveis.php... ";
$res = testUrl('verificar_entregadores_disponiveis.php');
$json = json_decode($res['response'], true);
if ($res['code'] == 200 && isset($json['success']) && $json['success']) {
    echo "OK (Total: " . $json['total'] . ")\n";
} else {
    echo "FAIL. Response: " . substr($res['response'], 0, 100) . "...\n";
}

// 4. Test add_lane
echo "4. Testing add_lane.php... ";
$res = testUrl('add_lane.php', ['nome' => 'Test Lane', 'cor' => '#000000']);
$json = json_decode($res['response'], true);
if ($res['code'] == 200 && isset($json['success']) && $json['success']) {
    echo "OK\n";
} else {
    echo "FAIL. Response: " . substr($res['response'], 0, 100) . "...\n";
}

echo "Done.\n";
?>
