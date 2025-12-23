<?php
include 'includes/header.php';

// Processar atualização de status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'atualizar_status') {
    $pedido_id = (int)$_POST['pedido_id'];
    $novo_status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
    $stmt->execute([$novo_status, $pedido_id]);
    
    $msg = 'Status atualizado com sucesso!';
    $msg_tipo = 'success';
}

// Buscar pedidos pendentes e em andamento
$stmt = $pdo->prepare("SELECT p.*, f.nome as forma_pagamento_nome 
                       FROM pedidos p 
                       LEFT JOIN formas_pagamento f ON p.forma_pagamento_id = f.id 
                       WHERE p.status IN ('pendente', 'em_andamento') 
                       ORDER BY p.data_pedido ASC");
$stmt->execute();
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper para buscar itens
function get_itens_pedido($pdo, $pedido_id) {
    $stmt = $pdo->prepare("SELECT * FROM pedido_itens WHERE pedido_id = ?");
    $stmt->execute([$pedido_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_opcoes_item($pdo, $item_id) {
    $stmt = $pdo->prepare("SELECT pio.*, o.nome as opcao_nome 
                           FROM pedido_item_opcoes pio 
                           LEFT JOIN opcoes o ON pio.opcao_id = o.id 
                           WHERE pio.pedido_item_id = ?");
    $stmt->execute([$item_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Cozinha - Pedidos em Aberto</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Cozinha</li>
        </ul>
    </div>
    
    <div class="alert alert-info d-flex align-items-center" role="alert">
        <iconify-icon icon="solar:info-circle-bold" class="text-xl me-2"></iconify-icon>
        <div>Esta página atualiza automaticamente a cada 30 segundos.</div>
    </div>

    <?php if (isset($msg)): ?>
    <div class="alert alert-<?php echo $msg_tipo; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row gy-4">
        <?php if (empty($pedidos)): ?>
            <div class="col-12">
                <div class="card p-4 text-center">
                    <div class="py-5">
                        <iconify-icon icon="solar:chef-hat-bold-duotone" class="text-6xl text-secondary-light mb-3"></iconify-icon>
                        <h5 class="text-secondary-light">Nenhum pedido na fila da cozinha no momento.</h5>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($pedidos as $pedido): 
                $itens = get_itens_pedido($pdo, $pedido['id']);
                $card_border = $pedido['status'] == 'pendente' ? 'border-warning' : 'border-info';
                $badge_class = $pedido['status'] == 'pendente' ? 'bg-warning-focus text-warning-main' : 'bg-info-focus text-info-main';
                $status_label = $pedido['status'] == 'pendente' ? 'Pendente' : 'Em Preparo';
            ?>
            <div class="col-xxl-3 col-xl-4 col-md-6">
                <div class="card h-100 radius-12 border-top border-4 <?php echo $card_border; ?>">
                    <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-lg fw-bold mb-0">#<?php echo $pedido['id']; ?></h6>
                            <span class="text-sm text-secondary-light"><?php echo date('H:i', strtotime($pedido['data_pedido'])); ?></span>
                        </div>
                        <span class="badge <?php echo $badge_class; ?> px-12 py-6 radius-4 text-sm fw-medium">
                            <?php echo $status_label; ?>
                        </span>
                    </div>
                    <div class="card-body p-24">
                        <div class="mb-3">
                            <span class="fw-medium text-heading">Cliente:</span>
                            <span class="text-secondary-light"><?php echo htmlspecialchars($pedido['cliente_nome']); ?></span>
                        </div>
                        
                        <h6 class="text-md fw-semibold mb-3 border-bottom pb-2">Itens:</h6>
                        <ul class="list-unstyled mb-0 d-flex flex-column gap-3">
                            <?php foreach ($itens as $item): 
                                $opcoes = get_opcoes_item($pdo, $item['id']);
                            ?>
                            <li class="d-flex align-items-start gap-2">
                                <span class="badge bg-primary-100 text-primary-600 radius-circle w-24-px h-24-px d-flex justify-content-center align-items-center flex-shrink-0">
                                    <?php echo $item['quantidade']; ?>
                                </span>
                                <div>
                                    <span class="fw-medium text-heading d-block"><?php echo htmlspecialchars($item['produto_nome']); ?></span>
                                    <?php if (!empty($item['observacoes'])): ?>
                                        <div class="text-xs text-danger mt-1 fw-bold">
                                            <iconify-icon icon="solar:info-circle-bold" class="align-middle"></iconify-icon> 
                                            Obs: <?php echo htmlspecialchars($item['observacoes']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($opcoes)): ?>
                                        <ul class="list-unstyled mb-0 mt-1 ps-2 border-start border-2">
                                            <?php foreach ($opcoes as $opt): ?>
                                                <li class="text-xs text-secondary-light">
                                                    + <?php echo htmlspecialchars($opt['opcao_nome']); ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <?php if (!empty($pedido['observacoes'])): ?>
                        <div class="mt-3 p-2 bg-danger-50 radius-8 border border-danger-100">
                            <span class="text-danger-main fw-bold text-sm d-block mb-1">Observações do Pedido:</span>
                            <p class="text-danger-main text-sm mb-0"><?php echo nl2br(htmlspecialchars($pedido['observacoes'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-base py-16 px-24 border-top">
                        <form method="POST" class="d-grid gap-2">
                            <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
                            <input type="hidden" name="acao" value="atualizar_status">
                            
                            <?php if ($pedido['status'] == 'pendente'): ?>
                                <button type="submit" name="status" value="em_andamento" class="btn btn-primary d-flex align-items-center justify-content-center gap-2">
                                    <iconify-icon icon="solar:fire-bold"></iconify-icon>
                                    Iniciar Preparo
                                </button>
                            <?php else: ?>
                                <button type="submit" name="status" value="pronto" class="btn btn-success d-flex align-items-center justify-content-center gap-2">
                                    <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                                    Marcar como Pronto
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    // Auto-refresh a cada 30 segundos
    setTimeout(function(){
        window.location.reload();
    }, 30000);
</script>

<?php include 'includes/footer.php'; ?>