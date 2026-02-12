<?php
require_once __DIR__ . '/config.php';

if (!function_exists('get_config')) {
    function get_config()
    {
        global $pdo;
        $stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
        return $stmt->fetch();
    }
}

if (!function_exists('get_categorias_ativas')) {
    function get_categorias_ativas()
    {
        global $pdo;
        $stmt = $pdo->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY ordem ASC");
        return $stmt->fetchAll();
    }
}

if (!function_exists('get_produtos_por_categoria')) {
    function get_produtos_por_categoria($categoria_id)
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE categoria_id = ? AND ativo = 1 ORDER BY ordem ASC");
        $stmt->execute([$categoria_id]);
        return $stmt->fetchAll();
    }
}

if (!function_exists('get_produto_detalhes')) {
    function get_produto_detalhes($produto_id)
    {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
        $stmt->execute([$produto_id]);
        $produto = $stmt->fetch();

        if ($produto) {
            // Buscar opções/adicionais
            $stmt_opcoes = $pdo->prepare("SELECT * FROM opcoes WHERE produto_id = ? ORDER BY tipo, nome");
            $stmt_opcoes->execute([$produto_id]);
            $produto['opcoes'] = $stmt_opcoes->fetchAll();
        }

        return $produto;
    }
}

if (!function_exists('formatar_moeda')) {
    function formatar_moeda($valor)
    {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }
}

if (!function_exists('loja_aberta')) {
    function loja_aberta()
    {
        global $pdo;

        // Verificar configuração de horários
        try {
            $stmt = $pdo->query("SELECT * FROM configuracao_horarios LIMIT 1");
            $config_horario = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            return true; // Se não houver tabela, considerar aberto
        }

        if ($config_horario) {
            // Verificar controle manual (tem prioridade)
            if ($config_horario['aberto_manual'] !== null) {
                return intval($config_horario['aberto_manual']) === 1;
            }

            // Se sistema de horários está desativado, considerar sempre aberto
            if (isset($config_horario['sistema_ativo']) && intval($config_horario['sistema_ativo']) === 0) {
                return true;
            }
        }

        // Se não tiver manual, verificar horário automático
        $dia_semana = date('w'); // 0 (Domingo) a 6 (Sábado)
        $hora_atual = date('H:i:s');

        try {
            $stmt = $pdo->prepare("SELECT * FROM horarios_funcionamento WHERE dia_semana = ? AND ativo = 1");
            $stmt->execute([$dia_semana]);
            $horario = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        catch (Exception $e) {
            return true; // Se não houver tabela, considerar aberto
        }

        if ($horario) {
            if ($hora_atual >= $horario['horario_abertura'] && $hora_atual <= $horario['horario_fechamento']) {
                return true;
            }
        }

        return false;
    }
}

// Função para converter preço de formato brasileiro (5,00) para float (5.00)
if (!function_exists('converterPreco')) {
    function converterPreco($valor)
    {
        if (empty($valor))
            return 0.00;
        // Remove R$ e espaços
        $valor = str_replace(['R$', ' '], '', $valor);
        // Remove pontos de milhar e troca vírgula por ponto
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
        return floatval($valor);
    }
}

// Função para obter avaliação média de um produto
if (!function_exists('get_produto_avaliacao')) {
    function get_produto_avaliacao($produto_id)
    {
        global $pdo;
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    AVG(avaliacao) as media,
                    COUNT(*) as total
                FROM avaliacoes 
                WHERE produto_id = ? AND avaliacao > 0 AND ativo = 1
            ");
            $stmt->execute([$produto_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && $result['total'] > 0) {
                return [
                    'media' => round($result['media'], 1),
                    'total' => (int)$result['total'],
                    'estrelas' => round($result['media'])
                ];
            }
        }
        catch (Exception $e) {
        // Silently handle errors
        }
        return null;
    }
}

// ⚡ OTIMIZAÇÃO BD: Buscar categorias com produtos em UMA ÚNICA QUERY (resolve N+1)
if (!function_exists('get_categorias_com_produtos')) {
    function get_categorias_com_produtos()
    {
        global $pdo;

        try {
            // Query otimizada: JOINs categorias + produtos + avaliações em uma única query
            $sql = "
                SELECT 
                    c.id as cat_id,
                    c.nome as cat_nome,
                    c.imagem as cat_imagem,
                    c.ordem as cat_ordem,
                    c.permite_meio_a_meio,
                    p.id as prod_id,
                    p.nome as prod_nome,
                    p.descricao,
                    p.preco,
                    p.preco_promocional,
                    p.imagem_path,
                    p.ordem as prod_ordem,
                    p.ativo,
                    COALESCE(AVG(av.avaliacao), 0) as avg_rating,
                    COALESCE(COUNT(av.id), 0) as total_ratings
                FROM categorias c
                LEFT JOIN produtos p ON p.categoria_id = c.id AND p.ativo = 1
                LEFT JOIN avaliacoes av ON av.produto_id = p.id AND av.avaliacao > 0 AND av.ativo = 1
                WHERE c.ativo = 1
                GROUP BY c.id, p.id
                ORDER BY c.ordem ASC, p.ordem ASC
            ";

            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                return [];
            }

            // Agrupar dados por categoria
            $categorias = [];
            foreach ($rows as $row) {
                $cat_id = $row['cat_id'];

                // Se categoria ainda não foi adicionada, adicionar
                if (!isset($categorias[$cat_id])) {
                    $categorias[$cat_id] = [
                        'id' => $cat_id,
                        'nome' => $row['cat_nome'],
                        'imagem' => $row['cat_imagem'],
                        'ordem' => $row['cat_ordem'],
                        'permite_meio_a_meio' => $row['permite_meio_a_meio'],
                        'produtos' => []
                    ];
                }

                // Se tem produto (não é resultado do LEFT JOIN vazio)
                if ($row['prod_id']) {
                    $categorias[$cat_id]['produtos'][] = [
                        'id' => $row['prod_id'],
                        'nome' => $row['prod_nome'],
                        'descricao' => $row['descricao'],
                        'preco' => $row['preco'],
                        'preco_promocional' => $row['preco_promocional'],
                        'imagem_path' => $row['imagem_path'],
                        'ordem' => $row['prod_ordem'],
                        'ativo' => $row['ativo'],
                        'rating' => [
                            'media' => round($row['avg_rating'], 1),
                            'total' => (int)$row['total_ratings'],
                            'estrelas' => round($row['avg_rating'])
                        ]
                    ];
                }
            }

            // Retornar como array indexado (compatível com foreach)
            return array_values($categorias);

        }
        catch (Exception $e) {
            // Fallback para função antiga se der erro
            error_log("Erro em get_categorias_com_produtos: " . $e->getMessage());
            return [];
        }
    }
}
// Função para sincronizar a lane do Kanban com o status do pedido
if (!function_exists('sync_pedido_lane_by_status')) {
    function sync_pedido_lane_by_status($pedido_id, $status)
    {
        global $pdo;

        // Mapear status para ação da lane
        $acao = '';
        switch ($status) {
            case 'em_andamento':
                $acao = 'em_preparo';
                break;
            case 'pronto':
                $acao = 'pronto';
                break;
            case 'saiu_entrega':
                $acao = 'saiu_entrega';
                break;
            case 'entregue':
            case 'concluido':
                $acao = 'entregue';
                break;
            case 'finalizado':
                $acao = 'finalizar';
                break;
            case 'cancelado':
                $acao = 'cancelar';
                break;
            default:
                return false;
        }

        // Buscar lane correspondente
        $stmt = $pdo->prepare("SELECT id FROM kanban_lanes WHERE acao = ? ORDER BY ordem ASC LIMIT 1");
        $stmt->execute([$acao]);
        $lane_id = $stmt->fetchColumn();

        if (!$lane_id) {
            // Fallback: tentar encontrar por nome
            $termo = '';
            if ($acao == 'em_preparo')
                $termo = 'preparo';
            elseif ($acao == 'pronto')
                $termo = 'pronto';
            elseif ($acao == 'saiu_entrega')
                $termo = 'saiu';
            elseif ($acao == 'entregue')
                $termo = 'entregue';
            elseif ($acao == 'finalizar')
                $termo = 'finalizado';
            elseif ($acao == 'cancelar')
                $termo = 'cancelado';

            if ($termo) {
                $stmt = $pdo->prepare("SELECT id FROM kanban_lanes WHERE nome LIKE ? ORDER BY ordem ASC LIMIT 1");
                $stmt->execute(["%$termo%"]);
                $lane_id = $stmt->fetchColumn();
            }
        }

        if ($lane_id) {
            $stmt = $pdo->prepare("UPDATE pedidos SET lane_id = ? WHERE id = ?");
            $stmt->execute([$lane_id, $pedido_id]);
            return true;
        }

        return false;
    }
}

// Função para adicionar ponto de fidelidade (disponível globalmente)
if (!function_exists('adicionar_ponto_fidelidade')) {
    function adicionar_ponto_fidelidade($cliente_id, $pedido_id)
    {
        global $pdo;
        try {
            // Verificar se fidelidade está ativa
            $stmt = $pdo->query("SELECT ativo FROM fidelidade_config LIMIT 1");
            $config = $stmt->fetch();
            if (!$config || !$config['ativo']) {
                return false;
            }

            // Verificar se já existe ponto para este pedido
            $stmt = $pdo->prepare("SELECT id FROM fidelidade_pontos WHERE pedido_id = ?");
            $stmt->execute([$pedido_id]);
            if ($stmt->fetch()) {
                return false;
            }

            // Inserir novo ponto
            $stmt = $pdo->prepare("
                INSERT INTO fidelidade_pontos (cliente_id, pedido_id, status)
                VALUES (?, ?, 'ativo')
            ");
            $stmt->execute([$cliente_id, $pedido_id]);

            return true;
        }
        catch (Exception $e) {
            error_log("Erro ao adicionar ponto de fidelidade: " . $e->getMessage());
            return false;
        }
    }
}
?>