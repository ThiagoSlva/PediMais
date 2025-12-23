<?php
include 'includes/config.php';
try {
    $stmt = $pdo->query("DESCRIBE verificacao_codigos");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($columns);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
