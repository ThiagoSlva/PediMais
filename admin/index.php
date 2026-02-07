<?php
require_once 'includes/header.php';

// Estatísticas básicas
$total_pedidos = $pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
$total_clientes = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
$total_produtos = $pdo->query("SELECT COUNT(*) FROM produtos WHERE ativo = 1")->fetchColumn();
$total_categorias = $pdo->query("SELECT COUNT(*) FROM categorias WHERE ativo = 1")->fetchColumn();
$pedidos_pendentes = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE status = 'pendente'")->fetchColumn();
$pedidos_hoje = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE DATE(data_pedido) = CURDATE()")->fetchColumn();
$faturamento_mes = $pdo->query("SELECT COALESCE(SUM(valor_total), 0) FROM pedidos WHERE status IN ('concluido', 'finalizado', 'entregue') AND MONTH(data_pedido) = MONTH(CURDATE()) AND YEAR(data_pedido) = YEAR(CURDATE())")->fetchColumn();
$faturamento_hoje = $pdo->query("SELECT COALESCE(SUM(valor_total), 0) FROM pedidos WHERE status IN ('concluido', 'finalizado', 'entregue') AND DATE(data_pedido) = CURDATE()")->fetchColumn();

// Últimos pedidos
$stmt_ultimos = $pdo->query("SELECT p.*, c.nome as cliente_nome FROM pedidos p LEFT JOIN clientes c ON p.cliente_id = c.id ORDER BY p.data_pedido DESC LIMIT 5");
$ultimos_pedidos = $stmt_ultimos->fetchAll(PDO::FETCH_ASSOC);

// Produtos mais vendidos
$stmt_top_produtos = $pdo->query("SELECT pi.produto_nome, SUM(pi.quantidade) as total_vendido FROM pedido_itens pi GROUP BY pi.produto_nome ORDER BY total_vendido DESC LIMIT 5");
$top_produtos = $stmt_top_produtos->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* Dashboard Premium Styles */
.stat-card {
    background: linear-gradient(135deg, #1a1d26 0%, #0f1117 100%);
    border: 1px solid #2d3446;
    border-radius: 16px;
    padding: 24px;
    height: 100%;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    opacity: 0.1;
    transform: translate(30%, -30%);
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
    border-color: #4a66f9;
}

.stat-card.purple::before { background: #8b5cf6; }
.stat-card.blue::before { background: #3b82f6; }
.stat-card.green::before { background: #10b981; }
.stat-card.orange::before { background: #f97316; }
.stat-card.pink::before { background: #ec4899; }
.stat-card.cyan::before { background: #06b6d4; }

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 16px;
}

.stat-icon.purple { background: rgba(139, 92, 246, 0.15); color: #8b5cf6; }
.stat-icon.blue { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
.stat-icon.green { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.stat-icon.orange { background: rgba(249, 115, 22, 0.15); color: #f97316; }
.stat-icon.pink { background: rgba(236, 72, 153, 0.15); color: #ec4899; }
.stat-icon.cyan { background: rgba(6, 182, 212, 0.15); color: #06b6d4; }

.stat-value {
    font-size: 2rem;
    font-weight: 800;
    color: #fff;
    line-height: 1;
    margin-bottom: 6px;
}

.stat-label {
    color: #9ca3af;
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 12px;
}

.stat-link {
    color: #4a66f9;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: color 0.2s;
}

.stat-link:hover {
    color: #6b7cfa;
}

/* Section Cards */
.section-card {
    background: #1a1d26;
    border: 1px solid #2d3446;
    border-radius: 16px;
    overflow: hidden;
}

.section-header {
    padding: 20px 24px;
    border-bottom: 1px solid #2d3446;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.section-title {
    font-weight: 700;
    font-size: 1.1rem;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title-icon {
    width: 32px;
    height: 32px;
    background: rgba(74, 102, 249, 0.15);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4a66f9;
}

.section-body {
    padding: 20px 24px;
}

/* Table Styles */
.custom-table {
    width: 100%;
    border-collapse: collapse;
}

.custom-table th {
    text-align: left;
    padding: 12px 0;
    color: #9ca3af;
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid #2d3446;
}

.custom-table td {
    padding: 16px 0;
    border-bottom: 1px solid rgba(45, 52, 70, 0.5);
    color: #e2e8f0;
}

.custom-table tr:last-child td {
    border-bottom: none;
}

.custom-table .order-code {
    font-family: 'Courier New', monospace;
    font-weight: 700;
    color: #4a66f9;
}

/* Status badges */
.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pendente { background: rgba(251, 191, 36, 0.15); color: #fbbf24; }
.status-preparando { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
.status-pronto { background: rgba(139, 92, 246, 0.15); color: #8b5cf6; }
.status-entregue, .status-concluido, .status-finalizado { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.status-cancelado { background: rgba(239, 68, 68, 0.15); color: #ef4444; }

/* Top Product Item */
.top-product-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px 0;
    border-bottom: 1px solid rgba(45, 52, 70, 0.5);
}

.top-product-item:last-child {
    border-bottom: none;
}

.top-product-rank {
    width: 28px;
    height: 28px;
    background: rgba(74, 102, 249, 0.15);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    color: #4a66f9;
}

.top-product-name {
    flex: 1;
    font-weight: 500;
    color: #e2e8f0;
}

.top-product-qty {
    font-weight: 700;
    color: #10b981;
}

/* Quick Actions */
.quick-action {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    background: #0f1117;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.2s;
    margin-bottom: 10px;
}

.quick-action:hover {
    background: rgba(74, 102, 249, 0.1);
    transform: translateX(4px);
}

.quick-action:last-child {
    margin-bottom: 0;
}

.quick-action-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.quick-action-icon.green { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.quick-action-icon.blue { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
.quick-action-icon.purple { background: rgba(139, 92, 246, 0.15); color: #8b5cf6; }
.quick-action-icon.orange { background: rgba(249, 115, 22, 0.15); color: #f97316; }

.quick-action-text {
    flex: 1;
    color: #e2e8f0;
    font-weight: 500;
}

.quick-action-arrow {
    color: #4a66f9;
}

/* Welcome Message */
.welcome-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}

.welcome-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 300px;
    height: 300px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}

.welcome-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 8px;
}

.welcome-subtitle {
    color: rgba(255,255,255,0.8);
    font-size: 0.95rem;
}

.welcome-date {
    margin-top: 15px;
    color: rgba(255,255,255,0.7);
    font-size: 0.85rem;
}
</style>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
    <h6 class="fw-semibold mb-0">Dashboard</h6>
    <ul class="d-flex align-items-center gap-2">
        <li class="fw-medium">
            <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                Dashboard
            </a>
        </li>
    </ul>
</div>

<?php if (is_admin()): ?>

<!-- Welcome Card -->
<div class="welcome-card">
    <div class="welcome-title">👋 Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!</div>
    <div class="welcome-subtitle">Aqui está o resumo do seu negócio</div>
    <div class="welcome-date">📅 <?php echo date('d \d\e F \d\e Y'); ?></div>
</div>

<!-- Stats Grid -->
<div class="row g-4 mb-4">
    <!-- Faturamento Hoje -->
    <div class="col-xl-3 col-lg-4 col-sm-6">
        <div class="stat-card green">
            <div class="stat-icon green">
                <iconify-icon icon="solar:wallet-money-outline"></iconify-icon>
            </div>
            <div class="stat-value">R$ <?php echo number_format($faturamento_hoje, 2, ',', '.'); ?></div>
            <div class="stat-label">Faturamento Hoje</div>
            <a href="pedidos.php" class="stat-link">Ver detalhes →</a>
        </div>
    </div>
    
    <!-- Faturamento Mês -->
    <div class="col-xl-3 col-lg-4 col-sm-6">
        <div class="stat-card cyan">
            <div class="stat-icon cyan">
                <iconify-icon icon="solar:chart-outline"></iconify-icon>
            </div>
            <div class="stat-value">R$ <?php echo number_format($faturamento_mes, 2, ',', '.'); ?></div>
            <div class="stat-label">Faturamento do Mês</div>
            <a href="pedidos.php" class="stat-link">Ver relatório →</a>
        </div>
    </div>
    
    <!-- Pedidos Hoje -->
    <div class="col-xl-3 col-lg-4 col-sm-6">
        <div class="stat-card blue">
            <div class="stat-icon blue">
                <iconify-icon icon="solar:bag-4-outline"></iconify-icon>
            </div>
            <div class="stat-value"><?php echo $pedidos_hoje; ?></div>
            <div class="stat-label">Pedidos Hoje</div>
            <a href="pedidos.php" class="stat-link">Ver pedidos →</a>
        </div>
    </div>
    
    <!-- Pedidos Pendentes -->
    <div class="col-xl-3 col-lg-4 col-sm-6">
        <div class="stat-card orange">
            <div class="stat-icon orange">
                <iconify-icon icon="solar:clock-circle-outline"></iconify-icon>
            </div>
            <div class="stat-value"><?php echo $pedidos_pendentes; ?></div>
            <div class="stat-label">Pedidos Pendentes</div>
            <a href="pedidos.php?status=pendente" class="stat-link">Verificar →</a>
        </div>
    </div>
    
    <!-- Total Produtos -->
    <div class="col-xl-3 col-lg-4 col-sm-6">
        <div class="stat-card purple">
            <div class="stat-icon purple">
                <iconify-icon icon="solar:box-outline"></iconify-icon>
            </div>
            <div class="stat-value"><?php echo $total_produtos; ?></div>
            <div class="stat-label">Produtos Ativos</div>
            <a href="produtos.php" class="stat-link">Gerenciar →</a>
        </div>
    </div>
    
    <!-- Total Categorias -->
    <div class="col-xl-3 col-lg-4 col-sm-6">
        <div class="stat-card pink">
            <div class="stat-icon pink">
                <iconify-icon icon="solar:widget-outline"></iconify-icon>
            </div>
            <div class="stat-value"><?php echo $total_categorias; ?></div>
            <div class="stat-label">Categorias</div>
            <a href="categorias.php" class="stat-link">Gerenciar →</a>
        </div>
    </div>
    
    <!-- Total Clientes -->
    <div class="col-xl-3 col-lg-4 col-sm-6">
        <div class="stat-card blue">
            <div class="stat-icon blue">
                <iconify-icon icon="solar:users-group-rounded-outline"></iconify-icon>
            </div>
            <div class="stat-value"><?php echo $total_clientes; ?></div>
            <div class="stat-label">Clientes</div>
            <a href="clientes.php" class="stat-link">Ver clientes →</a>
        </div>
    </div>
    
    <!-- Total Pedidos -->
    <div class="col-xl-3 col-lg-4 col-sm-6">
        <div class="stat-card green">
            <div class="stat-icon green">
                <iconify-icon icon="solar:cart-large-outline"></iconify-icon>
            </div>
            <div class="stat-value"><?php echo $total_pedidos; ?></div>
            <div class="stat-label">Total de Pedidos</div>
            <a href="pedidos.php" class="stat-link">Ver todos →</a>
        </div>
    </div>
</div>

<!-- Bottom Grid -->
<div class="row g-4">
    <!-- Últimos Pedidos -->
    <div class="col-xl-8">
        <div class="section-card">
            <div class="section-header">
                <div class="section-title">
                    <div class="section-title-icon">
                        <iconify-icon icon="solar:bag-4-outline"></iconify-icon>
                    </div>
                    Últimos Pedidos
                </div>
                <a href="pedidos.php" class="stat-link">Ver todos →</a>
            </div>
            <div class="section-body">
                <?php if (count($ultimos_pedidos) > 0): ?>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimos_pedidos as $pedido): ?>
                        <tr>
                            <td class="order-code">#<?php echo htmlspecialchars($pedido['codigo_pedido']); ?></td>
                            <td><?php echo htmlspecialchars($pedido['cliente_nome'] ?? $pedido['nome_cliente'] ?? 'N/A'); ?></td>
                            <td>R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $pedido['status']; ?>">
                                    <?php echo ucfirst($pedido['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m H:i', strtotime($pedido['data_pedido'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-center text-secondary py-4">Nenhum pedido registrado ainda.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-xl-4">
        <!-- Produtos Mais Vendidos -->
        <div class="section-card mb-4">
            <div class="section-header">
                <div class="section-title">
                    <div class="section-title-icon">
                        <iconify-icon icon="solar:star-outline"></iconify-icon>
                    </div>
                    Mais Vendidos
                </div>
            </div>
            <div class="section-body">
                <?php if (count($top_produtos) > 0): ?>
                    <?php foreach ($top_produtos as $index => $prod): ?>
                    <div class="top-product-item">
                        <div class="top-product-rank"><?php echo $index + 1; ?></div>
                        <div class="top-product-name"><?php echo htmlspecialchars($prod['produto_nome']); ?></div>
                        <div class="top-product-qty"><?php echo $prod['total_vendido']; ?>x</div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <p class="text-center text-secondary py-3">Sem dados ainda</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Ações Rápidas -->
        <div class="section-card">
            <div class="section-header">
                <div class="section-title">
                    <div class="section-title-icon">
                        <iconify-icon icon="solar:bolt-outline"></iconify-icon>
                    </div>
                    Ações Rápidas
                </div>
            </div>
            <div class="section-body">
                <a href="produtos_add.php" class="quick-action">
                    <div class="quick-action-icon green">
                        <iconify-icon icon="solar:add-circle-outline"></iconify-icon>
                    </div>
                    <span class="quick-action-text">Novo Produto</span>
                    <iconify-icon icon="solar:arrow-right-outline" class="quick-action-arrow"></iconify-icon>
                </a>
                <a href="pedidos_kanban.php" class="quick-action">
                    <div class="quick-action-icon blue">
                        <iconify-icon icon="solar:clipboard-list-outline"></iconify-icon>
                    </div>
                    <span class="quick-action-text">Kanban de Pedidos</span>
                    <iconify-icon icon="solar:arrow-right-outline" class="quick-action-arrow"></iconify-icon>
                </a>
                <a href="configuracoes.php" class="quick-action">
                    <div class="quick-action-icon purple">
                        <iconify-icon icon="solar:settings-outline"></iconify-icon>
                    </div>
                    <span class="quick-action-text">Configurações</span>
                    <iconify-icon icon="solar:arrow-right-outline" class="quick-action-arrow"></iconify-icon>
                </a>
                <a href="avaliacoes.php" class="quick-action">
                    <div class="quick-action-icon orange">
                        <iconify-icon icon="solar:star-outline"></iconify-icon>
                    </div>
                    <span class="quick-action-text">Ver Avaliações</span>
                    <iconify-icon icon="solar:arrow-right-outline" class="quick-action-arrow"></iconify-icon>
                </a>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
    <!-- Non-admin dashboard content -->
    <div class="row">
        <div class="col-12">
            <div class="welcome-card">
                <div style="text-align: center; position: relative; z-index: 1;">
                    <iconify-icon icon="solar:user-circle-bold-duotone" style="font-size: 64px; margin-bottom: 15px;"></iconify-icon>
                    <h3 class="welcome-title">Bem-vindo, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!</h3>
                    <p class="welcome-subtitle">Você está logado como <strong><?php echo ucfirst($_SESSION['usuario_nivel']); ?></strong>.</p>
                    <p style="color: rgba(255,255,255,0.8); margin-bottom: 0; margin-top: 10px;">Use o menu lateral para acessar suas funções.</p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>