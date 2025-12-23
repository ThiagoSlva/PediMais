<?php
include 'includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';

verificar_login();

$msg = '';
$msg_tipo = '';

// --- CRUD LOGIC ---

// 1. Adicionar Usuário
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['adicionar'])) {
    try {
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        $nivel = $_POST['nivel_acesso'];
        $ativo = 1; // Padrão ativo ao criar

        // Verificar se email já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception("Este e-mail já está cadastrado.");
        }

        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, nivel_acesso, ativo) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $email, $senha, $nivel, $ativo]);

        $msg = 'Usuário adicionado com sucesso!';
        $msg_tipo = 'success';
    } catch (Exception $e) {
        $msg = 'Erro ao adicionar usuário: ' . $e->getMessage();
        $msg_tipo = 'danger';
    }
}

// 2. Editar Usuário
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar_usuario'])) {
    try {
        $id = (int)$_POST['usuario_id'];
        $nome = $_POST['editar_nome'];
        $email = $_POST['editar_email'];
        $nivel = $_POST['editar_nivel_acesso'];
        $ativo = isset($_POST['editar_ativo']) ? 1 : 0;

        // Verificar se email já existe (exceto para o próprio usuário)
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            throw new Exception("Este e-mail já está em uso por outro usuário.");
        }

        // Se senha foi informada, atualiza. Senão, mantém.
        if (!empty($_POST['editar_senha'])) {
            $senha = password_hash($_POST['editar_senha'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, senha = ?, nivel_acesso = ?, ativo = ? WHERE id = ?");
            $stmt->execute([$nome, $email, $senha, $nivel, $ativo, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, nivel_acesso = ?, ativo = ? WHERE id = ?");
            $stmt->execute([$nome, $email, $nivel, $ativo, $id]);
        }

        $msg = 'Usuário atualizado com sucesso!';
        $msg_tipo = 'success';
    } catch (Exception $e) {
        $msg = 'Erro ao atualizar usuário: ' . $e->getMessage();
        $msg_tipo = 'danger';
    }
}

// 3. Deletar Usuário
if (isset($_GET['deletar'])) {
    try {
        $id = (int)$_GET['deletar'];
        
        // Impedir deletar o próprio usuário logado
        if ($id == $_SESSION['usuario_id']) {
            throw new Exception("Você não pode deletar sua própria conta.");
        }

        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);

        $msg = 'Usuário deletado com sucesso!';
        $msg_tipo = 'success';
    } catch (Exception $e) {
        $msg = 'Erro ao deletar usuário: ' . $e->getMessage();
        $msg_tipo = 'danger';
    }
}

// Buscar todos os usuários
$stmt = $pdo->query("SELECT * FROM usuarios ORDER BY nome ASC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<style>
/* Dark mode support for usuarios page */
[data-theme="dark"] .card,
html[data-theme="dark"] .card {
    background-color: var(--base-soft, #1e1e2e) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

[data-theme="dark"] .card.radius-12,
html[data-theme="dark"] .card.radius-12 {
    background-color: var(--base-soft, #1e1e2e) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

/* Forçar background escuro nos headers - MUITO ESPECÍFICO */
[data-theme="dark"] div.card-header,
[data-theme="dark"] .card > .card-header,
[data-theme="dark"] .card .card-header,
[data-theme="dark"] .card-header.bg-base,
[data-theme="dark"] .card-header.border-bottom,
html[data-theme="dark"] div.card-header,
html[data-theme="dark"] .card > .card-header,
html[data-theme="dark"] .card .card-header,
html[data-theme="dark"] .card-header.bg-base,
html[data-theme="dark"] .card-header.border-bottom {
    background-color: #1a1a2e !important;
    background: #1a1a2e !important;
    background-image: none !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

/* Forçar texto branco em todos os elementos do header */
[data-theme="dark"] .card-header *,
[data-theme="dark"] .card-header h6,
[data-theme="dark"] .card-header .text-lg,
[data-theme="dark"] .card-header .fw-semibold,
[data-theme="dark"] .card-header .mb-0,
html[data-theme="dark"] .card-header *,
html[data-theme="dark"] .card-header h6,
html[data-theme="dark"] .card-header .text-lg,
html[data-theme="dark"] .card-header .fw-semibold,
html[data-theme="dark"] .card-header .mb-0 {
    color: rgba(255, 255, 255, 0.9) !important;
    background-color: transparent !important;
    background: transparent !important;
}

/* Ícones nos headers - manter cores temáticas mas garantir visibilidade */
[data-theme="dark"] .card-header iconify-icon.text-primary-600,
[data-theme="dark"] .card-header iconify-icon.text-success-600,
[data-theme="dark"] .card-header iconify-icon.text-warning-600,
[data-theme="dark"] .card-header iconify-icon.text-info-600,
html[data-theme="dark"] .card-header iconify-icon.text-primary-600,
html[data-theme="dark"] .card-header iconify-icon.text-success-600,
html[data-theme="dark"] .card-header iconify-icon.text-warning-600,
html[data-theme="dark"] .card-header iconify-icon.text-info-600 {
    opacity: 0.9;
}

[data-theme="dark"] .card-body,
html[data-theme="dark"] .card-body {
    background-color: var(--base-soft, #1e1e2e) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}


[data-theme="dark"] .form-control,
[data-theme="dark"] .form-select,
html[data-theme="dark"] .form-control,
html[data-theme="dark"] .form-select {
    background-color: rgba(255, 255, 255, 0.05) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .form-control:focus,
[data-theme="dark"] .form-select:focus,
html[data-theme="dark"] .form-control:focus,
html[data-theme="dark"] .form-select:focus {
    background-color: rgba(255, 255, 255, 0.08) !important;
    border-color: #487FFF !important;
    color: rgba(255, 255, 255, 0.9) !important;
    box-shadow: 0 0 0 0.2rem rgba(72, 127, 255, 0.25);
}

[data-theme="dark"] .form-control::placeholder,
html[data-theme="dark"] .form-control::placeholder {
    color: rgba(255, 255, 255, 0.5) !important;
    opacity: 0.6;
}

[data-theme="dark"] .form-label,
html[data-theme="dark"] .form-label {
    color: rgba(255, 255, 255, 0.9) !important;
    font-weight: 600;
}

[data-theme="dark"] .table,
html[data-theme="dark"] .table {
    color: rgba(255, 255, 255, 0.9) !important;
}

/* Estilos MUITO específicos para o thead - forçar background escuro */
[data-theme="dark"] .table thead,
[data-theme="dark"] .table thead th,
[data-theme="dark"] table thead,
[data-theme="dark"] table thead th,
[data-theme="dark"] .table thead tr,
[data-theme="dark"] .table thead tr th,
[data-theme="dark"] .card .table thead th,
[data-theme="dark"] .card-body .table thead th,
[data-theme="dark"] .card-body .table thead tr th,
[data-theme="dark"] .table-responsive .table thead th,
html[data-theme="dark"] .table thead,
html[data-theme="dark"] .table thead th,
html[data-theme="dark"] table thead,
html[data-theme="dark"] table thead th,
html[data-theme="dark"] .table thead tr,
html[data-theme="dark"] .table thead tr th,
html[data-theme="dark"] .card .table thead th,
html[data-theme="dark"] .card-body .table thead th,
html[data-theme="dark"] .card-body .table thead tr th,
html[data-theme="dark"] .table-responsive .table thead th {
    background-color: #1a1a2e !important;
    background: #1a1a2e !important;
    background-image: none !important;
    color: rgba(255, 255, 255, 0.9) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    font-weight: 600 !important;
}

/* Forçar todos os elementos dentro do thead */
[data-theme="dark"] .table thead *,
[data-theme="dark"] .table thead th *,
[data-theme="dark"] .card .table thead th,
[data-theme="dark"] .card-body .table thead th,
[data-theme="dark"] .table-responsive .table thead th,
html[data-theme="dark"] .table thead *,
html[data-theme="dark"] .table thead th *,
html[data-theme="dark"] .card .table thead th,
html[data-theme="dark"] .card-body .table thead th,
html[data-theme="dark"] .table-responsive .table thead th {
    background-color: transparent !important;
    background: transparent !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

/* Sobrescrever qualquer estilo inline ou classe que force background branco */
[data-theme="dark"] .table thead[style*="background"],
[data-theme="dark"] .table thead th[style*="background"],
html[data-theme="dark"] .table thead[style*="background"],
html[data-theme="dark"] .table thead th[style*="background"] {
    background-color: #1a1a2e !important;
    background: #1a1a2e !important;
}

[data-theme="dark"] .table tbody td,
html[data-theme="dark"] .table tbody td {
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .table-striped > tbody > tr:nth-of-type(odd) > td,
html[data-theme="dark"] .table-striped > tbody > tr:nth-of-type(odd) > td {
    background-color: rgba(255, 255, 255, 0.02) !important;
}

[data-theme="dark"] .table-striped > tbody > tr:nth-of-type(even) > td,
html[data-theme="dark"] .table-striped > tbody > tr:nth-of-type(even) > td {
    background-color: rgba(255, 255, 255, 0.05) !important;
}

[data-theme="dark"] .table tbody tr:hover,
html[data-theme="dark"] .table tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.08) !important;
}

[data-theme="dark"] .btn-outline-primary,
html[data-theme="dark"] .btn-outline-primary {
    border-color: #487FFF !important;
    color: #487FFF !important;
}

[data-theme="dark"] .btn-outline-primary:hover,
html[data-theme="dark"] .btn-outline-primary:hover {
    background-color: #487FFF !important;
    border-color: #487FFF !important;
    color: white !important;
}

[data-theme="dark"] .btn-danger,
html[data-theme="dark"] .btn-danger {
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
}

[data-theme="dark"] .btn-danger:hover,
html[data-theme="dark"] .btn-danger:hover {
    background-color: #c82333 !important;
    border-color: #bd2130 !important;
}

[data-theme="dark"] .text-secondary-light,
html[data-theme="dark"] .text-secondary-light {
    color: rgba(255, 255, 255, 0.6) !important;
}

[data-theme="dark"] .modal-content,
html[data-theme="dark"] .modal-content {
    background-color: var(--base-soft, #1e1e2e) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

[data-theme="dark"] .modal-header,
html[data-theme="dark"] .modal-header {
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .modal-body,
html[data-theme="dark"] .modal-body {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .modal-footer,
html[data-theme="dark"] .modal-footer {
    border-color: rgba(255, 255, 255, 0.1) !important;
}

[data-theme="dark"] .form-check-label,
html[data-theme="dark"] .form-check-label {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] small,
html[data-theme="dark"] small {
    color: rgba(255, 255, 255, 0.6) !important;
}

[data-theme="dark"] .btn-outline-secondary,
html[data-theme="dark"] .btn-outline-secondary {
    border-color: rgba(255, 255, 255, 0.2) !important;
    color: rgba(255, 255, 255, 0.8) !important;
}

[data-theme="dark"] .btn-outline-secondary:hover,
html[data-theme="dark"] .btn-outline-secondary:hover {
    background-color: rgba(255, 255, 255, 0.1) !important;
    border-color: rgba(255, 255, 255, 0.3) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .btn-close,
html[data-theme="dark"] .btn-close {
    filter: invert(1);
    opacity: 0.7;
}

[data-theme="dark"] .btn-close:hover,
html[data-theme="dark"] .btn-close:hover {
    opacity: 1;
}

[data-theme="dark"] .alert,
html[data-theme="dark"] .alert {
    border-color: rgba(255, 255, 255, 0.1) !important;
}

/* Melhorias visuais */
.table {
    border-collapse: separate;
    border-spacing: 0;
}

.table thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    padding: 12px 16px;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table tbody td {
    padding: 14px 16px;
    vertical-align: middle;
}

.table tbody tr {
    transition: background-color 0.2s ease;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.875rem;
    border-radius: 6px;
    font-weight: 500;
}

.badge {
    padding: 6px 12px;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
</style>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Gerenciar Usuários</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Usuários</li>
        </ul>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_tipo; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row gy-4">
        <div class="col-lg-5">
            <div class="card radius-12">
                <div class="card-header border-bottom bg-base py-16 px-24">
                    <h6 class="text-lg fw-semibold mb-0 d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:user-plus-bold-duotone" class="text-success-600"></iconify-icon>
                        Adicionar Novo Usuário
                    </h6>
                </div>
                <div class="card-body p-24">
                    <form method="POST">
                        <?php 
                        // Generate CSRF token if not exists
                        if (!isset($_SESSION['csrf_token'])) {
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        }
                        ?>
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">                    
                        <div class="mb-20">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Nome Completo *</label>
                            <input type="text" name="nome" class="form-control radius-8" required placeholder="Digite o nome completo">
                        </div>
                        
                        <div class="mb-20">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">E-mail *</label>
                            <input type="email" name="email" class="form-control radius-8" required placeholder="usuario@exemplo.com">
                        </div>
                        
                        <div class="mb-20">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Senha *</label>
                            <input type="password" name="senha" class="form-control radius-8" required minlength="6" placeholder="Mínimo 6 caracteres">
                            <small class="text-secondary-light">Mínimo 6 caracteres</small>
                        </div>
                        
                        <div class="mb-20">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Nível de Acesso *</label>
                            <select name="nivel_acesso" class="form-select radius-8" required>
                                <option value="admin">👑 Administrador</option>
                                <option value="gerente">📊 Gerente</option>
                                <option value="cozinha" selected>👨‍🍳 Cozinha</option>
                                <option value="entregador">🚚 Entregador</option>
                            </select>
                            <small class="text-secondary-light d-block mt-2">
                                <strong>Cozinha:</strong> Acesso ao Kanban, só pode marcar "Em Preparo"<br>
                                <strong>Entregador:</strong> Vê pedidos em entrega, pode marcar "Entregue"
                            </small>
                        </div>
                        
                        <button type="submit" name="adicionar" class="btn btn-primary-600 w-100 radius-8 px-20 py-11 d-flex align-items-center justify-content-center gap-2">
                            <iconify-icon icon="solar:add-circle-bold-duotone" style="font-size: 1.1em; line-height: 1;"></iconify-icon>
                            <span>Adicionar Usuário</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-7">
            <div class="card radius-12">
                <div class="card-header border-bottom bg-base py-16 px-24">
                    <h6 class="text-lg fw-semibold mb-0 d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:users-group-rounded-bold-duotone" class="text-primary-600"></iconify-icon>
                        Lista de Usuários
                    </h6>
                </div>
                <div class="card-body p-24">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="min-width: 150px;">Nome</th>
                                    <th style="min-width: 200px;">E-mail</th>
                                    <th style="min-width: 120px;">Nível</th>
                                    <th style="min-width: 100px;">Status</th>
                                    <th style="min-width: 180px;" class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $u): 
                                    $inicial = strtoupper(substr($u['nome'], 0, 1));
                                    $nivelClass = 'bg-secondary-focus text-secondary-main';
                                    $nivelIcon = 'solar:user-bold';
                                    $nivel = $u['nivel'] ?? $u['nivel_acesso'] ?? 'user';
                                    $nivelNome = ucfirst($nivel);
                                    
                                    switch($nivel) {
                                        case 'admin': 
                                            $nivelClass = 'bg-danger-focus text-danger-main'; 
                                            $nivelIcon = 'solar:crown-bold-duotone';
                                            $nivelNome = 'Admin';
                                            break;
                                        case 'gerente': 
                                            $nivelClass = 'bg-primary-focus text-primary-main'; 
                                            $nivelIcon = 'solar:chart-bold-duotone';
                                            break;
                                        case 'cozinha': 
                                            $nivelClass = 'bg-warning-focus text-warning-main'; 
                                            $nivelIcon = 'solar:chef-hat-bold-duotone';
                                            break;
                                        case 'entregador': 
                                            $nivelClass = 'bg-info-focus text-info-main'; 
                                            $nivelIcon = 'solar:delivery-bold-duotone';
                                            break;
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="w-40-px h-40-px bg-primary-600 rounded-circle d-flex align-items-center justify-content-center text-white fw-bold">
                                                <?php echo $inicial; ?>
                                            </div>
                                            <span class="fw-medium"><?php echo htmlspecialchars($u['nome']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-secondary-light"><?php echo htmlspecialchars($u['email']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $nivelClass; ?> d-inline-flex align-items-center gap-1">
                                            <iconify-icon icon="<?php echo $nivelIcon; ?>" style="font-size: 14px;"></iconify-icon> 
                                            <?php echo $nivelNome; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($u['ativo']): ?>
                                            <span class="badge bg-success-focus text-success-main d-inline-flex align-items-center gap-1">
                                                <iconify-icon icon="solar:check-circle-bold" style="font-size: 12px;"></iconify-icon>
                                                Ativo
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-focus text-danger-main d-inline-flex align-items-center gap-1">
                                                <iconify-icon icon="solar:close-circle-bold" style="font-size: 12px;"></iconify-icon>
                                                Inativo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 btn-editar-usuario"
                                                    data-id="<?php echo $u['id']; ?>"
                                                    data-nome="<?php echo htmlspecialchars($u['nome']); ?>"
                                                    data-email="<?php echo htmlspecialchars($u['email']); ?>"
                                                    data-nivel="<?php echo htmlspecialchars($nivel); ?>"
                                                    data-ativo="<?php echo $u['ativo']; ?>">
                                                <iconify-icon icon="solar:pen-bold" style="font-size: 14px; line-height: 1;"></iconify-icon>
                                                <span>Editar</span>
                                            </button>
                                            
                                            <?php if ($u['id'] == $_SESSION['usuario_id']): ?>
                                                <span class="badge bg-primary-focus text-primary-main">Você</span>
                                            <?php else: ?>
                                                <a href="?deletar=<?php echo $u['id']; ?>" 
                                                   class="btn btn-sm btn-danger d-inline-flex align-items-center gap-1"
                                                   onclick="return confirm('Tem certeza que deseja deletar este usuário?')">
                                                    <iconify-icon icon="solar:trash-bin-outline" style="font-size: 14px; line-height: 1;"></iconify-icon>
                                                    <span>Deletar</span>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Editar Usuário -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-labelledby="modalEditarUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="formEditarUsuario">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="editar_usuario" value="1">
                <input type="hidden" name="usuario_id" id="editar_usuario_id">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2" id="modalEditarUsuarioLabel">
                        <iconify-icon icon="solar:user-bold" class="text-primary"></iconify-icon>
                        Editar Usuário
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-20">
                        <label for="editar_nome" class="form-label fw-semibold text-primary-light text-sm mb-8">Nome *</label>
                        <input type="text" class="form-control radius-8" name="editar_nome" id="editar_nome" required>
                    </div>
                    <div class="mb-20">
                        <label for="editar_email" class="form-label fw-semibold text-primary-light text-sm mb-8">E-mail *</label>
                        <input type="email" class="form-control radius-8" name="editar_email" id="editar_email" required>
                    </div>
                    <div class="mb-20">
                        <label for="editar_nivel_acesso" class="form-label fw-semibold text-primary-light text-sm mb-8">Nível de Acesso *</label>
                        <select class="form-select radius-8" name="editar_nivel_acesso" id="editar_nivel_acesso" required>
                            <option value="admin">👑 Administrador</option>
                            <option value="gerente">📊 Gerente</option>
                            <option value="cozinha">👨‍🍳 Cozinha</option>
                            <option value="entregador">🚚 Entregador</option>
                        </select>
                    </div>
                    <div class="mb-20">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="editar_ativo" id="editar_ativo">
                            <label class="form-check-label fw-semibold" for="editar_ativo">Usuário ativo</label>
                        </div>
                    </div>
                    <div class="mb-20">
                        <label for="editar_senha" class="form-label fw-semibold text-primary-light text-sm mb-8">Nova Senha (opcional)</label>
                        <input type="password" class="form-control radius-8" name="editar_senha" id="editar_senha" minlength="6" placeholder="Deixe vazio para manter a atual">
                        <small class="text-secondary-light">Informe apenas se desejar alterar a senha do usuário (mínimo 6 caracteres).</small>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between border-top">
                    <button type="button" class="btn btn-outline-secondary radius-8" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-600 radius-8 px-20 py-11 d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:floppy-disk-bold-duotone" style="font-size: 1.1em; line-height: 1;"></iconify-icon>
                        <span>Salvar alterações</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editButtons = document.querySelectorAll('.btn-editar-usuario');
        const modalElement = document.getElementById('modalEditarUsuario');
        const modal = modalElement ? new bootstrap.Modal(modalElement) : null;

        editButtons.forEach(button => {
            button.addEventListener('click', () => {
                if (!modal) return;
                const id = button.getAttribute('data-id');
                const nome = button.getAttribute('data-nome') || '';
                const email = button.getAttribute('data-email') || '';
                const nivel = button.getAttribute('data-nivel') || 'cozinha';
                const ativo = button.getAttribute('data-ativo') === '1';

                document.getElementById('editar_usuario_id').value = id;
                document.getElementById('editar_nome').value = nome;
                document.getElementById('editar_email').value = email;
                document.getElementById('editar_nivel_acesso').value = nivel;
                document.getElementById('editar_ativo').checked = ativo;
                document.getElementById('editar_senha').value = '';

                modal.show();
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>