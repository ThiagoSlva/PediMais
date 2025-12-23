<?php
include 'includes/header.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>window.location.href = 'pedidos.php';</script>";
    exit;
}

$pedido_id = (int)$_GET['id'];

// Processar atualização de status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'atualizar_status') {
    $novo_status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
    $stmt->execute([$novo_status, $pedido_id]);
    
    // Opcional: Adicionar log de histórico se houver tabela para isso
    
    $msg = 'Status atualizado com sucesso!';
    $msg_tipo = 'success';
}

// Buscar dados do pedido
$stmt = $pdo->prepare("SELECT p.*, f.nome as forma_pagamento_nome 
                       FROM pedidos p 
                       LEFT JOIN formas_pagamento f ON p.forma_pagamento_id = f.id 
                       WHERE p.id = ?");
$stmt->execute([$pedido_id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    echo "<div class='alert alert-danger'>Pedido não encontrado.</div>";
    include 'includes/footer.php';
    exit;
}

// Buscar itens do pedido
$stmt = $pdo->prepare("SELECT * FROM pedido_itens WHERE pedido_id = ?");
$stmt->execute([$pedido_id]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper para buscar opções do item
function get_opcoes_item($pdo, $item_id) {
    $stmt = $pdo->prepare("SELECT pio.*, o.nome as opcao_nome 
                           FROM pedido_item_opcoes pio 
                           LEFT JOIN opcoes o ON pio.opcao_id = o.id 
                           WHERE pio.pedido_item_id = ?");
    $stmt->execute([$item_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Definir taxa de entrega como 0 se não existir
$taxa_entrega = isset($pedido['taxa_entrega']) ? $pedido['taxa_entrega'] : 0;

?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Detalhes do Pedido #<?php echo $pedido['id']; ?></h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">
                <a href="pedidos.php" class="hover-text-primary">Pedidos</a>
            </li>
            <li>-</li>
            <li class="fw-medium">Detalhes</li>
        </ul>
    </div>

    <?php if (isset($msg)): ?>
    <div class="alert alert-<?php echo $msg_tipo; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row gy-4">
        <!-- Coluna Esquerda: Itens e Totais -->
        <div class="col-lg-8">
            <div class="card h-100 p-0 radius-12">
                <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center justify-content-between">
                    <h6 class="text-lg fw-semibold mb-0">Itens do Pedido</h6>
                    <span class="badge bg-primary-100 text-primary-600 text-sm px-12 py-6 radius-4">
                        <?php echo count($itens); ?> Itens
                    </span>
                </div>
                <div class="card-body p-24">
                    <div class="table-responsive">
                        <table class="table bordered-table mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Produto</th>
                                    <th scope="col" class="text-center">Qtd</th>
                                    <th scope="col" class="text-end">Preço Unit.</th>
                                    <th scope="col" class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($itens as $item): 
                                    $opcoes = get_opcoes_item($pdo, $item['id']);
                                    $total_item = $item['preco_unitario'] * $item['quantidade'];
                                    // Somar opções ao total do item se necessário (depende da lógica, aqui assumo que preço_unitario já inclui base, e opções somam)
                                    $total_opcoes = 0;
                                    foreach ($opcoes as $opt) {
                                        $total_opcoes += $opt['preco_adicional'] * $opt['quantidade'];
                                    }
                                    $total_item += $total_opcoes * $item['quantidade']; // Se opções multiplicam pela qtd do item
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <!-- Imagem opcional -->
                                            <div class="flex-grow-1">
                                                <h6 class="text-md mb-0 fw-medium"><?php echo htmlspecialchars($item['produto_nome']); ?></h6>
                                                <?php if (!empty($item['observacoes'])): ?>
                                                    <p class="text-sm text-secondary-light mb-0">Obs: <?php echo htmlspecialchars($item['observacoes']); ?></p>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($opcoes)): ?>
                                                    <ul class="list-unstyled mb-0 mt-1">
                                                        <?php foreach ($opcoes as $opt): ?>
                                                            <li class="text-xs text-secondary-light">
                                                                + <?php echo htmlspecialchars($opt['opcao_nome']); ?> 
                                                                (R$ <?php echo number_format($opt['preco_adicional'], 2, ',', '.'); ?>)
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center"><?php echo $item['quantidade']; ?></td>
                                    <td class="text-end">R$ <?php echo number_format($item['preco_unitario'], 2, ',', '.'); ?></td>
                                    <td class="text-end fw-bold">R$ <?php echo number_format($total_item, 2, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end fw-medium">Subtotal:</td>
                                    <td class="text-end fw-bold">R$ <?php echo number_format($pedido['valor_total'] - $taxa_entrega, 2, ',', '.'); ?></td>
                                </tr>
                                <?php if ($taxa_entrega > 0): ?>
                                <tr>
                                    <td colspan="3" class="text-end fw-medium">Taxa de Entrega:</td>
                                    <td class="text-end fw-bold">R$ <?php echo number_format($taxa_entrega, 2, ',', '.'); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="3" class="text-end fw-bold text-lg">Total:</td>
                                    <td class="text-end fw-bold text-lg text-primary-600">R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coluna Direita: Info Cliente e Status -->
        <div class="col-lg-4">
            <!-- Status e Ações -->
            <div class="card p-0 radius-12 mb-24">
                <div class="card-header border-bottom bg-base py-16 px-24">
                    <h6 class="text-lg fw-semibold mb-0">Status do Pedido</h6>
                </div>
                <div class="card-body p-24">
                    <form method="POST">
                        <input type="hidden" name="acao" value="atualizar_status">
                        <div class="mb-3">
                            <label class="form-label fw-medium">Situação Atual</label>
                            <select name="status" class="form-select">
                                <option value="pendente" <?php echo $pedido['status'] == 'pendente' ? 'selected' : ''; ?>>⏳ Pendente</option>
                                <option value="em_andamento" <?php echo $pedido['status'] == 'em_andamento' ? 'selected' : ''; ?>>👨‍🍳 Em Preparo</option>
                                <option value="pronto" <?php echo $pedido['status'] == 'pronto' ? 'selected' : ''; ?>>✅ Pronto</option>
                                <option value="saiu_entrega" <?php echo $pedido['status'] == 'saiu_entrega' ? 'selected' : ''; ?>>🏍️ Saiu para Entrega</option>
                                <option value="concluido" <?php echo $pedido['status'] == 'concluido' ? 'selected' : ''; ?>>🎉 Entregue</option>
                                <option value="finalizado" <?php echo $pedido['status'] == 'finalizado' ? 'selected' : ''; ?>>📦 Finalizado/Arquivado</option>
                                <option value="cancelado" <?php echo $pedido['status'] == 'cancelado' ? 'selected' : ''; ?>>❌ Cancelado</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mb-3">Atualizar Status</button>
                    </form>
                    
                    <a href="pedido_imprimir.php?id=<?php echo $pedido['id']; ?>" target="_blank" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2">
                        <iconify-icon icon="solar:printer-bold"></iconify-icon>
                        Imprimir Pedido
                    </a>
                </div>
            </div>

            <!-- Informações do Cliente -->
            <div class="card p-0 radius-12 mb-24">
                <div class="card-header border-bottom bg-base py-16 px-24">
                    <h6 class="text-lg fw-semibold mb-0">Cliente</h6>
                </div>
                <div class="card-body p-24">
                    <div class="d-flex align-items-center gap-3 mb-20">
                        <div class="w-50-px h-50-px bg-primary-50 rounded-circle d-flex justify-content-center align-items-center">
                            <iconify-icon icon="solar:user-bold" class="text-primary-600 text-2xl"></iconify-icon>
                        </div>
                        <div>
                            <h6 class="mb-0"><?php echo htmlspecialchars($pedido['cliente_nome']); ?></h6>
                            <span class="text-sm text-secondary-light">Cliente</span>
                        </div>
                    </div>
                    
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-3">
                        <li class="d-flex align-items-start gap-2">
                            <iconify-icon icon="solar:phone-bold" class="text-primary-600 text-lg mt-1"></iconify-icon>
                            <div>
                                <span class="d-block fw-medium text-secondary-light text-sm">Telefone</span>
                                <a href="https://wa.me/55<?php echo preg_replace('/[^0-9]/', '', $pedido['cliente_telefone']); ?>" target="_blank" class="text-primary-600 hover-text-primary-700">
                                    <?php echo htmlspecialchars($pedido['cliente_telefone']); ?>
                                </a>
                            </div>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <iconify-icon icon="solar:map-point-bold" class="text-primary-600 text-lg mt-1"></iconify-icon>
                            <div>
                                <span class="d-block fw-medium text-secondary-light text-sm">Endereço de Entrega</span>
                                <span class="d-block text-heading">
                                    <?php 
                                    if (isset($pedido['cliente_endereco'])) {
                                        echo htmlspecialchars($pedido['cliente_endereco']);
                                    } else {
                                        // Fallback para campos antigos se existirem (improvável dado o erro anterior, mas seguro)
                                        $endereco = [];
                                        if (isset($pedido['endereco_rua'])) $endereco[] = $pedido['endereco_rua'];
                                        if (isset($pedido['endereco_numero'])) $endereco[] = $pedido['endereco_numero'];
                                        if (isset($pedido['endereco_bairro'])) $endereco[] = $pedido['endereco_bairro'];
                                        echo htmlspecialchars(implode(', ', $endereco));
                                    }
                                    ?>
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Informações de Pagamento -->
            <div class="card p-0 radius-12">
                <div class="card-header border-bottom bg-base py-16 px-24">
                    <h6 class="text-lg fw-semibold mb-0">Pagamento</h6>
                </div>
                <div class="card-body p-24">
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-3">
                        <li class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary-light">Método:</span>
                            <span class="fw-medium text-heading"><?php echo htmlspecialchars($pedido['forma_pagamento_nome']); ?></span>
                        </li>
                        <?php if ($pedido['troco_para']): ?>
                        <li class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary-light">Troco para:</span>
                            <span class="fw-medium text-heading">R$ <?php echo number_format($pedido['troco_para'], 2, ',', '.'); ?></span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary-light">Troco a devolver:</span>
                            <span class="fw-bold text-success-main">R$ <?php echo number_format($pedido['troco_para'] - $pedido['valor_total'], 2, ',', '.'); ?></span>
                        </li>
                        <?php endif; ?>
                        
                        <?php if ($pedido['observacoes']): ?>
                        <li class="mt-2">
                            <span class="d-block text-secondary-light mb-1">Observações:</span>
                            <div class="bg-neutral-50 p-12 radius-8 text-sm">
                                <?php echo nl2br(htmlspecialchars($pedido['observacoes'])); ?>
                            </div>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>