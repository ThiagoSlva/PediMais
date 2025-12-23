<?php
require 'includes/config.php';

echo "=== Templates de Mensagens WhatsApp ===\n\n";
$stmt = $pdo->query("SELECT * FROM whatsapp_mensagens ORDER BY id");
while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $r['id'] . "\n";
    echo "Evento: " . ($r['evento'] ?? 'N/A') . "\n";
    echo "Título: " . ($r['titulo'] ?? 'N/A') . "\n";
    echo "Ativo: " . ($r['ativo'] ? 'SIM' : 'NÃO') . "\n";
    echo "Mensagem: " . substr($r['mensagem'] ?? '', 0, 100) . "...\n";
    echo "---\n";
}

echo "\n=== Configuração WhatsApp ===\n";
$stmt = $pdo->query("SELECT * FROM whatsapp_config LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);
echo "ativo: " . ($config['ativo'] ?? 'N/A') . "\n";
echo "notificar_status_pedido: " . ($config['notificar_status_pedido'] ?? 'N/A') . "\n";
echo "enviar_comprovante: " . ($config['enviar_comprovante'] ?? 'N/A') . "\n";
