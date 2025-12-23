<?php
include 'includes/auth.php';
include '../includes/config.php';

echo "<h2>Checking Message IDs 149 and 150:</h2>";
$stmt = $pdo->query("SELECT * FROM whatsapp_mensagens WHERE id IN (149, 150)");
$msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($msgs);
?>
