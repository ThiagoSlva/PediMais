<?php
include 'includes/config.php';

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM whatsapp_config");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "whatsapp_config columns:\n";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
