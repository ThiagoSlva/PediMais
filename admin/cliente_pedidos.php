<?php
include 'includes/header.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<script>window.location.href="clientes.php";</script>';
    exit;
}

$cliente_id = $_GET['id'];

// Buscar dados do cliente
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo '<div class="alert alert-danger">Cliente não encontrado.</div>';
    include 'includes/footer.php';
    exit;
}

// Buscar pedidos do cliente
$stmt = $pdo->prepare("
    SELECT p.*, 
           (SELECT COUNT(*) FROM pedido_itens WHERE pedido_id = p.id) as total_itens
    FROM pedidos p 
    WHERE p.cliente_id = ? 
    ORDER BY p.id DESC
");
$stmt->execute([$cliente_id]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Pedidos de <?php echo htmlspecialchars($cliente['nome']); ?></h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">
                <a href="clientes.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    Clientes
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Pedidos</li>
        </ul>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Histórico de Pedidos</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Total</th>
                            <th>Itens</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pedidos)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">Nenhum pedido encontrado para este cliente.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td>#<?php echo $pedido['id']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></td>
                                <td>R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></td>
                                <td><?php echo $pedido['total_itens']; ?></td>
                                <td>
                                    <?php
                                    $status_class = 'secondary';
                                    $status_label = $pedido['status'];
                                    
                                    switch ($pedido['status']) {
                                        case 'pendente':
                                            $status_class = 'warning';
                                            $status_label = 'Pendente';
                                            break;
                                        case 'em_preparo':
                                        case 'em_andamento':
                                            $status_class = 'info';
                                            $status_label = 'Em Preparo';
                                            break;
                                        case 'pronto':
                                            $status_class = 'primary';
                                            $status_label = 'Pronto';
                                            break;
                                        case 'saiu_entrega':
                                            $status_class = 'purple';
                                            $status_label = 'Saiu para Entrega';
                                            break;
                                        case 'entregue':
                                        case 'concluido':
                                            $status_class = 'success';
                                            $status_label = 'Concluído';
                                            break;
                                        case 'cancelado':
                                            $status_class = 'danger';
                                            $status_label = 'Cancelado';
                                            break;
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $status_class; ?>-focus text-<?php echo $status_class; ?>-main px-2 py-1">
                                        <?php echo $status_label; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="pedido_detalhe.php?id=<?php echo $pedido['id']; ?>" class="btn btn-sm btn-primary-600 radius-8">
                                        <iconify-icon icon="solar:eye-bold"></iconify-icon> Ver Detalhes
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>