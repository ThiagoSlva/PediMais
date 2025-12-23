<?php
/**
 * Setup script para criar a tabela de endereços de cliente
 * Execute uma vez para criar a estrutura do banco de dados
 */

require_once __DIR__ . '/../includes/config.php';

try {
    // Criar tabela cliente_enderecos
    $sql = "CREATE TABLE IF NOT EXISTS `cliente_enderecos` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `cliente_id` INT NOT NULL,
        `apelido` VARCHAR(50) DEFAULT NULL COMMENT 'Ex: Casa, Trabalho',
        `cep` VARCHAR(10),
        `rua` VARCHAR(255),
        `numero` VARCHAR(20),
        `complemento` VARCHAR(100),
        `bairro` VARCHAR(100),
        `cidade` VARCHAR(100),
        `estado` VARCHAR(2),
        `principal` TINYINT(1) DEFAULT 0 COMMENT 'Endereço padrão',
        `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE CASCADE,
        INDEX `idx_cliente_enderecos` (`cliente_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ Tabela 'cliente_enderecos' criada com sucesso!\n";
    
    // Migrar endereços existentes dos clientes para a nova tabela
    $sql = "INSERT INTO cliente_enderecos (cliente_id, apelido, cep, rua, numero, complemento, bairro, cidade, estado, principal)
            SELECT id, 'Principal', cep, rua, numero, complemento, bairro, cidade, estado, 1
            FROM clientes 
            WHERE (rua IS NOT NULL AND rua != '') 
               OR (bairro IS NOT NULL AND bairro != '')";
    
    $count = $pdo->exec($sql);
    echo "✅ Migrados {$count} endereços existentes para a nova tabela.\n";
    
    echo "\n🎉 Setup concluído com sucesso!\n";
    
} catch (PDOException $e) {
    // Verificar se é erro de tabela já existente ou FK duplicada
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "ℹ️ Tabela já existe, pulando criação.\n";
    } elseif (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo "ℹ️ Endereços já foram migrados anteriormente.\n";
    } else {
        echo "❌ Erro: " . $e->getMessage() . "\n";
    }
}
