<?php
include 'includes/config.php';
try {
    $telefone = '99999999999';
    $codigo = '123456';
    // Insert code valid for 10 minutes
    $stmt = $pdo->prepare("INSERT INTO verificacao_codigos (telefone, codigo, expira_em) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
    $stmt->execute([$telefone, $codigo]);
    
    // Ensure client exists
    $stmt = $pdo->prepare("SELECT id FROM clientes WHERE telefone = ?");
    $stmt->execute([$telefone]);
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO clientes (nome, telefone, telefone_verificado) VALUES ('Test Client', ?, 0)")->execute([$telefone]);
    } else {
        // Reset verification status
        $pdo->prepare("UPDATE clientes SET telefone_verificado = 0 WHERE telefone = ?")->execute([$telefone]);
    }
    
    echo "Code inserted and client prepared.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
