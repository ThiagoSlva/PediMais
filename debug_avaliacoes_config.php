<?php
/**
 * Debug script for review settings
 */
require_once 'includes/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 Debug Configuração Avaliações</h1>";

// 1. Verificar estado atual
echo "<h2>1. Estado Atual no Banco</h2>";
$stmt = $pdo->query("SELECT * FROM configuracao_avaliacoes");
$config = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($config, true) . "</pre>";

// 2. Se solicitado, testar update direto
if (isset($_GET['enable'])) {
    echo "<h2>2. Ativando mostrar_no_site...</h2>";
    $stmt = $pdo->prepare("UPDATE configuracao_avaliacoes SET mostrar_no_site = 1 WHERE id = ?");
    $stmt->execute([$config['id']]);
    echo "<p style='color:green'>✅ Comando executado! Rows affected: " . $stmt->rowCount() . "</p>";
    
    // Verificar novo estado
    $stmt = $pdo->query("SELECT * FROM configuracao_avaliacoes LIMIT 1");
    $new_config = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Novo valor de mostrar_no_site: <strong>" . $new_config['mostrar_no_site'] . "</strong></p>";
}

if (isset($_GET['disable'])) {
    echo "<h2>2. Desativando mostrar_no_site...</h2>";
    $stmt = $pdo->prepare("UPDATE configuracao_avaliacoes SET mostrar_no_site = 0 WHERE id = ?");
    $stmt->execute([$config['id']]);
    echo "<p style='color:orange'>⚠️ Desativado! Rows affected: " . $stmt->rowCount() . "</p>";
}

// 3. Verificar avaliações disponíveis
echo "<h2>3. Avaliações Disponíveis</h2>";
$stmt = $pdo->query("SELECT id, nome, cliente_nome, avaliacao, ativo, LEFT(descricao, 50) as preview FROM avaliacoes ORDER BY id DESC LIMIT 5");
$avaliacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Nome</th><th>Cliente</th><th>Nota</th><th>Ativo</th><th>Preview</th></tr>";
foreach ($avaliacoes as $av) {
    echo "<tr>";
    echo "<td>{$av['id']}</td>";
    echo "<td>{$av['nome']}</td>";
    echo "<td>{$av['cliente_nome']}</td>";
    echo "<td>{$av['avaliacao']}</td>";
    echo "<td>{$av['ativo']}</td>";
    echo "<td>{$av['preview']}</td>";
    echo "</tr>";
}
echo "</table>";

// 4. Links de ação
echo "<br><br>";
echo "<a href='?enable=1' style='padding:10px 20px; background:green; color:white; text-decoration:none; border-radius:5px; margin-right:10px;'>✅ Ativar Exibição</a>";
echo "<a href='?disable=1' style='padding:10px 20px; background:orange; color:white; text-decoration:none; border-radius:5px;'>❌ Desativar Exibição</a>";
echo "<br><br>";
echo "<a href='index.php'>🏠 Voltar ao Cardápio</a>";
?>
