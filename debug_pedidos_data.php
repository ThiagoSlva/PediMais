<?php
include 'includes/config.php';
try {
    $stmt = $pdo->query("SELECT id, status, pagamento_online FROM pedidos LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
