<?php
require_once __DIR__ . '/config.php';

if (!function_exists('get_config')) {
    function get_config() {
        global $pdo;
        $stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
        return $stmt->fetch();
    }
}

if (!function_exists('get_categorias_ativas')) {
    function get_categorias_ativas() {
        global $pdo;
        $stmt = $pdo->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY ordem ASC");
        return $stmt->fetchAll();
    }
}

if (!function_exists('get_produtos_por_categoria')) {
    function get_produtos_por_categoria($categoria_id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE categoria_id = ? AND ativo = 1 ORDER BY ordem ASC");
        $stmt->execute([$categoria_id]);
        return $stmt->fetchAll();
    }
}

if (!function_exists('get_produto_detalhes')) {
    function get_produto_detalhes($produto_id) {
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
    function formatar_moeda($valor) {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }
}

if (!function_exists('loja_aberta')) {
    function loja_aberta() {
        global $pdo;
        
        // Verificar configuração de horários
        try {
            $stmt = $pdo->query("SELECT * FROM configuracao_horarios LIMIT 1");
            $config_horario = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
    function converterPreco($valor) {
        if (empty($valor)) return 0.00;
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
    function get_produto_avaliacao($produto_id) {
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
        } catch (Exception $e) {
            // Silently handle errors
        }
        return null;
    }
}
?>