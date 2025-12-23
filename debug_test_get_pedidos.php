<?php
session_start();
$_SESSION['cliente_id'] = 1;

// Capture output
ob_start();
include 'cliente/api/get_pedidos.php';
$output = ob_get_clean();

echo $output;
?>
