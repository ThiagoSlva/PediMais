<?php
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../../includes/config.php';
}
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/auth.php';

// Verificar login apenas se não estiver na página de login
if (basename($_SERVER['PHP_SELF']) != 'login.php') {
    verificar_login();
    
    // Verificar permissão de acesso à página atual
    verificar_permissao();
}

$usuario = get_usuario_atual();

// Carregar configurações do site (nome dinâmico)
$site_config = [];
try {
    $stmt = $pdo->query("SELECT * FROM configuracoes WHERE id = 1");
    $site_config = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    // Tabela pode não existir ainda
}
$nome_site = $site_config['nome_site'] ?? 'PedeMais';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - <?php echo htmlspecialchars($nome_site); ?></title>
    <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>/uploads/config/favicon.png" sizes="16x16">
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.php">
    <meta name="theme-color" content="#4caf50">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?php echo htmlspecialchars($nome_site); ?>">
    <link rel="apple-touch-icon" href="<?php echo SITE_URL; ?>/uploads/config/logo.png">
    
    <!-- remix icon font css  -->
    <!-- remix icon font css  -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/admin/assets/css/remixicon.css">
    <!-- BootStrap css -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/admin/assets/css/lib/bootstrap.min.css">
    <!-- Data Table css -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/admin/assets/css/lib/dataTables.min.css">
    <!-- main css -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/admin/assets/css/style.css">
    <!-- Custom admin css -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/admin/assets/css/admin-custom.css">
    <!-- Mobile fixes -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/admin/assets/css/mobile-fixes.css">
    <!-- Sino vermelho -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/admin/assets/css/sino-vermelho.css">
    <!-- Realtime Updates -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/admin/assets/css/realtime-updates.css">
    
    <!-- Iconify -->
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body data-nivel="admin">
<!-- Sidebar -->
<aside class="sidebar">
    <button type="button" class="sidebar-close-btn d-lg-none">
        <iconify-icon icon="radix-icons:cross-2"></iconify-icon>
    </button>
    <div>
        <a href="index.php" class="sidebar-logo d-flex align-items-center justify-content-center gap-2 p-3">
            <img src="<?php echo SITE_URL; ?>/uploads/config/logo.png" alt="<?php echo htmlspecialchars($nome_site); ?>" style="max-height: 35px; display: block;">
            <span class="fw-bold text-md"><?php echo htmlspecialchars($nome_site); ?></span>
        </a>
    </div>
    <div class="sidebar-menu-area">
        <?php 
        $menu_items = get_menu_items();
        $current_page = basename($_SERVER['PHP_SELF']);
        ?>
        <ul class="sidebar-menu" id="sidebar-menu">
            <?php foreach ($menu_items as $section => $items): ?>
                <?php if ($section !== 'Principal' && $section !== 'Conta'): ?>
                    <li class="sidebar-menu-group-title"><?php echo htmlspecialchars($section); ?></li>
                <?php endif; ?>
                <?php foreach ($items as $item): 
                    $is_active = ($current_page === $item['href']) ? 'active-page' : '';
                ?>
                    <li>
                        <a href="<?php echo $item['href']; ?>" class="<?php echo $is_active; ?>">
                            <iconify-icon icon="<?php echo $item['icon']; ?>" class="menu-icon"></iconify-icon>
                            <span><?php echo htmlspecialchars($item['label']); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </ul>
    </div>
</aside>

<main class="dashboard-main">
    <!-- Navbar -->
    <div class="navbar-header">
        <div class="row align-items-center justify-content-between">
            <div class="col-auto">
                <div class="d-flex flex-wrap align-items-center gap-2 gap-md-4">
                    <button type="button" class="sidebar-mobile-toggle d-lg-none">
                        <iconify-icon icon="heroicons:bars-3-solid" class="icon"></iconify-icon>
                    </button>
                    
                    <?php if (is_admin()): ?>
                    <!-- Toggle Sistema de Entregadores -->
                    <div class="d-flex align-items-center gap-1 gap-md-2 me-1 me-md-2" title="Sistema de Atribuição de Entregadores">
                        <iconify-icon icon="solar:delivery-bold" class="text-primary-light toggle-icon-mobile"></iconify-icon>
                        <div class="form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="toggleEntregadores">
                        </div>
                    </div>
                    
                    <!-- Toggle Abrir/Fechar Estabelecimento -->
                    <div class="d-flex align-items-center gap-1 gap-md-2 me-1 me-md-3" title="Abrir/Fechar Estabelecimento">
                        <iconify-icon icon="solar:shop-bold" class="toggle-icon-mobile" id="iconEstabelecimento"></iconify-icon>
                        <div class="form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="toggleEstabelecimento">
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-auto">
                <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3">
                    <?php if (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] !== 'entregador'): ?>
                    <!-- Notification Bell -->
                    <div class="dropdown">
                        <button class="has-indicator w-40-px h-40-px bg-neutral-200 rounded-circle d-flex justify-content-center align-items-center" 
                                type="button" data-bs-toggle="dropdown" id="notification-bell">
                            <iconify-icon icon="iconoir:bell" class="text-primary-light text-xl"></iconify-icon>
                        </button>
                        <div class="dropdown-menu to-top dropdown-menu-lg p-0">
                            <div class="m-16 py-12 px-16 radius-8 bg-primary-50 mb-16 d-flex align-items-center justify-content-between gap-2">
                                <div>
                                    <h6 class="text-lg text-primary-light fw-semibold mb-0">Pedidos Pendentes</h6>
                                </div>
                                <span class="text-primary-600 fw-semibold text-lg w-40-px h-40-px rounded-circle bg-base d-flex justify-content-center align-items-center" id="notification-badge">0</span>
                            </div>
                            
                            <div class="max-h-400-px overflow-y-auto scroll-sm pe-4" id="notification-list">
                                <div class="px-24 py-12 text-center">
                                    <p class="mb-0 text-sm text-secondary-light">Carregando...</p>
                                </div>
                            </div>
                            
                            <div class="text-center py-12 px-16 d-flex align-items-center justify-content-between gap-2">
                                <button type="button" onclick="limparNotificacoes()" class="btn btn-sm btn-outline-secondary" title="Limpar notificações vistas">
                                    <iconify-icon icon="solar:trash-bin-trash-outline"></iconify-icon>
                                </button>
                                <a href="pedidos.php" class="text-primary-600 fw-semibold text-md">Ver Todos os Pedidos</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Dark/Light Mode Toggle -->
                    <button type="button" class="w-40-px h-40-px bg-neutral-200 dark-light-btn rounded-circle d-flex justify-content-center align-items-center" id="theme-toggle" data-theme-toggle>
                        <iconify-icon icon="solar:moon-bold" class="icon text-lg moon"></iconify-icon>
                        <iconify-icon icon="solar:sun-bold" class="icon text-lg sun d-none"></iconify-icon>
                    </button>
                    
                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <button class="d-flex justify-content-center align-items-center rounded-circle" type="button" data-bs-toggle="dropdown">
                            <img src="<?php echo SITE_URL; ?>/uploads/config/logo.png" alt="Perfil" class="w-40-px h-40-px object-fit-cover rounded-circle">
                        </button>
                        <div class="dropdown-menu to-top dropdown-menu-sm">
                            <div class="py-12 px-16 radius-8 bg-primary-50 mb-16 d-flex align-items-center justify-content-between gap-2">
                                <div>
                                    <h6 class="text-lg text-primary-light fw-semibold mb-2"><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></h6>
                                    <span class="text-secondary-light fw-medium text-sm"><?php echo ucfirst($_SESSION['usuario_nivel']); ?></span>
                                </div>
                            </div>
                            <ul class="to-top-list">
                                <li>
                                    <a class="dropdown-item text-black px-0 py-8 hover-bg-transparent hover-text-primary d-flex align-items-center gap-3" href="logout.php">
                                        <iconify-icon icon="lucide:power" class="icon text-xl"></iconify-icon> Sair
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-main-body">
