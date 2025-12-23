<div class="card mb-16 border border-secondary-100 shadow-none radius-8 cursor-move" data-id="<?php echo $pedido['id']; ?>">
    <div class="card-body p-12">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="text-md fw-semibold mb-0">#<?php echo $pedido['codigo_pedido']; ?></h6>
            <span class="text-xs text-secondary-light"><?php echo date('H:i', strtotime($pedido['data_pedido'])); ?></span>
        </div>
        <p class="text-sm text-secondary-light mb-1 fw-medium"><?php echo htmlspecialchars($pedido['cliente_nome']); ?></p>
        <p class="text-xs text-secondary-light mb-2 text-truncate"><?php echo htmlspecialchars($pedido['cliente_endereco']); ?></p>
        
        <div class="d-flex justify-content-between align-items-center mt-2">
            <span class="badge bg-primary-50 text-primary-600 radius-4 px-6 py-2 text-xs">
                <?php echo ucfirst($pedido['tipo_entrega']); ?>
            </span>
            <span class="fw-bold text-sm text-primary-600">R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></span>
        </div>
        
        <div class="mt-2 pt-2 border-top border-secondary-100 d-flex justify-content-end">
            <button type="button" class="btn btn-sm btn-outline-primary radius-4 px-8 py-2 text-xs" onclick="window.location.href='pedidos.php?id=<?php echo $pedido['id']; ?>'">
                Ver Detalhes
            </button>
        </div>
    </div>
</div>
