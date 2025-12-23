<?php
include 'includes/config.php';

try {
    // Check and add 'ativo' to 'produtos'
    $stmt = $pdo->query("DESCRIBE produtos");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('ativo', $columns)) {
        $pdo->exec("ALTER TABLE produtos ADD COLUMN ativo TINYINT(1) DEFAULT 1");
        echo "Added 'ativo' column to 'produtos' table.\n";
    } else {
        echo "'ativo' column already exists in 'produtos' table.\n";
    }

    // Check and add 'ativo' to 'categorias'
    $stmt = $pdo->query("DESCRIBE categorias");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('ativo', $columns)) {
        $pdo->exec("ALTER TABLE categorias ADD COLUMN ativo TINYINT(1) DEFAULT 1");
        echo "Added 'ativo' column to 'categorias' table.\n";
    } else {
        echo "'ativo' column already exists in 'categorias' table.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
