<?php
/**
 * Fix: Create missing token for order and test
 */
require_once 'includes/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 Fix Token de Avaliação</h1>";

$pedido_id = $_GET['pedido_id'] ?? 3;

// 1. Verificar estrutura da tabela avaliacoes
echo "<h2>1. Estrutura da Tabela Avaliacoes</h2>";
try {
    $stmt = $pdo->query("DESCRIBE avaliacoes");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p style='color:red'>ERRO: " . $e->getMessage() . "</p>";
}

// 2. Buscar dados do pedido
echo "<h2>2. Dados do Pedido #$pedido_id</h2>";
$stmt = $pdo->prepare("SELECT id, codigo_pedido, cliente_nome FROM pedidos WHERE id = ?");
$stmt->execute([$pedido_id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($pedido, true) . "</pre>";

// 3. Verificar se já existe token
echo "<h2>3. Token Existente</h2>";
$stmt = $pdo->prepare("SELECT * FROM avaliacoes WHERE pedido_id = ?");
$stmt->execute([$pedido_id]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);
if ($existing) {
    echo "<p style='color:green'>✅ Token existe: {$existing['token']}</p>";
    echo "<pre>" . print_r($existing, true) . "</pre>";
} else {
    echo "<p style='color:red'>❌ Nenhum token encontrado</p>";
}

// 4. Criar token se solicitado
if (isset($_GET['create_token']) && $pedido) {
    echo "<h2>4. Criando Token...</h2>";
    try {
        $token = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("INSERT INTO avaliacoes (pedido_id, nome, cliente_nome, token, avaliacao) VALUES (?, ?, ?, ?, 0)");
        $result = $stmt->execute([$pedido_id, $pedido['cliente_nome'], $pedido['cliente_nome'], $token]);
        
        if ($result) {
            echo "<p style='color:green'>✅ Token criado com sucesso: $token</p>";
            $link = (defined('SITE_URL') ? SITE_URL : 'http://seu-site.com') . '/avaliar_pedido.php?token=' . $token;
            echo "<p><a href='$link' target='_blank'>🔗 $link</a></p>";
        } else {
            echo "<p style='color:red'>❌ Falha ao inserir (sem erro reportado)</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>ERRO PDO: " . $e->getMessage() . "</p>";
        echo "<p>Código: " . $e->getCode() . "</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>ERRO: " . $e->getMessage() . "</p>";
    }
}

// 5. Verificar SITE_URL
echo "<h2>5. Verificar SITE_URL</h2>";
if (defined('SITE_URL')) {
    echo "<p>SITE_URL definido: <strong>" . SITE_URL . "</strong></p>";
} else {
    echo "<p style='color:red'>❌ SITE_URL NÃO está definido! Isso pode causar problemas.</p>";
}

echo "<br><br>";
echo "<a href='?pedido_id=$pedido_id&create_token=1' style='padding:10px 20px; background:green; color:white; text-decoration:none; border-radius:5px;'>🔧 Criar Token Manualmente</a>";
?>
