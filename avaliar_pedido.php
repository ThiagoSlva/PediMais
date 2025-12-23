<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$msg = '';
$msg_tipo = '';
$pedido = null;
$produtos_pedido = [];
$ja_avaliou = false;

// Migrar tabela avaliacoes para incluir produto_id se não existir
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM avaliacoes LIKE 'produto_id'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE avaliacoes ADD COLUMN produto_id INT NULL AFTER pedido_id");
        $pdo->exec("ALTER TABLE avaliacoes ADD INDEX idx_produto_id (produto_id)");
    }
} catch (Exception $e) {
    // Silently handle
}

// Verificar se o token foi fornecido
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $msg = 'Link de avaliação inválido.';
    $msg_tipo = 'danger';
} else {
    // Buscar avaliação pelo token
    $stmt = $pdo->prepare("SELECT * FROM avaliacoes WHERE token = ?");
    $stmt->execute([$token]);
    $avaliacao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$avaliacao) {
        $msg = 'Link de avaliação não encontrado ou expirado.';
        $msg_tipo = 'danger';
    } else {
        // Buscar dados do pedido
        $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
        $stmt->execute([$avaliacao['pedido_id']]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pedido) {
            // Buscar produtos do pedido
            $stmt = $pdo->prepare("
                SELECT pi.*, p.nome as produto_nome, p.imagem_path as produto_imagem
                FROM pedido_itens pi
                LEFT JOIN produtos p ON pi.produto_id = p.id
                WHERE pi.pedido_id = ?
            ");
            $stmt->execute([$pedido['id']]);
            $produtos_pedido = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Buscar avaliações já feitas para este pedido
            $stmt = $pdo->prepare("SELECT produto_id, avaliacao FROM avaliacoes WHERE pedido_id = ? AND produto_id IS NOT NULL AND avaliacao > 0");
            $stmt->execute([$pedido['id']]);
            $avaliacoes_feitas = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Verificar se já avaliou todos os produtos
            if (count($avaliacoes_feitas) >= count($produtos_pedido) && count($produtos_pedido) > 0) {
                $ja_avaliou = true;
                $msg = 'Você já avaliou todos os produtos deste pedido. Obrigado!';
                $msg_tipo = 'success';
            }
        }
    }
}

// Processar envio das avaliações
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $pedido && !$ja_avaliou) {
    $avaliacoes_salvas = 0;
    
    try {
        foreach ($produtos_pedido as $prod) {
            $produto_id = $prod['produto_id'];
            $nota_key = 'nota_' . $produto_id;
            $comentario_key = 'comentario_' . $produto_id;
            
            if (isset($_POST[$nota_key])) {
                $nota = (int)$_POST[$nota_key];
                $comentario = trim($_POST[$comentario_key] ?? '');
                
                if ($nota >= 1 && $nota <= 5) {
                    // Verificar se já existe avaliação para este produto neste pedido
                    $stmt = $pdo->prepare("SELECT id FROM avaliacoes WHERE pedido_id = ? AND produto_id = ?");
                    $stmt->execute([$pedido['id'], $produto_id]);
                    
                    if ($stmt->fetch()) {
                        // Atualizar existente
                        $stmt = $pdo->prepare("UPDATE avaliacoes SET avaliacao = ?, descricao = ?, data_avaliacao = NOW() WHERE pedido_id = ? AND produto_id = ?");
                        $stmt->execute([$nota, $comentario, $pedido['id'], $produto_id]);
                    } else {
                        // Inserir nova avaliação por produto
                        $stmt = $pdo->prepare("INSERT INTO avaliacoes (pedido_id, produto_id, nome, cliente_nome, avaliacao, descricao, ativo, data_avaliacao) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
                        $stmt->execute([
                            $pedido['id'],
                            $produto_id,
                            $avaliacao['nome'] ?? $avaliacao['cliente_nome'] ?? 'Cliente',
                            $avaliacao['cliente_nome'] ?? 'Cliente',
                            $nota,
                            $comentario
                        ]);
                    }
                    $avaliacoes_salvas++;
                }
            }
        }
        
        if ($avaliacoes_salvas > 0) {
            $msg = 'Obrigado pelas suas avaliações!';
            $msg_tipo = 'success';
            $ja_avaliou = true;
        } else {
            $msg = 'Por favor, avalie pelo menos um produto.';
            $msg_tipo = 'warning';
        }
        
    } catch (PDOException $e) {
        $msg = 'Erro ao salvar avaliação. Tente novamente.';
        $msg_tipo = 'danger';
    }
}

// Buscar configurações visuais
$stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);
$nome_loja = $config['nome_loja'] ?? 'Nosso Estabelecimento';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliar Produtos - <?php echo htmlspecialchars($nome_loja); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0f1117;
            --card-bg: #1a1d26;
            --card-border: #2d3446;
            --primary: #4a66f9;
            --primary-light: #6b7cfa;
            --success: #10b981;
            --warning: #fbbf24;
            --danger: #ef4444;
            --text-primary: #ffffff;
            --text-secondary: #9ca3af;
            --gradient-start: #667eea;
            --gradient-end: #764ba2;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-dark);
            min-height: 100vh;
            color: var(--text-primary);
            padding: 20px;
        }
        
        .container {
            max-width: 500px;
            margin: 0 auto;
        }
        
        /* Header com ícone */
        .header-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.4);
        }
        
        .page-title {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 8px;
        }
        
        .page-subtitle {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 25px;
            font-size: 0.95rem;
        }
        
        /* Card de Código do Pedido */
        .order-code-card {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .order-code {
            font-size: 1.8rem;
            font-weight: 800;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
        }
        
        /* Card Principal */
        .main-card {
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid var(--card-border);
            overflow: hidden;
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-header-icon {
            width: 24px;
            height: 24px;
            background: var(--primary);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .card-header-title {
            font-weight: 700;
            font-size: 1rem;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Order Info Grid */
        .order-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            padding: 15px;
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .order-info-item {
            text-align: center;
        }
        
        .order-info-label {
            color: var(--primary);
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        
        .order-info-value {
            font-weight: 700;
            font-size: 0.9rem;
        }
        
        /* Alertas */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--success);
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--danger);
        }
        
        .alert-warning {
            background: rgba(251, 191, 36, 0.15);
            border: 1px solid rgba(251, 191, 36, 0.3);
            color: var(--warning);
        }
        
        /* Produto Item */
        .product-item {
            background: var(--bg-dark);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
        }
        
        .product-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .product-avatar {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        
        .product-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
        }
        
        .product-info {
            flex: 1;
        }
        
        .product-name {
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 2px;
        }
        
        .product-qty {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }
        
        /* Star Rating */
        .stars-container {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .star {
            font-size: 28px;
            color: #3a3f4d;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }
        
        .star:hover,
        .star.active {
            color: var(--warning);
            transform: scale(1.15);
        }
        
        .star.active {
            text-shadow: 0 0 15px rgba(251, 191, 36, 0.5);
        }
        
        /* Input */
        .comment-input {
            width: 100%;
            padding: 14px 16px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            color: var(--text-primary);
            font-family: inherit;
            font-size: 0.9rem;
            resize: none;
            transition: border-color 0.2s;
        }
        
        .comment-input::placeholder {
            color: var(--text-secondary);
        }
        
        .comment-input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.35);
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.5);
        }
        
        .btn-submit:active {
            transform: translateY(0);
        }
        
        /* Thank You State */
        .thank-you {
            text-align: center;
            padding: 40px 20px;
        }
        
        .thank-you-icon {
            width: 80px;
            height: 80px;
            background: var(--success);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            box-shadow: 0 10px 40px rgba(16, 185, 129, 0.4);
        }
        
        .thank-you h2 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        .thank-you p {
            color: var(--text-secondary);
        }
        
        /* Error State */
        .error-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        .error-icon {
            width: 80px;
            height: 80px;
            background: var(--danger);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header-icon">⭐</div>
        <h1 class="page-title">Avalie seus Produtos</h1>
        <p class="page-subtitle"><?php echo htmlspecialchars($nome_loja); ?></p>
        
        <?php if ($msg && !$ja_avaliou): ?>
            <div class="alert alert-<?php echo $msg_tipo; ?>">
                <?php if ($msg_tipo === 'warning'): ?>⚠️<?php elseif ($msg_tipo === 'danger'): ?>❌<?php else: ?>✅<?php endif; ?>
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($ja_avaliou): ?>
            <!-- Estado de Agradecimento -->
            <div class="main-card">
                <div class="card-body">
                    <div class="thank-you">
                        <div class="thank-you-icon">✓</div>
                        <h2>Obrigado!</h2>
                        <p>Suas avaliações foram registradas com sucesso.</p>
                    </div>
                </div>
            </div>
            
        <?php elseif ($pedido && count($produtos_pedido) > 0): ?>
            <!-- Código do Pedido -->
            <div class="order-code-card">
                <div class="order-code">#<?php echo htmlspecialchars($pedido['codigo_pedido']); ?></div>
            </div>
            
            <!-- Card Principal -->
            <div class="main-card">
                <div class="card-header">
                    <div class="card-header-icon">📦</div>
                    <span class="card-header-title">Itens do Pedido</span>
                </div>
                
                <div class="card-body">
                    <!-- Info do Pedido -->
                    <div class="order-info">
                        <div class="order-info-item">
                            <div class="order-info-label">Pedido</div>
                            <div class="order-info-value">#<?php echo htmlspecialchars($pedido['codigo_pedido']); ?></div>
                        </div>
                        <div class="order-info-item">
                            <div class="order-info-label">Data</div>
                            <div class="order-info-value"><?php echo date('d/m/Y', strtotime($pedido['data_pedido'])); ?></div>
                        </div>
                        <div class="order-info-item">
                            <div class="order-info-label">Itens</div>
                            <div class="order-info-value"><?php echo count($produtos_pedido); ?> produto(s)</div>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <?php foreach ($produtos_pedido as $index => $prod): 
                            $produto_id = $prod['produto_id'];
                            $avaliacao_existente = $avaliacoes_feitas[$produto_id] ?? 0;
                            $inicial = strtoupper(substr($prod['produto_nome'] ?? $prod['nome'] ?? 'P', 0, 1));
                        ?>
                        <div class="product-item">
                            <div class="product-header">
                                <div class="product-avatar">
                                    <?php if (!empty($prod['produto_imagem'])): ?>
                                        <img src="<?php echo htmlspecialchars($prod['produto_imagem']); ?>" alt="">
                                    <?php else: ?>
                                        <?php echo $inicial; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <div class="product-name"><?php echo htmlspecialchars($prod['produto_nome'] ?? $prod['nome'] ?? 'Produto'); ?></div>
                                    <div class="product-qty">Qtd: <?php echo $prod['quantidade']; ?></div>
                                </div>
                            </div>
                            
                            <input type="hidden" name="nota_<?php echo $produto_id; ?>" id="nota_<?php echo $produto_id; ?>" value="<?php echo $avaliacao_existente; ?>">
                            
                            <div class="stars-container" data-produto="<?php echo $produto_id; ?>">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <span 
                                        class="star <?php echo $i <= $avaliacao_existente ? 'active' : ''; ?>" 
                                        data-value="<?php echo $i; ?>"
                                        onclick="setRating(<?php echo $produto_id; ?>, <?php echo $i; ?>)">★</span>
                                <?php endfor; ?>
                            </div>
                            
                            <textarea 
                                name="comentario_<?php echo $produto_id; ?>" 
                                class="comment-input" 
                                placeholder="Comentário opcional..."
                                rows="2"></textarea>
                        </div>
                        <?php endforeach; ?>
                        
                        <button type="submit" class="btn-submit">
                            Enviar Avaliações
                        </button>
                    </form>
                </div>
            </div>
            
        <?php else: ?>
            <!-- Estado de Erro -->
            <div class="main-card">
                <div class="card-body">
                    <div class="error-state">
                        <div class="error-icon">❌</div>
                        <h2>Pedido não encontrado</h2>
                        <p>Não foi possível carregar os produtos deste pedido.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function setRating(produtoId, value) {
            document.getElementById('nota_' + produtoId).value = value;
            
            const container = document.querySelector(`.stars-container[data-produto="${produtoId}"]`);
            const stars = container.querySelectorAll('.star');
            
            stars.forEach((star, index) => {
                if (index < value) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }
    </script>
</body>
</html>
