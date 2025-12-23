<?php
include 'includes/header.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<script>window.location.href="clientes.php";</script>';
    exit;
}

$id = $_GET['id'];
$msg = '';
$msg_type = '';

// Buscar dados do cliente
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    echo '<div class="alert alert-danger">Cliente não encontrado.</div>';
    include 'includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $cep = $_POST['cep'];
    $endereco = $_POST['endereco'];
    $numero = $_POST['numero'];
    $bairro = $_POST['bairro'];
    $cidade = $_POST['cidade'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $complemento = $_POST['complemento'];

    // Validação básica
    if (empty($nome) || empty($telefone)) {
        $msg = 'Por favor, preencha os campos obrigatórios (Nome, Telefone).';
        $msg_type = 'danger';
    } else {
        // Verificar se telefone já existe (excluindo o próprio cliente)
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE telefone = ? AND id != ?");
        $stmt->execute([$telefone, $id]);
        if ($stmt->rowCount() > 0) {
            $msg = 'Este telefone já está cadastrado para outro cliente.';
            $msg_type = 'danger';
        } else {
            // Atualizar senha se fornecida
            if (!empty($senha)) {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE clientes SET nome=?, telefone=?, email=?, senha=?, cep=?, rua=?, numero=?, bairro=?, cidade=?, estado=?, complemento=? WHERE id=?");
                $params = [$nome, $telefone, $email, $senha_hash, $cep, $endereco, $numero, $bairro, $cidade, $estado, $complemento, $id];
            } else {
                $stmt = $pdo->prepare("UPDATE clientes SET nome=?, telefone=?, email=?, cep=?, rua=?, numero=?, bairro=?, cidade=?, estado=?, complemento=? WHERE id=?");
                $params = [$nome, $telefone, $email, $cep, $endereco, $numero, $bairro, $cidade, $estado, $complemento, $id];
            }

            if ($stmt->execute($params)) {
                $msg = 'Cliente atualizado com sucesso!';
                $msg_type = 'success';
                // Atualizar dados na variável para refletir no formulário
                $cliente['nome'] = $nome;
                $cliente['telefone'] = $telefone;
                $cliente['email'] = $email;
                $cliente['cep'] = $cep;
                $cliente['rua'] = $endereco;
                $cliente['numero'] = $numero;
                $cliente['bairro'] = $bairro;
                $cliente['cidade'] = $cidade;
                $cliente['estado'] = $estado;
                $cliente['complemento'] = $complemento;
            } else {
                $msg = 'Erro ao atualizar cliente.';
                $msg_type = 'danger';
            }
        }
    }
}
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Editar Cliente</h6>
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
            <li class="fw-medium">Editar</li>
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
                        <input type="text" class="form-control radius-8" id="nome" name="nome" value="<?php echo htmlspecialchars($cliente['nome']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="telefone" class="form-label fw-semibold text-primary-light text-sm mb-8">Telefone (WhatsApp) <span class="text-danger-600">*</span></label>
                        <input type="text" class="form-control radius-8" id="telefone" name="telefone" value="<?php echo htmlspecialchars($cliente['telefone']); ?>" required placeholder="(00) 00000-0000">
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label fw-semibold text-primary-light text-sm mb-8">Email</label>
                        <input type="email" class="form-control radius-8" id="email" name="email" value="<?php echo htmlspecialchars($cliente['email']); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="senha" class="form-label fw-semibold text-primary-light text-sm mb-8">Senha (Deixe em branco para manter a atual)</label>
                        <input type="password" class="form-control radius-8" id="senha" name="senha">
                    </div>
                    
                    <div class="col-12">
                        <h6 class="fw-semibold mb-0 mt-3">Endereço</h6>
                        <hr>
                    </div>

                    <div class="col-md-3">
                        <label for="cep" class="form-label fw-semibold text-primary-light text-sm mb-8">CEP</label>
                        <input type="text" class="form-control radius-8" id="cep" name="cep" value="<?php echo htmlspecialchars($cliente['cep'] ?? ''); ?>">
                    </div>
                    <div class="col-md-7">
                        <label for="endereco" class="form-label fw-semibold text-primary-light text-sm mb-8">Rua/Avenida</label>
                        <input type="text" class="form-control radius-8" id="endereco" name="endereco" value="<?php echo htmlspecialchars($cliente['rua'] ?? $cliente['endereco'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="numero" class="form-label fw-semibold text-primary-light text-sm mb-8">Número</label>
                        <input type="text" class="form-control radius-8" id="numero" name="numero" value="<?php echo htmlspecialchars($cliente['numero'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="bairro" class="form-label fw-semibold text-primary-light text-sm mb-8">Bairro</label>
                        <input type="text" class="form-control radius-8" id="bairro" name="bairro" value="<?php echo htmlspecialchars($cliente['bairro'] ?? ''); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="cidade" class="form-label fw-semibold text-primary-light text-sm mb-8">Cidade</label>
                        <input type="text" class="form-control radius-8" id="cidade" name="cidade" value="<?php echo htmlspecialchars($cliente['cidade'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="estado" class="form-label fw-semibold text-primary-light text-sm mb-8">UF</label>
                        <input type="text" class="form-control radius-8" id="estado" name="estado" value="<?php echo htmlspecialchars($cliente['estado'] ?? ''); ?>" maxlength="2" style="text-transform: uppercase;">
                    </div>
                    <div class="col-md-6">
                        <label for="complemento" class="form-label fw-semibold text-primary-light text-sm mb-8">Complemento</label>
                        <input type="text" class="form-control radius-8" id="complemento" name="complemento" value="<?php echo htmlspecialchars($cliente['complemento'] ?? ''); ?>">
                    </div>

                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary-600 radius-8 px-20 py-11">Salvar Alterações</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Endereços Adicionais Salvos -->
    <?php
    // Buscar endereços salvos da tabela cliente_enderecos
    $stmtEnderecos = $pdo->prepare("SELECT * FROM cliente_enderecos WHERE cliente_id = ? ORDER BY principal DESC, criado_em DESC");
    $stmtEnderecos->execute([$id]);
    $enderecosSalvos = $stmtEnderecos->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <?php if (!empty($enderecosSalvos)): ?>
    <div class="card h-100 p-0 radius-12 mt-4">
        <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center justify-content-between">
            <h6 class="text-lg mb-0">Endereços Salvos (<?php echo count($enderecosSalvos); ?>)</h6>
        </div>
        <div class="card-body p-24">
            <div class="table-responsive">
                <table class="table table-borderless">
                    <thead>
                        <tr>
                            <th>Apelido</th>
                            <th>Endereço</th>
                            <th>Bairro</th>
                            <th>Cidade</th>
                            <th>Principal</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enderecosSalvos as $end): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($end['apelido'] ?: 'Sem apelido'); ?></strong></td>
                            <td><?php echo htmlspecialchars($end['rua'] . ', ' . $end['numero']); ?></td>
                            <td><?php echo htmlspecialchars($end['bairro']); ?></td>
                            <td><?php echo htmlspecialchars($end['cidade'] . '/' . $end['estado']); ?></td>
                            <td>
                                <?php if ($end['principal']): ?>
                                    <span class="badge bg-success">Sim</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Não</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="cliente_endereco_delete.php?id=<?php echo $end['id']; ?>&cliente_id=<?php echo $id; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Tem certeza que deseja excluir este endereço?');">
                                    <iconify-icon icon="solar:trash-bin-trash-outline"></iconify-icon>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
    // Máscara de telefone (JavaScript puro)
    document.getElementById('telefone').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 11) value = value.slice(0, 11);
        if (value.length > 6) {
            value = `(${value.slice(0, 2)}) ${value.slice(2, 7)}-${value.slice(7)}`;
        } else if (value.length > 2) {
            value = `(${value.slice(0, 2)}) ${value.slice(2)}`;
        } else if (value.length > 0) {
            value = `(${value}`;
        }
        e.target.value = value;
    });
    
    // Máscara de CEP (JavaScript puro)
    document.getElementById('cep').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 8) value = value.slice(0, 8);
        if (value.length > 5) {
            value = `${value.slice(0, 5)}-${value.slice(5)}`;
        }
        e.target.value = value;
    });
</script>

<?php include 'includes/footer.php'; ?>