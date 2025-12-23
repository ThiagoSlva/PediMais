<?php
require 'includes/config.php';

try {
    $stmt = $pdo->query("DESCRIBE configuracao_horarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($columns);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
