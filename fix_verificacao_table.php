<?php
/**
 * Fix: Atualizar tabela verificacao_codigos para ter as colunas corretas
 */
require_once 'includes/config.php';

echo "<h2>Corrigindo tabela verificacao_codigos</h2>";

try {
    // Verificar estrutura atual
    $stmt = $pdo->query("DESCRIBE verificacao_codigos");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Colunas atuais: " . implode(', ', $columns) . "</p>";
    
    // Verificar se cliente_id existe
    if (!in_array('cliente_id', $columns)) {
        echo "<p>Adicionando coluna cliente_id...</p>";
        $pdo->exec("ALTER TABLE verificacao_codigos ADD COLUMN cliente_id INT NOT NULL AFTER id");
        echo "<p style='color: green;'>✅ Coluna cliente_id adicionada!</p>";
    }
    
    // Verificar se codigo existe
    if (!in_array('codigo', $columns)) {
        echo "<p>Adicionando coluna codigo...</p>";
        $pdo->exec("ALTER TABLE verificacao_codigos ADD COLUMN codigo VARCHAR(6) NOT NULL");
        echo "<p style='color: green;'>✅ Coluna codigo adicionada!</p>";
    }
    
    // Verificar se expira_em existe
    if (!in_array('expira_em', $columns)) {
        echo "<p>Adicionando coluna expira_em...</p>";
        $pdo->exec("ALTER TABLE verificacao_codigos ADD COLUMN expira_em DATETIME NOT NULL");
        echo "<p style='color: green;'>✅ Coluna expira_em adicionada!</p>";
    }
    
    // Verificar se usado existe
    if (!in_array('usado', $columns)) {
        echo "<p>Adicionando coluna usado...</p>";
        $pdo->exec("ALTER TABLE verificacao_codigos ADD COLUMN usado TINYINT(1) DEFAULT 0");
        echo "<p style='color: green;'>✅ Coluna usado adicionada!</p>";
    }
    
    // Verificar se data_envio existe
    if (!in_array('data_envio', $columns)) {
        echo "<p>Adicionando coluna data_envio...</p>";
        $pdo->exec("ALTER TABLE verificacao_codigos ADD COLUMN data_envio DATETIME DEFAULT CURRENT_TIMESTAMP");
        echo "<p style='color: green;'>✅ Coluna data_envio adicionada!</p>";
    }
    
    // Mostrar estrutura final
    $stmt = $pdo->query("DESCRIBE verificacao_codigos");
    $columns_final = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Colunas finais: " . implode(', ', $columns_final) . "</p>";
    
    echo "<h3 style='color: green;'>✅ Tabela corrigida com sucesso!</h3>";
    echo "<p><a href='index.php'>Voltar ao site e testar novamente</a></p>";
    
} catch (PDOException $e) {
    // Se tabela não existe, criar do zero
    if (strpos($e->getMessage(), 'doesn\'t exist') !== false) {
        echo "<p>Criando tabela verificacao_codigos...</p>";
        $pdo->exec("CREATE TABLE verificacao_codigos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cliente_id INT NOT NULL,
            codigo VARCHAR(6) NOT NULL,
            expira_em DATETIME NOT NULL,
            usado TINYINT(1) DEFAULT 0,
            data_envio DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        echo "<h3 style='color: green;'>✅ Tabela criada com sucesso!</h3>";
    } else {
        echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
    }
}

// Verificar também a coluna telefone_verificado na tabela clientes
echo "<h2>Verificando tabela clientes</h2>";

try {
    $stmt = $pdo->query("DESCRIBE clientes");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('telefone_verificado', $columns)) {
        echo "<p>Adicionando coluna telefone_verificado...</p>";
        $pdo->exec("ALTER TABLE clientes ADD COLUMN telefone_verificado TINYINT(1) DEFAULT 0");
        echo "<p style='color: green;'>✅ Coluna telefone_verificado adicionada!</p>";
    } else {
        echo "<p style='color: green;'>✅ Coluna telefone_verificado já existe!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}
