<?php
// Definir timezone para Brasil (Brasília)
date_default_timezone_set('America/Sao_Paulo');

// Carregar variáveis de ambiente
require_once __DIR__ . '/env_loader.php';

// Guard para evitar redefinição
if (!defined('DB_HOST')) {
    // Configurações do Banco de Dados (via .env ou fallback)
    define('DB_HOST', env('DB_HOST', 'localhost'));
    define('DB_NAME', env('DB_NAME', 'cardapix'));
    define('DB_USER', env('DB_USER', 'root'));
    define('DB_PASS', env('DB_PASS', ''));

    // Configurações do Sistema
    // Detectar IP local dinamicamente para acesso via rede (LAN)
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    define('SITE_URL', "$protocol://$host");
}

// Conexão PDO apenas se não existir
if (!isset($pdo)) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    catch (PDOException $e) {
        error_log("Erro na conexão com o banco de dados: " . $e->getMessage());
        die("Erro na conexão com o banco de dados. Por favor, tente novamente mais tarde.");
    }
}
?>