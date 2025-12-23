<?php
include 'includes/config.php';
try {
    $stmt = $pdo->query("SELECT * FROM avaliacoes LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        print_r(array_keys($row));
    } else {
        echo "No reviews found, but table exists.";
        // Fallback: describe again but cleaner
        $stmt = $pdo->query("DESCRIBE avaliacoes");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        print_r($columns);
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
