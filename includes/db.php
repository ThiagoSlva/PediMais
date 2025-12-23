<?php
declare(strict_types=1);

// Configurações do Banco de Dados
define('DB_SERVER', '104.225.130.177');
define('DB_USERNAME', 'xfxpanel_cardapix'); // Mudar para um usuário seguro em produção
define('DB_PASSWORD', '72734108Thi@go'); // Mudar para uma senha segura em produção
define('DB_NAME', 'xfxpanel_cardapix');

// Opções de conexão PDO com segurança aprimorada
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

// Conexão com o Banco de Dados
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
