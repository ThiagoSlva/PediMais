<?php
/**
 * Deletar endereço do cliente
 */
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$cliente_id = filter_input(INPUT_GET, 'cliente_id', FILTER_VALIDATE_INT);

if (!$id || !$cliente_id) {
    header('Location: clientes.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM cliente_enderecos WHERE id = ? AND cliente_id = ?");
    $stmt->execute([$id, $cliente_id]);
    
    header("Location: cliente_edit.php?id={$cliente_id}&msg=endereco_deletado");
    exit;
    
} catch (PDOException $e) {
    header("Location: cliente_edit.php?id={$cliente_id}&error=1");
    exit;
}
