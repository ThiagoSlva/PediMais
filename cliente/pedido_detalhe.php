<?php
require_once '../includes/config.php';
require_once 'includes/auth.php';

verificar_login_cliente();

$cliente_id = $_SESSION['cliente_id'];
$pedido_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$pedido_id) {
    header('Location: pedidos.php');
    exit;
}

// Get order and verify ownership
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ? AND cliente_id = ?");
$stmt->execute([$pedido_id, $cliente_id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    header('Location: pedidos.php');
    exit;
}

// Get order items with product images
$stmt = $pdo->prepare("
    SELECT pi.*, p.imagem_path as produto_imagem 
    FROM pedido_itens pi
    LEFT JOIN produtos p ON pi.produto_id = p.id
    WHERE pi.pedido_id = ?
");
$stmt->execute([$pedido_id]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get payment method name
$forma_pagamento = 'Não informado';
if ($pedido['forma_pagamento_id']) {
    $stmt = $pdo->prepare("SELECT nome FROM formas_pagamento WHERE id = ?");
    $stmt->execute([$pedido['forma_pagamento_id']]);
    $fp = $stmt->fetch();
    if ($fp) {
        $forma_pagamento = $fp['nome'];
    }
}

// Order timeline
$timeline = [
    ['status' => 'pendente', 'label' => 'Pedido Recebido', 'icon' => 'fa-receipt', 'completed' => true],
    ['status' => 'em_preparo', 'label' => 'Em Preparo', 'icon' => 'fa-fire', 'completed' => in_array($pedido['status'], ['em_preparo', 'preparando', 'saiu_entrega', 'entregue', 'concluido'])],
    ['status' => 'saiu_entrega', 'label' => $pedido['tipo_entrega'] === 'delivery' ? 'Saiu para Entrega' : 'Pronto para Retirada', 'icon' => $pedido['tipo_entrega'] === 'delivery' ? 'fa-motorcycle' : 'fa-store', 'completed' => in_array($pedido['status'], ['saiu_entrega', 'entregue', 'concluido'])],
    ['status' => 'entregue', 'label' => $pedido['tipo_entrega'] === 'delivery' ? 'Entregue' : 'Retirado', 'icon' => 'fa-check-circle', 'completed' => in_array($pedido['status'], ['entregue', 'concluido'])]
];

// Check if cancelled
$is_cancelled = $pedido['status'] === 'cancelado';

// Calculate subtotal
$subtotal = 0;
foreach ($itens as $item) {
    $subtotal += $item['preco_unitario'] * $item['quantidade'];
}
$taxa_entrega = $pedido['taxa_entrega'] ?? 0;
$desconto = $pedido['desconto'] ?? 0;

$page_title = 'Pedido #' . $pedido['codigo_pedido'];
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 fade-in">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 0.875rem;">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color: var(--primary);">Início</a></li>
                <li class="breadcrumb-item"><a href="pedidos.php" class="text-decoration-none" style="color: var(--primary);">Pedidos</a></li>
                <li class="breadcrumb-item active" style="color: var(--gray-500);">#<?php echo htmlspecialchars($pedido['codigo_pedido']); ?></li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-3">
            <h4 class="mb-0" style="color: var(--gray-900);">
                Pedido #<?php echo htmlspecialchars($pedido['codigo_pedido']); ?>
            </h4>
            <span class="status-badge <?php echo $pedido['status']; ?>" style="font-size: 0.875rem;">
                <?php echo ucfirst(str_replace('_', ' ', $pedido['status'])); ?>
            </span>
        </div>
    </div>
    
    <div class="d-flex gap-2">
        <a href="pedidos.php" class="btn btn-glass">
            <i class="fa-solid fa-arrow-left me-1"></i>Voltar
        </a>
        <?php if ($pedido['status'] === 'entregue' || $pedido['status'] === 'concluido'): ?>
            <button class="btn btn-premium btn-primary-gradient" onclick="repetirPedido(<?php echo $pedido_id; ?>)">
                <i class="fa-solid fa-repeat me-1"></i>Repetir Pedido
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column -->
    <div class="col-lg-8">
        <!-- Order Timeline -->
        <?php if (!$is_cancelled): ?>
        <div class="card-premium mb-4 fade-in">
            <div class="card-header">
                <i class="fa-solid fa-route me-2 text-primary"></i>Acompanhamento do Pedido
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between position-relative">
                    <!-- Progress line -->
                    <div style="position: absolute; top: 20px; left: 10%; width: 80%; height: 3px; background: var(--gray-200); z-index: 0;">
                        <?php 
                        $progress = 0;
                        if (in_array($pedido['status'], ['em_preparo', 'preparando'])) $progress = 33;
                        elseif ($pedido['status'] === 'saiu_entrega') $progress = 66;
                        elseif (in_array($pedido['status'], ['entregue', 'concluido'])) $progress = 100;
                        ?>
                        <div style="height: 100%; width: <?php echo $progress; ?>%; background: var(--primary-gradient); transition: width 0.5s ease;"></div>
                    </div>
                    
                    <?php foreach ($timeline as $step): ?>
                        <div class="text-center" style="z-index: 1;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; margin: 0 auto 0.5rem; display: flex; align-items: center; justify-content: center; 
                                        <?php echo $step['completed'] ? 'background: var(--primary-gradient); color: white;' : 'background: var(--gray-200); color: var(--gray-500);'; ?>">
                                <i class="fa-solid <?php echo $step['icon']; ?>"></i>
                            </div>
                            <small style="color: <?php echo $step['completed'] ? 'var(--gray-900)' : 'var(--gray-500)'; ?>; font-size: 0.75rem; display: block; max-width: 80px;">
                                <?php echo $step['label']; ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="alert d-flex align-items-center gap-3 mb-4 fade-in" style="background: var(--danger-light); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: var(--radius-md);">
            <i class="fa-solid fa-times-circle fa-lg" style="color: var(--danger);"></i>
            <div>
                <strong style="color: #991B1B;">Pedido Cancelado</strong>
                <p class="mb-0" style="font-size: 0.875rem; color: #991B1B;">Este pedido foi cancelado.</p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Order Items -->
        <div class="card-premium fade-in" style="animation-delay: 0.1s;">
            <div class="card-header">
                <i class="fa-solid fa-basket-shopping me-2 text-primary"></i>Itens do Pedido
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($itens as $item): ?>
                        <div class="list-group-item p-3" style="border-color: var(--gray-100);">
                            <div class="d-flex gap-3">
                                <!-- Product Image -->
                                <div style="width: 60px; height: 60px; border-radius: var(--radius-md); overflow: hidden; flex-shrink: 0; background: var(--gray-100);">
                                    <?php if (!empty($item['produto_imagem']) && file_exists('../' . $item['produto_imagem'])): ?>
                                        <img src="../<?php echo htmlspecialchars($item['produto_imagem']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['produto_nome']); ?>"
                                             style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-center h-100">
                                            <i class="fa-solid fa-utensils" style="color: var(--gray-400);"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Item Details -->
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1" style="color: var(--gray-900);">
                                                <span class="badge bg-primary me-1" style="font-size: 0.7rem;"><?php echo $item['quantidade']; ?>x</span>
                                                <?php echo htmlspecialchars($item['produto_nome']); ?>
                                            </h6>
                                            <?php if (!empty($item['observacoes'])): ?>
                                                <small style="color: var(--gray-500);">
                                                    <i class="fa-solid fa-comment me-1"></i><?php echo htmlspecialchars($item['observacoes']); ?>
                                                </small>
                                            <?php endif; ?>
                                            <?php if (!empty($item['adicionais'])): ?>
                                                <small class="d-block" style="color: var(--gray-500);">
                                                    <i class="fa-solid fa-plus-circle me-1"></i><?php echo htmlspecialchars($item['adicionais']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <span class="fw-semibold" style="color: var(--gray-900);">
                                            R$ <?php echo number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Order Summary -->
                <div class="p-3" style="background: var(--gray-50);">
                    <div class="d-flex justify-content-between mb-2" style="font-size: 0.875rem;">
                        <span style="color: var(--gray-600);">Subtotal</span>
                        <span style="color: var(--gray-800);">R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></span>
                    </div>
                    <?php if ($taxa_entrega > 0): ?>
                    <div class="d-flex justify-content-between mb-2" style="font-size: 0.875rem;">
                        <span style="color: var(--gray-600);">Taxa de Entrega</span>
                        <span style="color: var(--gray-800);">R$ <?php echo number_format($taxa_entrega, 2, ',', '.'); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($desconto > 0): ?>
                    <div class="d-flex justify-content-between mb-2" style="font-size: 0.875rem;">
                        <span style="color: var(--success);">Desconto</span>
                        <span style="color: var(--success);">- R$ <?php echo number_format($desconto, 2, ',', '.'); ?></span>
                    </div>
                    <?php endif; ?>
                    <hr style="border-color: var(--gray-200);">
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold" style="color: var(--gray-900);">Total</span>
                        <span class="fw-bold fs-5" style="color: var(--primary);">R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Column -->
    <div class="col-lg-4">
        <!-- Order Info -->
        <div class="card-premium mb-4 fade-in" style="animation-delay: 0.15s;">
            <div class="card-header">
                <i class="fa-solid fa-info-circle me-2 text-info"></i>Informações
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small style="color: var(--gray-500);">Data do Pedido</small>
                    <p class="mb-0 fw-medium" style="color: var(--gray-800);">
                        <i class="fa-regular fa-calendar me-2"></i>
                        <?php echo date('d/m/Y \à\s H:i', strtotime($pedido['data_pedido'])); ?>
                    </p>
                </div>
                
                <div class="mb-3">
                    <small style="color: var(--gray-500);">Forma de Pagamento</small>
                    <p class="mb-0 fw-medium" style="color: var(--gray-800);">
                        <i class="fa-solid fa-credit-card me-2"></i>
                        <?php echo htmlspecialchars($forma_pagamento); ?>
                        <?php if (!empty($pedido['troco_para']) && $pedido['troco_para'] > 0): ?>
                            <br><small style="color: var(--gray-500);">Troco para R$ <?php echo number_format($pedido['troco_para'], 2, ',', '.'); ?></small>
                        <?php endif; ?>
                    </p>
                </div>
                
                <div>
                    <small style="color: var(--gray-500);">Tipo de Entrega</small>
                    <p class="mb-0 fw-medium" style="color: var(--gray-800);">
                        <i class="fa-solid <?php echo $pedido['tipo_entrega'] === 'delivery' ? 'fa-motorcycle' : 'fa-store'; ?> me-2"></i>
                        <?php echo $pedido['tipo_entrega'] === 'delivery' ? 'Delivery' : 'Retirada no Local'; ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Delivery Address -->
        <?php if ($pedido['tipo_entrega'] === 'delivery' && !empty($pedido['cliente_endereco'])): ?>
        <div class="card-premium mb-4 fade-in" style="animation-delay: 0.2s;">
            <div class="card-header">
                <i class="fa-solid fa-location-dot me-2 text-danger"></i>Endereço de Entrega
            </div>
            <div class="card-body">
                <p class="mb-0" style="color: var(--gray-700); line-height: 1.6;">
                    <?php echo nl2br(htmlspecialchars($pedido['cliente_endereco'])); ?>
                </p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- PIX Payment -->
        <?php if (!empty($pedido['pagamento_online']) && $pedido['status'] === 'pendente' && !empty($pedido['qr_code_base64'])): ?>
        <div class="card-premium mb-4 fade-in" style="animation-delay: 0.25s;">
            <div class="card-header" style="background: linear-gradient(135deg, #00C2A8, #00D9B5); color: white;">
                <i class="fa-solid fa-qrcode me-2"></i>Pagamento PIX
            </div>
            <div class="card-body text-center">
                <p style="color: var(--gray-600); font-size: 0.875rem;">Escaneie o QR Code para pagar</p>
                <img src="data:image/png;base64,<?php echo $pedido['qr_code_base64']; ?>" 
                     alt="QR Code PIX"
                     class="img-fluid mb-3" style="max-width: 200px; border-radius: var(--radius-md);">
                <?php if (!empty($pedido['pix_copia_cola'])): ?>
                    <div class="input-group">
                        <input type="text" class="form-control form-control-sm" id="pixCode" 
                               value="<?php echo htmlspecialchars($pedido['pix_copia_cola']); ?>" readonly>
                        <button class="btn btn-outline-primary btn-sm" onclick="copiarPix()">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Actions -->
        <div class="card-premium fade-in" style="animation-delay: 0.3s;">
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php if ($pedido['status'] === 'entregue' || $pedido['status'] === 'concluido'): ?>
                        <a href="../avaliar_pedido.php?token=<?php echo md5($pedido['id'] . $pedido['cliente_id']); ?>&pedido=<?php echo $pedido['id']; ?>" 
                           class="btn btn-premium" style="background: var(--warning); color: white;">
                            <i class="fa-solid fa-star me-2"></i>Avaliar Pedido
                        </a>
                    <?php endif; ?>
                    
                    <button class="btn btn-outline-premium" onclick="repetirPedido(<?php echo $pedido_id; ?>)">
                        <i class="fa-solid fa-repeat me-2"></i>Repetir Pedido
                    </button>
                    
                    <a href="pedidos.php" class="btn btn-glass">
                        <i class="fa-solid fa-list me-2"></i>Ver Todos os Pedidos
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copiarPix() {
    const input = document.getElementById('pixCode');
    input.select();
    document.execCommand('copy');
    showToast('Código PIX copiado!', 'success');
}

function repetirPedido(pedidoId) {
    Swal.fire({
        title: 'Repetir este pedido?',
        text: 'Os mesmos itens serão adicionados ao seu carrinho.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#9C27B0',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Sim, repetir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Redirect to menu with items (implementation depends on cart system)
            fetch('api/repetir_pedido.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ pedido_id: pedidoId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Itens adicionados ao carrinho!', 'success');
                    setTimeout(() => window.location.href = '../index.php', 1000);
                } else {
                    showToast(data.message || 'Erro ao adicionar itens', 'error');
                }
            })
            .catch(error => {
                // Fallback: redirect to menu
                window.location.href = '../index.php';
            });
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>