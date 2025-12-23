<?php
require_once '../includes/config.php';
require_once 'includes/auth.php';

verificar_login_cliente();
$cliente = get_cliente_atual();

// Buscar últimos pedidos
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE cliente_id = ? ORDER BY data_pedido DESC LIMIT 10");
$stmt->execute([$cliente['id']]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Dashboard - Área do Cliente';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-primary text-white">
                <div class="card-body p-4">
                    <h2 class="fw-bold">Olá, <?php echo htmlspecialchars($cliente['nome']); ?>! 👋</h2>
                    <p class="mb-0">Bem-vindo de volta à sua área do cliente.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Estatísticas Rápidas -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title text-muted mb-3">Total de Pedidos</h5>
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-primary-subtle p-3 rounded">
                            <i class="fa-solid fa-receipt fa-2x text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h2 class="mb-0 fw-bold"><?php echo count($pedidos); ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title text-muted mb-3">Novo Pedido</h5>
                    <p class="card-text">Bateu a fome? Faça um novo pedido agora mesmo!</p>
                    <a href="../index.php" class="btn btn-outline-primary w-100">
                        <i class="fa-solid fa-utensils me-2"></i> Ver Cardápio
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title text-muted mb-3">Meus Dados</h5>
                    <p class="card-text">Mantenha seus dados atualizados para facilitar a entrega.</p>
                    <a href="perfil.php" class="btn btn-outline-secondary w-100">
                        <i class="fa-solid fa-user-pen me-2"></i> Editar Perfil
                    </a>
                </div>
            </div>
        </div>

        <!-- Lista de Pedidos -->
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i> Últimos Pedidos</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($pedidos)): ?>
                        <div class="text-center py-5">
                            <i class="fa-solid fa-basket-shopping fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Você ainda não realizou nenhum pedido.</p>
                            <a href="../index.php" class="btn btn-primary">Fazer meu primeiro pedido</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="ps-4">#</th>
                                        <th scope="col">Data</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Total</th>
                                        <th scope="col" class="text-end pe-4">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pedidos as $pedido): 
                                        $status_class = 'bg-secondary';
                                        $status_icon = 'fa-circle';
                                        switch($pedido['status']) {
                                            case 'pendente': $status_class = 'bg-warning text-dark'; $status_icon = 'fa-hourglass-half'; break;
                                            case 'em_andamento': $status_class = 'bg-info text-dark'; $status_icon = 'fa-fire-burner'; break;
                                            case 'pronto': $status_class = 'bg-primary'; $status_icon = 'fa-check'; break;
                                            case 'saiu_entrega': $status_class = 'bg-info'; $status_icon = 'fa-motorcycle'; break;
                                            case 'concluido': $status_class = 'bg-success'; $status_icon = 'fa-check-double'; break;
                                            case 'cancelado': $status_class = 'bg-danger'; $status_icon = 'fa-xmark'; break;
                                        }
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-bold">#<?php echo $pedido['id']; ?></td>
                                        <td>
                                            <div><?php echo date('d/m/Y', strtotime($pedido['data_pedido'])); ?></div>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($pedido['data_pedido'])); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill <?php echo $status_class; ?> px-3 py-2">
                                                <i class="fa-solid <?php echo $status_icon; ?> me-1"></i>
                                                <?php echo ucfirst(str_replace('_', ' ', $pedido['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold text-primary">
                                            R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="pedido_detalhe.php?id=<?php echo $pedido['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                Detalhes
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>