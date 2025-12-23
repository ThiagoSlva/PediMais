<?php
include 'includes/config.php';

$stmt = $pdo->query("SELECT cor_principal, cor_secundaria, tema, cor_fundo, cor_texto, cor_card FROM configuracoes LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($config);
?>
