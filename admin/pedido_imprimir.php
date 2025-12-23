<?php
include 'includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('ID do pedido não fornecido.');
}

$pedido_id = $_GET['id'];

// Buscar detalhes do pedido (endereço está na tabela pedidos, não clientes)
$stmt = $pdo->prepare("SELECT p.*, c.nome AS cliente_nome_db, c.telefone AS cliente_telefone_db, fp.nome AS forma_pagamento_nome
                       FROM pedidos p 
                       LEFT JOIN clientes c ON p.cliente_id = c.id 
                       LEFT JOIN formas_pagamento fp ON p.forma_pagamento_id = fp.id
                       WHERE p.id = ?");
$stmt->execute([$pedido_id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    die('Pedido não encontrado.');
}

// Usar nome/telefone do pedido ou fallback para cliente
$pedido['cliente_nome'] = $pedido['cliente_nome'] ?? $pedido['cliente_nome_db'] ?? 'Cliente';
$pedido['cliente_telefone'] = $pedido['cliente_telefone'] ?? $pedido['cliente_telefone_db'] ?? '';
$pedido['forma_pagamento'] = $pedido['forma_pagamento_nome'] ?? 'Não informado';

// Variáveis que estavam faltando
$pedido['taxa_entrega'] = $pedido['taxa_entrega'] ?? 0;
$pedido['desconto'] = $pedido['desconto'] ?? 0;
$pedido['total_pedido'] = $pedido['valor_total']; // Mapear valor_total para o nome usado no template

// Buscar itens do pedido
$stmt_itens = $pdo->prepare("SELECT pi.*, pr.nome AS produto_nome 
                             FROM pedido_itens pi 
                             LEFT JOIN produtos pr ON pi.produto_id = pr.id 
                             WHERE pi.pedido_id = ?");
$stmt_itens->execute([$pedido_id]);
$itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

// Calcular total dos produtos
$pedido['total_produtos'] = 0;
foreach ($itens as $item) {
    $pedido['total_produtos'] += $item['quantidade'] * $item['preco_unitario'];
}

$site_config = get_config();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido #<?php echo $pedido['codigo_pedido']; ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .ticket {
            width: 300px; /* Largura típica de impressora térmica 80mm */
            margin: 0 auto;
            background-color: #fff;
            padding: 15px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .mb-10 { margin-bottom: 10px; }
        .mb-20 { margin-bottom: 20px; }
        .border-bottom { border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 5px; }
        .border-top { border-top: 1px dashed #000; padding-top: 5px; margin-top: 5px; }
        .items-table { width: 100%; border-collapse: collapse; }
        .items-table th, .items-table td { text-align: left; padding: 2px 0; }
        .items-table .qty { width: 30px; }
        .items-table .price { text-align: right; }
        
        @media print {
            body { background-color: #fff; padding: 0; }
            .ticket { box-shadow: none; width: 100%; margin: 0; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="ticket">
        <div class="text-center mb-10">
            <h3 style="margin: 0;"><?php echo htmlspecialchars($site_config['titulo_site'] ?? 'CardapiX'); ?></h3>
            <p style="margin: 5px 0;">Pedido #<?php echo $pedido['codigo_pedido']; ?></p>
            <p style="margin: 0; font-size: 12px;"><?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></p>
        </div>

        <div class="border-bottom mb-10">
            <p class="fw-bold" style="margin: 0;">Cliente:</p>
            <p style="margin: 0;"><?php echo htmlspecialchars($pedido['cliente_nome']); ?></p>
            <p style="margin: 0;"><?php echo htmlspecialchars($pedido['cliente_telefone']); ?></p>
            
            <?php if ($pedido['tipo_entrega'] == 'delivery'): ?>
                <p class="fw-bold" style="margin: 5px 0 0 0;">Endereço de Entrega:</p>
                <p style="margin: 0;"><?php echo htmlspecialchars($pedido['cliente_endereco'] ?? 'Não informado'); ?></p>
            <?php else: ?>
                <p class="fw-bold" style="margin: 5px 0 0 0;">Retirada no Balcão</p>
            <?php endif; ?>
        </div>

        <table class="items-table mb-10">
            <thead>
                <tr>
                    <th class="qty">Qtd</th>
                    <th>Item</th>
                    <th class="price">Vl.</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens as $item): ?>
                    <tr>
                        <td class="qty"><?php echo $item['quantidade']; ?>x</td>
                        <td>
                            <?php echo htmlspecialchars($item['produto_nome']); ?>
                            <?php if (!empty($item['observacao'])): ?>
                                <br><small>(<?php echo htmlspecialchars($item['observacao']); ?>)</small>
                            <?php endif; ?>
                        </td>
                        <td class="price"><?php echo number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="border-top">
            <div style="display: flex; justify-content: space-between;">
                <span>Subtotal:</span>
                <span>R$ <?php echo number_format($pedido['total_produtos'], 2, ',', '.'); ?></span>
            </div>
            <?php if ($pedido['taxa_entrega'] > 0): ?>
                <div style="display: flex; justify-content: space-between;">
                    <span>Taxa Entrega:</span>
                    <span>R$ <?php echo number_format($pedido['taxa_entrega'], 2, ',', '.'); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($pedido['desconto'] > 0): ?>
                <div style="display: flex; justify-content: space-between;">
                    <span>Desconto:</span>
                    <span>- R$ <?php echo number_format($pedido['desconto'], 2, ',', '.'); ?></span>
                </div>
            <?php endif; ?>
            <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 16px; margin-top: 5px;">
                <span>TOTAL:</span>
                <span>R$ <?php echo number_format($pedido['total_pedido'], 2, ',', '.'); ?></span>
            </div>
        </div>

        <div class="border-top mb-10">
            <p style="margin: 0;"><b>Forma de Pagamento:</b><br> <?php echo ucfirst($pedido['forma_pagamento']); ?></p>
            <?php if ($pedido['troco_para'] > 0): ?>
                <p style="margin: 0;"><b>Troco para:</b> R$ <?php echo number_format($pedido['troco_para'], 2, ',', '.'); ?></p>
                <p style="margin: 0;"><b>Troco:</b> R$ <?php echo number_format($pedido['troco_para'] - $pedido['total_pedido'], 2, ',', '.'); ?></p>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($pedido['observacao'])): ?>
            <div class="border-top mb-10">
                <p style="margin: 0;"><b>Observações do Pedido:</b><br><?php echo nl2br(htmlspecialchars($pedido['observacao'])); ?></p>
            </div>
        <?php endif; ?>

        <div class="text-center no-print" style="margin-top: 20px;">
            <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Imprimir</button>
            <br><br>
            <a href="javascript:window.close()" style="color: #666; text-decoration: none;">Fechar Janela</a>
        </div>
    </div>

    <script>
        // Impressão automática se parâmetro auto=1
        <?php if (isset($_GET['auto']) && $_GET['auto'] == '1'): ?>
        window.onload = function() {
            // Pequeno delay para garantir que a página carregou
            setTimeout(function() {
                window.print();
                // Fechar a janela após imprimir (após o usuário confirmar/cancelar)
                window.onafterprint = function() {
                    window.close();
                };
            }, 500);
        };
        <?php endif; ?>
    </script>
</body>
</html>