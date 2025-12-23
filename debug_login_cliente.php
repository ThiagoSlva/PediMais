<?php
session_start();
$_SESSION['cliente_id'] = 1; // Assuming ID 1 exists, if not I'll need to create one or find one.
$_SESSION['cliente_nome'] = 'Teste Cliente';
$_SESSION['cliente_email'] = 'teste@cliente.com';
header('Location: cliente/dashboard.php');
