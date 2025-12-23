<?php
require 'includes/config.php';

// Ativar templates necessários
$pdo->exec("UPDATE whatsapp_mensagens SET ativo = 1 WHERE id IN (5, 9)");
echo "Templates 5 (Pendente) e 9 (Entregue) ativados com sucesso!";
