<?php
session_start();

function verificar_login_cliente() {
    if (!isset($_SESSION['cliente_id'])) {
        header('Location: login.php');
        exit;
    }
}

function get_cliente_atual() {
    if (isset($_SESSION['cliente_id'])) {
        return [
            'id' => $_SESSION['cliente_id'],
            'nome' => $_SESSION['cliente_nome'],
            'email' => $_SESSION['cliente_email']
        ];
    }
    return null;
}
?>