<?php
include 'includes/header.php';
require_once __DIR__ . '/../includes/csrf.php';

// Processar ações
$msg = '';
$msg_tipo = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validar_csrf()) {
        $msg = 'Token de segurança inválido. Recarregue a página.';
        $msg_tipo = 'danger';
    }
    elseif (isset($_POST['acao'])) {
        $acao = $_POST['acao'];

        if ($acao == 'adicionar') {
            $nome = $_POST['nome'];
            $taxa = str_replace(',', '.', str_replace('.', '', $_POST['taxa'])); // Format currency
            $ativo = isset($_POST['ativo']) ? 1 : 0;
            $cidade_id = 1; // Default to 1 for now

            $stmt = $pdo->prepare("INSERT INTO bairros_entrega (nome, taxa, ativo, cidade_id) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$nome, $taxa, $ativo, $cidade_id])) {
                $msg = 'Bairro adicionado com sucesso!';
                $msg_tipo = 'success';
            }
            else {
                $msg = 'Erro ao adicionar bairro.';
                $msg_tipo = 'danger';
            }
        }
        elseif ($acao == 'editar') {
            $id = $_POST['id'];
            $nome = $_POST['nome'];
            $taxa = str_replace(',', '.', str_replace('.', '', $_POST['taxa']));
            $ativo = isset($_POST['ativo']) ? 1 : 0;

            $stmt = $pdo->prepare("UPDATE bairros_entrega SET nome = ?, taxa = ?, ativo = ? WHERE id = ?");
            if ($stmt->execute([$nome, $taxa, $ativo, $id])) {
                $msg = 'Bairro atualizado com sucesso!';
                $msg_tipo = 'success';
            }
            else {
                $msg = 'Erro ao atualizar bairro.';
                $msg_tipo = 'danger';
            }
        }
        elseif ($acao == 'excluir') {
            $id = $_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM bairros_entrega WHERE id = ?");
            if ($stmt->execute([$id])) {
                $msg = 'Bairro excluído com sucesso!';
                $msg_tipo = 'success';
            }
            else {
                $msg = 'Erro ao excluir bairro.';
                $msg_tipo = 'danger';
            }
        }
    }
}

// Buscar dados para edição se necessário
$editar_bairro = null;
if (isset($_GET['acao']) && $_GET['acao'] == 'editar' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM bairros_entrega WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $editar_bairro = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Buscar todos os bairros
$stmt = $pdo->query("SELECT * FROM bairros_entrega ORDER BY nome ASC");
$bairros = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Taxas de Entrega por Bairro</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Entregas</li>
        </ul>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_tipo; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php
endif; ?>

    <div class="row gy-4">
        <!-- Formulário -->
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0"><?php echo $editar_bairro ? 'Editar Bairro' : 'Adicionar Bairro'; ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="entregas.php">
                        <?php echo campo_csrf(); ?>
                        <input type="hidden" name="acao" value="<?php echo $editar_bairro ? 'editar' : 'adicionar'; ?>">
                        <?php if ($editar_bairro): ?>
                            <input type="hidden" name="id" value="<?php echo $editar_bairro['id']; ?>">
                        <?php
endif; ?>

                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome do Bairro</label>
                            <input type="text" class="form-control" id="nome" name="nome" required 
                                   value="<?php echo $editar_bairro ? htmlspecialchars($editar_bairro['nome']) : ''; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="taxa" class="form-label">Taxa de Entrega (R$)</label>
                            <input type="text" class="form-control money" id="taxa" name="taxa" required 
                                   value="<?php echo $editar_bairro ? number_format($editar_bairro['taxa'], 2, ',', '.') : ''; ?>">
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="ativo" name="ativo" 
                                   <?php echo($editar_bairro && !$editar_bairro['ativo']) ? '' : 'checked'; ?>>
                            <label class="form-check-label" for="ativo">Ativo</label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <?php echo $editar_bairro ? 'Salvar Alterações' : 'Adicionar'; ?>
                        </button>
                        
                        <?php if ($editar_bairro): ?>
                            <a href="entregas.php" class="btn btn-outline-secondary w-100 mt-2">Cancelar</a>
                        <?php
endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lista -->
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Bairros Cadastrados</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Taxa</th>
                                    <th>Status</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bairros)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4">Nenhum bairro cadastrado.</td>
                                    </tr>
                                <?php
else: ?>
                                    <?php foreach ($bairros as $bairro): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($bairro['nome']); ?></td>
                                        <td>R$ <?php echo number_format($bairro['taxa'], 2, ',', '.'); ?></td>
                                        <td>
                                            <?php if ($bairro['ativo']): ?>
                                                <span class="badge bg-success-focus text-success-main px-2 py-1">Ativo</span>
                                            <?php
        else: ?>
                                                <span class="badge bg-danger-focus text-danger-main px-2 py-1">Inativo</span>
                                            <?php
        endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="entregas.php?acao=editar&id=<?php echo $bairro['id']; ?>" class="btn btn-sm btn-primary-600 radius-8">
                                                <iconify-icon icon="solar:pen-new-square-broken"></iconify-icon>
                                            </a>
                                            <form method="POST" action="entregas.php" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir?');">
                                                <?php echo campo_csrf(); ?>
                                                <input type="hidden" name="acao" value="excluir">
                                                <input type="hidden" name="id" value="<?php echo $bairro['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger-600 radius-8">
                                                    <iconify-icon icon="solar:trash-bin-trash-bold"></iconify-icon>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php
    endforeach; ?>
                                <?php
endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Simple money mask if not already present in footer
    document.addEventListener('DOMContentLoaded', function() {
        const moneyInputs = document.querySelectorAll('.money');
        moneyInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                value = (value / 100).toFixed(2) + '';
                value = value.replace('.', ',');
                value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
                e.target.value = value;
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>