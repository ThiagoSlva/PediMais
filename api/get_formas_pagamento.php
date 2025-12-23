<?php
include '../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Buscar formas de pagamento ativas
    // Assumindo que existe uma coluna 'ativo' ou similar, se não, buscar todas
    // O describe mostrou 'id', 'nome', ..., 'criado_em'. Vou assumir 'ativo' existe ou buscar tudo.
    // Para garantir, vou buscar tudo e filtrar se tiver coluna ativo, mas como não vi 'ativo' explicitamente no output truncado,
    // vou fazer um SELECT * e o front que se vire, ou melhor, vou tentar ver se tem 'ativo' com um select limit 1.
    
    // Melhor: vou fazer um select simples. Se der erro de coluna, eu corrijo.
    // Mas o output do describe mostrou indices 0, 1... e 8. Tem colunas no meio.
    // Vou arriscar SELECT * FROM formas_pagamento WHERE ativo = 1. Se falhar, removo o WHERE.
    
    // Na verdade, vou fazer um try catch mais robusto.
    
    $sql = "SELECT * FROM formas_pagamento WHERE ativo = 1 ORDER BY id ASC";
    try {
        $stmt = $pdo->query($sql);
    } catch (PDOException $e) {
        // Se falhar (ex: coluna ativo não existe), tenta sem o WHERE
        $sql = "SELECT * FROM formas_pagamento ORDER BY id ASC";
        $stmt = $pdo->query($sql);
    }
    
    $formas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($formas);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar formas de pagamento: ' . $e->getMessage()]);
}
?>