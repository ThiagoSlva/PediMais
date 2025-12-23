<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$codigo = $_GET['codigo'] ?? '';

if (empty($codigo)) {
    header('Location: index.php');
    exit;
}

// Buscar pedido
$stmt = $pdo->prepare("
    SELECT p.*, fp.nome as forma_pagamento_nome, fp.tipo as forma_pagamento_tipo
    FROM pedidos p 
    LEFT JOIN formas_pagamento fp ON p.forma_pagamento_id = fp.id
    WHERE p.codigo_pedido = ?
");
$stmt->execute([$codigo]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    header('Location: index.php');
    exit;
}

// Buscar itens do pedido
$stmt_itens = $pdo->prepare("SELECT * FROM pedido_itens WHERE pedido_id = ?");
$stmt_itens->execute([$pedido['id']]);
$itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

// Buscar configurações
$config = get_config();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado - <?php echo htmlspecialchars($config['nome_loja'] ?? 'Cardápio Digital'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --bg-dark: #0f0f1a;
            --surface-card: #1a1a2e;
            --text-primary: #ffffff;
            --text-muted: #9ca3af;
            --success: #22c55e;
            --border-color: #2d2d44;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            width: 100%;
        }
        .success-icon {
            width: 100px;
            height: 100px;
            background: var(--success);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 40px auto 30px;
            animation: pulse 2s infinite;
        }
        .success-icon i { font-size: 50px; color: white; }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        h1 { text-align: center; margin-bottom: 10px; font-size: 1.8rem; }
        .subtitle { text-align: center; color: var(--text-muted); margin-bottom: 30px; }
        .card {
            background: var(--surface-card);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-title i { color: var(--primary-color); }
        .codigo-pedido {
            background: var(--primary-color);
            color: white;
            padding: 16px;
            border-radius: 12px;
            text-align: center;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 3px;
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: var(--text-muted); }
        .info-value { font-weight: 600; }
        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .item-row:last-child { border-bottom: none; }
        .item-nome { font-weight: 500; }
        .item-qtd { color: var(--text-muted); font-size: 0.9rem; }
        .item-preco { color: var(--primary-color); font-weight: 600; }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding-top: 16px;
            margin-top: 16px;
            border-top: 2px solid var(--border-color);
            font-size: 1.2rem;
            font-weight: 700;
        }
        .total-row .total-valor { color: var(--success); }
        .btn {
            display: block;
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            margin-bottom: 12px;
            transition: transform 0.2s, opacity 0.2s;
        }
        .btn:hover { transform: translateY(-2px); opacity: 0.9; }
        .btn-primary { background: var(--primary-color); color: white; }
        .btn-outline { background: transparent; border: 2px solid var(--border-color); color: var(--text-primary); }
        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pendente { background: rgba(255, 193, 7, 0.2); color: #ffc107; }
        .status-confirmado { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">
            <i class="fa-solid fa-check"></i>
        </div>
        
        <h1>Pedido Confirmado!</h1>
        <p class="subtitle">Seu pedido foi recebido com sucesso</p>
        
        <div class="codigo-pedido">
            #<?php echo htmlspecialchars($pedido['codigo_pedido']); ?>
        </div>
        
        <div class="card">
            <div class="card-title">
                <i class="fa-solid fa-info-circle"></i> Detalhes do Pedido
            </div>
            <div class="info-row">
                <span class="info-label">Status</span>
                <span class="status-badge status-<?php echo $pedido['status']; ?>">
                    <?php echo ucfirst($pedido['status']); ?>
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Cliente</span>
                <span class="info-value"><?php echo htmlspecialchars($pedido['cliente_nome']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Telefone</span>
                <span class="info-value"><?php echo htmlspecialchars($pedido['cliente_telefone']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Entrega</span>
                <span class="info-value"><?php echo $pedido['tipo_entrega'] === 'delivery' ? 'Delivery' : 'Retirada no Local'; ?></span>
            </div>
            <?php if ($pedido['tipo_entrega'] === 'delivery' && !empty($pedido['cliente_endereco'])): ?>
            <div class="info-row">
                <span class="info-label">Endereço</span>
                <span class="info-value" style="text-align: right; max-width: 60%;"><?php echo htmlspecialchars($pedido['cliente_endereco']); ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="info-label">Pagamento</span>
                <span class="info-value"><?php echo htmlspecialchars($pedido['forma_pagamento_nome'] ?? 'N/A'); ?></span>
            </div>
        </div>
        
        <div class="card">
            <div class="card-title">
                <i class="fa-solid fa-shopping-bag"></i> Itens do Pedido
            </div>
            <?php foreach ($itens as $item): ?>
            <div class="item-row">
                <div>
                    <div class="item-nome"><?php echo htmlspecialchars($item['produto_nome']); ?></div>
                    <div class="item-qtd">Qtd: <?php echo $item['quantidade']; ?></div>
                </div>
                <div class="item-preco">
                    R$ <?php echo number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.'); ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="total-row">
                <span>Total</span>
                <span class="total-valor">R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></span>
            </div>
        </div>
        
        <?php
        // Buscar histórico de pedidos deste telefone
        $stmt_hist = $pdo->prepare("
            SELECT codigo_pedido, data_pedido, valor_total, status 
            FROM pedidos 
            WHERE cliente_telefone = ? 
            AND id != ? 
            ORDER BY id DESC 
            LIMIT 5
        ");
        $stmt_hist->execute([$pedido['cliente_telefone'], $pedido['id']]);
        $historico = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($historico) > 0):
        ?>
        <div class="card">
            <div class="card-title">
                <i class="fa-solid fa-clock-rotate-left"></i> Seus Pedidos Recentes
            </div>
            
            <?php foreach ($historico as $h): ?>
            <div class="item-row" style="align-items: center;">
                <div>
                    <div class="item-nome">#<?php echo htmlspecialchars($h['codigo_pedido']); ?></div>
                    <div class="item-qtd"><?php echo date('d/m/Y H:i', strtotime($h['data_pedido'])); ?></div>
                </div>
                <div style="text-align: right;">
                    <div class="item-preco">R$ <?php echo number_format($h['valor_total'], 2, ',', '.'); ?></div>
                    <span class="status-badge status-<?php echo $h['status']; ?>" style="font-size: 0.75rem; padding: 4px 8px;">
                        <?php echo ucfirst($h['status']); ?>
                    </span>
                    <div style="margin-top: 4px;">
                        <a href="confirmacao.php?codigo=<?php echo $h['codigo_pedido']; ?>" style="color: var(--primary-color); font-size: 0.85rem; text-decoration: none;">
                            Ver Detalhes <i class="fa-solid fa-chevron-right" style="font-size: 0.7rem;"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <a href="index.php" class="btn btn-primary">
            <i class="fa-solid fa-home"></i> Voltar ao Cardápio
        </a>
        
        <a href="https://wa.me/55<?php echo preg_replace('/[^0-9]/', '', $config['whatsapp'] ?? ''); ?>?text=Olá! Fiz o pedido %23<?php echo $pedido['codigo_pedido']; ?>" target="_blank" class="btn btn-outline">
            <i class="fa-brands fa-whatsapp"></i> Falar no WhatsApp
        </a>
    </div>
</body>
</html>
