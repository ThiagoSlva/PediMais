<?php
session_start();
// Simular login para teste
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nivel'] = 'admin';

require_once 'includes/config.php';

echo "<h2>Teste da API Concluir Pedido</h2>";

// 1. Verificar pedidos existentes
echo "<h3>1. Pedidos existentes:</h3>";
$stmt = $pdo->query("SELECT id, status, arquivado FROM pedidos ORDER BY id DESC LIMIT 5");
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($pedidos);
echo "</pre>";

if (empty($pedidos)) {
    echo "<p>Nenhum pedido encontrado!</p>";
    exit;
}

$primeiro_pedido = $pedidos[0]['id'];

// 2. Testar atualização direta
echo "<h3>2. Atualizando pedido #$primeiro_pedido diretamente:</h3>";
try {
    $stmt = $pdo->prepare("UPDATE pedidos SET status = 'finalizado', arquivado = 1, entregue = 1 WHERE id = ?");
    $result = $stmt->execute([$primeiro_pedido]);
    echo "Resultado: " . ($result ? "✅ Sucesso" : "❌ Falhou") . "<br>";
    echo "Linhas afetadas: " . $stmt->rowCount() . "<br>";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "<br>";
}

// 3. Verificar resultado
echo "<h3>3. Estado após atualização:</h3>";
$stmt = $pdo->query("SELECT id, status, arquivado, entregue FROM pedidos ORDER BY id DESC LIMIT 5");
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($pedidos);
echo "</pre>";

echo "<h3>✅ Teste concluído! <a href='admin/pedidos.php'>Ver Pedidos</a></h3>";
