<?php
// cliente/includes/navbar.php - Premium Navbar
$current_page = basename($_SERVER['PHP_SELF']);

// Get avatar/initials
$initials = 'C';
if (isset($cliente_nome) && !empty($cliente_nome)) {
    $parts = explode(' ', $cliente_nome);
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $initials .= strtoupper(substr(end($parts), 0, 1));
    }
}
?>
<nav class="navbar-premium">
    <div class="container-dashboard">
        <div class="d-flex justify-content-between align-items-center">
            <!-- Logo -->
            <a class="text-decoration-none d-flex align-items-center gap-2" href="index.php">
                <div style="width: 40px; height: 40px; background: var(--primary-gradient); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-utensils text-white"></i>
                </div>
                <span class="fw-bold text-gradient d-none d-sm-inline" style="font-size: 1.25rem;">PediMais</span>
            </a>
            
            <!-- Desktop Navigation -->
            <div class="d-none d-md-flex align-items-center gap-2">
                <a href="index.php" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-house"></i> Início
                </a>
                <a href="pedidos.php" class="nav-link <?php echo $current_page === 'pedidos.php' || $current_page === 'pedido_detalhe.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-receipt"></i> Pedidos
                </a>
                <a href="enderecos.php" class="nav-link <?php echo $current_page === 'enderecos.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-location-dot"></i> Endereços
                </a>
                <a href="perfil.php" class="nav-link <?php echo $current_page === 'perfil.php' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user"></i> Perfil
                </a>
            </div>
            
            <!-- Right Side -->
            <div class="d-flex align-items-center gap-3">
                <!-- Theme Toggle -->
                <button class="theme-toggle" onclick="toggleTheme()" title="Alternar tema">
                    <i class="fa-solid fa-sun" id="themeIcon"></i>
                </button>
                
                <!-- User Dropdown -->
                <div class="dropdown">
                    <button class="btn p-0 border-0 bg-transparent" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if (isset($cliente_foto) && $cliente_foto && file_exists('../' . $cliente_foto)): ?>
                            <img src="../<?php echo htmlspecialchars($cliente_foto); ?>" alt="Avatar" class="avatar">
                        <?php else: ?>
                            <div class="avatar-initials"><?php echo $initials; ?></div>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2">
                        <li class="px-3 py-2 border-bottom">
                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($cliente_nome); ?></div>
                            <small class="text-muted">Minha Conta</small>
                        </li>
                        <li><a class="dropdown-item py-2" href="perfil.php"><i class="fa-solid fa-user me-2 text-muted"></i> Meu Perfil</a></li>
                        <li><a class="dropdown-item py-2" href="enderecos.php"><i class="fa-solid fa-location-dot me-2 text-muted"></i> Meus Endereços</a></li>
                        <li><a class="dropdown-item py-2" href="pedidos.php"><i class="fa-solid fa-receipt me-2 text-muted"></i> Meus Pedidos</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Sair</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Mobile Navigation -->
        <div class="d-md-none mt-3">
            <div class="d-flex justify-content-around">
                <a href="index.php" class="nav-link text-center <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" style="flex: 1;">
                    <i class="fa-solid fa-house d-block mb-1"></i>
                    <small>Início</small>
                </a>
                <a href="pedidos.php" class="nav-link text-center <?php echo $current_page === 'pedidos.php' ? 'active' : ''; ?>" style="flex: 1;">
                    <i class="fa-solid fa-receipt d-block mb-1"></i>
                    <small>Pedidos</small>
                </a>
                <a href="enderecos.php" class="nav-link text-center <?php echo $current_page === 'enderecos.php' ? 'active' : ''; ?>" style="flex: 1;">
                    <i class="fa-solid fa-location-dot d-block mb-1"></i>
                    <small>Endereços</small>
                </a>
                <a href="perfil.php" class="nav-link text-center <?php echo $current_page === 'perfil.php' ? 'active' : ''; ?>" style="flex: 1;">
                    <i class="fa-solid fa-user d-block mb-1"></i>
                    <small>Perfil</small>
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
function toggleTheme() {
    const html = document.documentElement;
    const body = document.body;
    const icon = document.getElementById('themeIcon');
    
    if (html.getAttribute('data-theme') === 'dark') {
        html.setAttribute('data-theme', 'light');
        body.classList.remove('dark-mode');
        icon.className = 'fa-solid fa-sun';
        document.cookie = 'theme=light; path=/; max-age=31536000';
    } else {
        html.setAttribute('data-theme', 'dark');
        body.classList.add('dark-mode');
        icon.className = 'fa-solid fa-moon';
        document.cookie = 'theme=dark; path=/; max-age=31536000';
    }
}

// Update icon on load
document.addEventListener('DOMContentLoaded', function() {
    const theme = document.documentElement.getAttribute('data-theme');
    const icon = document.getElementById('themeIcon');
    if (theme === 'dark') {
        icon.className = 'fa-solid fa-moon';
    } else {
        icon.className = 'fa-solid fa-sun';
    }
});
</script>
