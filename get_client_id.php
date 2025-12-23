<?php
require 'includes/config.php';
$stmt = $pdo->query("SELECT id FROM clientes LIMIT 1");
$id = $stmt->fetchColumn();
echo $id ? $id : "No clients found";
?>
