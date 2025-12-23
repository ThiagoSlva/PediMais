<?php
include '../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Buscar produtos que permitem meio a meio
    // Tentando 'permite_meio_a_meio'
    $sql = "SELECT * FROM produtos WHERE permite_meio_a_meio = 1 AND ativo = 1 ORDER BY nome ASC";
    
    try {
        $stmt = $pdo->query($sql);
    } catch (PDOException $e) {
        // Se falhar, pode ser que a coluna tenha outro nome ou não exista
        // Tentar buscar por categoria 'Pizzas' (assumindo ID ou nome)
        // Mas vamos tentar 'meio_a_meio' se 'permite_meio_a_meio' falhar
        $sql = "SELECT * FROM produtos WHERE meio_a_meio = 1 AND ativo = 1 ORDER BY nome ASC";
        try {
            $stmt = $pdo->query($sql);
        } catch (PDOException $e2) {
             // Última tentativa: buscar tudo que tem 'max_sabores_meio_meio' > 0
             $sql = "SELECT * FROM produtos WHERE max_sabores_meio_meio > 0 AND ativo = 1 ORDER BY nome ASC";
             $stmt = $pdo->query($sql);
        }
    }

    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($produtos);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar pizzas: ' . $e->getMessage()]);
}
?>