<?php
/**
 * Setup script para criar a tabela de adicionais de itens de pedido
 * Execute uma vez para criar a estrutura do banco de dados
 */

require_once __DIR__ . '/../includes/config.php';

try {
    // Criar tabela pedido_item_adicionais
    $sql = "CREATE TABLE IF NOT EXISTS `pedido_item_adicionais` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `pedido_item_id` INT NOT NULL,
        `adicional_id` INT NOT NULL,
        `nome` VARCHAR(255) NOT NULL,
        `preco` DECIMAL(10,2) DEFAULT 0.00,
        `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_pedido_item` (`pedido_item_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ Tabela 'pedido_item_adicionais' criada com sucesso!\n";
    
    echo "\n🎉 Setup concluído com sucesso!\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "ℹ️ Tabela já existe.\n";
    } else {
        echo "❌ Erro: " . $e->getMessage() . "\n";
    }
}
