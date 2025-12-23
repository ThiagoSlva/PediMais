<?php
// Definir timezone para Brasil (Brasília)
date_default_timezone_set('America/Sao_Paulo');

// Guard para evitar redefinição
if (!defined('DB_HOST')) {
    // Configurações do Banco de Dados
    define('DB_HOST', '104.225.130.177');
    define('DB_NAME', 'xfxpanel_cardapix');
    define('DB_USER', 'xfxpanel_cardapix');
    define('DB_PASS', '72734108Thi@go');

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
    } catch (PDOException $e) {
        die("Erro na conexão com o banco de dados: " . $e->getMessage());
    }
}
?>