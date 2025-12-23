<?php
require_once 'includes/config.php';

echo "<h2>Debug: cliente_enderecos</h2>";

// Verificar estrutura da tabela
try {
    $stmt = $pdo->query("DESCRIBE cliente_enderecos");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Colunas: " . implode(', ', $cols) . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Erro: " . $e->getMessage() . "</p>";
}

// Verificar registros
$stmt = $pdo->query("SELECT * FROM cliente_enderecos ORDER BY id DESC LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Total de endereços: " . count($rows) . "</p>";

if (empty($rows)) {
    echo "<p>Nenhum endereço cadastrado.</p>";
} else {
    echo "<table border='1' cellpadding='5'><tr>";
    foreach (array_keys($rows[0]) as $col) {
        echo "<th>$col</th>";
    }
    echo "</tr>";
    foreach ($rows as $row) {
        echo "<tr>";
        foreach ($row as $val) {
            echo "<td>" . htmlspecialchars($val ?? '') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}
