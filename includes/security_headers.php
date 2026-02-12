<?php
/**
 * Configurações de Segurança - Headers e Sessão
 * Incluir ANTES de session_start() em todos os arquivos que usam sessão
 */

// ===== Configurações de Sessão Segura =====
ini_set('session.cookie_httponly', 1); // Impede acesso ao cookie via JavaScript
ini_set('session.cookie_samesite', 'Lax'); // Proteção contra CSRF
ini_set('session.use_strict_mode', 1); // Rejeita IDs de sessão não inicializados

// cookie_secure APENAS se HTTPS estiver ativo (não quebra localhost)
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

// ===== Headers de Segurança HTTP =====
header('X-Content-Type-Options: nosniff'); // Previne MIME-sniffing
header('X-Frame-Options: SAMEORIGIN'); // Previne clickjacking
header('X-XSS-Protection: 1; mode=block'); // Filtro XSS do navegador
header('Referrer-Policy: strict-origin-when-cross-origin'); // Controla informação de referrer
