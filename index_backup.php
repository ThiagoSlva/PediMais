<?php
require_once 'includes/functions.php';

$config = get_config();
$categorias = get_categorias_ativas();
$loja_aberta = loja_aberta();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($config['site_titulo']); ?></title>
<link rel="icon" href="admin/<?php echo $config['site_favicon']; ?>">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --primary-color: #9C27B0; /* Poderia vir do banco também */
  --surface-body: #f5f6fa;
  --text-primary: #1f2937;
}
body { font-family: 'Poppins', sans-serif; background: var(--surface-body); margin: 0; padding-bottom: 80px; }
header { position: relative; text-align: center; color: white; }
header .bg { width: 100%; height: 200px; object-fit: cover; }
header .logo { width: 100px; height: 100px; border-radius: 50%; border: 4px solid white; position: absolute; bottom: -50px; left: 50%; transform: translateX(-50%); background: white; }
h1 { margin-top: 60px; text-align: center; color: var(--text-primary); }

.status { text-align: center; margin: 10px 0; font-weight: bold; }
.status.aberto { color: green; }
.status.fechado { color: red; }

.categories { display: flex; overflow-x: auto; padding: 20px; gap: 15px; }
.category { min-width: 100px; text-align: center; cursor: pointer; }
.category img { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 2px solid transparent; }
.category.active img { border-color: var(--primary-color); }

.accordion { max-width: 800px; margin: 0 auto; padding: 0 15px; }
.accordion-item { background: white; border-radius: 8px; margin-bottom: 10px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
.accordion-header { padding: 15px; background: #fff; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-weight: 600; }
.accordion-content { display: none; padding: 15px; border-top: 1px solid #eee; }
.accordion-content.active { display: block; }

.product-card { display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #eee; cursor: pointer; }
.product-card:last-child { border-bottom: none; }
.product-info h4 { margin: 0 0 5px; }
.product-info .description { font-size: 0.85rem; color: #666; margin: 0 0 5px; }
.product-info .price { font-weight: bold; color: var(--primary-color); }
.product-card img { width: 80px; height: 80px; border-radius: 8px; object-fit: cover; }

/* Modal Styles (Simplificado) */
.modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
.modal.active { display: flex; }
.modal-content { background: white; width: 90%; max-width: 500px; border-radius: 10px; overflow: hidden; max-height: 90vh; overflow-y: auto; }
</style>
</head>
<body>

<header>
  <img src="admin/<?php echo $config['site_capa']; ?>" class="bg" alt="Capa">
  <img src="admin/<?php echo $config['site_logo']; ?>" class="logo" alt="Logo">
</header>

<h1><?php echo htmlspecialchars($config['site_titulo']); ?></h1>

<div class="status <?php echo $loja_aberta ? 'aberto' : 'fechado'; ?>">
    <?php if ($loja_aberta): ?>
        <i class="fa-solid fa-clock"></i> Aberto agora
    <?php else: ?>
        <i class="fa-solid fa-lock"></i> Fechado
    <?php endif; ?>
</div>

<div class="categories">
    <?php foreach ($categorias as $cat): ?>
    <div class="category" data-category="<?php echo $cat['id']; ?>">
        <img src="<?php echo $cat['imagem']; ?>" alt="<?php echo $cat['nome']; ?>">
        <span style="display:block; font-size: 0.8rem; margin-top: 5px;"><?php echo $cat['nome']; ?></span>
    </div>
    <?php endforeach; ?>
</div>

<div class="accordion">
    <?php foreach ($categorias as $cat): 
        $produtos = get_produtos_por_categoria($cat['id']);
        if (empty($produtos)) continue;
    ?>
    <div class="accordion-item" data-category-id="<?php echo $cat['id']; ?>">
        <div class="accordion-header">
            <span><?php echo $cat['nome']; ?></span>
            <i class="fa-solid fa-chevron-down"></i>
        </div>
        <div class="accordion-content">
            <?php foreach ($produtos as $prod): ?>
            <div class="product-card" onclick="abrirProduto(<?php echo $prod['id']; ?>)">
                <div class="product-info">
                    <h4><?php echo $prod['nome']; ?></h4>
                    <p class="description"><?php echo $prod['descricao']; ?></p>
                    <p class="price">
                        <?php 
                        if ($prod['preco_promocional'] > 0) {
                            echo '<span style="text-decoration: line-through; color: #999; font-size: 0.8rem;">' . formatar_moeda($prod['preco']) . '</span> ';
                            echo formatar_moeda($prod['preco_promocional']);
                        } else {
                            echo formatar_moeda($prod['preco']);
                        }
                        ?>
                    </p>
                </div>
                <?php if ($prod['imagem_path']): ?>
                <img src="<?php echo $prod['imagem_path']; ?>" alt="<?php echo $prod['nome']; ?>">
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Floating Cart Button -->
<div id="floating-cart" style="position: fixed; bottom: 20px; right: 20px; background: var(--primary-color); color: white; padding: 15px; border-radius: 50px; cursor: pointer; display: none; align-items: center; gap: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); z-index: 999;">
    <i class="fa-solid fa-cart-shopping"></i>
    <span id="cart-total">R$ 0,00</span>
    <span id="cart-count" style="background: white; color: var(--primary-color); border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold;">0</span>
</div>

<!-- Modal Produto -->
<div id="modal-produto" class="modal">
    <div class="modal-content" id="modal-produto-content">
        <!-- Conteúdo carregado via JS -->
    </div>
</div>

<!-- Modal Carrinho -->
<div id="modal-carrinho" class="modal">
    <div class="modal-content">
        <div style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;">Seu Carrinho</h3>
            <button onclick="fecharModal('modal-carrinho')" style="background: none; border: none; font-size: 1.2rem;">&times;</button>
        </div>
        <div id="cart-items" style="padding: 15px; max-height: 300px; overflow-y: auto;">
            <!-- Itens do carrinho -->
        </div>
        <div style="padding: 15px; border-top: 1px solid #eee;">
            <div style="display: flex; justify-content: space-between; font-weight: bold; margin-bottom: 15px;">
                <span>Total:</span>
                <span id="cart-total-modal">R$ 0,00</span>
            </div>
            <button onclick="abrirCheckout()" style="width: 100%; padding: 12px; background: green; color: white; border: none; border-radius: 5px; font-weight: bold;">Finalizar Pedido</button>
        </div>
    </div>
</div>

<!-- Modal Checkout -->
<div id="modal-checkout" class="modal">
    <div class="modal-content">
        <div style="padding: 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;">Finalizar Pedido</h3>
            <button onclick="fecharModal('modal-checkout')" style="background: none; border: none; font-size: 1.2rem;">&times;</button>
        </div>
        <div style="padding: 15px; max-height: 70vh; overflow-y: auto;">
            <form id="form-checkout">
                <h5 style="margin-bottom: 10px;">Seus Dados</h5>
                <div style="margin-bottom: 10px;">
                    <input type="text" id="cliente-nome" placeholder="Seu Nome" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" required>
                </div>
                <div style="margin-bottom: 10px;">
                    <input type="tel" id="cliente-telefone" placeholder="Seu Telefone (WhatsApp)" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" required>
                </div>

                <h5 style="margin-bottom: 10px; margin-top: 20px;">Entrega</h5>
                <div style="margin-bottom: 10px;">
                    <select id="tipo-entrega" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" onchange="toggleEndereco()">
                        <option value="delivery">Entrega (Delivery)</option>
                        <option value="retirada">Retirada no Balcão</option>
                    </select>
                </div>
                <div id="endereco-container" style="margin-bottom: 10px;">
                    <input type="text" id="cliente-endereco" placeholder="Endereço Completo (Rua, Número, Bairro)" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>

                <h5 style="margin-bottom: 10px; margin-top: 20px;">Pagamento</h5>
                <div style="margin-bottom: 10px;">
                    <select id="forma-pagamento" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" onchange="toggleTroco()">
                        <option value="pix">PIX (QR Code)</option>
                        <option value="cartao">Cartão (Maquininha)</option>
                        <option value="dinheiro">Dinheiro</option>
                    </select>
                </div>
                <div id="troco-container" style="margin-bottom: 10px; display: none;">
                    <input type="text" id="troco-para" placeholder="Troco para quanto?" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>

                <div style="margin-bottom: 10px;">
                    <textarea id="observacoes" placeholder="Observações gerais do pedido..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                </div>
            </form>
        </div>
        <div style="padding: 15px; border-top: 1px solid #eee;">
            <button onclick="enviarPedido()" style="width: 100%; padding: 12px; background: var(--primary-color); color: white; border: none; border-radius: 5px; font-weight: bold;">Confirmar Pedido</button>
        </div>
    </div>
</div>

<!-- Modal Sucesso / PIX -->
<div id="modal-sucesso" class="modal">
    <div class="modal-content" style="text-align: center; padding: 30px;">
        <i class="fa-solid fa-check-circle" style="font-size: 3rem; color: green; margin-bottom: 15px;"></i>
        <h3>Pedido Realizado!</h3>
        <p>Seu pedido #<span id="sucesso-pedido-id"></span> foi recebido.</p>
        
        <div id="pix-container" style="display: none; margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 8px;">
            <h5>Pagamento via PIX</h5>
            <p style="font-size: 0.9rem; color: #666;">Escaneie o QR Code abaixo para pagar:</p>
            <img id="pix-qrcode" src="" style="width: 200px; height: 200px; object-fit: contain; margin: 10px 0;">
            <br>
            <textarea id="pix-copia-cola" readonly style="width: 100%; height: 60px; font-size: 0.8rem; margin-top: 10px;"></textarea>
            <button onclick="copiarPix()" style="margin-top: 5px; padding: 5px 10px; font-size: 0.8rem;">Copiar Código</button>
        </div>

        <button onclick="window.location.reload()" style="margin-top: 20px; padding: 10px 20px; background: #333; color: white; border: none; border-radius: 5px;">Fechar</button>
    </div>
</div>

<script src="assets/js/app.js"></script>
</body>
</html>