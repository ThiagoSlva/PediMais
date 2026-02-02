<?php
require_once '../includes/config.php';
require_once 'includes/auth.php';

// Sistema de Analytics - Rastreamento de Visitantes
require_once '../includes/analytics_tracker.php';
if (function_exists('track_visitor')) {
    track_visitor('Área do Cliente');
}

verificar_login_cliente();

$cliente_id = $_SESSION['cliente_id'];

// Get client data
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

// Get statistics - last 6 months for default view
$seis_meses_atras = date('Y-m-d H:i:s', strtotime('-6 months'));

// Total orders (all time)
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pedidos WHERE cliente_id = ?");
$stmt->execute([$cliente_id]);
$total_pedidos = $stmt->fetch()['total'];

// Total spent (all time)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(valor_total), 0) as total FROM pedidos WHERE cliente_id = ? AND status NOT IN ('cancelado')");
$stmt->execute([$cliente_id]);
$total_gasto = $stmt->fetch()['total'];

// Orders in progress
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM pedidos WHERE cliente_id = ? AND status IN ('pendente', 'em_preparo', 'preparando', 'saiu_entrega')");
$stmt->execute([$cliente_id]);
$pedidos_andamento = $stmt->fetch()['total'];

// Fidelity points
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM fidelidade_pontos WHERE cliente_id = ? AND status = 'ativo'");
$stmt->execute([$cliente_id]);
$pontos_fidelidade = $stmt->fetch()['total'];

// Get fidelity config
$stmt = $pdo->query("SELECT * FROM fidelidade_config WHERE id = 1");
$fidelidade_config = $stmt->fetch(PDO::FETCH_ASSOC);
$pontos_necessarios = $fidelidade_config['quantidade_pedidos'] ?? 10;
$fidelidade_ativo = $fidelidade_config['ativo'] ?? 0;

// Recent orders (last 5) - within 6 months
$stmt = $pdo->prepare("
    SELECT p.*, 
           GROUP_CONCAT(pi.produto_nome SEPARATOR ', ') as itens_resumo
    FROM pedidos p
    LEFT JOIN pedido_itens pi ON p.id = pi.pedido_id
    WHERE p.cliente_id = ? AND p.data_pedido >= ?
    GROUP BY p.id
    ORDER BY p.data_pedido DESC 
    LIMIT 5
");
$stmt->execute([$cliente_id, $seis_meses_atras]);
$pedidos_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Primary address
$stmt = $pdo->prepare("SELECT * FROM cliente_enderecos WHERE cliente_id = ? ORDER BY principal DESC, id DESC LIMIT 1");
$stmt->execute([$cliente_id]);
$endereco_principal = $stmt->fetch(PDO::FETCH_ASSOC);

$page_title = 'Dashboard';
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4 fade-in">
    <div>
        <h4 class="mb-1" style="color: var(--gray-900);">
            Olá, <span class="text-gradient"><?php echo htmlspecialchars(explode(' ', $cliente['nome'])[0]); ?></span>! 👋
        </h4>
        <p class="mb-0" style="color: var(--gray-500); font-size: 0.875rem;">
            Bem-vindo de volta ao seu painel
        </p>
    </div>
    <a href="../index.php" class="btn btn-premium btn-primary-gradient">
        <i class="fa-solid fa-cart-plus"></i>
        <span class="d-none d-sm-inline">Novo Pedido</span>
    </a>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3 fade-in" style="animation-delay: 0.1s;">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <div class="stat-value"><?php echo $total_pedidos; ?></div>
            <div class="stat-label">Total de Pedidos</div>
        </div>
    </div>
    <div class="col-6 col-lg-3 fade-in" style="animation-delay: 0.2s;">
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="fa-solid fa-brazilian-real-sign"></i>
            </div>
            <div class="stat-value">R$ <?php echo number_format($total_gasto, 0, ',', '.'); ?></div>
            <div class="stat-label">Total Gasto</div>
        </div>
    </div>
    <div class="col-6 col-lg-3 fade-in" style="animation-delay: 0.3s;">
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div class="stat-value"><?php echo $pedidos_andamento; ?></div>
            <div class="stat-label">Em Andamento</div>
        </div>
    </div>
    <div class="col-6 col-lg-3 fade-in" style="animation-delay: 0.4s;">
        <div class="stat-card">
            <div class="stat-icon info">
                <i class="fa-solid fa-star"></i>
            </div>
            <div class="stat-value"><?php echo $pontos_fidelidade; ?></div>
            <div class="stat-label">Pontos Fidelidade</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <h6 class="mb-3" style="color: var(--gray-700);">
            <i class="fa-solid fa-bolt me-2 text-warning"></i>Acesso Rápido
        </h6>
    </div>
    <div class="col-4 col-md-2 fade-in" style="animation-delay: 0.1s;">
        <a href="../index.php" class="quick-action">
            <div class="action-icon">
                <i class="fa-solid fa-utensils"></i>
            </div>
            <span class="action-label">Cardápio</span>
        </a>
    </div>
    <div class="col-4 col-md-2 fade-in" style="animation-delay: 0.15s;">
        <a href="pedidos.php" class="quick-action">
            <div class="action-icon">
                <i class="fa-solid fa-list"></i>
            </div>
            <span class="action-label">Pedidos</span>
        </a>
    </div>
    <div class="col-4 col-md-2 fade-in" style="animation-delay: 0.2s;">
        <a href="enderecos.php" class="quick-action">
            <div class="action-icon">
                <i class="fa-solid fa-location-dot"></i>
            </div>
            <span class="action-label">Endereços</span>
        </a>
    </div>
    <div class="col-4 col-md-2 fade-in" style="animation-delay: 0.25s;">
        <a href="perfil.php" class="quick-action">
            <div class="action-icon">
                <i class="fa-solid fa-user-pen"></i>
            </div>
            <span class="action-label">Perfil</span>
        </a>
    </div>
    <?php if ($fidelidade_ativo): ?>
    <div class="col-4 col-md-2 fade-in" style="animation-delay: 0.3s;">
        <a href="fidelidade.php" class="quick-action">
            <div class="action-icon">
                <i class="fa-solid fa-gift"></i>
            </div>
            <span class="action-label">Fidelidade</span>
        </a>
    </div>
    <?php endif; ?>
    <div class="col-4 col-md-2 fade-in" style="animation-delay: 0.35s;">
        <a href="../index.php" class="quick-action">
            <div class="action-icon">
                <i class="fa-solid fa-headset"></i>
            </div>
            <span class="action-label">Suporte</span>
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column -->
    <div class="col-lg-8">
        <!-- Recent Orders -->
        <div class="card-premium mb-4 fade-in" style="animation-delay: 0.2s;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Pedidos Recentes</span>
                <a href="pedidos.php" class="text-decoration-none" style="color: var(--primary); font-size: 0.875rem;">
                    Ver todos <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($pedidos_recentes)): ?>
                    <div class="empty-state py-5">
                        <div class="empty-icon">
                            <i class="fa-solid fa-receipt"></i>
                        </div>
                        <div class="empty-title">Nenhum pedido ainda</div>
                        <div class="empty-text">Faça seu primeiro pedido agora!</div>
                        <a href="../index.php" class="btn btn-premium btn-primary-gradient">
                            <i class="fa-solid fa-utensils me-2"></i>Ver Cardápio
                        </a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($pedidos_recentes as $pedido): ?>
                            <?php
                            $status_class = $pedido['status'];
                            $itens = $pedido['itens_resumo'] ?: 'Itens do pedido';
                            if (strlen($itens) > 50) {
                                $itens = substr($itens, 0, 50) . '...';
                            }
                            ?>
                            <a href="pedido_detalhe.php?id=<?php echo $pedido['id']; ?>" class="list-group-item list-group-item-action p-3 border-0" style="border-bottom: 1px solid var(--gray-100) !important;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="fw-semibold" style="color: var(--gray-900);">
                                                #<?php echo htmlspecialchars($pedido['codigo_pedido']); ?>
                                            </span>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $pedido['status'])); ?>
                                            </span>
                                        </div>
                                        <p class="mb-1" style="color: var(--gray-600); font-size: 0.875rem;">
                                            <?php echo htmlspecialchars($itens); ?>
                                        </p>
                                        <small style="color: var(--gray-500);">
                                            <i class="fa-regular fa-calendar me-1"></i>
                                            <?php echo date('d/m/Y \à\s H:i', strtotime($pedido['data_pedido'])); ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold" style="color: var(--primary);">
                                            R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?>
                                        </div>
                                        <small style="color: var(--gray-500);">
                                            <?php echo ucfirst($pedido['tipo_entrega']); ?>
                                        </small>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Right Column -->
    <div class="col-lg-4">
        <!-- Primary Address -->
        <div class="card-premium mb-4 fade-in" style="animation-delay: 0.3s;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-location-dot me-2 text-danger"></i>Endereço Principal</span>
                <a href="enderecos.php" class="text-decoration-none" style="color: var(--primary); font-size: 0.875rem;">
                    Gerenciar
                </a>
            </div>
            <div class="card-body">
                <?php if ($endereco_principal): ?>
                    <div class="d-flex align-items-start gap-3">
                        <div style="width: 40px; height: 40px; background: var(--danger-light); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fa-solid fa-house" style="color: var(--danger);"></i>
                        </div>
                        <div>
                            <?php if ($endereco_principal['apelido']): ?>
                                <div class="fw-semibold mb-1" style="color: var(--gray-900);">
                                    <?php echo htmlspecialchars($endereco_principal['apelido']); ?>
                                </div>
                            <?php endif; ?>
                            <p class="mb-0" style="color: var(--gray-600); font-size: 0.875rem; line-height: 1.5;">
                                <?php echo htmlspecialchars($endereco_principal['rua']); ?>, <?php echo htmlspecialchars($endereco_principal['numero']); ?>
                                <?php if ($endereco_principal['complemento']): ?>
                                    - <?php echo htmlspecialchars($endereco_principal['complemento']); ?>
                                <?php endif; ?>
                                <br>
                                <?php echo htmlspecialchars($endereco_principal['bairro']); ?> - <?php echo htmlspecialchars($endereco_principal['cidade']); ?>/<?php echo htmlspecialchars($endereco_principal['estado']); ?>
                                <br>
                                CEP: <?php echo htmlspecialchars($endereco_principal['cep']); ?>
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="fa-solid fa-map-marker-alt fa-2x mb-2" style="color: var(--gray-300);"></i>
                        <p class="mb-2" style="color: var(--gray-500); font-size: 0.875rem;">Nenhum endereço cadastrado</p>
                        <a href="enderecos.php" class="btn btn-sm btn-outline-premium">
                            <i class="fa-solid fa-plus me-1"></i>Adicionar
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Fidelity Card -->
        <?php if ($fidelidade_ativo): ?>
        <div class="fidelity-card fade-in" style="animation-delay: 0.4s;">
            <div class="fidelity-title">
                <i class="fa-solid fa-crown me-2"></i>Programa de Fidelidade
            </div>
            <div class="fidelity-points">
                <?php echo $pontos_fidelidade; ?> / <?php echo $pontos_necessarios; ?> pontos
            </div>
            <div class="fidelity-progress">
                <div class="progress-bar" style="width: <?php echo min(100, ($pontos_fidelidade / $pontos_necessarios) * 100); ?>%;"></div>
            </div>
            <div class="fidelity-text">
                <?php if ($pontos_fidelidade >= $pontos_necessarios): ?>
                    🎉 Parabéns! Você pode resgatar sua recompensa!
                <?php else: ?>
                    Faltam <?php echo $pontos_necessarios - $pontos_fidelidade; ?> pontos para sua recompensa
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
