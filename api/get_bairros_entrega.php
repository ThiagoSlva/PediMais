<?php
/**
 * API: Buscar Bairros para Entrega
 * Retorna cidades e bairros cadastrados com valores de entrega
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../includes/config.php';

try {
    // Buscar configuração de entrega
    $stmt = $pdo->query("SELECT * FROM configuracao_entrega LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config) {
        // Configuração padrão se não existir
        $config = [
            'modo_gratis_valor_ativo' => 0,
            'valor_minimo_gratis' => 0,
            'modo_gratis_todos_ativo' => 0,
            'modo_valor_fixo_ativo' => 0,
            'valor_fixo_entrega' => 0,
            'modo_por_bairro_ativo' => 1,
            'aceita_retirada' => 1,
            'taxa_retirada' => 0
        ];
    }
    
    // Buscar cidades ativas
    $stmt = $pdo->query("SELECT * FROM cidades WHERE ativo = 1 ORDER BY nome ASC");
    $cidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar bairros ativos
    $stmt = $pdo->query("
        SELECT b.*, c.nome as cidade_nome, c.estado as cidade_estado 
        FROM bairros b 
        JOIN cidades c ON b.cidade_id = c.id 
        WHERE c.ativo = 1 AND b.entrega_disponivel = 1
        ORDER BY c.nome ASC, b.nome ASC
    ");
    $bairros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizar bairros por cidade
    $cidades_com_bairros = [];
    foreach ($cidades as $cidade) {
        $bairros_cidade = array_filter($bairros, function($b) use ($cidade) {
            return (int)$b['cidade_id'] === (int)$cidade['id'];
        });
        
        if (!empty($bairros_cidade)) {
            $cidades_com_bairros[] = [
                'id' => $cidade['id'],
                'nome' => $cidade['nome'],
                'estado' => $cidade['estado'],
                'bairros' => array_values(array_map(function($b) {
                    return [
                        'id' => $b['id'],
                        'nome' => $b['nome'],
                        'valor_entrega' => floatval($b['valor_entrega']),
                        'gratis_acima_de' => $b['gratis_acima_de'] ? floatval($b['gratis_acima_de']) : null
                    ];
                }, $bairros_cidade))
            ];
        }
    }
    
    echo json_encode([
        'sucesso' => true,
        'config' => [
            'modo_gratis_valor_ativo' => (bool)$config['modo_gratis_valor_ativo'],
            'valor_minimo_gratis' => floatval($config['valor_minimo_gratis']),
            'modo_gratis_todos_ativo' => (bool)$config['modo_gratis_todos_ativo'],
            'modo_valor_fixo_ativo' => (bool)$config['modo_valor_fixo_ativo'],
            'valor_fixo_entrega' => floatval($config['valor_fixo_entrega']),
            'modo_por_bairro_ativo' => (bool)$config['modo_por_bairro_ativo'],
            'aceita_retirada' => (bool)$config['aceita_retirada'],
            'taxa_retirada' => floatval($config['taxa_retirada'])
        ],
        'cidades' => $cidades_com_bairros,
        'total_cidades' => count($cidades_com_bairros),
        'total_bairros' => count($bairros)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'sucesso' => false,
        'erro' => 'Erro ao buscar bairros: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
