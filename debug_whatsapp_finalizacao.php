<?php
/**
 * Debug script to test WhatsApp finalization message
 */
require_once 'includes/config.php';
require_once 'includes/whatsapp_helper.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 Debug WhatsApp Finalization</h1>";

// 1. Verificar configuração de avaliações
echo "<h2>1. Configuração de Avaliações</h2>";
$stmt = $pdo->query("SELECT * FROM configuracao_avaliacoes LIMIT 1");
$av_config = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>" . print_r($av_config, true) . "</pre>";

if ($av_config && $av_config['ativo']) {
    echo "<p style='color:green'>✅ Sistema de avaliações ATIVO</p>";
} else {
    echo "<p style='color:red'>❌ Sistema de avaliações INATIVO</p>";
}

// 2. Verificar templates de WhatsApp
echo "<h2>2. Templates de WhatsApp (Status Finalizados)</h2>";
$stmt = $pdo->query("SELECT id, tipo, ativo, LEFT(mensagem, 100) as preview FROM whatsapp_mensagens WHERE id IN (9, 12)");
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Tipo</th><th>Ativo</th><th>Preview</th></tr>";
foreach ($templates as $t) {
    $color = $t['ativo'] ? 'green' : 'red';
    echo "<tr>";
    echo "<td>{$t['id']}</td>";
    echo "<td>{$t['tipo']}</td>";
    echo "<td style='color:$color'>" . ($t['ativo'] ? 'SIM' : 'NÃO') . "</td>";
    echo "<td>{$t['preview']}...</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Verificar configuração do WhatsApp
echo "<h2>3. Configuração WhatsApp</h2>";
$whatsapp = new WhatsAppHelper($pdo);
echo "<p>Configurado: " . ($whatsapp->isConfigured() ? '<span style="color:green">SIM</span>' : '<span style="color:red">NÃO</span>') . "</p>";
echo "<p>Notificação de Status: " . ($whatsapp->shouldSendStatusNotification() ? '<span style="color:green">SIM</span>' : '<span style="color:red">NÃO</span>') . "</p>";

// 4. Verificar último pedido finalizado
echo "<h2>4. Último Pedido Finalizado</h2>";
$stmt = $pdo->query("SELECT id, codigo_pedido, cliente_nome, cliente_telefone, status, valor_total FROM pedidos WHERE status IN ('finalizado', 'concluido') ORDER BY id DESC LIMIT 1");
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);
if ($pedido) {
    echo "<pre>" . print_r($pedido, true) . "</pre>";
    
    // Verificar se tem token de avaliação
    $stmt = $pdo->prepare("SELECT * FROM avaliacoes WHERE pedido_id = ?");
    $stmt->execute([$pedido['id']]);
    $avaliacao = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h3>Token de Avaliação:</h3>";
    if ($avaliacao) {
        echo "<pre>" . print_r($avaliacao, true) . "</pre>";
        echo "<p><a href='avaliar_pedido.php?token={$avaliacao['token']}' target='_blank'>🔗 Testar Link de Avaliação</a></p>";
    } else {
        echo "<p style='color:red'>❌ Nenhum token de avaliação encontrado para este pedido!</p>";
    }
} else {
    echo "<p style='color:red'>Nenhum pedido finalizado encontrado</p>";
}

// 5. Verificar logs de WhatsApp
echo "<h2>5. Últimos Logs de WhatsApp</h2>";
$stmt = $pdo->query("SELECT * FROM whatsapp_logs ORDER BY id DESC LIMIT 5");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Telefone</th><th>Status</th><th>Erro</th><th>Data</th></tr>";
foreach ($logs as $l) {
    $color = $l['status'] == 'enviado' ? 'green' : 'red';
    echo "<tr>";
    echo "<td>{$l['id']}</td>";
    echo "<td>{$l['cliente_telefone']}</td>";
    echo "<td style='color:$color'>{$l['status']}</td>";
    echo "<td>" . substr($l['erro'] ?? '', 0, 50) . "</td>";
    echo "<td>{$l['enviado_em']}</td>";
    echo "</tr>";
}
echo "</table>";

// 6. Testar envio manual (se solicitado)
if (isset($_GET['test_send']) && $pedido) {
    echo "<h2>6. Teste de Envio Manual</h2>";
    echo "<p>Testando envio para status 'finalizado'...</p>";
    
    $result = $whatsapp->sendStatusUpdate($pedido, 'finalizado');
    echo "<pre>" . print_r($result, true) . "</pre>";
}

echo "<br><br><a href='?test_send=1'>🚀 Testar Envio Manual (finalizado)</a>";
?>
