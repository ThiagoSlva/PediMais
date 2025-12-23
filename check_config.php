<?php
require_once 'includes/config.php';
require_once 'includes/whatsapp_helper.php';

$stmt = $pdo->query("SELECT enviar_comprovante, notificar_status_pedido FROM whatsapp_config LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo "enviar_comprovante: " . $row['enviar_comprovante'] . "\n";
echo "notificar_status_pedido: " . $row['notificar_status_pedido'] . "\n";

$wh = new WhatsAppHelper($pdo);
echo "shouldSendOrderNotification: " . ($wh->shouldSendOrderNotification() ? "SIM" : "NAO") . "\n";
?>
