<?php
// cliente/includes/sidebar.php - Recreated
// Sidebar might not be used in the current layout (navbar used instead), 
// but creating it just in case some encrypted files reference it.
?>
<div class="d-flex flex-column flex-shrink-0 p-3 bg-white" style="width: 280px; height: 100vh; position: fixed; left: 0; top: 0; border-right: 1px solid #eee;">
    <a href="index.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-dark text-decoration-none">
        <span class="fs-4 fw-bold text-primary">CardapiX</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="index.php" class="nav-link link-dark">
                <i class="fa-solid fa-house me-2"></i>
                Início
            </a>
        </li>
        <li>
            <a href="pedidos.php" class="nav-link link-dark">
                <i class="fa-solid fa-receipt me-2"></i>
                Meus Pedidos
            </a>
        </li>
        <li>
            <a href="perfil.php" class="nav-link link-dark">
                <i class="fa-solid fa-user me-2"></i>
                Perfil
            </a>
        </li>
    </ul>
    <hr>
    <div class="dropdown">
        <a href="logout.php" class="d-flex align-items-center link-dark text-decoration-none">
            <i class="fa-solid fa-right-from-bracket me-2"></i>
            <strong>Sair</strong>
        </a>
    </div>
</div>
