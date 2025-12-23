<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'includes/config.php';
require_once 'includes/horarios_helper.php';

echo "Testing HorariosHelper...\n";
if (!$pdo) die("PDO is null in script\n");

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracao_horarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sistema_ativo TINYINT(1) DEFAULT 1,
        loja_aberta_manual TINYINT(1) DEFAULT NULL
    )");
    echo "Table created/checked in test script.\n";
    
    $stmt = $pdo->query("SELECT id FROM configuracao_horarios LIMIT 1");
    if (!$stmt->fetch()) {
        $pdo->exec("INSERT INTO configuracao_horarios (sistema_ativo, loja_aberta_manual) VALUES (1, NULL)");
        echo "Row inserted.\n";
    }
} catch (PDOException $e) {
    die("Error creating table: " . $e->getMessage() . "\n");
}

$horarios = new HorariosHelper($pdo);
echo "Object created.\n";
$aberta = $horarios->isLojaAberta();
echo "Loja aberta: " . ($aberta ? 'Sim' : 'Não') . "\n";
?>
