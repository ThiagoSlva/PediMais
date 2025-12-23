<?php
include 'includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';

verificar_login_cliente();

$cliente_id = $_SESSION['cliente_id'];
$msg = '';
$msg_tipo = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['acao']) && $_POST['acao'] == 'atualizar_perfil') {
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        // Telefone geralmente é fixo ou requer verificação, vamos permitir editar por enquanto
        $telefone = preg_replace('/[^0-9]/', '', $_POST['telefone']);
        
        try {
            $stmt = $pdo->prepare("UPDATE clientes SET nome = ?, email = ?, telefone = ? WHERE id = ?");
            $stmt->execute([$nome, $email, $telefone, $cliente_id]);
            $msg = 'Perfil atualizado com sucesso!';
            $msg_tipo = 'success';
            
            // Atualizar sessão se necessário
            $_SESSION['cliente_nome'] = $nome;
        } catch (PDOException $e) {
            $msg = 'Erro ao atualizar: ' . $e->getMessage();
            $msg_tipo = 'danger';
        }
    } elseif (isset($_POST['acao']) && $_POST['acao'] == 'alterar_senha') {
        $senha_atual = $_POST['senha_atual'];
        $nova_senha = $_POST['nova_senha'];
        $confirma_senha = $_POST['confirma_senha'];
        
        if ($nova_senha !== $confirma_senha) {
            $msg = 'As senhas não conferem!';
            $msg_tipo = 'danger';
        } else {
            // Verificar senha atual
            $stmt = $pdo->prepare("SELECT senha FROM clientes WHERE id = ?");
            $stmt->execute([$cliente_id]);
            $cliente = $stmt->fetch();
            
            if ($cliente && password_verify($senha_atual, $cliente['senha'])) {
                $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE clientes SET senha = ? WHERE id = ?");
                $stmt->execute([$hash, $cliente_id]);
                $msg = 'Senha alterada com sucesso!';
                $msg_tipo = 'success';
            } else {
                $msg = 'Senha atual incorreta!';
                $msg_tipo = 'danger';
            }
        }
    }
}

// Buscar dados do cliente
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Meu Perfil</h4>
        <a href="index.php" class="btn btn-outline-secondary btn-sm">Voltar</a>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-<?php echo $msg_tipo; ?> alert-dismissible fade show" role="alert">
            <?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    Dados Pessoais
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="acao" value="atualizar_perfil">
                        
                        <div class="mb-3">
                            <label class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" name="nome" value="<?php echo htmlspecialchars($cliente['nome']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Telefone (WhatsApp)</label>
                            <input type="text" class="form-control" name="telefone" value="<?php echo htmlspecialchars($cliente['telefone']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($cliente['email']); ?>">
                        </div>

                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    Alterar Senha
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="acao" value="alterar_senha">
                        
                        <div class="mb-3">
                            <label class="form-label">Senha Atual</label>
                            <input type="password" class="form-control" name="senha_atual" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nova Senha</label>
                            <input type="password" class="form-control" name="nova_senha" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Confirmar Nova Senha</label>
                            <input type="password" class="form-control" name="confirma_senha" required>
                        </div>

                        <button type="submit" class="btn btn-warning text-white">Alterar Senha</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>