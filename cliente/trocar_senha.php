<?php
require_once '../includes/config.php';
require_once '../includes/validar_senha.php';
require_once '../includes/csrf.php';
require_once 'includes/auth.php';

verificar_login_cliente();

$cliente_id = $_SESSION['cliente_id'];
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validar_csrf()) {
        $erro = 'Token de segurança inválido. Recarregue a página.';
    }
    else {
        $senha_atual = $_POST['senha_atual'] ?? '';
        $nova_senha = $_POST['nova_senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';

        if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
            $erro = 'Por favor, preencha todos os campos.';
        }
        elseif (!senha_atende_requisitos($nova_senha)) {
            $erros_senha = validar_senha($nova_senha);
            $erro = implode(' ', $erros_senha);
        }
        elseif ($nova_senha !== $confirmar_senha) {
            $erro = 'As senhas não conferem.';
        }
        else {
            // Verificar senha atual
            $stmt = $pdo->prepare("SELECT senha FROM clientes WHERE id = ?");
            $stmt->execute([$cliente_id]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cliente || !password_verify($senha_atual, $cliente['senha'])) {
                $erro = 'Senha atual incorreta.';
            }
            else {
                // Atualizar senha
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE clientes SET senha = ? WHERE id = ?");

                if ($stmt->execute([$senha_hash, $cliente_id])) {
                    $sucesso = 'Senha alterada com sucesso!';
                }
                else {
                    $erro = 'Erro ao alterar a senha. Tente novamente.';
                }
            }
        }
    } // fecha else validar_csrf
}

$page_title = 'Trocar Senha';
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4 fade-in">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 0.875rem;">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color: var(--primary);">Início</a></li>
                <li class="breadcrumb-item"><a href="perfil.php" class="text-decoration-none" style="color: var(--primary);">Perfil</a></li>
                <li class="breadcrumb-item active" style="color: var(--gray-500);">Trocar Senha</li>
            </ol>
        </nav>
        <h4 class="mb-0" style="color: var(--gray-900);">
            <i class="fa-solid fa-key me-2 text-warning"></i>Trocar Senha
        </h4>
    </div>
</div>

<!-- Alert Messages -->
<?php if ($erro): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4 fade-in" role="alert" 
     style="border-radius: var(--radius-md); border: none; background: var(--danger-light, #FEE2E2); color: #991B1B;">
    <i class="fa-solid fa-exclamation-circle me-2"></i>
    <?php echo $erro; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php
endif; ?>

<?php if ($sucesso): ?>
<div class="alert alert-success alert-dismissible fade show mb-4 fade-in" role="alert" 
     style="border-radius: var(--radius-md); border: none; background: var(--success-light, #D1FAE5); color: #065F46;">
    <i class="fa-solid fa-check-circle me-2"></i>
    <?php echo $sucesso; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php
endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card-premium fade-in">
            <div class="card-header">
                <i class="fa-solid fa-lock me-2 text-warning"></i>Alterar Senha
            </div>
            <div class="card-body">
                <form method="POST" class="form-premium" id="formTrocarSenha">
                    <?php echo campo_csrf(); ?>
                    <div class="mb-3">
                        <label class="form-label">Senha Atual <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="senha_atual" id="senhaAtual" required>
                            <button type="button" class="btn btn-glass" onclick="togglePassword('senhaAtual')">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <hr class="my-4" style="border-color: var(--gray-100);">

                    <div class="mb-2 p-3" style="background: var(--gray-100, #f0f0f0); border-radius: 8px; font-size: 0.85rem; color: var(--gray-600, #666);">
                        <strong><i class="fa-solid fa-info-circle me-1"></i> Requisitos da nova senha:</strong>
                        <ul class="mb-0 mt-1 ps-3">
                            <li>Mínimo 8 caracteres</li>
                            <li>Pelo menos 1 letra maiúscula</li>
                            <li>Pelo menos 1 letra minúscula</li>
                            <li>Pelo menos 1 número</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nova Senha <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="nova_senha" id="novaSenha" 
                                   minlength="8" required>
                            <button type="button" class="btn btn-glass" onclick="togglePassword('novaSenha')">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Confirmar Nova Senha <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="confirmar_senha" id="confirmaSenha" 
                                   minlength="8" required>
                            <button type="button" class="btn btn-glass" onclick="togglePassword('confirmaSenha')">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="perfil.php" class="btn btn-outline-secondary flex-fill">
                            <i class="fa-solid fa-arrow-left me-2"></i>Voltar
                        </a>
                        <button type="submit" class="btn btn-premium flex-fill" style="background: var(--warning); color: white;">
                            <i class="fa-solid fa-key me-2"></i>Alterar Senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-solid fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa-solid fa-eye';
    }
}

// Validação no front antes de enviar
document.getElementById('formTrocarSenha').addEventListener('submit', function(e) {
    const nova = document.getElementById('novaSenha').value;
    const confirma = document.getElementById('confirmaSenha').value;
    
    if (nova !== confirma) {
        e.preventDefault();
        if (typeof showToast === 'function') {
            showToast('As senhas não conferem!', 'error');
        } else {
            alert('As senhas não conferem!');
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>