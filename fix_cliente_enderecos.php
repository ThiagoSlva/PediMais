<?php
require_once __DIR__ . '/includes/config.php';

try {
    // Dropar tabela e recriar com todas as colunas
    $pdo->exec("DROP TABLE IF EXISTS cliente_enderecos");
    echo "Tabela anterior removida.\n";
    
    $sql = "CREATE TABLE `cliente_enderecos` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `cliente_id` INT NOT NULL,
        `apelido` VARCHAR(50) DEFAULT NULL,
        `cep` VARCHAR(10),
        `rua` VARCHAR(255),
        `numero` VARCHAR(20),
        `complemento` VARCHAR(100),
        `bairro` VARCHAR(100),
        `cidade` VARCHAR(100),
        `estado` VARCHAR(2),
        `principal` TINYINT(1) DEFAULT 0,
        `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_cliente_enderecos` (`cliente_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ Tabela cliente_enderecos recriada com todas as colunas!\n";
    
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
