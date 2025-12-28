<?php
/**
 * Configurações de Email SMTP
 * Carrega credenciais do arquivo .env
 */

// Garantir que o env_loader foi carregado
if (!function_exists('env')) {
    require_once __DIR__ . '/env_loader.php';
}

// Configurações de Email (via .env)
if (!defined('EMAIL_SMTP_HOST')) {
    define('EMAIL_SMTP_HOST', env('EMAIL_SMTP_HOST', 'mail.shopdix.com.br'));
    define('EMAIL_SMTP_PORT', env('EMAIL_SMTP_PORT', 465));
    define('EMAIL_SMTP_USER', env('EMAIL_SMTP_USER', 'atendimento@shopdix.com.br'));
    define('EMAIL_SMTP_PASSWORD', env('EMAIL_SMTP_PASSWORD', ''));
    define('EMAIL_FROM_NAME', env('EMAIL_FROM_NAME', 'PediMais'));
}
