<?php
include 'includes/config.php';

echo "Color Configuration Fields:\n";
try {
    $stmt = $pdo->query("DESCRIBE configuracoes");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\nAll columns in configuracoes:\n";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\n\nColor-related values:\n";
    $stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    foreach ($config as $key => $value) {
        if (strpos($key, 'cor') !== false || strpos($key, 'tema') !== false || strpos($key, 'dark') !== false || strpos($key, 'color') !== false || strpos($key, 'background') !== false) {
            echo "$key: " . ($value ?? 'NULL') . "\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
