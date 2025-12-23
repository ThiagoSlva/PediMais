<?php
require_once 'includes/config.php';

try {
    $tables = $pdo->query("SHOW TABLES LIKE '%fidelidade%'")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables found:\n";
    print_r($tables);
    
    foreach ($tables as $table) {
        echo "\nSchema for $table:\n";
        $columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        print_r($columns);
    }
    
    // Also check for a 'pontos' column in clientes just in case
    echo "\nChecking 'clientes' table for 'pontos' column:\n";
    $columns = $pdo->query("DESCRIBE clientes")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('pontos', $columns)) {
        echo "Column 'pontos' FOUND in 'clientes'.\n";
    } else {
        echo "Column 'pontos' NOT FOUND in 'clientes'.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
