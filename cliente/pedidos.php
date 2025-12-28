<?php
require_once '../includes/config.php';
require_once 'includes/auth.php';

verificar_login_cliente();

$cliente_id = $_SESSION['cliente_id'];

// Filter parameters
$status_filter = $_GET['status'] ?? '';
$busca = $_GET['busca'] ?? '';
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

// Build query - limit to last 6 months by default
$seis_meses_atras = date('Y-m-d H:i:s', strtotime('-6 months'));
$where = ["p.cliente_id = ?", "p.data_pedido >= ?"];
$params = [$cliente_id, $seis_meses_atras];

if ($status_filter) {
    $where[] = "p.status = ?";
    $params[] = $status_filter;
}

if ($busca) {
    $where[] = "(p.codigo_pedido LIKE ? OR DATE_FORMAT(p.data_pedido, '%d/%m/%Y') LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

$where_clause = implode(' AND ', $where);

// Count total
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pedidos p WHERE $where_clause");
$stmt->execute($params);
$total_pedidos = $stmt->fetchColumn();
$total_paginas = ceil($total_pedidos / $por_pagina);

// Get orders
$stmt = $pdo->prepare("
    SELECT p.*, 
           GROUP_CONCAT(CONCAT(pi.quantidade, 'x ', pi.produto_nome) SEPARATOR ', ') as itens_resumo,
           COUNT(pi.id) as total_itens
    FROM pedidos p
    LEFT JOIN pedido_itens pi ON p.id = pi.pedido_id
    WHERE $where_clause
    GROUP BY p.id
    ORDER BY p.data_pedido DESC
    LIMIT $por_pagina OFFSET $offset
");
$stmt->execute($params);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get status counts for filters
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) as total 
    FROM pedidos 
    WHERE cliente_id = ? AND data_pedido >= ?
    GROUP BY status
");
$stmt->execute([$cliente_id, $seis_meses_atras]);
$status_counts = [];
while ($row = $stmt->fetch()) {
    $status_counts[$row['status']] = $row['total'];
}

$page_title = 'Meus Pedidos';
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 fade-in">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 0.875rem;">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color: var(--primary);">Início</a></li>
                <li class="breadcrumb-item active" style="color: var(--gray-500);">Pedidos</li>
            </ol>
        </nav>
        <h4 class="mb-0" style="color: var(--gray-900);">
            <i class="fa-solid fa-receipt me-2 text-primary"></i>Meus Pedidos
        </h4>
        <small style="color: var(--gray-500);">Exibindo últimos 6 meses</small>
    </div>
    
    <a href="../index.php" class="btn btn-premium btn-primary-gradient">
        <i class="fa-solid fa-cart-plus me-2"></i>Novo Pedido
    </a>
</div>

<!-- Search and Filters -->
<div class="card-premium mb-4 fade-in">
    <div class="card-body py-3">
        <div class="row g-3 align-items-center">
            <!-- Search -->
            <div class="col-md-4">
                <form method="GET" class="d-flex gap-2">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0" style="border-color: var(--gray-200);">
                            <i class="fa-solid fa-search" style="color: var(--gray-400);"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" name="busca" 
                               placeholder="Buscar por código ou data..."
                               value="<?php echo htmlspecialchars($busca); ?>"
                               style="border-color: var(--gray-200);">
                    </div>
                    <button type="submit" class="btn btn-outline-premium">Buscar</button>
                </form>
            </div>
            
            <!-- Status Filters -->
            <div class="col-md-8">
                <div class="d-flex flex-wrap gap-2 justify-content-md-end">
                    <a href="?<?php echo $busca ? 'busca=' . urlencode($busca) : ''; ?>" 
                       class="btn btn-sm <?php echo !$status_filter ? 'btn-primary-gradient' : 'btn-glass'; ?>">
                        Todos
                        <span class="badge bg-white text-dark ms-1"><?php echo array_sum($status_counts); ?></span>
                    </a>
                    
                    <?php
                    $status_labels = [
                        'pendente' => ['label' => 'Pendentes', 'icon' => 'fa-clock', 'color' => 'warning'],
                        'em_preparo' => ['label' => 'Preparo', 'icon' => 'fa-fire', 'color' => 'info'],
                        'preparando' => ['label' => 'Preparo', 'icon' => 'fa-fire', 'color' => 'info'],
                        'saiu_entrega' => ['label' => 'Em Entrega', 'icon' => 'fa-motorcycle', 'color' => 'primary'],
                        'entregue' => ['label' => 'Entregues', 'icon' => 'fa-check', 'color' => 'success'],
                        'concluido' => ['label' => 'Concluídos', 'icon' => 'fa-check', 'color' => 'success'],
                        'cancelado' => ['label' => 'Cancelados', 'icon' => 'fa-times', 'color' => 'danger']
                    ];
                    
                    foreach ($status_counts as $status => $count):
                        $info = $status_labels[$status] ?? ['label' => ucfirst($status), 'icon' => 'fa-circle', 'color' => 'secondary'];
                    ?>
                    <a href="?status=<?php echo $status; ?><?php echo $busca ? '&busca=' . urlencode($busca) : ''; ?>" 
                       class="btn btn-sm <?php echo $status_filter === $status ? 'btn-primary-gradient' : 'btn-glass'; ?>">
                        <i class="fa-solid <?php echo $info['icon']; ?> me-1"></i>
                        <?php echo $info['label']; ?>
                        <span class="badge bg-white text-dark ms-1"><?php echo $count; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Orders List -->
<?php if (empty($pedidos)): ?>
    <div class="empty-state py-5 fade-in">
        <div class="empty-icon">
            <i class="fa-solid fa-receipt"></i>
        </div>
        <div class="empty-title">
            <?php echo $busca || $status_filter ? 'Nenhum pedido encontrado' : 'Nenhum pedido ainda'; ?>
        </div>
        <div class="empty-text">
            <?php if ($busca || $status_filter): ?>
                Tente ajustar os filtros de busca
            <?php else: ?>
                Faça seu primeiro pedido agora!
            <?php endif; ?>
        </div>
        <?php if (!$busca && !$status_filter): ?>
            <a href="../index.php" class="btn btn-premium btn-primary-gradient">
                <i class="fa-solid fa-utensils me-2"></i>Ver Cardápio
            </a>
        <?php else: ?>
            <a href="pedidos.php" class="btn btn-outline-premium">
                <i class="fa-solid fa-times me-2"></i>Limpar Filtros
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($pedidos as $index => $pedido): ?>
            <?php
            $status_class = $pedido['status'];
            $itens = $pedido['itens_resumo'] ?: 'Itens do pedido';
            if (strlen($itens) > 80) {
                $itens = substr($itens, 0, 80) . '...';
            }
            ?>
            <div class="col-12 fade-in" style="animation-delay: <?php echo $index * 0.05; ?>s;">
                <a href="pedido_detalhe.php?id=<?php echo $pedido['id']; ?>" class="text-decoration-none">
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span class="order-id">Pedido #<?php echo htmlspecialchars($pedido['codigo_pedido']); ?></span>
                                <span class="status-badge <?php echo $status_class; ?> ms-2">
                                    <?php echo ucfirst(str_replace('_', ' ', $pedido['status'])); ?>
                                </span>
                            </div>
                            <span class="order-date">
                                <i class="fa-regular fa-calendar me-1"></i>
                                <?php echo date('d/m/Y \à\s H:i', strtotime($pedido['data_pedido'])); ?>
                            </span>
                        </div>
                        
                        <div class="order-items">
                            <i class="fa-solid fa-basket-shopping me-2" style="color: var(--gray-400);"></i>
                            <?php echo htmlspecialchars($itens); ?>
                        </div>
                        
                        <div class="order-footer">
                            <div>
                                <span class="order-total">R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></span>
                                <span class="ms-2" style="color: var(--gray-500); font-size: 0.75rem;">
                                    • <?php echo $pedido['total_itens']; ?> <?php echo $pedido['total_itens'] == 1 ? 'item' : 'itens'; ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span style="color: var(--gray-500); font-size: 0.75rem;">
                                    <i class="fa-solid <?php echo $pedido['tipo_entrega'] === 'delivery' ? 'fa-motorcycle' : 'fa-store'; ?> me-1"></i>
                                    <?php echo ucfirst($pedido['tipo_entrega']); ?>
                                </span>
                                <i class="fa-solid fa-chevron-right" style="color: var(--gray-400);"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_paginas > 1): ?>
        <nav class="mt-4 fade-in">
            <ul class="pagination justify-content-center gap-2">
                <?php if ($pagina > 1): ?>
                    <li class="page-item">
                        <a class="page-link rounded-pill px-3" style="border: none; background: var(--gray-100);"
                           href="?pagina=<?php echo $pagina - 1; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?><?php echo $busca ? '&busca=' . urlencode($busca) : ''; ?>">
                            <i class="fa-solid fa-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php
                $start = max(1, $pagina - 2);
                $end = min($total_paginas, $pagina + 2);
                
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <li class="page-item <?php echo $i === $pagina ? 'active' : ''; ?>">
                        <a class="page-link rounded-pill px-3" 
                           style="border: none; <?php echo $i === $pagina ? 'background: var(--primary); color: white;' : 'background: var(--gray-100);'; ?>"
                           href="?pagina=<?php echo $i; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?><?php echo $busca ? '&busca=' . urlencode($busca) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($pagina < $total_paginas): ?>
                    <li class="page-item">
                        <a class="page-link rounded-pill px-3" style="border: none; background: var(--gray-100);"
                           href="?pagina=<?php echo $pagina + 1; ?><?php echo $status_filter ? '&status=' . urlencode($status_filter) : ''; ?><?php echo $busca ? '&busca=' . urlencode($busca) : ''; ?>">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <p class="text-center mt-2" style="color: var(--gray-500); font-size: 0.875rem;">
                Mostrando <?php echo count($pedidos); ?> de <?php echo $total_pedidos; ?> pedidos
            </p>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>