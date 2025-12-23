<?php
/**
 * API: Calcular Frete Completo
 * Suporta todos os modos de entrega:
 * - Modo 1: Grátis a partir de X valor (pode combinar com outros)
 * - Modo 2: Grátis para todos (exclusivo)
 * - Modo 3: Valor fixo único (exclusivo)
 * - Modo 4: Preço por quilômetro (exclusivo)
 * - Modo 5: Por bairro/região (exclusivo)
 */

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/db.php';
global $pdo;

// Parâmetros
$valor_pedido = (float)($_GET['valor'] ?? 0);
$bairro = $_GET['bairro'] ?? '';
$cidade = $_GET['cidade'] ?? '';
$cep_destino = $_GET['cep'] ?? '';

error_log("🚚 ======= CÁLCULO DE FRETE =======");
error_log("💰 Valor do pedido: R$ " . number_format($valor_pedido, 2, ',', '.'));
error_log("📍 Bairro: {$bairro}");
error_log("🏙️ Cidade: {$cidade}");
error_log("📮 CEP: {$cep_destino}");

try {
    // Buscar configuração
    $stmt = $pdo->query("SELECT * FROM configuracao_entrega WHERE id = 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config) {
        throw new Exception("Configuração de entrega não encontrada");
    }
    
    // Garantir que campos de modo existem (backward compatibility)
    $config['modo_gratis_valor_ativo'] = isset($config['modo_gratis_valor_ativo']) ? (int)$config['modo_gratis_valor_ativo'] : 0;
    $config['modo_gratis_todos_ativo'] = isset($config['modo_gratis_todos_ativo']) ? (int)$config['modo_gratis_todos_ativo'] : 0;
    $config['modo_valor_fixo_ativo'] = isset($config['modo_valor_fixo_ativo']) ? (int)$config['modo_valor_fixo_ativo'] : 0;
    $config['modo_por_km_ativo'] = isset($config['modo_por_km_ativo']) ? (int)$config['modo_por_km_ativo'] : 0;
    $config['modo_por_bairro_ativo'] = isset($config['modo_por_bairro_ativo']) ? (int)$config['modo_por_bairro_ativo'] : 0;
    
    error_log("⚙️ Configuração:");
    error_log("  - Modo 1 (Grátis >=X): " . ($config['modo_gratis_valor_ativo'] ? 'ATIVO' : 'INATIVO'));
    error_log("  - Modo 2 (Grátis Todos): " . ($config['modo_gratis_todos_ativo'] ? 'ATIVO' : 'INATIVO'));
    error_log("  - Modo 3 (Valor Fixo): " . ($config['modo_valor_fixo_ativo'] ? 'ATIVO' : 'INATIVO'));
    error_log("  - Modo 4 (Por Km): " . ($config['modo_por_km_ativo'] ? 'ATIVO' : 'INATIVO'));
    error_log("  - Modo 5 (Por Bairro): " . ($config['modo_por_bairro_ativo'] ? 'ATIVO' : 'INATIVO'));
    
    $valor_frete = 0;
    $tempo_estimado = '30-45 min';
    $mensagem = '';
    $entrega_disponivel = true;
    $gratis_ativo = false;
    $modo_usado = '';
    
    // =====================================================
    // MODO 2: ENTREGA GRÁTIS PARA TODOS (prioridade máxima se ativo)
    // =====================================================
    if ($config['modo_gratis_todos_ativo'] == 1) {
        error_log("🎁 MODO 2 ATIVO: Entrega grátis para todos");
        $valor_frete = 0;
        $mensagem = 'Entrega grátis para todo o Brasil!';
        $tempo_estimado = '30-45 min';
        $modo_usado = 'gratis_todos';
        
        echo json_encode([
            'erro' => false,
            'valor' => 0,
            'tempo_estimado' => $tempo_estimado,
            'mensagem' => $mensagem,
            'entrega_disponivel' => true,
            'modo' => $modo_usado,
            'gratis' => true,
            'motivo_gratis' => 'Promoção: entrega grátis para todos'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        error_log("⚠️ MODO 2 INATIVO - não aplicando entrega grátis para todos");
    }
    
    // =====================================================
    // MODO 5: POR BAIRRO/REGIÃO
    // =====================================================
    if ($config['modo_por_bairro_ativo'] == 1 && !empty($bairro)) {
        error_log("🏘️ MODO 5 ATIVO: Buscando valor por bairro '{$bairro}'");
        
        // Normalizar nome do bairro (remover acentos e espaços extras)
        $bairro_normalizado = trim($bairro);
        
        $stmt = $pdo->prepare("
            SELECT b.*, c.nome as cidade_nome, c.estado 
            FROM bairros_entrega b 
            LEFT JOIN cidades_entrega c ON b.cidade_id = c.id 
            WHERE LOWER(TRIM(b.nome)) = LOWER(TRIM(:bairro)) 
            AND b.ativo = 1
            LIMIT 1
        ");
        $stmt->execute(['bairro' => $bairro_normalizado]);
        $bairro_config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("🔍 Query resultado: " . json_encode($bairro_config));
        
        if ($bairro_config) {
            $valor_frete = (float)$bairro_config['valor_entrega'];
            $tempo_estimado = $bairro_config['tempo_estimado'] ?? '30-45 min';
            $modo_usado = 'por_bairro';
            
            error_log("✅ Bairro encontrado: {$bairro_config['nome']}");
            error_log("💵 Valor base do bairro: R$ " . number_format($valor_frete, 2, ',', '.'));
            error_log("🏙️ Cidade: {$bairro_config['cidade_nome']}/{$bairro_config['estado']}");
            error_log("🎁 Grátis acima de: " . ($bairro_config['gratis_acima_valor'] ?? 'NULL'));
            
            // Verificar se bairro tem "grátis acima de X"
            if (!empty($bairro_config['gratis_acima_valor']) && $valor_pedido >= $bairro_config['gratis_acima_valor']) {
                error_log("🎁 Bairro: Grátis acima de R$ " . number_format($bairro_config['gratis_acima_valor'], 2, ',', '.'));
                $valor_frete = 0;
                $gratis_ativo = true;
                $mensagem = "Entrega grátis para {$bairro_config['nome']} em pedidos acima de R$ " . 
                           number_format($bairro_config['gratis_acima_valor'], 2, ',', '.');
            } else {
                $mensagem = "{$bairro_config['nome']}, {$bairro_config['cidade_nome']}/{$bairro_config['estado']}";
                if (!empty($bairro_config['gratis_acima_valor'])) {
                    $mensagem .= " - Grátis em pedidos acima de R$ " . 
                                number_format($bairro_config['gratis_acima_valor'], 2, ',', '.');
                }
            }
        } else {
            error_log("❌ Bairro não encontrado: '{$bairro}'");
            error_log("💡 Verifique se o bairro está cadastrado e ativo");
            
            // Listar bairros disponíveis para debug
            $stmt_debug = $pdo->query("SELECT nome FROM bairros_entrega WHERE ativo = 1");
            $bairros_disponiveis = $stmt_debug->fetchAll(PDO::FETCH_COLUMN);
            error_log("📋 Bairros ativos: " . implode(', ', $bairros_disponiveis));
            
            echo json_encode([
                'erro' => true,
                'mensagem' => "Desculpe, não atendemos o bairro '{$bairro}' no momento.",
                'entrega_disponivel' => false,
                'bairros_disponiveis' => $bairros_disponiveis
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    // =====================================================
    // MODO 4: PREÇO POR QUILÔMETRO
    // =====================================================
    elseif ($config['modo_por_km_ativo'] == 1 && !empty($cep_destino)) {
        error_log("📏 MODO 4 ATIVO: Calculando por quilômetro");
        
        $cep_origem = $config['endereco_referencia_cep'] ?? '';
        
        if (empty($cep_origem)) {
            error_log("❌ CEP de referência não configurado");
            echo json_encode([
                'erro' => true,
                'mensagem' => 'CEP de referência não configurado. Configure em Configuração de Entrega.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Limpar CEPs
        $cep_origem = preg_replace('/\D/', '', $cep_origem);
        $cep_destino = preg_replace('/\D/', '', $cep_destino);
        
        // Buscar distância (via API interna)
        $url_distancia = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . 
                        "/calcular_distancia.php?" . http_build_query([
                            'origem' => $cep_origem,
                            'destino' => $cep_destino
                        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_distancia);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $distancia_data = json_decode($response, true);
        
        if (isset($distancia_data['erro']) && $distancia_data['erro']) {
            error_log("❌ Erro ao calcular distância: " . ($distancia_data['mensagem'] ?? 'desconhecido'));
            echo json_encode([
                'erro' => true,
                'mensagem' => $distancia_data['mensagem'] ?? 'Erro ao calcular distância'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $valor_frete = (float)($distancia_data['valor'] ?? 0);
        $tempo_estimado = $distancia_data['tempo_estimado'] ?? '30-45 min';
        $modo_usado = 'por_km';
        
        $distancia_km = $distancia_data['distancia_km'] ?? 0;
        $km_gratis = $distancia_data['km_gratis'] ?? 0;
        $preco_por_km = $distancia_data['preco_por_km'] ?? 0;
        
        error_log("📏 Distância: {$distancia_km} km");
        error_log("🎁 Km grátis: {$km_gratis} km");
        error_log("💵 Preço/km: R$ " . number_format($preco_por_km, 2, ',', '.'));
        error_log("💰 Valor calculado: R$ " . number_format($valor_frete, 2, ',', '.'));
        
        $mensagem = "Distância: {$distancia_km} km";
        if ($km_gratis > 0) {
            $mensagem .= " (primeiros {$km_gratis} km grátis)";
        }
    }
    
    // =====================================================
    // MODO 3: VALOR FIXO ÚNICO
    // =====================================================
    elseif ($config['modo_valor_fixo_ativo'] == 1) {
        error_log("📦 MODO 3 ATIVO: Valor fixo único");
        $valor_frete = (float)$config['valor_fixo_entrega'];
        $tempo_estimado = '30-45 min';
        $modo_usado = 'valor_fixo';
        $mensagem = 'Taxa de entrega fixa';
        
        error_log("💵 Valor fixo: R$ " . number_format($valor_frete, 2, ',', '.'));
    }
    
    // =====================================================
    // NENHUM MODO ATIVO (fallback)
    // =====================================================
    else {
        error_log("⚠️ Nenhum modo de entrega ativo");
        echo json_encode([
            'erro' => true,
            'mensagem' => 'Nenhum modo de entrega configurado. Configure em Configuração de Entrega.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // =====================================================
    // MODO 1: GRÁTIS A PARTIR DE X VALOR (pode combinar com qualquer outro)
    // Este é verificado POR ÚLTIMO e ZERA o frete se atingir o valor
    // CRÍTICO: Só aplica se MODO 1 ESTIVER ATIVO (modo_gratis_valor_ativo = 1)
    // =====================================================
    if ($config['modo_gratis_valor_ativo'] == 1) {
        $valor_minimo = (float)$config['valor_minimo_gratis'];
        error_log("🎁 MODO 1 ATIVO: Grátis acima de R$ " . number_format($valor_minimo, 2, ',', '.'));
        
        if ($valor_pedido >= $valor_minimo) {
            error_log("✅ Pedido atingiu valor mínimo! Frete ZERADO pelo Modo 1");
            $valor_frete_original = $valor_frete;
            $valor_frete = 0;
            $gratis_ativo = true;
            
            if ($valor_frete_original > 0) {
                $mensagem = "🎉 Entrega grátis! Você economizou R$ " . 
                           number_format($valor_frete_original, 2, ',', '.') . 
                           " (pedido acima de R$ " . number_format($valor_minimo, 2, ',', '.') . ")";
            } else {
                $mensagem = "🎉 Entrega grátis em pedidos acima de R$ " . 
                           number_format($valor_minimo, 2, ',', '.');
            }
        } else {
            $falta = $valor_minimo - $valor_pedido;
            error_log("⚠️ Faltam R$ " . number_format($falta, 2, ',', '.') . " para frete grátis");
            
            if (!empty($mensagem)) {
                $mensagem .= " | ";
            }
            $mensagem .= "Faltam R$ " . number_format($falta, 2, ',', '.') . 
                        " para entrega grátis";
        }
    } else {
        error_log("⚠️ MODO 1 INATIVO - não aplicando desconto de frete grátis");
    }
    
    // Resultado final
    error_log("📊 ======= RESULTADO =======");
    error_log("💰 Valor do frete: R$ " . number_format($valor_frete, 2, ',', '.'));
    error_log("⏱️ Tempo estimado: {$tempo_estimado}");
    error_log("🎁 Grátis: " . ($gratis_ativo ? 'SIM' : 'NÃO'));
    error_log("🔧 Modo usado: {$modo_usado}");
    error_log("================================");
    
    echo json_encode([
        'erro' => false,
        'valor' => $valor_frete,
        'tempo_estimado' => $tempo_estimado,
        'mensagem' => $mensagem,
        'entrega_disponivel' => $entrega_disponivel,
        'gratis' => $gratis_ativo,
        'modo' => $modo_usado,
        'bairro' => $bairro,
        'cidade' => $cidade
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("❌ ERRO: " . $e->getMessage());
    error_log($e->getTraceAsString());
    
    echo json_encode([
        'erro' => true,
        'mensagem' => 'Erro ao calcular frete: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
