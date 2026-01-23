<?php
require_once 'includes/header.php';

// Migration: Criar tabela de configuração de pizzas se não existir
try {
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

// Processar configuração de pizza
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'config_pizza') {
    $tipo_cobranca = $_POST['tipo_cobranca'] ?? 'maior_valor';
    if (in_array($tipo_cobranca, ['maior_valor', 'media'])) {
        $stmt = $pdo->prepare("UPDATE configuracao_pizzas SET tipo_cobranca = ? WHERE id = 1");
        $stmt->execute([$tipo_cobranca]);
        $msg = 'Configuração de pizza atualizada!';
        $msg_type = 'success';
    }
}

// Buscar configuração atual
$config_pizza_stmt = $pdo->query("SELECT * FROM configuracao_pizzas LIMIT 1");
$config_pizza = $config_pizza_stmt->fetch(PDO::FETCH_ASSOC);

// Ações
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Verificar se há produtos desta categoria com itens de pedido vinculados
    $stmt_check = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM produtos p 
        INNER JOIN pedido_itens pi ON pi.produto_id = p.id 
        WHERE p.categoria_id = ?
    ");
    $stmt_check->execute([$id]);
    $has_orders = $stmt_check->fetch(PDO::FETCH_ASSOC)['total'] > 0;
    
    if ($has_orders) {
        $msg = 'Não é possível excluir esta categoria. Ela possui produtos que fazem parte de pedidos. Você pode desativá-la em vez de excluí-la.';
        $msg_type = 'warning';
    } else {
        try {
            // Primeiro excluir produtos da categoria (se não houver pedidos)
            $stmt_produtos = $pdo->prepare("DELETE FROM produtos WHERE categoria_id = ?");
            $stmt_produtos->execute([$id]);
            
            // Depois excluir a categoria
            $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
            if ($stmt->execute([$id])) {
                $msg = 'Categoria e seus produtos excluídos com sucesso!';
                $msg_type = 'success';
            } else {
                $msg = 'Erro ao excluir categoria.';
                $msg_type = 'danger';
            }
        } catch (PDOException $e) {
            $msg = 'Erro ao excluir categoria. Verifique se não há dados vinculados.';
            $msg_type = 'danger';
        }
    }
}

// Busca
$busca = isset($_GET['busca']) ? $_GET['busca'] : '';
$where = "";
$params = [];
if ($busca) {
    $where = "WHERE nome LIKE ?";
    $params[] = "%$busca%";
}

// Paginação (simplificada por enquanto, listando tudo)
$sql = "SELECT * FROM categorias $where ORDER BY ordem ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_categorias = count($categorias);

// Mensagem via GET (de redirecionamentos)
if (isset($_GET['mensagem'])) {
    $msg = $_GET['mensagem'];
    $msg_type = isset($_GET['tipo']) ? $_GET['tipo'] : 'success';
}
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Categorias</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Categorias</li>
        </ul>
    </div>

    <?php if (isset($msg)): ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($msg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Configuração de Pizza Meio a Meio -->
    <div class="card h-100 p-0 radius-12 mb-4">
        <div class="card-header">
            <h6 class="mb-0 fw-semibold">🍕 Configuração de Pizza Meio a Meio</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="categorias.php">
                <input type="hidden" name="action" value="config_pizza">
                
                <p class="text-secondary-light mb-3">Como calcular o preço quando o cliente escolher pizza meio a meio?</p>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check" style="padding: 15px; background: <?php echo ($config_pizza['tipo_cobranca'] ?? 'maior_valor') === 'maior_valor' ? 'rgba(74, 102, 249, 0.1)' : 'transparent'; ?>; border-radius: 10px; border: 2px solid <?php echo ($config_pizza['tipo_cobranca'] ?? 'maior_valor') === 'maior_valor' ? '#4a66f9' : '#2d3446'; ?>;">
                            <input class="form-check-input" type="radio" name="tipo_cobranca" id="maior_valor" value="maior_valor" 
                                   <?php echo ($config_pizza['tipo_cobranca'] ?? 'maior_valor') === 'maior_valor' ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-semibold" for="maior_valor">
                                📈 Cobrar pelo <strong>Maior Valor</strong>
                            </label>
                            <small class="d-block text-secondary-light mt-1">Se as pizzas custam R$ 40 e R$ 50, cobra R$ 50</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check" style="padding: 15px; background: <?php echo ($config_pizza['tipo_cobranca'] ?? 'maior_valor') === 'media' ? 'rgba(74, 102, 249, 0.1)' : 'transparent'; ?>; border-radius: 10px; border: 2px solid <?php echo ($config_pizza['tipo_cobranca'] ?? 'maior_valor') === 'media' ? '#4a66f9' : '#2d3446'; ?>;">
                            <input class="form-check-input" type="radio" name="tipo_cobranca" id="media" value="media"
                                   <?php echo ($config_pizza['tipo_cobranca'] ?? 'maior_valor') === 'media' ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-semibold" for="media">
                                📊 Cobrar pela <strong>Média</strong>
                            </label>
                            <small class="d-block text-secondary-light mt-1">Se as pizzas custam R$ 40 e R$ 50, cobra R$ 45</small>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary radius-8 d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:diskette-outline"></iconify-icon>
                        Salvar Configuração
                    </button>
                </div>
            </form>
        </div>
    </div>


    <div class="card h-100 p-0 radius-12">
        <div class="card-body p-24">
            <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between mb-20">
                <h6 class="mb-2 fw-bold text-lg mb-0">Lista de Categorias</h6>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-sm fw-medium text-secondary-light"><?php echo $total_categorias; ?> categoria(s)</span>
                    <a href="categorias_add.php" class="btn btn-primary text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:add-circle-outline" class="icon text-lg"></iconify-icon>
                        <span>Adicionar Nova</span>
                    </a>
                </div>
            </div>
            
            <!-- Filtros -->
            <form method="GET" action="categorias.php" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="busca" class="form-label fw-semibold text-sm mb-2">Buscar por Nome</label>
                        <input type="text" 
                               class="form-control radius-8" 
                               id="busca" 
                               name="busca" 
                               value="<?php echo htmlspecialchars($busca); ?>" 
                               placeholder="Digite o nome da categoria...">
                    </div>
                    <div class="col-md-6 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary radius-8 px-4 d-flex align-items-center gap-2">
                            <iconify-icon icon="solar:magnifer-outline"></iconify-icon>
                            Filtrar
                        </button>
                        <?php if ($busca): ?>
                            <a href="categorias.php" class="btn btn-outline-secondary radius-8 px-4 d-flex align-items-center gap-2">
                                <iconify-icon icon="solar:refresh-outline"></iconify-icon>
                                Limpar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
            
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Ordem</th>
                            <th scope="col">Imagem</th>
                            <th scope="col">Nome</th>
                            <th scope="col">Descrição</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categorias)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">Nenhuma categoria encontrada.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categorias as $cat): ?>
                            <tr>
                                <td class="fw-bold text-primary-600"><?php echo $cat['ordem']; ?></td>
                                <td>
                                    <?php 
                                    // O caminho salvo no banco é 'admin/uploads/categorias/xxx.jpg'
                                    // No admin, precisamos de 'uploads/categorias/xxx.jpg' (relativo)
                                    $img_url = $cat['imagem'] ? str_replace('admin/', '', $cat['imagem']) : 'assets/images/sem-foto.jpg';
                                    ?>
                                    <img src="<?php echo htmlspecialchars($img_url); ?>" alt="<?php echo htmlspecialchars($cat['nome']); ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;"
                                         onerror="this.src='assets/images/sem-foto.jpg'; this.onerror=null;">
                                </td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($cat['nome']); ?></td>
                                <td>
                                    <?php echo isset($cat['descricao']) ? htmlspecialchars($cat['descricao']) : ''; ?>
                                </td>
                                <td>
                                    <?php if ($cat['ativo']): ?>
                                        <span class="bg-success-focus text-success-main px-24 py-4 rounded-pill fw-medium text-sm">Ativa</span>
                                    <?php else: ?>
                                        <span class="bg-danger-focus text-danger-main px-24 py-4 rounded-pill fw-medium text-sm">Inativa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center gap-10 justify-content-center">
                                        <a href="categorias_edit.php?id=<?php echo $cat['id']; ?>" 
                                           class="bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle"
                                           title="Editar">
                                            <iconify-icon icon="lucide:edit" class="icon text-xl"></iconify-icon>
                                        </a>
                                        <a href="categorias.php?action=delete&id=<?php echo $cat['id']; ?>" 
                                           class="bg-danger-focus text-danger-600 bg-hover-danger-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle"
                                           onclick="return confirm('Tem certeza que deseja excluir esta categoria?');"
                                           title="Excluir">
                                            <iconify-icon icon="mingcute:delete-2-line" class="icon text-xl"></iconify-icon>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>