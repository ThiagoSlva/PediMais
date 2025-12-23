<?php
include 'includes/config.php';

try {
    $stmt = $pdo->query("DESCRIBE formas_pagamento");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns: " . implode(", ", $columns) . "\n";
} catch (PDOException $e) {
    echo "Table does not exist or error: " . $e->getMessage();
}
?>
