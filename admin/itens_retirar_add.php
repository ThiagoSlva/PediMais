<?php
require_once 'includes/header.php';
require_once __DIR__ . '/../includes/csrf.php';

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validar_csrf()) {
        $msg = 'Token de segurança inválido. Recarregue a página.';
        $msg_type = 'danger';
    }
    else {
        $nome = $_POST['nome'];
        $descricao = $_POST['descricao'];
        $ordem = isset($_POST['ordem']) ? (int)$_POST['ordem'] : 0;
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        try {
            // Tenta inserir com ordem
            $sql = "INSERT INTO itens_retirar (nome, descricao, ordem, ativo) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $descricao, $ordem, $ativo]);

            echo "<script>window.location.href = 'itens_retirar.php?mensagem=Item adicionado com sucesso!&tipo=success';</script>";
            exit;
        }
        catch (PDOException $e) {
            // Se falhar (provavelmente por falta da coluna ordem), tenta sem ordem
            try {
                $sql = "INSERT INTO itens_retirar (nome, descricao, ativo) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nome, $descricao, $ativo]);

                echo "<script>window.location.href = 'itens_retirar.php?mensagem=Item adicionado com sucesso!&tipo=success';</script>";
                exit;
            }
            catch (PDOException $e2) {
                $msg = 'Erro ao adicionar item: ' . $e2->getMessage();
                $msg_type = 'danger';
            }
        }
    } // fecha else validar_csrf
}
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Adicionar Item para Retirar</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">
                <a href="itens_retirar.php" class="hover-text-primary">Retirar</a>
            </li>
            <li>-</li>
            <li class="fw-medium">Adicionar</li>
        </ul>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($msg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php
endif; ?>

    <div class="card h-100 p-0 radius-12">
        <div class="card-body p-24">
            <form method="POST">
                <?php echo campo_csrf(); ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Nome do Item</label>
                        <input type="text" class="form-control radius-8" name="nome" required placeholder="Ex: Cebola">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Ordem de Exibição</label>
                        <input type="number" class="form-control radius-8" name="ordem" value="0">
                    </div>
                    
                    <div class="col-md-12">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Descrição (Opcional)</label>
                        <input type="text" class="form-control radius-8" name="descricao" placeholder="Ex: Sem cebola">
                    </div>
                    
                    <div class="col-md-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" checked>
                            <label class="form-check-label fw-medium text-secondary-light" for="ativo">Item Ativo</label>
                        </div>
                    </div>
                    
                    <div class="col-md-12 d-flex justify-content-end gap-2 mt-4">
                        <a href="itens_retirar.php" class="btn btn-outline-secondary radius-8 px-20 py-11">Cancelar</a>
                        <button type="submit" class="btn btn-primary radius-8 px-20 py-11">Salvar Item</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>