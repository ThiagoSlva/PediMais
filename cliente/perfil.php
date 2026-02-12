<?php
require_once '../includes/config.php';
require_once '../includes/validar_senha.php';
require_once '../includes/csrf.php';
require_once 'includes/auth.php';

verificar_login_cliente();

$cliente_id = $_SESSION['cliente_id'];
$msg = '';
$msg_tipo = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validar_csrf()) {
        $msg = 'Token de segurança inválido. Recarregue a página.';
        $msg_tipo = 'danger';
    }
    else {
        $acao = $_POST['acao'] ?? '';

        if ($acao == 'atualizar_perfil') {
            $nome = trim($_POST['nome']);
            $email = trim($_POST['email']);
            $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone']);
            $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');

            if (empty($nome)) {
                $msg = 'O nome é obrigatório';
                $msg_tipo = 'danger';
            }
            else {
                try {
                    // Check if CPF column exists, if not create it
                    $stmt = $pdo->query("SHOW COLUMNS FROM clientes LIKE 'cpf'");
                    if ($stmt->rowCount() == 0) {
                        $pdo->exec("ALTER TABLE clientes ADD COLUMN cpf VARCHAR(14) NULL AFTER email");
                    }

                    $stmt = $pdo->prepare("UPDATE clientes SET nome = ?, email = ?, telefone = ?, cpf = ? WHERE id = ?");
                    $stmt->execute([$nome, $email, $telefone, $cpf ?: null, $cliente_id]);

                    $msg = 'Perfil atualizado com sucesso!';
                    $msg_tipo = 'success';
                    $_SESSION['cliente_nome'] = $nome;
                }
                catch (PDOException $e) {
                    $msg = 'Erro ao atualizar: ' . $e->getMessage();
                    $msg_tipo = 'danger';
                }
            }
        }
        elseif ($acao == 'alterar_senha') {
            $senha_atual = $_POST['senha_atual'];
            $nova_senha = $_POST['nova_senha'];
            $confirma_senha = $_POST['confirma_senha'];

            if ($nova_senha !== $confirma_senha) {
                $msg = 'As senhas não conferem!';
                $msg_tipo = 'danger';
            }
            elseif (!senha_atende_requisitos($nova_senha)) {
                $erros_senha = validar_senha($nova_senha);
                $msg = implode(' ', $erros_senha);
                $msg_tipo = 'danger';
            }
            else {
                $stmt = $pdo->prepare("SELECT senha FROM clientes WHERE id = ?");
                $stmt->execute([$cliente_id]);
                $cliente_check = $stmt->fetch();

                if ($cliente_check && password_verify($senha_atual, $cliente_check['senha'])) {
                    $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE clientes SET senha = ? WHERE id = ?");
                    $stmt->execute([$hash, $cliente_id]);
                    $msg = 'Senha alterada com sucesso!';
                    $msg_tipo = 'success';
                }
                else {
                    $msg = 'Senha atual incorreta!';
                    $msg_tipo = 'danger';
                }
            }
        }
    } // fecha else validar_csrf
}

// Get client data
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

// Get initials for avatar
$initials = 'C';
if (!empty($cliente['nome'])) {
    $parts = explode(' ', $cliente['nome']);
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $initials .= strtoupper(substr(end($parts), 0, 1));
    }
}

$page_title = 'Meu Perfil';
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4 fade-in">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 0.875rem;">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color: var(--primary);">Início</a></li>
                <li class="breadcrumb-item active" style="color: var(--gray-500);">Perfil</li>
            </ol>
        </nav>
        <h4 class="mb-0" style="color: var(--gray-900);">
            <i class="fa-solid fa-user me-2 text-primary"></i>Meu Perfil
        </h4>
    </div>
</div>

<!-- Alert Messages -->
<?php if ($msg): ?>
<div class="alert alert-<?php echo $msg_tipo; ?> alert-dismissible fade show mb-4 fade-in" role="alert" 
     style="border-radius: var(--radius-md); border: none; <?php echo $msg_tipo === 'success' ? 'background: var(--success-light); color: #065F46;' : 'background: var(--danger-light); color: #991B1B;'; ?>">
    <i class="fa-solid <?php echo $msg_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
    <?php echo $msg; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php
endif; ?>

<div class="row g-4">
    <!-- Left Column - Profile Photo & Info -->
    <div class="col-lg-4">
        <div class="card-premium text-center mb-4 fade-in">
            <div class="card-body py-5">
                <!-- Photo Upload -->
                <div class="photo-upload mb-4" id="photoUploadContainer">
                    <?php if (!empty($cliente['foto_perfil']) && file_exists('../' . $cliente['foto_perfil'])): ?>
                        <img src="../<?php echo htmlspecialchars($cliente['foto_perfil']); ?>" 
                             alt="Foto de perfil" class="avatar avatar-xl" id="avatarPreview">
                    <?php
else: ?>
                        <div class="avatar-initials xl" id="avatarInitials"><?php echo $initials; ?></div>
                    <?php
endif; ?>
                    <div class="upload-overlay" onclick="document.getElementById('fotoInput').click();">
                        <i class="fa-solid fa-camera"></i>
                    </div>
                </div>
                
                <input type="file" id="fotoInput" accept="image/*" style="display: none;" onchange="uploadFoto(this)">
                
                <h5 class="mb-1" style="color: var(--gray-900);"><?php echo htmlspecialchars($cliente['nome']); ?></h5>
                <p class="mb-3" style="color: var(--gray-500); font-size: 0.875rem;">
                    <i class="fa-solid fa-phone me-1"></i>
                    <?php echo htmlspecialchars($cliente['telefone']); ?>
                </p>
                
                <button class="btn btn-sm btn-outline-premium" onclick="document.getElementById('fotoInput').click();">
                    <i class="fa-solid fa-camera me-2"></i>Alterar Foto
                </button>
                
                <hr class="my-4" style="border-color: var(--gray-100);">
                
                <div class="text-start">
                    <div class="d-flex justify-content-between mb-2" style="font-size: 0.875rem;">
                        <span style="color: var(--gray-500);">Membro desde</span>
                        <span style="color: var(--gray-700);"><?php echo date('d/m/Y', strtotime($cliente['criado_em'])); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2" style="font-size: 0.875rem;">
                        <span style="color: var(--gray-500);">Total de pedidos</span>
                        <span style="color: var(--gray-700);"><?php echo $cliente['total_pedidos']; ?></span>
                    </div>
                    <div class="d-flex justify-content-between" style="font-size: 0.875rem;">
                        <span style="color: var(--gray-500);">Total gasto</span>
                        <span style="color: var(--primary); font-weight: 600;">R$ <?php echo number_format($cliente['valor_total_gasto'], 2, ',', '.'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Column - Forms -->
    <div class="col-lg-8">
        <!-- Personal Data -->
        <div class="card-premium mb-4 fade-in" style="animation-delay: 0.1s;">
            <div class="card-header">
                <i class="fa-solid fa-user-pen me-2 text-primary"></i>Dados Pessoais
            </div>
            <div class="card-body">
                <form method="POST" action="" class="form-premium">
                    <?php echo campo_csrf(); ?>
                    <input type="hidden" name="acao" value="atualizar_perfil">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nome Completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nome" 
                                   value="<?php echo htmlspecialchars($cliente['nome']); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Telefone (WhatsApp) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control phone-mask" name="telefone" 
                                   value="<?php echo htmlspecialchars($cliente['telefone']); ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?php echo htmlspecialchars($cliente['email'] ?? ''); ?>"
                                   placeholder="exemplo@email.com">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">CPF <span class="text-muted">(opcional)</span></label>
                            <input type="text" class="form-control cpf-mask" name="cpf" 
                                   value="<?php echo htmlspecialchars($cliente['cpf'] ?? ''); ?>"
                                   placeholder="000.000.000-00">
                            <small style="color: var(--gray-500);">Usado para notas fiscais</small>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-premium btn-primary-gradient">
                            <i class="fa-solid fa-check me-2"></i>Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Change Password -->
        <div class="card-premium fade-in" style="animation-delay: 0.2s;">
            <div class="card-header">
                <i class="fa-solid fa-lock me-2 text-warning"></i>Alterar Senha
            </div>
            <div class="card-body">
                <form method="POST" action="" class="form-premium" id="formSenha">
                    <?php echo campo_csrf(); ?>
                    <input type="hidden" name="acao" value="alterar_senha">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Senha Atual <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="senha_atual" id="senhaAtual" required>
                                <button type="button" class="btn btn-glass" onclick="togglePassword('senhaAtual')">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Nova Senha <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="nova_senha" id="novaSenha" 
                                       minlength="6" required>
                                <button type="button" class="btn btn-glass" onclick="togglePassword('novaSenha')">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <small style="color: var(--gray-500);">Mín 8 chars, maiúscula, minúscula e número</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Confirmar Nova Senha <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="confirma_senha" id="confirmaSenha" 
                                       minlength="6" required>
                                <button type="button" class="btn btn-glass" onclick="togglePassword('confirmaSenha')">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-premium" style="background: var(--warning); color: white;">
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

function uploadFoto(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validate file type
        if (!file.type.match(/image\/(jpeg|jpg|png|webp)/)) {
            showToast('Formato inválido. Use JPG, PNG ou WebP.', 'error');
            return;
        }
        
        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            showToast('Imagem muito grande. Máximo 5MB.', 'error');
            return;
        }
        
        // Show loading
        const container = document.getElementById('photoUploadContainer');
        const originalContent = container.innerHTML;
        container.innerHTML = `
            <div class="avatar-initials xl">
                <i class="fa-solid fa-spinner fa-spin"></i>
            </div>
            <div class="upload-overlay" style="opacity: 0.5;">
                <i class="fa-solid fa-spinner fa-spin"></i>
            </div>
        `;
        
        // Upload file
        const formData = new FormData();
        formData.append('foto', file);
        
        fetch('api/upload_foto.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Foto atualizada com sucesso!', 'success');
                // Update avatar
                container.innerHTML = `
                    <img src="../${data.foto_url}?t=${Date.now()}" alt="Foto de perfil" class="avatar avatar-xl" id="avatarPreview">
                    <div class="upload-overlay" onclick="document.getElementById('fotoInput').click();">
                        <i class="fa-solid fa-camera"></i>
                    </div>
                `;
            } else {
                showToast(data.message || 'Erro ao fazer upload', 'error');
                container.innerHTML = originalContent;
            }
        })
        .catch(error => {
            showToast('Erro ao fazer upload da foto', 'error');
            container.innerHTML = originalContent;
        });
    }
}

// Password confirmation validation
document.getElementById('formSenha').addEventListener('submit', function(e) {
    const nova = document.getElementById('novaSenha').value;
    const confirma = document.getElementById('confirmaSenha').value;
    
    if (nova !== confirma) {
        e.preventDefault();
        showToast('As senhas não conferem!', 'error');
    }
});
</script>

<?php include 'includes/footer.php'; ?>