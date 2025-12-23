<?php
include '../includes/config.php';

try {
    $tables = [];
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    echo "<h1>Database Tables</h1>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li><strong>$table</strong>";
        
        // Get columns
        $stmt_cols = $pdo->query("DESCRIBE $table");
        echo "<ul>";
        while ($col = $stmt_cols->fetch(PDO::FETCH_ASSOC)) {
            echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
        }
        echo "</ul>";
        echo "</li>";
    }
    echo "</ul>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
