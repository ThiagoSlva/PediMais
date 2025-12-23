<?php
include 'includes/config.php';
try {
    // Find an order that is NOT in avaliacoes
    $sql = "SELECT p.id FROM pedidos p LEFT JOIN avaliacoes a ON p.id = a.pedido_id WHERE a.id IS NULL LIMIT 1";
    $stmt = $pdo->query($sql);
    $id = $stmt->fetchColumn();
    if ($id) {
        echo $id;
    } else {
        echo "No eligible order found";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
