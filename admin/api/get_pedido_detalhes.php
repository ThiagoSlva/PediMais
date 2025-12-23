<?php
require_once '../../includes/config.php';
require_once '../includes/auth.php';

verificar_login();

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->execute([$id]);
$pedido = $stmt->fetch();

$stmt_itens = $pdo->prepare("SELECT * FROM pedido_itens WHERE pedido_id = ?");
$stmt_itens->execute([$id]);
$itens = $stmt_itens->fetchAll();

?>
<div style="margin-bottom: 20px;">
    <p><strong>Cliente:</strong> <?php echo htmlspecialchars($pedido['cliente_nome']); ?></p>
    <p><strong>Telefone:</strong> <?php echo $pedido['cliente_telefone']; ?></p>
    <p><strong>Endereço:</strong> <?php echo htmlspecialchars($pedido['cliente_endereco']); ?></p>
    <p><strong>Observações:</strong> <?php echo nl2br(htmlspecialchars($pedido['observacoes'])); ?></p>
</div>

<table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
    <tr style="background:#f8f9fa; border-bottom:1px solid #eee;">
        <th style="padding:10px; text-align:left;">Item</th>
        <th style="padding:10px; text-align:center;">Qtd</th>
        <th style="padding:10px; text-align:right;">Total</th>
    </tr>
    <?php foreach ($itens as $item): ?>
    <tr style="border-bottom:1px solid #eee;">
        <td style="padding:10px;">
            <?php echo htmlspecialchars($item['produto_nome']); ?>
            <?php if ($item['observacoes']): ?>
                <br><small style="color:#666;"><?php echo htmlspecialchars($item['observacoes']); ?></small>
            <?php endif; ?>
        </td>
        <td style="padding:10px; text-align:center;"><?php echo $item['quantidade']; ?></td>
        <td style="padding:10px; text-align:right;">R$ <?php echo number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.'); ?></td>
    </tr>
    <?php endforeach; ?>
    <tr>
        <td colspan="2" style="padding:10px; text-align:right; font-weight:bold;">Total Pedido:</td>
        <td style="padding:10px; text-align:right; font-weight:bold;">R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?></td>
    </tr>
</table>

<form method="POST" style="background:#f8f9fa; padding:15px; border-radius:5px;">
    <input type="hidden" name="acao" value="atualizar_status">
    <input type="hidden" name="pedido_id" value="<?php echo $id; ?>">
    <label><strong>Atualizar Status:</strong></label>
    <select name="status" style="padding:5px; margin-left:10px; border-radius:4px; border:1px solid #ddd;">
        <option value="pendente" <?php echo $pedido['status'] == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
        <option value="em_preparo" <?php echo $pedido['status'] == 'em_preparo' ? 'selected' : ''; ?>>Em Preparo</option>
        <option value="saiu_entrega" <?php echo $pedido['status'] == 'saiu_entrega' ? 'selected' : ''; ?>>Saiu para Entrega</option>
        <option value="concluido" <?php echo $pedido['status'] == 'concluido' ? 'selected' : ''; ?>>Concluído</option>
        <option value="cancelado" <?php echo $pedido['status'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
    </select>
    <button type="submit" class="btn btn-primary btn-sm" style="margin-left:10px;">Salvar</button>
</form>
