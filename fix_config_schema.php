<?php
require_once 'includes/config.php';

try {
    echo "Checking configuracoes table...\n";

    // Ensure table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracoes (
        id INT AUTO_INCREMENT PRIMARY KEY
    )");

    // List of columns to ensure exist
    $columns_needed = [
        'nome_site' => 'VARCHAR(255)',
        'descricao_site' => 'TEXT',
        'logo' => 'VARCHAR(255)',
        'favicon' => 'VARCHAR(255)',
        'capa' => 'VARCHAR(255)',
        'cep' => 'VARCHAR(20)',
        'rua' => 'VARCHAR(255)',
        'numero' => 'VARCHAR(50)',
        'complemento' => 'VARCHAR(255)',
        'bairro' => 'VARCHAR(100)',
        'cidade' => 'VARCHAR(100)',
        'estado' => 'VARCHAR(2)',
        'whatsapp' => 'VARCHAR(20)',
        'email_contato' => 'VARCHAR(255)',
        'facebook' => 'VARCHAR(255)',
        'instagram' => 'VARCHAR(255)',
        'tema' => "VARCHAR(50) DEFAULT 'roxo'",
        'cor_principal' => 'VARCHAR(20)',
        'cor_secundaria' => 'VARCHAR(20)'
    ];

    $stmt = $pdo->query("DESCRIBE configuracoes");
    $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($columns_needed as $col => $def) {
        if (!in_array($col, $existing_columns)) {
            echo "Adding column: $col\n";
            $pdo->exec("ALTER TABLE configuracoes ADD COLUMN $col $def");
        } else {
            echo "Column exists: $col\n";
        }
    }

    // Ensure at least one row exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM configuracoes");
    if ($stmt->fetchColumn() == 0) {
        echo "Inserting default row...\n";
        $pdo->exec("INSERT INTO configuracoes (nome_site, tema) VALUES ('PedeMais', 'roxo')");
    }

    echo "Done.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
