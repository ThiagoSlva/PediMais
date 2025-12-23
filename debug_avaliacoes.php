<?php
include 'includes/config.php';
try {
    $stmt = $pdo->query("DESCRIBE avaliacoes");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($columns);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
