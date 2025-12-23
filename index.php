<?php
session_start();
require_once 'includes/functions.php';

$config = get_config();
$categorias = get_categorias_ativas();
$loja_aberta = loja_aberta();

// Verificar se cliente está logado
$cliente_logado = null;
if (isset($_SESSION['cliente_id'])) {
    require_once 'includes/config.php';
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$_SESSION['cliente_id']]);
    $cliente_logado = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Define theme colors based on tema field
$temas = [
    'verde' => ['#4caf50', '#45a049'],
    'azul' => ['#2196F3', '#1976D2'],
    'roxo' => ['#9C27B0', '#7B1FA2'],
    'rosa' => ['#E91E63', '#C2185B'],
    'laranja' => ['#FF9800', '#F57C00'],
    'vermelho' => ['#F44336', '#D32F2F'],
    'teal' => ['#009688', '#00796B'],
    'indigo' => ['#3F51B5', '#303F9F'],
    'amber' => ['#FFC107', '#FFA000'],
    'cyan' => ['#00BCD4', '#0097A7'],
    'deep-purple' => ['#673AB7', '#512DA8'],
    'pink' => ['#EC407A', '#C2185B'],
];

$tema = $config['tema'] ?? 'roxo';
if ($tema === 'custom') {
    $cor_principal = $config['cor_principal'] ?? '#9C27B0';
    $cor_secundaria = $config['cor_secundaria'] ?? '#7B1FA2';
} else {
    $cor_principal = $temas[$tema][0] ?? '#9C27B0';
    $cor_secundaria = $temas[$tema][1] ?? '#7B1FA2';
}

// Tema de Layout (Shioki, etc)
$tema_layout = $config['tema_layout'] ?? 'default';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="<?php echo $cor_principal; ?>">
<title><?php echo htmlspecialchars($config['nome_site'] ?? 'PedeMais'); ?></title>
<link rel="icon" href="admin/uploads/config/<?php echo $config['favicon'] ?? 'favicon.ico'; ?>">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<?php if ($tema_layout === 'shioki'): ?>
<link href="assets/css/shioki.css" rel="stylesheet">
<?php endif; ?>
<style>
:root {
  --primary-color: <?php echo $cor_principal; ?>;
  --primary-dark: <?php echo $cor_secundaria; ?>;
  --primary-light: <?php echo $cor_principal; ?>1a;
  --primary-shadow: <?php echo $cor_principal; ?>66;
  --primary-shadow-hover: <?php echo $cor_principal; ?>73;
}

/* Dark Theme (default) */
body, [data-theme="dark"] {
  --surface-body: #1a1a2e;
  --surface-card: #16213e;
  --surface-soft: #1f2937;
  --border-color: rgba(255, 255, 255, 0.1);
  --text-primary: #f8fafc;
  --text-secondary: #94a3b8;
  --text-muted: #64748b;
  --text-inverse: #1f2937;
  --modal-overlay: rgba(0, 0, 0, 0.75);
  --card-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
  --card-shadow-hover: 0 4px 14px rgba(0, 0, 0, 0.4);
}

/* Light Theme */
[data-theme="light"] {
  --surface-body: #f5f5f5;
  --surface-card: #ffffff;
  --surface-soft: #f0f0f0;
  --border-color: rgba(0, 0, 0, 0.1);
  --text-primary: #1f2937;
  --text-secondary: #4b5563;
  --text-muted: #6b7280;
  --text-inverse: #f8fafc;
  --modal-overlay: rgba(0, 0, 0, 0.5);
  --card-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  --card-shadow-hover: 0 4px 14px rgba(0, 0, 0, 0.15);
}

* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'Poppins', sans-serif;
  background: var(--surface-body);
  color: var(--text-primary);
  padding-bottom: 80px; /* Espaço para carrinho flutuante mobile */
}

/* Header */
header {
  position: relative;
  text-align: center;
  color: white;
}
header img.bg {
  width: 100%;
  height: 220px;
  object-fit: cover;
  filter: brightness(60%);
}
header .logo {
  position: absolute;
  bottom: -50px;
  left: 50%;
  transform: translateX(-50%);
  width: 100px;
  height: 100px;
  border-radius: 50%;
  border: 4px solid white;
  object-fit: cover;
  background: #fff;
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
header h1 {
  position: absolute;
  bottom: 60px;
  width: 100%;
  font-size: 1.3rem;
  font-weight: 700;
  text-shadow: 0 2px 4px rgba(0,0,0,0.7);
  padding: 0 20px;
}

/* Status */
.status {
  text-align: center;
  margin: 65px 0 10px;
  font-weight: bold;
}
.status span {
  padding: 6px 16px;
  border-radius: 16px;
  font-size: 0.9rem;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  box-shadow: 0 6px 16px rgba(15, 23, 42, 0.15);
}
.status.aberto span { background: var(--primary-color); color: white; }
.status.fechado span { background: #e53935; color: white; }

.social, .location {
  text-align: center;
  margin: 10px 0;
}
.social a {
  color: var(--text-secondary);
  font-size: 1.4rem;
  margin: 0 12px;
  transition: transform 0.2s;
  display: inline-block;
}
.social a:hover { transform: scale(1.2); }
.location { font-size: 0.9rem; color: var(--text-secondary); padding: 0 20px; }

/* Categories - Slider Horizontal */
.categories-wrapper {
  overflow-x: auto;
  padding: 20px 0;
}
.categories {
  display: flex;
  gap: 15px;
  padding: 0 20px;
  width: max-content;
}
.category {
  flex: 0 0 100px;
  text-align: center;
  cursor: pointer;
  transition: transform 0.2s;
}
.category:hover { transform: translateY(-5px); }
.category img {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid transparent;
  box-shadow: var(--card-shadow);
}
.category.active img {
  border-color: var(--primary-color);
  transform: scale(1.1);
}
.category-name {
  display: block;
  font-size: 0.85rem;
  margin-top: 5px;
  font-weight: 600;
  color: var(--text-primary);
}

/* Accordion */
.accordion { margin: 15px 10px; }
.accordion-item {
  background: var(--surface-card);
  border-radius: 12px;
  margin-bottom: 12px;
  box-shadow: var(--card-shadow);
  overflow: hidden;
}
.accordion-header {
  padding: 18px;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 700;
  font-size: 1.1rem;
  border-bottom: 1px solid var(--border-color);
}
.accordion-content {
  display: none;
  padding: 15px;
}
.accordion-content.active { display: block; }

/* Product Card */
.product-card {
  display: flex;
  justify-content: space-between;
  margin-bottom: 12px;
  padding: 15px;
  border-radius: 12px;
  background: var(--surface-card);
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  cursor: pointer;
  transition: transform 0.2s;
  border: 1px solid var(--border-color);
}
.product-card:hover { transform: translateY(-2px); }
.product-info { flex: 1; padding-right: 15px; }
.product-info h4 { margin: 0 0 6px 0; font-size: 1.05rem; font-weight: 600; }
.product-info .description { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px; }
.product-info .price { color: var(--primary-color); font-weight: 700; font-size: 1.1rem; }
.product-card img {
  width: 90px;
  height: 90px;
  border-radius: 10px;
  object-fit: cover;
}

/* Modals */
.modal, .produto-modal {
  display: none;
  position: fixed;
  z-index: 10050;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background: var(--modal-overlay);
}
.modal.active { display: flex; align-items: center; justify-content: center; }
.modal-content {
  background: var(--surface-card);
  border-radius: 16px;
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  position: relative;
  padding: 20px;
  margin: auto; /* Ensures centering in flex container */
}

/* Produto Modal Bottom Sheet */
.produto-modal.active { display: block; }
.produto-modal-content {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: var(--surface-card);
  border-radius: 24px 24px 0 0;
  max-height: 90vh;
  padding: 20px;
  animation: slideUp 0.3s ease-out;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.produto-modal-content #produto-detalhes {
  overflow-y: auto;
  flex: 1;
  padding-bottom: 100px; /* Space for fixed footer */
  -webkit-overflow-scrolling: touch;
}
.produto-modal-content .modal-footer-fixed {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: #151922;
  padding: 15px 20px;
  border-top: 1px solid #2d3446;
  z-index: 10;
}
@keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }

/* Carrinho Flutuante */
.carrinho-flutuante {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: var(--surface-card);
  box-shadow: 0 -4px 12px rgba(15, 23, 42, 0.12);
  z-index: 1500;
  display: none; /* JS controla */
}
.carrinho-header {
  display: flex;
  justify-content: space-between;
  padding: 16px 20px;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
  color: white;
  cursor: pointer;
}
.carrinho-corpo {
  max-height: 60vh;
  overflow-y: auto;
  display: none;
  padding: 20px;
}

/* Mobile Floating Button */
#carrinho-flutuante-mobile {
  position: fixed;
  bottom: 20px;
  right: 20px;
  width: 60px;
  height: 60px;
  background: var(--primary-color);
  border-radius: 50%;
  display: none;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  box-shadow: 0 4px 12px rgba(0,0,0,0.3);
  z-index: 10040;
  cursor: pointer;
}
#carrinho-badge-mobile {
  position: absolute;
  top: -5px;
  right: -5px;
  background: red;
  color: white;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  font-size: 0.8rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Modal Carrinho Mobile */
#modal-carrinho-mobile {
  display: none;
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  background: white;
  z-index: 10045;
  border-radius: 20px 20px 0 0;
  box-shadow: 0 -5px 20px rgba(0,0,0,0.2);
  max-height: 80vh;
  overflow-y: auto;
}
#modal-carrinho-mobile.active { display: block; animation: slideUp 0.3s; }

/* Forms */
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
.form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
.tipo-entrega-lista, .forma-pagamento-lista { display: flex; gap: 10px; flex-wrap: wrap; }
.tipo-entrega-item, .forma-pagamento-item {
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 8px;
  cursor: pointer;
  flex: 1;
  text-align: center;
}
.tipo-entrega-item.selected, .forma-pagamento-item.selected {
  border-color: var(--primary-color);
  background: var(--primary-light);
  font-weight: bold;
}
.btn-finalizar {
  width: 100%;
  padding: 15px;
  background: var(--primary-color);
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: bold;
  font-size: 1.1rem;
  margin-top: 20px;
  cursor: pointer;
}
</style>
</head>
<body class="<?php echo $tema_layout === 'shioki' ? 'tema-shioki' : ''; ?>">

<header>
  <img src="admin/uploads/config/<?php echo $config['capa'] ?? 'capa.png'; ?>" class="bg" alt="Capa">
  <img src="admin/uploads/config/<?php echo $config['logo'] ?? 'logo.png'; ?>" class="logo" alt="Logo">
  <h1><?php echo htmlspecialchars($config['nome_site'] ?? 'PedeMais'); ?></h1>
</header>

<div class="status <?php echo $loja_aberta ? 'aberto' : 'fechado'; ?>">
    <span>
        <?php if ($loja_aberta): ?>
            <i class="fa-solid fa-clock"></i> Aberto agora
        <?php else: ?>
            <i class="fa-solid fa-lock"></i> Fechado
        <?php endif; ?>
    </span>
</div>

<div class="header-actions" style="display: flex; justify-content: center; gap: 15px; margin: 15px 0;">
    <!-- Cliente Login -->
    <a href="cliente/" class="action-btn btn-user" style="position: relative;">
        <i class="fa-regular fa-user"></i>
    </a>
    
    <!-- Promoções -->
    <?php
    // Contar produtos em promoção
    $qtd_promo = 0;
    foreach ($categorias as $cat_promo) {
        $prods_promo = get_produtos_por_categoria($cat_promo['id']);
        foreach ($prods_promo as $pp) {
            if ($pp['preco_promocional'] > 0) $qtd_promo++;
        }
    }
    ?>
    <a href="#promocoes" onclick="filtrarPromocoes()" class="action-btn btn-promo" style="position: relative;">
        <i class="fa-solid fa-percent"></i>
        <?php if ($qtd_promo > 0): ?>
        <span class="badge-count"><?php echo $qtd_promo; ?></span>
        <?php endif; ?>
    </a>

    <!-- Instagram -->
    <?php if (!empty($config['instagram'] ?? '')): 
        $instagram = trim($config['instagram']);
        if (strpos($instagram, 'http') === false && strpos($instagram, 'instagram.com') === false) {
            $instagram = ltrim($instagram, '@');
            $instagram = 'https://instagram.com/' . $instagram;
        }
    ?>
    <a href="<?php echo $instagram; ?>" target="_blank" class="action-btn btn-instagram">
        <i class="fa-brands fa-instagram"></i>
    </a>
    <?php endif; ?>

    <!-- Facebook -->
    <?php if (!empty($config['facebook'] ?? '')): 
        $facebook = trim($config['facebook']);
        if (strpos($facebook, 'http') === false && strpos($facebook, 'facebook.com') === false) {
            $facebook = 'https://facebook.com/' . $facebook;
        }
    ?>
    <a href="<?php echo $facebook; ?>" target="_blank" class="action-btn btn-facebook">
        <i class="fa-brands fa-facebook-f"></i>
    </a>
    <?php endif; ?>

    <!-- WhatsApp -->
    <?php if (!empty($config['whatsapp'] ?? '')): ?>
    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $config['whatsapp']); ?>" target="_blank" class="action-btn btn-whatsapp">
        <i class="fa-brands fa-whatsapp"></i>
    </a>
    <?php endif; ?>
    
    <!-- Theme Toggle -->
    <button type="button" onclick="toggleFrontendTheme()" class="action-btn btn-theme" id="theme-toggle-frontend" title="Alternar modo claro/escuro">
        <i class="fa-solid fa-sun" id="theme-icon-sun" style="display:none;"></i>
        <i class="fa-solid fa-moon" id="theme-icon-moon"></i>
    </button>
</div>

<style>
.action-btn {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    text-decoration: none;
    transition: transform 0.2s;
    border: 2px solid rgba(255,255,255,0.1);
}
.action-btn:hover { transform: scale(1.1); }

.btn-user {
    background: rgba(33, 150, 243, 0.15);
    color: #2196F3;
    border-color: rgba(33, 150, 243, 0.3);
}
.btn-promo {
    background: rgba(255, 87, 34, 0.15);
    color: #FF5722;
    border-color: rgba(255, 87, 34, 0.3);
}
.btn-whatsapp {
    background: rgba(76, 175, 80, 0.15);
    color: #4CAF50;
    border-color: rgba(76, 175, 80, 0.3);
}
.badge-count {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #FFC107;
    color: #000;
    font-size: 0.7rem;
    font-weight: bold;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--surface-body);
}

.btn-theme {
    background: rgba(147, 51, 234, 0.15);
    color: #9333ea;
    border-color: rgba(147, 51, 234, 0.3);
}
[data-theme="light"] .btn-theme {
    background: rgba(99, 102, 241, 0.15);
    color: #6366f1;
    border-color: rgba(99, 102, 241, 0.3);
}
</style>


<style>
.btn-instagram {
    background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
    color: white !important;
    border-color: transparent;
}
.btn-facebook {
    background: rgba(24, 119, 242, 0.15);
    color: #1877F2;
    border-color: rgba(24, 119, 242, 0.3);
}
</style>

<div class="location">
    <i class="fa-solid fa-location-dot"></i> 
    <?php echo htmlspecialchars(($config['rua'] ?? '') . ', ' . ($config['numero'] ?? '') . ' - ' . ($config['bairro'] ?? '')); ?>
</div>

<!-- Categories Slider -->
<div class="categories-wrapper">
    <div class="categories">
        <?php foreach ($categorias as $cat): ?>
        <div class="category" data-category="<?php echo $cat['id']; ?>">
            <img src="<?php echo $cat['imagem'] ? $cat['imagem'] : 'admin/assets/images/sem-foto.jpg'; ?>" alt="<?php echo $cat['nome']; ?>">
            <span class="category-name"><?php echo $cat['nome']; ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Products Accordion -->
<div class="accordion">
    <?php foreach ($categorias as $cat): 
        $produtos = get_produtos_por_categoria($cat['id']);
        if (empty($produtos)) continue;
    ?>
    <div class="accordion-item" data-category-id="<?php echo $cat['id']; ?>">
        <div class="accordion-header">
            <span>
                <?php echo $cat['nome']; ?>
                <span style="font-size: 0.85rem; color: #999; font-weight: normal;">(<?php echo count($produtos); ?>)</span>
            </span>
            <i class="fa-solid fa-chevron-down"></i>
        </div>
        <div class="accordion-content">
            <?php foreach ($produtos as $prod): 
                $rating = get_produto_avaliacao($prod['id']);
            ?>
            <div class="product-card" onclick="abrirProduto(<?php echo $prod['id']; ?>)">
                <div class="product-info">
                    <h4><?php echo $prod['nome']; ?></h4>
                    <?php if ($rating): ?>
                    <div class="product-rating" style="display: flex; align-items: center; gap: 5px; margin: 3px 0;">
                        <span style="color: #ffc107; font-size: 0.85rem;">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <span style="color: <?php echo $i <= $rating['estrelas'] ? '#ffc107' : '#ddd'; ?>;">★</span>
                            <?php endfor; ?>
                        </span>
                        <span style="color: #888; font-size: 0.75rem;">(<?php echo $rating['total']; ?>)</span>
                    </div>
                    <?php endif; ?>
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
                <img src="<?php echo $prod['imagem_path'] ? $prod['imagem_path'] : 'admin/assets/images/sem-foto.jpg'; ?>" alt="<?php echo $prod['nome']; ?>">
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php
// Buscar configuração de avaliações
$stmt_av_config = $pdo->query("SELECT * FROM configuracao_avaliacoes LIMIT 1");
$av_config = $stmt_av_config->fetch(PDO::FETCH_ASSOC);

// Se mostrar avaliações no site está ativo, buscar avaliações
if ($av_config && $av_config['mostrar_no_site']):
    $stmt_avaliacoes = $pdo->query("SELECT * FROM avaliacoes WHERE avaliacao > 0 AND ativo = 1 ORDER BY data_avaliacao DESC LIMIT 10");
    $avaliacoes_site = $stmt_avaliacoes->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($avaliacoes_site) > 0):
?>
<!-- Seção de Avaliações -->
<div class="reviews-section" style="margin: 30px 10px 100px 10px;">
    <h3 style="text-align: center; margin-bottom: 20px; color: var(--text-primary); font-size: 1.2rem; display: flex; align-items: center; justify-content: center; gap: 10px;">
        <i class="fa-solid fa-star" style="color: #ffc107;"></i> O que nossos clientes dizem
    </h3>
    
    <div class="reviews-carousel" style="overflow-x: auto; display: flex; gap: 15px; padding: 10px 5px; scroll-snap-type: x mandatory;">
        <?php foreach ($avaliacoes_site as $av): 
            $primeiro_nome = explode(' ', trim($av['cliente_nome']))[0];
        ?>
        <div class="review-card" style="flex: 0 0 280px; background: var(--surface-card); border-radius: 16px; padding: 20px; box-shadow: var(--card-shadow); scroll-snap-align: start;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <div style="width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.1rem;">
                    <?php echo strtoupper(substr($primeiro_nome, 0, 1)); ?>
                </div>
                <div>
                    <strong style="color: var(--text-primary); font-size: 1rem;"><?php echo htmlspecialchars($primeiro_nome); ?></strong>
                    <div style="color: #ffc107; font-size: 0.9rem;">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <i class="fa-solid fa-star" style="color: <?php echo $i <= ($av['avaliacao'] ?? 0) ? '#ffc107' : '#e0e0e0'; ?>;"></i>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            <?php if (!empty($av['descricao'])): ?>
            <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; margin: 0;">
                "<?php echo htmlspecialchars(mb_substr($av['descricao'], 0, 120)); ?><?php echo mb_strlen($av['descricao']) > 120 ? '...' : ''; ?>"
            </p>
            <?php endif; ?>
            <p style="color: var(--text-muted); font-size: 0.75rem; margin-top: 10px;">
                <?php echo date('d/m/Y', strtotime($av['data_avaliacao'])); ?>
            </p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php 
    endif;
endif; 
?>

<!-- Modal Produto Bottom Sheet -->
<!-- Modal Produto Bottom Sheet (Premium Dark) -->
<div id="produto-modal" class="produto-modal">
    <div class="produto-modal-overlay" onclick="fecharModal('produto-modal')" style="position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); backdrop-filter:blur(3px);"></div>
    <div class="produto-modal-content" style="background:#151922; color:white; border-top:1px solid #2d3446;">
        
        <!-- Close Button -->
        <div style="position: absolute; top: 15px; right: 15px; z-index: 10;">
            <button onclick="fecharModal('produto-modal')" style="background:#2d3446; border:none; color:white; width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer;">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>

        <div id="produto-loading" style="text-align:center; padding:40px;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size:2rem; color:var(--primary-color);"></i>
            <p style="margin-top:10px; color:#aaa;">Carregando...</p>
        </div>

        <div id="produto-detalhes" style="display:none; padding-bottom: 20px;">
            <!-- Imagem -->
            <div style="margin: -25px -25px 20px -25px;">
                <img id="modal-produto-imagem" src="" style="width:100%; height:250px; object-fit:cover; border-radius: 20px 20px 0 0;">
            </div>

            <!-- Título -->
            <h2 id="modal-produto-nome" style="margin-bottom:15px; font-size: 1.5rem; font-weight: 800;"></h2>
            <p id="modal-produto-descricao" style="color:#a0aec0; margin-bottom:20px; line-height: 1.5; font-size: 0.95rem;"></p>

            <!-- Preço Box -->
            <div style="background:#1e2433; padding:15px; border-radius:12px; border:1px solid #2d3446; display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <span style="font-weight:700; color:#e2e8f0;">Preço:</span>
                <span id="modal-produto-preco" style="font-size:1.3rem; color:#4a66f9; font-weight:800;"></span>
            </div>
            
            <!-- Adicionais -->
            <div id="adicionais-section" style="display:none; margin-bottom:20px;">
                <h4 style="margin-bottom:10px; color:#e2e8f0;">Adicionais</h4>
                <div id="adicionais-lista"></div>
            </div>
            
            <!-- Itens para Retirar -->
            <div id="retirar-section" style="display:none; margin-bottom:20px;">
                <h4 style="margin-bottom:10px; color:#e2e8f0; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-minus-circle" style="color:#ef4444;"></i> Retirar do Pedido
                </h4>
                <p style="color:#9ca3af; font-size:0.85rem; margin-bottom:12px;">Selecione os itens que deseja remover:</p>
                <div id="retirar-lista" style="display:flex; flex-wrap:wrap; gap:10px;"></div>
            </div>
            
            <!-- Meio a Meio -->
            <div id="segundo-sabor-section" style="display:none; margin-bottom:20px;">
                <h4 style="margin-bottom:10px; color:#e2e8f0;">Meio a Meio</h4>
                <div id="segundo-sabor-lista"></div>
            </div>
            
            <!-- Observações -->
            <div style="margin-bottom:25px;">
                <label style="display:flex; align-items:center; gap:8px; margin-bottom:10px; font-weight:600; color:#e2e8f0;">
                    <i class="fa-solid fa-message" style="color:#4a66f9;"></i> Observações (opcional)
                </label>
                <textarea id="produto-obs" style="width:100%; padding:15px; background:#0f131a; border:1px solid #2d3446; border-radius:12px; color:white; font-family:inherit; min-height:80px; resize:none;" placeholder="Ex: Sem gelo, bem passado, etc..."></textarea>
            </div>
        </div>
        
        <!-- Footer Actions (Fixed at bottom) -->
        <div class="modal-footer-fixed">
            <div style="display:flex; gap:15px; align-items:center;">
                <!-- Quantity -->
                <div style="display:flex; align-items:center; gap:0; background:#1e2433; border:1px solid #2d3446; border-radius:12px; padding:5px; height: 50px;">
                    <button onclick="alterarQuantidade(-1)" style="border:none; background:none; color:white; width:40px; height:100%; font-size:1.2rem; cursor:pointer; border-radius:8px 0 0 8px;">-</button>
                    <span id="quantidade-valor" style="font-weight:800; min-width:30px; text-align:center; font-size:1.1rem;">1</span>
                    <button onclick="alterarQuantidade(1)" style="border:none; background:none; color:white; width:40px; height:100%; font-size:1.2rem; cursor:pointer; border-radius:0 8px 8px 0;">+</button>
                </div>

                <!-- Add Button -->
                <button onclick="adicionarAoCarrinho()" style="flex:1; height: 50px; background:#3b55e6; color:white; border:none; border-radius:12px; font-weight:700; font-size:1rem; display:flex; justify-content:space-between; align-items:center; padding: 0 20px; cursor:pointer; box-shadow: 0 4px 15px rgba(59, 85, 230, 0.3);">
                    <span><i class="fa-solid fa-cart-plus"></i> Adicionar</span>
                    <span id="modal-total-btn">R$ 0,00</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Carrinho Flutuante Desktop -->
<div id="carrinho-flutuante" class="carrinho-flutuante">
    <div class="carrinho-header" onclick="toggleCarrinho()">
        <div>
            <i class="fa-solid fa-shopping-cart"></i>
            <span id="carrinho-qtd">0</span> itens
        </div>
        <div>
            R$ <span id="carrinho-total-valor">0,00</span>
            <i class="fa-solid fa-chevron-up carrinho-toggle-icon"></i>
        </div>
    </div>
    <div class="carrinho-corpo">
        <div id="carrinho-itens-lista"></div>
        <button onclick="irParaCheckout()" class="btn-finalizar">Finalizar Pedido</button>
    </div>
</div>

<!-- Carrinho Mobile Button -->
<div id="carrinho-flutuante-mobile" onclick="irParaCheckout()">
    <i class="fa-solid fa-shopping-cart"></i>
    <span id="carrinho-badge-mobile">0</span>
</div>

<!-- Modal Carrinho Mobile -->
<div id="modal-carrinho-mobile">
    <div style="padding:15px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
        <h3 style="margin:0;">Seu Carrinho</h3>
        <button onclick="document.getElementById('modal-carrinho-mobile').classList.remove('active')" style="border:none; background:none; font-size:1.5rem;">&times;</button>
    </div>
    <div id="carrinho-mobile-itens-lista" style="padding:15px;"></div>
    <div id="carrinho-mobile-resumo" style="border-top:1px solid #eee;"></div>
</div>

<!-- Modal Checkout -->
<div id="modal-checkout" class="modal">
    <div class="modal-content">
        <div style="padding:15px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0;">Finalizar Pedido</h3>
            <button onclick="fecharModal('modal-checkout')" style="border:none; background:none; font-size:1.5rem;">&times;</button>
        </div>
        <div id="modal-body-checkout" style="padding:15px;">
            <!-- Renderizado via JS -->
        </div>
    </div>
</div>

<script>
    window.siteConfig = <?php echo json_encode($config); ?>;
</script>
<script src="assets/js/app_new.js"></script>

<!-- MODAL DE PAGAMENTO PIX (Premium Dark Layout) -->
<div id="modal-pagamento-pix" class="modal" style="z-index: 10050;">
    <div class="modal-content" style="max-width: 400px; padding: 0; overflow: hidden; display: flex; flex-direction: column; background: var(--surface-card); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
        
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #009ee3 0%, #0077aa 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; display: flex; align-items: center; gap: 10px;">
                <i class="fa-brands fa-pix" style="font-size: 1.4rem;"></i> Pagar com PIX
            </h3>
            <button onclick="fecharModalPix()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Body Scrollable -->
        <div style="padding: 24px; text-align: center; overflow-y: auto; color: var(--text-primary);">
            
            <!-- Info Pedido -->
            <div style="background: rgba(0, 158, 227, 0.1); border: 1px solid rgba(0, 158, 227, 0.2); border-radius: 16px; padding: 16px; margin-bottom: 24px;">
                <p style="margin: 0; color: var(--text-secondary); font-size: 0.85rem; font-weight: 500; letter-spacing: 0.5px; text-transform: uppercase;">Pedido</p>
                <h2 id="pix-codigo-pedido" style="margin: 8px 0; color: #009ee3; font-weight: 800; font-size: 1.8rem; letter-spacing: 1px;">#-------</h2>
                <div style="display: flex; justify-content: center; align-items: center; gap: 6px; color: #ef4444; font-weight: 600; font-size: 0.9rem; background: rgba(239, 68, 68, 0.1); padding: 4px 12px; border-radius: 20px; display: inline-flex;">
                    <i class="fa-regular fa-clock"></i> Pague em até <span id="pix-tempo">10 minutos</span>
                </div>
            </div>

            <!-- QR Code Section -->
            <div style="margin-bottom: 24px;">
                <p style="color: var(--text-primary); font-weight: 600; margin-bottom: 12px; font-size: 1rem;">
                    <i class="fa-solid fa-qrcode" style="color: var(--text-secondary); margin-right: 8px;"></i> Escaneie o QR Code
                </p>
                <div style="background: white; padding: 12px; border-radius: 16px; display: inline-block; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
                    <img id="pix-qrcode-img" src="" alt="QR Code PIX" style="width: 100%; max-width: 220px; height: auto; object-fit: contain; display: block;">
                </div>
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 12px;">Abra o app do seu banco e escaneie o código</p>
            </div>

            <!-- Copia e Cola Section -->
            <div style="margin-bottom: 24px; text-align: left;">
                <p style="color: var(--text-primary); font-weight: 600; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; font-size: 1rem;">
                    <i class="fa-regular fa-clipboard" style="color: #f97316;"></i> PIX Copia e Cola
                </p>
                
                <div style="background: var(--surface-body); border: 1px solid var(--border-color); border-radius: 12px; padding: 4px; display: flex; align-items: center; position: relative;">
                    <textarea id="pix-copia-cola" readonly style="flex: 1; height: 60px; padding: 10px; border: none; background: transparent; font-family: 'Courier New', monospace; font-size: 0.85rem; color: var(--text-secondary); resize: none; outline: none;"></textarea>
                    
                    <button onclick="copiarPix()" style="width: 50px; height: 50px; background: rgba(0, 158, 227, 0.15); color: #009ee3; border: 1px solid rgba(0, 158, 227, 0.3); border-radius: 8px; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px; font-size: 0.75rem; margin-right: 4px; transition: all 0.2s;">
                        <i class="fa-regular fa-copy" style="font-size: 1.2rem;"></i>
                        <span style="font-weight: 600;">Copiar</span>
                    </button>
                    
                    <div id="copy-feedback" style="display: none; position: absolute; top: -35px; right: 0; background: #10b981; color: white; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                        <i class="fa-solid fa-check"></i> Copiado!
                    </div>
                </div>
                
                <p style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 8px; margin-left: 4px;">Cole este código no seu aplicativo de banco.</p>
            </div>

            <!-- Aviso -->
            <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); color: #f59e0b; padding: 12px; border-radius: 12px; font-size: 0.85rem; margin-bottom: 20px; text-align: left; line-height: 1.5; display: flex; align-items: flex-start; gap: 10px;">
                <i class="fa-solid fa-circle-info" style="margin-top: 3px;"></i> 
                <span><strong>Importante:</strong> Confirmamos seu pagamento automaticamente, não precisa enviar comprovante (opcional).</span>
            </div>

            <!-- Actions -->
            <button onclick="enviarComprovanteZap()" style="width: 100%; padding: 16px; background: #25D366; color: white; border: none; border-radius: 12px; font-weight: 600; margin-bottom: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 1rem; transition: transform 0.2s; box-shadow: 0 4px 6px -1px rgba(37, 211, 102, 0.2);">
                <i class="fa-brands fa-whatsapp" style="font-size: 1.2rem;"></i> Enviar Comprovante
            </button>
            
            <button onclick="fecharModalPix()" style="width: 100%; padding: 16px; background: transparent; color: var(--text-secondary); border: 1px solid var(--border-color); border-radius: 12px; font-weight: 600; cursor: pointer; font-size: 0.95rem; transition: background 0.2s;">
                Fechar
            </button>
        </div>
    </div>
</div>

<?php if ($cliente_logado): ?>
<script>
// Dados do cliente logado
window.clienteLogado = {
    id: <?php echo $cliente_logado['id']; ?>,
    nome: "<?php echo htmlspecialchars($cliente_logado['nome'] ?? '', ENT_QUOTES); ?>",
    telefone: "<?php echo htmlspecialchars($cliente_logado['telefone'] ?? '', ENT_QUOTES); ?>",
    email: "<?php echo htmlspecialchars($cliente_logado['email'] ?? '', ENT_QUOTES); ?>",
    cep: "<?php echo htmlspecialchars($cliente_logado['cep'] ?? '', ENT_QUOTES); ?>",
    rua: "<?php echo htmlspecialchars($cliente_logado['rua'] ?? '', ENT_QUOTES); ?>",
    numero: "<?php echo htmlspecialchars($cliente_logado['numero'] ?? '', ENT_QUOTES); ?>",
    complemento: "<?php echo htmlspecialchars($cliente_logado['complemento'] ?? '', ENT_QUOTES); ?>",
    bairro: "<?php echo htmlspecialchars($cliente_logado['bairro'] ?? '', ENT_QUOTES); ?>",
    cidade: "<?php echo htmlspecialchars($cliente_logado['cidade'] ?? '', ENT_QUOTES); ?>",
    estado: "<?php echo htmlspecialchars($cliente_logado['estado'] ?? '', ENT_QUOTES); ?>"
};
console.log('👤 Cliente logado:', window.clienteLogado.nome);
</script>
<?php endif; ?>

<!-- Theme Toggle Script -->
<script>
// Inicializar tema do frontend
(function() {
    const savedTheme = localStorage.getItem('frontend_theme') || 'dark';
    document.body.setAttribute('data-theme', savedTheme);
    updateThemeIcons(savedTheme);
})();

function toggleFrontendTheme() {
    const currentTheme = document.body.getAttribute('data-theme') || 'dark';
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    document.body.setAttribute('data-theme', newTheme);
    localStorage.setItem('frontend_theme', newTheme);
    updateThemeIcons(newTheme);
}

function updateThemeIcons(theme) {
    const sunIcon = document.getElementById('theme-icon-sun');
    const moonIcon = document.getElementById('theme-icon-moon');
    
    if (sunIcon && moonIcon) {
        if (theme === 'light') {
            sunIcon.style.display = 'none';
            moonIcon.style.display = 'inline-block';
        } else {
            sunIcon.style.display = 'inline-block';
            moonIcon.style.display = 'none';
        }
    }
}
</script>

</body>
</html>