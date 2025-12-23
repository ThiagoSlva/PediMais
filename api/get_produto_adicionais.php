<?php
/**
 * API: Get Product Additionals
 * Returns the additional groups and items associated with a specific product
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../includes/config.php';

$produto_id = filter_input(INPUT_GET, 'produto_id', FILTER_VALIDATE_INT);

if (!$produto_id) {
    echo json_encode(['success' => false, 'message' => 'Produto não especificado']);
    exit;
}

try {
    // Get groups associated with this product
    $sql = "SELECT ga.* 
            FROM grupos_adicionais ga
            INNER JOIN produto_grupos pg ON ga.id = pg.grupo_id
            WHERE pg.produto_id = ? AND ga.ativo = 1
            ORDER BY pg.ordem ASC, ga.ordem ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$produto_id]);
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each group, get its items
    foreach ($grupos as &$grupo) {
        $sql_itens = "SELECT id, nome, descricao, preco_adicional 
                      FROM grupo_adicional_itens 
                      WHERE grupo_id = ? AND ativo = 1 
                      ORDER BY ordem ASC, nome ASC";
        $stmt_itens = $pdo->prepare($sql_itens);
        $stmt_itens->execute([$grupo['id']]);
        $grupo['itens'] = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert numeric strings to proper types
        $grupo['minimo_escolha'] = (int)$grupo['minimo_escolha'];
        $grupo['maximo_escolha'] = (int)$grupo['maximo_escolha'];
        $grupo['obrigatorio'] = (bool)$grupo['obrigatorio'];
        
        foreach ($grupo['itens'] as &$item) {
            $item['preco_adicional'] = (float)$item['preco_adicional'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'grupos' => $grupos
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar adicionais: ' . $e->getMessage()
    ]);
}
?>
