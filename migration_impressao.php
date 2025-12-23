<?php
/**
 * Migration para adicionar colunas necessárias para o sistema de impressão
 * Execute este arquivo uma vez para atualizar o banco de dados
 */
require_once 'includes/config.php';

echo "=== Migration: Sistema de Impressão ===\n\n";

try {
    // 1. Adicionar coluna 'impresso' na tabela pedidos
    $stmt = $pdo->query("DESCRIBE pedidos");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('impresso', $columns)) {
        $pdo->exec("ALTER TABLE pedidos ADD COLUMN impresso TINYINT(1) DEFAULT 0");
        echo "✓ Coluna 'impresso' adicionada em pedidos\n";
    } else {
        echo "- Coluna 'impresso' já existe em pedidos\n";
    }
    
    if (!in_array('data_impressao', $columns)) {
        $pdo->exec("ALTER TABLE pedidos ADD COLUMN data_impressao DATETIME NULL");
        echo "✓ Coluna 'data_impressao' adicionada em pedidos\n";
    } else {
        echo "- Coluna 'data_impressao' já existe em pedidos\n";
    }
    
    // 2. Adicionar coluna 'api_token' na tabela usuarios
    $stmt = $pdo->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('api_token', $columns)) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN api_token VARCHAR(64) NULL");
        echo "✓ Coluna 'api_token' adicionada em usuarios\n";
        
        // Gerar token para o admin
        $token = bin2hex(random_bytes(32));
        $pdo->exec("UPDATE usuarios SET api_token = '$token' WHERE nivel_acesso = 'admin' LIMIT 1");
        echo "✓ Token gerado para admin: $token\n";
    } else {
        echo "- Coluna 'api_token' já existe em usuarios\n";
        
        // Mostrar token existente
        $stmt = $pdo->query("SELECT api_token FROM usuarios WHERE nivel_acesso = 'admin' AND api_token IS NOT NULL LIMIT 1");
        $row = $stmt->fetch();
        if ($row && $row['api_token']) {
            echo "  Token admin existente: " . $row['api_token'] . "\n";
        } else {
            $token = bin2hex(random_bytes(32));
            $pdo->exec("UPDATE usuarios SET api_token = '$token' WHERE nivel_acesso = 'admin' LIMIT 1");
            echo "✓ Token gerado para admin: $token\n";
        }
    }
    
    echo "\n=== Migration concluída com sucesso! ===\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
