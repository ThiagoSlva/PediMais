<?php
require_once 'includes/header.php';
require_once __DIR__ . '/../includes/image_optimization.php';

// Migration: Adicionar coluna permite_meio_a_meio se não existir
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM categorias LIKE 'permite_meio_a_meio'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE categorias ADD COLUMN permite_meio_a_meio TINYINT(1) DEFAULT 0");
    }
    
    // Criar tabela de configuração de pizzas se não existir
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracao_pizzas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tipo_cobranca ENUM('maior_valor', 'media') DEFAULT 'maior_valor'
    )");
    
    // Garantir registro inicial
    $check = $pdo->query("SELECT id FROM configuracao_pizzas LIMIT 1");
    if (!$check->fetch()) {
        $pdo->exec("INSERT INTO configuracao_pizzas (tipo_cobranca) VALUES ('maior_valor')");
    }
} catch (Exception $e) {
    // Silently handle
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$msg = '';
$msg_type = '';

// Buscar dados da categoria
$stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
$stmt->execute([$id]);
$categoria = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$categoria) {
    echo "<script>window.location.href = 'categorias.php?mensagem=Categoria não encontrada&tipo=danger';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $ordem = (int)$_POST['ordem'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $permite_meio_a_meio = isset($_POST['permite_meio_a_meio']) ? 1 : 0;

    // Upload de imagem
    $imagem = $categoria['imagem']; // Manter imagem atual por padrão
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
        $upload_dir = __DIR__ . '/../uploads/categorias/';
        $file_base = $upload_dir . 'cat_' . time();
        
        // Comprimir e otimizar imagem
        $compress_result = compressAndOptimizeImage($_FILES['imagem']['tmp_name'], $file_base, 75, 800, 800);
        
        if ($compress_result['success']) {
            $imagem = $compress_result['file'];
            $msg = 'Categoria atualizada! Imagem comprimida com redução de ' . $compress_result['compression_ratio'] . '%';
            $msg_type = 'success';
        } else {
            $msg = 'Erro ao processar imagem: ' . $compress_result['error'];
            $msg_type = 'danger';
        }
    }

    $sql = "UPDATE categorias SET nome = ?, ordem = ?, ativo = ?, imagem = ?, permite_meio_a_meio = ? WHERE id = ?";
    $params = [$nome, $ordem, $ativo, $imagem, $permite_meio_a_meio, $id];
    
    try {
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            // Redirect to list with success message
            echo "<script>window.location.href = 'categorias.php?mensagem=Categoria atualizada com sucesso!&tipo=success';</script>";
            exit;
        } else {
            $msg = 'Erro ao atualizar categoria.';
            $msg_type = 'danger';
        }
    } catch (PDOException $e) {
        $msg = 'Erro no banco de dados: ' . $e->getMessage();
        $msg_type = 'danger';
    }
}
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Editar Categoria</h6>
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
            <li class="fw-medium">Editar</li>
        </ul>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($msg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="card h-100 p-0 radius-12">
        <div class="card-body p-24">
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Nome da Categoria</label>
                        <input type="text" class="form-control radius-8" name="nome" required value="<?php echo htmlspecialchars($categoria['nome']); ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Ordem de Exibição</label>
                        <input type="number" class="form-control radius-8" name="ordem" value="<?php echo $categoria['ordem']; ?>">
                    </div>
                    
                    <div class="col-md-12">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">Imagem</label>
                        <input type="file" class="form-control radius-8" name="imagem" accept="image/*">
                        <?php if ($categoria['imagem']): 
                            // O caminho no banco é relativo à raiz (ex: uploads/categorias/xxx.jpg)
                            // Estamos em /admin, então precisamos subir um nível
                            $img_src = str_replace('admin/', '', $categoria['imagem']);
                            if (!str_starts_with($img_src, 'http') && !str_starts_with($img_src, '../')) {
                                $img_src = '../' . $img_src;
                            }
                        ?>
                            <div class="mt-2">
                                <small class="text-secondary-light">Imagem atual:</small><br>
                                <img src="<?php echo htmlspecialchars($img_src); ?>" 
                                     style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; margin-top: 5px;"
                                     onerror="this.style.display='none'">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" <?php echo $categoria['ativo'] ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-medium text-secondary-light" for="ativo">Categoria Ativa</label>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="permite_meio_a_meio" name="permite_meio_a_meio" <?php echo ($categoria['permite_meio_a_meio'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-medium text-secondary-light" for="permite_meio_a_meio">
                                🍕 Permite Pizza Meio a Meio
                            </label>
                            <small class="d-block text-muted mt-1">Ative para categorias de pizza onde cliente pode escolher 2 sabores</small>
                        </div>
                    </div>
                    
                    <div class="col-md-12 d-flex justify-content-end gap-2 mt-4">
                        <a href="categorias.php" class="btn btn-outline-secondary radius-8 px-20 py-11">Cancelar</a>
                        <button type="submit" class="btn btn-primary radius-8 px-20 py-11">Salvar Alterações</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>