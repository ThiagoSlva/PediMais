<?php
include '../includes/config.php';

function addColumn($pdo, $table, $column, $type) {
    try {
        $pdo->exec("ALTER TABLE $table ADD COLUMN $column $type");
        echo "Coluna $column adicionada em $table.<br>";
    } catch (PDOException $e) {
        // Ignorar erro se coluna já existe (código 42S21 ou similar)
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "Coluna $column já existe em $table.<br>";
        } else {
            echo "Erro ao adicionar $column: " . $e->getMessage() . "<br>";
        }
    }
}

addColumn($pdo, 'whatsapp_config', 'ativo', 'TINYINT(1) DEFAULT 1');
addColumn($pdo, 'whatsapp_config', 'enviar_comprovante', 'TINYINT(1) DEFAULT 0');
addColumn($pdo, 'whatsapp_config', 'enviar_link_acompanhamento', 'TINYINT(1) DEFAULT 1');
addColumn($pdo, 'whatsapp_config', 'popup_finalizacao_ativo', 'TINYINT(1) DEFAULT 1');
addColumn($pdo, 'whatsapp_config', 'whatsapp_estabelecimento', 'VARCHAR(20) DEFAULT ""');
addColumn($pdo, 'whatsapp_config', 'usar_mercadopago', 'TINYINT(1) DEFAULT 1');

echo "Migração concluída.";
?>
