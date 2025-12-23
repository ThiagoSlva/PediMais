<?php
include 'includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';

verificar_login_cliente();

$cliente_id = $_SESSION['cliente_id'];

// Buscar pedidos do cliente
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE cliente_id = ? ORDER BY data_pedido DESC");
$stmt->execute([$cliente_id]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php'; // Assumindo que existe ou será criado/ajustado
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Meus Pedidos</h4>
        <a href="index.php" class="btn btn-outline-primary btn-sm">Voltar</a>
    </div>

    <?php if (count($pedidos) > 0): ?>
        <div class="list-group">
            <?php foreach ($pedidos as $pedido): ?>
                <a href="pedido_detalhe.php?id=<?php echo $pedido['id']; ?>" class="list-group-item list-group-item-action flex-column align-items-start">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">Pedido #<?php echo $pedido['codigo_pedido']; ?></h5>
                        <small><?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></small>
                    </div>
                    <p class="mb-1">
                        Status: 
                        <span class="badge bg-<?php 
                            echo $pedido['status'] == 'pendente' ? 'warning' : 
                                ($pedido['status'] == 'concluido' ? 'success' : 
                                ($pedido['status'] == 'cancelado' ? 'danger' : 'info')); 
                        ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $pedido['status'])); ?>
                        </span>
                    </p>
                    <small>Total: R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></small>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">Você ainda não realizou nenhum pedido.</div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>