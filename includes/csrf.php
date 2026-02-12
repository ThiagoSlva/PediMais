<?php
/**
 * CSRF Protection Helper
 * Gera e valida tokens CSRF para proteger formulários contra ataques Cross-Site Request Forgery
 */

function gerar_token_csrf(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time']) ||
    (time() - $_SESSION['csrf_token_time']) > 3600) { // Expira em 1h
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }

    return $_SESSION['csrf_token'];
}

function campo_csrf(): string
{
    $token = gerar_token_csrf();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

function validar_csrf(?string $token = null): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }

    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }

    // Verificar expiração (1 hora)
    if (empty($_SESSION['csrf_token_time']) || (time() - $_SESSION['csrf_token_time']) > 3600) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}
