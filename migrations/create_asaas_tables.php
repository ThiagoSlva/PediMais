<?php
/**
 * Migration: Criar tabelas para Gateway Asaas
 * Execute este arquivo uma única vez para criar as tabelas necessárias
 */

require_once __DIR__ . '/../includes/config.php';

try {
    echo "Iniciando migração para Gateway Asaas...\n";

    // 1. Criar tabela asaas_config
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `asaas_config` (
          `id` int(11) NOT NULL DEFAULT 1,
          `ativo` tinyint(1) DEFAULT 0 COMMENT 'Ativar/Desativar pagamento online via Asaas',
          `nome` varchar(100) DEFAULT 'PIX Asaas' COMMENT 'Nome exibido no checkout',
          `sandbox_mode` tinyint(1) DEFAULT 1 COMMENT '1=Sandbox, 0=Produção',
          `access_token` varchar(255) DEFAULT NULL COMMENT 'Access Token da API Asaas',
          `address_key` varchar(255) DEFAULT NULL COMMENT 'Chave PIX cadastrada no Asaas',
          `prazo_pagamento_minutos` int(11) DEFAULT 30 COMMENT 'Prazo em minutos para pagamento',
          `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Tabela asaas_config criada\n";

    // 2. Inserir registro padrão
    $stmt = $pdo->query("SELECT COUNT(*) FROM asaas_config");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO asaas_config (id, ativo, nome) VALUES (1, 0, 'PIX Asaas')");
        echo "✅ Registro padrão inserido em asaas_config\n";
    }

    // 3. Criar tabela asaas_pagamentos
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `asaas_pagamentos` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `pedido_id` int(11) NOT NULL,
          `payment_id` varchar(100) DEFAULT NULL COMMENT 'ID do pagamento no Asaas',
          `qr_code` text DEFAULT NULL COMMENT 'Código PIX copia e cola (payload)',
          `qr_code_base64` longtext DEFAULT NULL COMMENT 'Imagem do QR Code em base64',
          `status` varchar(50) DEFAULT 'pending',
          `valor` decimal(10,2) NOT NULL,
          `expiracao` datetime DEFAULT NULL,
          `pago_em` datetime DEFAULT NULL,
          `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `pedido_id` (`pedido_id`),
          CONSTRAINT `fk_asaas_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Tabela asaas_pagamentos criada\n";

    // 4. Criar tabela gateway_settings
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `gateway_settings` (
          `id` int(11) NOT NULL DEFAULT 1,
          `gateway_ativo` enum('mercadopago','asaas','none') DEFAULT 'none' COMMENT 'Gateway de pagamento ativo',
          `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✅ Tabela gateway_settings criada\n";

    // 5. Inserir registro padrão em gateway_settings
    $stmt = $pdo->query("SELECT COUNT(*) FROM gateway_settings");
    if ($stmt->fetchColumn() == 0) {
        // Verificar se Mercado Pago está ativo para manter compatibilidade
        $mp_stmt = $pdo->query("SELECT ativo FROM mercadopago_config LIMIT 1");
        $mp_config = $mp_stmt->fetch(PDO::FETCH_ASSOC);
        $gateway_padrao = ($mp_config && $mp_config['ativo']) ? 'mercadopago' : 'none';
        
        $pdo->exec("INSERT INTO gateway_settings (id, gateway_ativo) VALUES (1, '$gateway_padrao')");
        echo "✅ Registro padrão inserido em gateway_settings (gateway: $gateway_padrao)\n";
    }

    echo "\n🎉 Migração concluída com sucesso!\n";

} catch (PDOException $e) {
    echo "❌ Erro na migração: " . $e->getMessage() . "\n";
    exit(1);
}
?>
