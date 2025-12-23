<?php
/**
 * API para buscar pizzas disponíveis para meio a meio
 * Retorna todas as pizzas da mesma categoria que permitem meio a meio
 */
require_once '../includes/config.php';

header('Content-Type: application/json');

$categoria_id = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;
$excluir_produto = isset($_GET['excluir']) ? (int)$_GET['excluir'] : 0;

if (!$categoria_id) {
    echo json_encode(['success' => false, 'error' => 'Categoria não informada']);
    exit;
}

try {
    // Verificar se categoria permite meio a meio
    $stmt = $pdo->prepare("SELECT permite_meio_a_meio FROM categorias WHERE id = ?");
    $stmt->execute([$categoria_id]);
    $cat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cat || !$cat['permite_meio_a_meio']) {
        echo json_encode(['success' => false, 'error' => 'Categoria não permite meio a meio']);
        exit;
    }
    
    // Buscar todos os produtos ativos da categoria (exceto o atual)
    $sql = "SELECT id, nome, preco, preco_promocional, imagem_path, descricao 
            FROM produtos 
            WHERE categoria_id = ? AND ativo = 1";
    $params = [$categoria_id];
    
    if ($excluir_produto) {
        $sql .= " AND id != ?";
        $params[] = $excluir_produto;
    }
    
    $sql .= " ORDER BY nome ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pizzas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar configuração de cobrança
    $stmt_config = $pdo->query("SELECT tipo_cobranca FROM configuracao_pizzas LIMIT 1");
    $config = $stmt_config->fetch(PDO::FETCH_ASSOC);
    $tipo_cobranca = $config ? $config['tipo_cobranca'] : 'maior_valor';
    
    // Processar preços
    foreach ($pizzas as &$pizza) {
        $pizza['preco_final'] = $pizza['preco_promocional'] > 0 ? $pizza['preco_promocional'] : $pizza['preco'];
        $pizza['imagem_path'] = $pizza['imagem_path'] ?: 'admin/assets/images/sem-foto.jpg';
    }
    
    echo json_encode([
        'success' => true,
        'pizzas' => $pizzas,
        'tipo_cobranca' => $tipo_cobranca // 'maior_valor' ou 'media'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
