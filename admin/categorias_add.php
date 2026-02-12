<?php
require_once 'includes/header.php';
require_once __DIR__ . '/../includes/image_optimization.php';
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
        $descricao = isset($_POST['descricao']) ? $_POST['descricao'] : '';
        $ordem = (int)$_POST['ordem'];
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        $permite_meio_a_meio = isset($_POST['permite_meio_a_meio']) ? 1 : 0;

        // Upload de imagem
        $imagem = '';
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
            // Modificação: Salvar em admin/uploads/
            $upload_dir = __DIR__ . '/uploads/categorias/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_base = $upload_dir . 'cat_' . time();

            // Comprimir e otimizar imagem
            $compress_result = compressAndOptimizeImage($_FILES['imagem']['tmp_name'], $file_base, 75, 800, 800);

            if ($compress_result['success']) {
                $imagem = $compress_result['file'];
                $msg = 'Imagem comprimida! Redução: ' . $compress_result['compression_ratio'] . '%';
                $msg_type = 'success';
            }
            else {
                $msg = 'Erro ao processar imagem: ' . $compress_result['error'];
                $msg_type = 'danger';
            }
        }

        // Check if descricao column exists, if not, ignore it
        // For now, we'll try to insert without description if it fails, or just insert standard fields
        // Let's stick to the known fields first: nome, ordem, ativo, imagem

        if (empty($msg) || $msg_type === 'success') {
            $sql = "INSERT INTO categorias (nome, ordem, ativo, imagem, permite_meio_a_meio) VALUES (?, ?, ?, ?, ?)";
            $params = [$nome, $ordem, $ativo, $imagem, $permite_meio_a_meio];

            // If we want to support description, we'd need to alter the table first. 
            // I'll assume standard fields for now to be safe.

            try {
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute($params)) {
                    // Redirect to list with success message
                    echo "<script>window.location.href = 'categorias.php?mensagem=Categoria adicionada com sucesso!&tipo=success';</script>";
                    exit;
                }
                else {
                    $msg = 'Erro ao adicionar categoria.';
                    $msg_type = 'danger';
                }
            }
            catch (PDOException $e) {
                $msg = 'Erro no banco de dados: ' . $e->getMessage();
                $msg_type = 'danger';
            }
        }
    } // fecha else validar_csrf
}
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Adicionar Categoria</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">
                <a href="categorias.php" class="hover-text-primary">Categorias</a>
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
            <form method="POST" enctype="multipart/form-data">
                <?php echo campo_csrf(); ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Nome da Categoria</label>
                        <input type="text" class="form-control radius-8" name="nome" required placeholder="Ex: Lanches">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Ordem de Exibição</label>
                        <input type="number" class="form-control radius-8" name="ordem" value="0">
                    </div>
                    
                    <div class="col-md-12">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Imagem</label>
                        <input type="file" class="form-control radius-8" name="imagem" accept="image/*">
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" checked>
                            <label class="form-check-label fw-medium text-secondary-light" for="ativo">Categoria Ativa</label>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="permite_meio_a_meio" name="permite_meio_a_meio">
                            <label class="form-check-label fw-medium text-secondary-light" for="permite_meio_a_meio">
                                🍕 Permite Pizza Meio a Meio
                            </label>
                            <small class="d-block text-muted mt-1">Ative para categorias de pizza</small>
                        </div>
                    </div>
                    
                    <div class="col-md-12 d-flex justify-content-end gap-2 mt-4">
                        <a href="categorias.php" class="btn btn-outline-secondary radius-8 px-20 py-11">Cancelar</a>
                        <button type="submit" class="btn btn-primary radius-8 px-20 py-11">Salvar Categoria</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>