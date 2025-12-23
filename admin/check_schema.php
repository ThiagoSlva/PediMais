<?php
include 'includes/auth.php';
include '../includes/config.php';

echo "<h2>Columns in whatsapp_config:</h2>";
$stmt = $pdo->query("DESCRIBE whatsapp_config");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($columns);

echo "<h2>Messages in whatsapp_mensagens:</h2>";
$stmt = $pdo->query("SELECT id, tipo FROM whatsapp_mensagens");
$msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($msgs);
?>
