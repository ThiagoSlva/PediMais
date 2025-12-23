<?php
// setup_db_includes.php
// Ensures all tables required by includes exist

require_once 'includes/config.php';

echo "Setting up database tables...\n";

try {
    // 1. configuracoes (from admin/configuracoes.php)
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome_site VARCHAR(255),
        descricao_site TEXT,
        logo VARCHAR(255),
        favicon VARCHAR(255),
        capa VARCHAR(255),
        cep VARCHAR(20),
        rua VARCHAR(255),
        numero VARCHAR(50),
        complemento VARCHAR(255),
        bairro VARCHAR(100),
        cidade VARCHAR(100),
        estado VARCHAR(2),
        whatsapp VARCHAR(20),
        email_contato VARCHAR(255),
        facebook VARCHAR(255),
        instagram VARCHAR(255),
        tema VARCHAR(50) DEFAULT 'roxo',
        cor_principal VARCHAR(20),
        cor_secundaria VARCHAR(20)
    )");
    $stmt = $pdo->query("SELECT id FROM configuracoes WHERE id = 1");
    if (!$stmt->fetch()) {
        $pdo->exec("INSERT INTO configuracoes (id, nome_site, tema) VALUES (1, 'PedeMais', 'roxo')");
    }
    echo "[OK] configuracoes table setup.\n";

    // 2. configuracao_horarios & horarios_funcionamento (from admin/horarios.php)
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracao_horarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sistema_ativo TINYINT(1) DEFAULT 1,
        loja_aberta_manual TINYINT(1) DEFAULT NULL,
        mensagem_fechado TEXT
    )");
    $stmt = $pdo->query("SELECT id FROM configuracao_horarios LIMIT 1");
    if (!$stmt->fetch()) {
        $pdo->exec("INSERT INTO configuracao_horarios (sistema_ativo, loja_aberta_manual, mensagem_fechado) VALUES (1, NULL, 'Estamos fechados no momento.')");
    }
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS horarios_funcionamento (
        id INT AUTO_INCREMENT PRIMARY KEY,
        dia_semana INT NOT NULL,
        abertura TIME,
        fechamento TIME,
        ativo TINYINT(1) DEFAULT 1
    )");
    echo "[OK] horarios tables setup.\n";

    // 3. configuracao_entrega, cidades, bairros (from admin/configuracao_entrega.php)
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracao_entrega (
        id INT AUTO_INCREMENT PRIMARY KEY,
        modo_gratis_valor_ativo TINYINT(1) DEFAULT 0,
        valor_minimo_gratis DECIMAL(10,2) DEFAULT 0.00,
        modo_gratis_todos_ativo TINYINT(1) DEFAULT 0,
        modo_valor_fixo_ativo TINYINT(1) DEFAULT 0,
        valor_fixo_entrega DECIMAL(10,2) DEFAULT 0.00,
        modo_por_bairro_ativo TINYINT(1) DEFAULT 1,
        aceita_retirada TINYINT(1) DEFAULT 1,
        taxa_retirada DECIMAL(10,2) DEFAULT 0.00
    )");
    $stmt = $pdo->query("SELECT id FROM configuracao_entrega LIMIT 1");
    if (!$stmt->fetch()) {
        $pdo->exec("INSERT INTO configuracao_entrega (modo_por_bairro_ativo, aceita_retirada) VALUES (1, 1)");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS cidades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        estado VARCHAR(2) NOT NULL,
        ativo TINYINT(1) DEFAULT 1
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS bairros (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cidade_id INT NOT NULL,
        nome VARCHAR(100) NOT NULL,
        valor_entrega DECIMAL(10,2) DEFAULT 0.00,
        gratis_acima_de DECIMAL(10,2) DEFAULT NULL,
        entrega_disponivel TINYINT(1) DEFAULT 1,
        FOREIGN KEY (cidade_id) REFERENCES cidades(id) ON DELETE CASCADE
    )");
    echo "[OK] delivery tables setup.\n";

    // 4. whatsapp_config (from admin/whatsapp_config.php)
    $pdo->exec("CREATE TABLE IF NOT EXISTS whatsapp_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ativo TINYINT(1) DEFAULT 0,
        base_url VARCHAR(255),
        apikey VARCHAR(255),
        instance_name VARCHAR(100),
        enviar_comprovante TINYINT(1) DEFAULT 0,
        notificar_status_pedido TINYINT(1) DEFAULT 0,
        enviar_link_acompanhamento TINYINT(1) DEFAULT 0,
        popup_finalizacao_ativo TINYINT(1) DEFAULT 0,
        whatsapp_estabelecimento VARCHAR(20),
        usar_mercadopago TINYINT(1) DEFAULT 0,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    // Ensure columns exist if table already existed without them
    try {
        $pdo->exec("ALTER TABLE whatsapp_config ADD COLUMN instance_name VARCHAR(100)");
    } catch (Exception $e) {}

    echo "[OK] whatsapp_config table setup.\n";

    // 5. configuracao_recaptcha (from admin/recaptcha_config.php - inferred)
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracao_recaptcha (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ativo TINYINT(1) DEFAULT 0,
        site_key VARCHAR(255),
        secret_key VARCHAR(255),
        ativo_login_admin TINYINT(1) DEFAULT 0,
        ativo_login_cliente TINYINT(1) DEFAULT 0,
        ativo_cadastro_cliente TINYINT(1) DEFAULT 0,
        ativo_checkout TINYINT(1) DEFAULT 0
    )");
    $stmt = $pdo->query("SELECT id FROM configuracao_recaptcha LIMIT 1");
    if (!$stmt->fetch()) {
        $pdo->exec("INSERT INTO configuracao_recaptcha (ativo) VALUES (0)");
    }
    echo "[OK] recaptcha table setup.\n";
    
    // 6. kanban_lanes (already done in previous task, but safe to ensure)
    $pdo->exec("CREATE TABLE IF NOT EXISTS kanban_lanes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(50) NOT NULL,
        cor VARCHAR(20) DEFAULT '#e2e8f0',
        ordem INT DEFAULT 0,
        ativo TINYINT(1) DEFAULT 1
    )");
    echo "[OK] kanban_lanes table setup.\n";

    // 7. system_logs (from log_helper.php)
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nivel VARCHAR(20) NOT NULL,
        mensagem TEXT NOT NULL,
        contexto TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "[OK] system_logs table setup.\n";

} catch (PDOException $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
}
?>
