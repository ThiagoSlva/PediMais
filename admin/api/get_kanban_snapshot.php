<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';
require_once '../includes/auth.php';

// verificar_login(); // Uncomment if auth is needed

try {
    // Verificar se coluna 'arquivado' existe, se não, criar
    $stmt = $pdo->query("SHOW COLUMNS FROM pedidos LIKE 'arquivado'");
    $temArquivado = $stmt->rowCount() > 0;
    if (!$temArquivado) {
        $pdo->exec("ALTER TABLE pedidos ADD COLUMN arquivado TINYINT(1) DEFAULT 0");
        $pdo->exec("ALTER TABLE pedidos ADD COLUMN data_conclusao DATETIME NULL");
        $temArquivado = true;
    }
    
    // Verificar se coluna 'acao' existe em kanban_lanes, se não, criar
    $stmt = $pdo->query("SHOW COLUMNS FROM kanban_lanes LIKE 'acao'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE kanban_lanes ADD COLUMN acao VARCHAR(50) DEFAULT NULL");
    }

    // 1. Fetch Lanes
    $stmt = $pdo->query("SELECT * FROM kanban_lanes ORDER BY ordem ASC");
    $lanes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $kanbanData = [];

    foreach ($lanes as $lane) {
        // 2. Fetch Orders for this Lane (excluindo arquivados)
        // Join with clientes to get name/phone
        $sql = "SELECT p.*, c.nome as cliente_nome, c.telefone as cliente_telefone, 
                       COALESCE(c.endereco_principal, '') as cliente_endereco
                FROM pedidos p
                LEFT JOIN clientes c ON p.cliente_id = c.id
                WHERE p.lane_id = ?
                  AND (p.arquivado IS NULL OR p.arquivado = 0)
                  AND p.status != 'finalizado'
                ORDER BY p.id DESC";
        
        $stmtOrders = $pdo->prepare($sql);
        $stmtOrders->execute([$lane['id']]);
        $pedidos = $stmtOrders->fetchAll(PDO::FETCH_ASSOC);

        // Format orders
        $formattedPedidos = [];
        foreach ($pedidos as $p) {
            // Buscar itens do pedido da tabela pedido_itens
            $itensTexto = '';
            try {
                $stmtItens = $pdo->prepare("SELECT pi.*, pr.nome as produto_nome 
                                            FROM pedido_itens pi 
                                            LEFT JOIN produtos pr ON pi.produto_id = pr.id 
                                            WHERE pi.pedido_id = ?");
                $stmtItens->execute([$p['id']]);
                $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($itens)) {
                    $itensArray = [];
                    foreach ($itens as $item) {
                        $nome = $item['produto_nome'] ?? $item['nome_produto'] ?? 'Produto';
                        $qtd = $item['quantidade'] ?? 1;
                        $obs = $item['observacoes'] ?? '';
                        
                        $itemStr = "{$qtd}x {$nome}";
                        if (!empty($obs)) {
                            $itemStr .= " ({$obs})";
                        }
                        $itensArray[] = $itemStr;
                    }
                    $itensTexto = implode(', ', $itensArray);
                }
            } catch (Exception $e) {
                // Se falhar, tenta campo resumo_pedidos
                $itensTexto = $p['resumo_pedidos'] ?? '';
            }
            
            // Se ainda vazio, tenta campo itens_json
            if (empty($itensTexto) && !empty($p['itens_json'])) {
                $itensJson = json_decode($p['itens_json'], true);
                if (is_array($itensJson)) {
                    $itensArray = [];
                    foreach ($itensJson as $item) {
                        $nome = $item['nome'] ?? 'Produto';
                        $qtd = $item['quantidade'] ?? 1;
                        $itensArray[] = "{$qtd}x {$nome}";
                    }
                    $itensTexto = implode(', ', $itensArray);
                }
            }
            
            $formattedPedidos[] = [
                'id' => $p['id'],
                'data_pedido' => $p['data_pedido'],
                'codigo_pedido' => $p['codigo_pedido'] ?? '',
                'cliente_nome' => $p['cliente_nome'] ?? 'Cliente não identificado',
                'cliente_telefone' => $p['cliente_telefone'] ?? '',
                'cliente_endereco' => $p['cliente_endereco'] ?? '',
                'mesa' => $p['mesa'] ?? '',
                'itens' => $itensTexto ?: 'Sem itens',
                'valor_total' => $p['valor_total'] ?? 0,
                'pago' => (($p['status_pagamento'] ?? '') == 'aprovado') || (($p['pago'] ?? 0) == 1),
                'em_preparo' => ($p['em_preparo'] ?? 0) == 1,
                'saiu_entrega' => ($p['saiu_entrega'] ?? 0) == 1,
                'entregue' => ($p['entregue'] ?? 0) == 1,
                'tipo_entrega' => $p['tipo_entrega'] ?? 'delivery',
                'tem_retirados' => false
            ];
        }

        $kanbanData[] = [
            'id' => $lane['id'],
            'nome' => $lane['nome'],
            'cor' => $lane['cor'],
            'ordem' => $lane['ordem'] ?? 0,
            'acao' => $lane['acao'] ?? null,
            'total' => count($formattedPedidos),
            'pedidos' => $formattedPedidos
        ];
    }

    echo json_encode(['success' => true, 'lanes' => $kanbanData]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
