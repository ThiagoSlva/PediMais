<?php
require_once 'includes/config.php';

echo "<h2>Diagnóstico Completo da Coluna Status</h2>";

// 1. Verificar estrutura da coluna
echo "<h3>1. Estrutura da coluna 'status':</h3>";
$stmt = $pdo->query("DESCRIBE pedidos");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
foreach ($columns as $col) {
    if ($col['Field'] == 'status') {
        print_r($col);
    }
}
echo "</pre>";

// 2. Verificar se há trigger ou constraint
echo "<h3>2. Tentando atualização com diferentes métodos:</h3>";

// Método 1: UPDATE direto com valor literal
echo "<b>Método 1 - UPDATE direto:</b><br>";
$result = $pdo->exec("UPDATE pedidos SET status = 'finalizado' WHERE id = 2");
echo "Linhas afetadas: $result<br><br>";

// Verificar
$stmt = $pdo->query("SELECT id, status FROM pedidos WHERE id = 2");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Status após update: '{$row['status']}'<br><br>";

// Se ainda vazio, pode ser um problema de tipo ENUM
echo "<h3>3. Verificando se é ENUM e os valores permitidos:</h3>";
$stmt = $pdo->query("SHOW COLUMNS FROM pedidos WHERE Field = 'status'");
$col = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Tipo: " . $col['Type'] . "<br>";
echo "Null: " . $col['Null'] . "<br>";
echo "Default: " . $col['Default'] . "<br>";

// Se for ENUM, listar valores
if (strpos($col['Type'], 'enum') !== false) {
    echo "<br><b>Valores ENUM permitidos:</b> " . $col['Type'] . "<br>";
    
    // Verificar se 'finalizado' está na lista
    if (strpos($col['Type'], 'finalizado') === false) {
        echo "<br><b>⚠️ 'finalizado' NÃO está nos valores permitidos! Adicionando...</b><br>";
        
        // Alterar coluna para incluir 'finalizado'
        $sql = "ALTER TABLE pedidos MODIFY COLUMN status ENUM('pendente','confirmado','em_andamento','pronto','saiu_entrega','concluido','finalizado','cancelado') DEFAULT 'pendente'";
        try {
            $pdo->exec($sql);
            echo "✅ Coluna alterada com sucesso!<br><br>";
            
            // Agora tentar atualizar
            $pdo->exec("UPDATE pedidos SET status = 'finalizado' WHERE arquivado = 1");
            echo "✅ Pedidos arquivados atualizados para 'finalizado'<br>";
        } catch (Exception $e) {
            echo "Erro: " . $e->getMessage() . "<br>";
        }
    }
} else {
    // Não é ENUM, tentar forçar
    echo "<br>Não é ENUM. Tentando forçar valor...<br>";
    $pdo->exec("UPDATE pedidos SET status = 'finalizado' WHERE id IN (1, 2)");
}

// 4. Verificar resultado final
echo "<h3>4. Estado final dos pedidos:</h3>";
$stmt = $pdo->query("SELECT id, status, arquivado FROM pedidos ORDER BY id DESC");
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($pedidos);
echo "</pre>";

echo "<h3><a href='admin/pedidos.php'>→ Ir para Pedidos</a></h3>";
