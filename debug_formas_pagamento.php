<?php
require_once 'includes/config.php';
$stmt = $pdo->query("SELECT * FROM formas_pagamento");
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('debug_output.json', json_encode($res, JSON_PRETTY_PRINT));
echo "Done";
?>
