<?php
declare(strict_types=1);

/**
 * Conexão com Banco de Dados
 * Credenciais carregadas via .env para segurança
 */

// Carregar variáveis de ambiente
require_once __DIR__ . '/env_loader.php';

// Evitar redefinição de constantes
if (!defined('DB_SERVER')) {
    // Configurações do Banco de Dados (via .env)
    define('DB_SERVER', env('DB_HOST', 'localhost'));
    define('DB_USERNAME', env('DB_USER', 'root'));
    define('DB_PASSWORD', env('DB_PASS', ''));
    define('DB_NAME', env('DB_NAME', 'cardapix'));
}

// Opções de conexão PDO com segurança aprimorada
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

// Conexão com o Banco de Dados (apenas se não existir)
if (!isset($pdo)) {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USERNAME,
            DB_PASSWORD,
            $options
        );
    } catch (PDOException $e) {
        error_log("Erro de conexão ao banco de dados: " . $e->getMessage());
        die("ERRO: Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.");
    }
}
