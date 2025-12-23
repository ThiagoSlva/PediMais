<?php
require_once 'includes/config.php';

echo "Forcing table creation...\n";

function exec_sql($pdo, $sql, $name) {
    try {
        $pdo->exec($sql);
        echo "[OK] $name created/checked.\n";
    } catch (PDOException $e) {
        echo "[ERROR] $name: " . $e->getMessage() . "\n";
    }
}

// 1. configuracoes
exec_sql($pdo, "CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_site VARCHAR(255),
    tema VARCHAR(50) DEFAULT 'roxo'
)", "configuracoes");

// 2. configuracao_horarios
exec_sql($pdo, "CREATE TABLE IF NOT EXISTS configuracao_horarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sistema_ativo TINYINT(1) DEFAULT 1,
    loja_aberta_manual TINYINT(1) DEFAULT NULL
)", "configuracao_horarios");

// 3. horarios_funcionamento
exec_sql($pdo, "CREATE TABLE IF NOT EXISTS horarios_funcionamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dia_semana INT NOT NULL,
    abertura TIME,
    fechamento TIME,
    ativo TINYINT(1) DEFAULT 1
)", "horarios_funcionamento");

// 4. configuracao_entrega
exec_sql($pdo, "CREATE TABLE IF NOT EXISTS configuracao_entrega (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modo_gratis_valor_ativo TINYINT(1) DEFAULT 0
)", "configuracao_entrega");

// 5. whatsapp_config
exec_sql($pdo, "CREATE TABLE IF NOT EXISTS whatsapp_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ativo TINYINT(1) DEFAULT 0
)", "whatsapp_config");

// 6. configuracao_recaptcha
exec_sql($pdo, "CREATE TABLE IF NOT EXISTS configuracao_recaptcha (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ativo TINYINT(1) DEFAULT 0
)", "configuracao_recaptcha");

// 7. kanban_lanes
exec_sql($pdo, "CREATE TABLE IF NOT EXISTS kanban_lanes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL
)", "kanban_lanes");

// 8. system_logs
exec_sql($pdo, "CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nivel VARCHAR(20) NOT NULL,
    mensagem TEXT NOT NULL
)", "system_logs");

echo "Done.\n";
?>
