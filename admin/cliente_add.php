<?php
include 'includes/header.php';

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $cep = $_POST['cep'] ?? '';
    $rua = $_POST['endereco'] ?? ''; // Campo do form é "endereco" mas coluna é "rua"
    $numero = $_POST['numero'] ?? '';
    $bairro = $_POST['bairro'] ?? '';
    $complemento = $_POST['complemento'] ?? '';
    $referencia = $_POST['referencia'] ?? '';

    // Montar endereço principal (texto completo para exibição)
    $endereco_principal = '';
    if ($rua) {
        $endereco_principal = $rua;
        if ($numero) $endereco_principal .= ', ' . $numero;
        if ($bairro) $endereco_principal .= ' - ' . $bairro;
    }

    // Validação básica
    if (empty($nome) || empty($telefone) || empty($senha)) {
        $msg = 'Por favor, preencha os campos obrigatórios (Nome, Telefone, Senha).';
        $msg_type = 'danger';
    } else {
        // Verificar se telefone já existe
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE telefone = ?");
        $stmt->execute([$telefone]);
        if ($stmt->rowCount() > 0) {
            $msg = 'Este telefone já está cadastrado.';
            $msg_type = 'danger';
        } else {
            // Hash da senha
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // Inserir cliente com as colunas corretas
            $sql = "INSERT INTO clientes (nome, telefone, email, senha, cep, rua, numero, bairro, complemento, endereco_principal, ativo, criado_em) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$nome, $telefone, $email, $senha_hash, $cep, $rua, $numero, $bairro, $complemento, $endereco_principal])) {
                $msg = 'Cliente cadastrado com sucesso!';
                $msg_type = 'success';
                // Limpar campos
                $nome = $telefone = $email = $senha = $cep = $rua = $numero = $bairro = $complemento = $referencia = '';
            } else {
                $msg = 'Erro ao cadastrar cliente.';
                $msg_type = 'danger';
            }
        }
    }
}
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Adicionar Cliente</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">
                <a href="clientes.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    Clientes
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Adicionar</li>
        </ul>
    </div>

    <div class="card h-100 p-0 radius-12">
        <div class="card-body p-24">
            <?php if ($msg): ?>
                <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="row gy-4">
                    <div class="col-md-6">
                        <label for="nome" class="form-label fw-semibold text-primary-light text-sm mb-8">Nome Completo <span class="text-danger-600">*</span></label>
                        <input type="text" class="form-control radius-8" id="nome" name="nome" value="<?php echo htmlspecialchars($nome ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="telefone" class="form-label fw-semibold text-primary-light text-sm mb-8">Telefone (WhatsApp) <span class="text-danger-600">*</span></label>
                        <input type="text" class="form-control radius-8" id="telefone" name="telefone" value="<?php echo htmlspecialchars($telefone ?? ''); ?>" required placeholder="(00) 00000-0000">
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label fw-semibold text-primary-light text-sm mb-8">Email</label>
                        <input type="email" class="form-control radius-8" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="senha" class="form-label fw-semibold text-primary-light text-sm mb-8">Senha <span class="text-danger-600">*</span></label>
                        <input type="password" class="form-control radius-8" id="senha" name="senha" required>
                    </div>
                    
                    <div class="col-12">
                        <h6 class="fw-semibold mb-0 mt-3">Endereço</h6>
                        <hr>
                    </div>

                    <div class="col-md-3">
                        <label for="cep" class="form-label fw-semibold text-primary-light text-sm mb-8">CEP</label>
                        <input type="text" class="form-control radius-8" id="cep" name="cep" value="<?php echo htmlspecialchars($cep ?? ''); ?>">
                    </div>
                    <div class="col-md-7">
                        <label for="endereco" class="form-label fw-semibold text-primary-light text-sm mb-8">Rua/Avenida</label>
                        <input type="text" class="form-control radius-8" id="endereco" name="endereco" value="<?php echo htmlspecialchars($endereco ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="numero" class="form-label fw-semibold text-primary-light text-sm mb-8">Número</label>
                        <input type="text" class="form-control radius-8" id="numero" name="numero" value="<?php echo htmlspecialchars($numero ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="bairro" class="form-label fw-semibold text-primary-light text-sm mb-8">Bairro</label>
                        <input type="text" class="form-control radius-8" id="bairro" name="bairro" value="<?php echo htmlspecialchars($bairro ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="complemento" class="form-label fw-semibold text-primary-light text-sm mb-8">Complemento</label>
                        <input type="text" class="form-control radius-8" id="complemento" name="complemento" value="<?php echo htmlspecialchars($complemento ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="referencia" class="form-label fw-semibold text-primary-light text-sm mb-8">Ponto de Referência</label>
                        <input type="text" class="form-control radius-8" id="referencia" name="referencia" value="<?php echo htmlspecialchars($referencia ?? ''); ?>">
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary-600 radius-8 px-20 py-11">Cadastrar Cliente</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/lib/jquery-3.7.1.min.js"></script>
<script src="assets/js/lib/jquery.mask.min.js"></script>
<script>
    $(document).ready(function(){
        $('#telefone').mask('(00) 00000-0000');
        $('#cep').mask('00000-000');
    });
</script>

<?php include 'includes/footer.php'; ?>