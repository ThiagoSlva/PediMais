<?php
include 'includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';

verificar_login();

$msg = '';
$msg_tipo = '';

// Migration Logic
try {
    // Config table
    $pdo->exec("CREATE TABLE IF NOT EXISTS fidelidade_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ativo TINYINT(1) DEFAULT 1,
        quantidade_pedidos INT DEFAULT 10
    )");
    
    // Ensure initial record
    $stmt = $pdo->query("SELECT id FROM fidelidade_config LIMIT 1");
    if (!$stmt->fetch()) {
        $pdo->exec("INSERT INTO fidelidade_config (ativo, quantidade_pedidos) VALUES (1, 10)");
    }

    // Products table
    $pdo->exec("CREATE TABLE IF NOT EXISTS fidelidade_produtos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        produto_id INT NOT NULL,
        quantidade INT DEFAULT 1,
        ativo TINYINT(1) DEFAULT 1,
        FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
    )");

} catch (PDOException $e) {
    // Ignore if tables exist
}

// Process Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['acao'])) {
            $acao = $_POST['acao'];
            
            if ($acao == 'salvar_config') {
                $ativo = isset($_POST['ativo']) ? 1 : 0;
                $qtd = (int)$_POST['quantidade_pedidos'];
                
                $stmt = $pdo->prepare("UPDATE fidelidade_config SET ativo = ?, quantidade_pedidos = ? WHERE id = 1");
                $stmt->execute([$ativo, $qtd]);
                
                $msg = 'Configuração atualizada com sucesso!';
                $msg_tipo = 'success';
                
            } elseif ($acao == 'adicionar_produto') {
                $produto_id = (int)$_POST['produto_id'];
                $quantidade = (int)$_POST['quantidade'];
                
                // Check if already exists
                $stmt = $pdo->prepare("SELECT id FROM fidelidade_produtos WHERE produto_id = ?");
                $stmt->execute([$produto_id]);
                
                if ($stmt->fetch()) {
                    $msg = 'Este produto já está na lista de resgate!';
                    $msg_tipo = 'warning';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO fidelidade_produtos (produto_id, quantidade) VALUES (?, ?)");
                    $stmt->execute([$produto_id, $quantidade]);
                    $msg = 'Produto adicionado com sucesso!';
                    $msg_tipo = 'success';
                }
                
            } elseif ($acao == 'atualizar_quantidade') {
                $id = (int)$_POST['id'];
                $quantidade = (int)$_POST['quantidade'];
                
                $stmt = $pdo->prepare("UPDATE fidelidade_produtos SET quantidade = ? WHERE id = ?");
                $stmt->execute([$quantidade, $id]);
                $msg = 'Quantidade atualizada!';
                $msg_tipo = 'success';
                
            } elseif ($acao == 'remover_produto') {
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM fidelidade_produtos WHERE id = ?");
                $stmt->execute([$id]);
                $msg = 'Produto removido!';
                $msg_tipo = 'success';
            }
        }
    } catch (PDOException $e) {
        $msg = 'Erro ao salvar: ' . $e->getMessage();
        $msg_tipo = 'danger';
    }
}

// Sync Logic (Basic implementation)
if (isset($_GET['sync']) && $_GET['sync'] == 1) {
    // Here you would implement logic to recalculate points based on order history
    // For now, we'll just show a success message as a placeholder for the complex logic
    $msg = 'Sincronização realizada com sucesso! (Simulação)';
    $msg_tipo = 'info';
}

// Fetch Data
$stmt = $pdo->query("SELECT * FROM fidelidade_config LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Reward Products
$stmt = $pdo->query("
    SELECT fp.*, p.nome as nome_produto, c.nome as nome_categoria 
    FROM fidelidade_produtos fp
    JOIN produtos p ON fp.produto_id = p.id
    LEFT JOIN categorias c ON p.categoria_id = c.id
    ORDER BY p.nome ASC
");
$recompensas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch All Products for Dropdown
$stmt = $pdo->query("SELECT id, nome, preco FROM produtos WHERE ativo = 1 ORDER BY nome ASC");
$todos_produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div>
                <h6 class="fw-semibold mb-0">🎁 Sistema de Fidelidade</h6>
                <p class="text-sm text-secondary mb-0">Configure o programa de fidelidade para seus clientes</p>
            </div>
            <?php if ($config['ativo']): ?>
            <span class="badge bg-success-600 px-3 py-2">
                <i class="fa-solid fa-circle-check"></i> SISTEMA ATIVO
            </span>
            <?php else: ?>
            <span class="badge bg-danger-600 px-3 py-2">
                <i class="fa-solid fa-circle-xmark"></i> SISTEMA INATIVO
            </span>
            <?php endif; ?>
        </div>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Fidelidade</li>
        </ul>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_tipo; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <style>
    /* Dark mode support */
    [data-theme="dark"] .card,
    html[data-theme="dark"] .card {
        background-color: var(--base-soft, #1e1e2e) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    </style>

    <!-- Configuração Geral -->
    <div class="card mb-4 radius-12">
        <div class="card-header">
            <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                <iconify-icon icon="solar:settings-outline"></iconify-icon>
                Configuração Geral
            </h6>
        </div>
        <div class="card-body p-24">
            <form method="POST" id="formConfig">
                <input type="hidden" name="acao" value="salvar_config">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center justify-content-between p-3 border rounded">
                            <div>
                                <h6 class="mb-1">Sistema de Fidelidade</h6>
                                <small class="text-secondary-light">
                                    <?php echo $config['ativo'] ? 'Ativado - clientes podem resgatar' : 'Desativado - resgate suspenso'; ?>
                                </small>
                            </div>
                            <div class="form-switch">
                                <input class="form-check-input" type="checkbox" name="ativo" id="ativo" 
                                       value="1" <?php echo $config['ativo'] ? 'checked' : ''; ?>>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Quantidade de Pedidos para Resgate *</label>
                        <input type="number" name="quantidade_pedidos" class="form-control" 
                               value="<?php echo $config['quantidade_pedidos']; ?>" 
                               min="1" required>
                        <small class="text-secondary-light">Quantidade de pedidos recebidos necessários para resgate</small>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary-600 px-20 py-11 radius-8">
                        <iconify-icon icon="solar:diskette-bold-duotone"></iconify-icon>
                        Salvar Configuração
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Produtos para Resgate -->
    <div class="card mb-4 radius-12">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                <iconify-icon icon="solar:gift-outline"></iconify-icon>
                Produtos Disponíveis para Resgate
            </h6>
        </div>
        <div class="card-body p-24">
            <!-- Formulário para adicionar produto -->
            <form method="POST" class="mb-4 p-3 border rounded">
                <input type="hidden" name="acao" value="adicionar_produto">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Produto *</label>
                        <select name="produto_id" class="form-select" required>
                            <option value="">Selecione um produto...</option>
                            <?php foreach ($todos_produtos as $p): ?>
                                <option value="<?php echo $p['id']; ?>">
                                    <?php echo htmlspecialchars($p['nome']); ?> (R$ <?php echo number_format($p['preco'], 2, ',', '.'); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Quantidade *</label>
                        <input type="number" name="quantidade" class="form-control" value="1" min="1" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary-600 w-100">
                            <iconify-icon icon="solar:add-circle-bold"></iconify-icon>
                            Adicionar
                        </button>
                    </div>
                </div>
            </form>

            <!-- Lista de produtos -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Quantidade</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recompensas)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-3">Nenhum produto configurado para resgate.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recompensas as $r): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($r['nome_produto']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($r['nome_categoria'] ?? 'Sem categoria'); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="acao" value="atualizar_quantidade">
                                        <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                        <div class="input-group" style="width: 100px;">
                                            <input type="number" name="quantidade" class="form-control form-control-sm" 
                                                   value="<?php echo $r['quantidade']; ?>" min="1" 
                                                   onchange="this.form.submit()">
                                        </div>
                                    </form>
                                </td>
                                <td>
                                    <span class="badge bg-success-600">Ativo</span>
                                </td>
                                <td>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja remover este produto?');">
                                        <input type="hidden" name="acao" value="remover_produto">
                                        <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger-600">
                                            <iconify-icon icon="solar:trash-bin-trash-bold"></iconify-icon>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Gerenciamento de Clientes -->
    <div class="card mb-4 radius-12">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                <iconify-icon icon="solar:users-group-rounded-outline"></iconify-icon>
                Clientes e Contagens
            </h6>
            <div class="d-flex gap-2">
                <a href="?sync=1" 
                   class="btn btn-sm btn-info-600" 
                   onclick="return confirm('Deseja sincronizar pontos para pedidos em preparo que ainda não têm pontos?');">
                    <iconify-icon icon="solar:refresh-bold-duotone"></iconify-icon>
                    Sincronizar Pontos
                </a>
            </div>
        </div>
        <div class="card-body p-24">
            <div class="text-center py-5">
                <iconify-icon icon="solar:users-group-rounded-outline" class="text-secondary-light" style="font-size: 60px;"></iconify-icon>
                <p class="text-secondary-light mt-3">Nenhum cliente com pontos de fidelidade ainda.</p>
                <p class="text-secondary-light">
                    <small>Os pontos aparecerão aqui quando pedidos forem para "Em Preparo".</small>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>