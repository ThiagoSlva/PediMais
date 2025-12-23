<?php
require_once 'includes/config.php';

// 1. Inspect Column Type
echo "--- Column 'status' Type ---\n";
$stmt = $pdo->query("SHOW COLUMNS FROM pedidos LIKE 'status'");
$col = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($col);

// 2. Check current status of #27
$id = 27;
$stmt = $pdo->prepare("SELECT status FROM pedidos WHERE id = ?");
$stmt->execute([$id]);
$curr = $stmt->fetchColumn();
echo "\nCurrent Status (#$id): '$curr'\n";

// 3. Try Update 'em_preparo'
echo "\n--- Attempting Update to 'em_preparo' ---\n";
$stmt = $pdo->prepare("UPDATE pedidos SET status = 'em_preparo' WHERE id = ?");
try {
    $stmt->execute([$id]);
    echo "Execute OK. Rows Affected: " . $stmt->rowCount() . "\n";
} catch (Exception $e) {
    echo "Update Failed: " . $e->getMessage() . "\n";
}

// Verify
$stmt->execute([$id]); // re-execute select? no.
$stmt = $pdo->prepare("SELECT status FROM pedidos WHERE id = ?");
$stmt->execute([$id]);
$new = $stmt->fetchColumn();
echo "New Status (#$id): '$new'\n";

if ($new !== 'em_preparo') {
    // 4. Try Update 'Em Preparo' (Title Case)
    echo "\n--- Attempting Update to 'Em Preparo' ---\n";
    $stmt = $pdo->prepare("UPDATE pedidos SET status = 'Em Preparo' WHERE id = ?");
    try {
        $stmt->execute([$id]);
        echo "Execute OK. Rows Affected: " . $stmt->rowCount() . "\n";
    } catch (Exception $e) {
        echo "Update Failed: " . $e->getMessage() . "\n";
    }
    
    // Verify
    $stmt = $pdo->prepare("SELECT status FROM pedidos WHERE id = ?");
    $stmt->execute([$id]);
    $new2 = $stmt->fetchColumn();
    echo "New Status (#$id): '$new2'\n";
}
?>
