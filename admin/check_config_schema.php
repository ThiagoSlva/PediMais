<?php
require 'includes/config.php';

try {
    $stmt = $pdo->query("DESCRIBE configuracoes");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns: " . implode(", ", $columns) . "\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
