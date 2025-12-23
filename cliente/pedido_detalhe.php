<?php
include 'includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';

verificar_login_cliente();

$cliente_id = $_SESSION['cliente_id'];
$pedido_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$pedido_id) {
    header('Location: pedidos.php');
    exit;
}

// Buscar pedido e verificar se pertence ao cliente
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ? AND cliente_id = ?");
$stmt->execute([$pedido_id, $cliente_id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    header('Location: pedidos.php');
    exit;
}

// Buscar itens
$stmt_itens = $pdo->prepare("SELECT * FROM pedido_itens WHERE pedido_id = ?");
$stmt_itens->execute([$pedido_id]);
$itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Detalhes do Pedido #<?php echo $pedido['codigo_pedido']; ?></h4>
        <a href="pedidos.php" class="btn btn-outline-secondary btn-sm">Voltar</a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    Itens do Pedido
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($itens as $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo $item['quantidade']; ?>x</strong> <?php echo htmlspecialchars($item['produto_nome']); ?>
                                <?php if ($item['observacoes']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($item['observacoes']); ?></small>
                                <?php endif; ?>
                            </div>
                            <span>R$ <?php echo number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.'); ?></span>
                        </li>
                    <?php endforeach; ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center fw-bold">
                        <span>Total</span>
                        <span>R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    Status e Pagamento
                </div>
                <div class="card-body">
                    <p><strong>Status:</strong> 
                        <span class="badge bg-<?php 
                            echo $pedido['status'] == 'pendente' ? 'warning' : 
                                ($pedido['status'] == 'concluido' ? 'success' : 
                                ($pedido['status'] == 'cancelado' ? 'danger' : 'info')); 
                        ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $pedido['status'])); ?>
                        </span>
                    </p>
                    <p><strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></p>
                    <p><strong>Pagamento:</strong> 
                        <?php 
                            // Buscar nome da forma de pagamento se necessário, ou usar ID/Tipo
                            // Simplificando:
                            echo "ID " . $pedido['forma_pagamento_id']; 
                        ?>
                    </p>

                    <?php if ($pedido['pagamento_online'] && $pedido['status'] == 'pendente' && $pedido['qr_code_base64']): ?>
                        <div class="text-center mt-3">
                            <h6>Pague com PIX:</h6>
                            <img src="data:image/png;base64,<?php echo $pedido['qr_code_base64']; ?>" class="img-fluid" style="max-width: 200px;">
                            <div class="mt-2">
                                <small class="text-muted">Escaneie o QR Code acima para pagar.</small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Entrega
                </div>
                <div class="card-body">
                    <p><strong>Tipo:</strong> <?php echo ucfirst($pedido['tipo_entrega']); ?></p>
                    <?php if ($pedido['tipo_entrega'] == 'delivery'): ?>
                        <p><strong>Endereço:</strong> <?php echo htmlspecialchars($pedido['cliente_endereco']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>