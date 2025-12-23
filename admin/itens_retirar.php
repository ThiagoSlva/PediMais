<?php
require_once 'includes/header.php';

// Gerar token CSRF se não existir
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$msg = '';
$msg_type = '';

// Ações
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    // Validar token
    if (isset($_GET['token']) && $_GET['token'] === $_SESSION['csrf_token']) {
        $id = (int)$_GET['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM itens_retirar WHERE id = ?");
            if ($stmt->execute([$id])) {
                $msg = 'Item excluído com sucesso!';
                $msg_type = 'success';
            } else {
                $msg = 'Erro ao excluir item.';
                $msg_type = 'danger';
            }
        } catch (PDOException $e) {
            $msg = 'Erro ao excluir: ' . $e->getMessage();
            $msg_type = 'danger';
        }
    } else {
        $msg = 'Token de segurança inválido.';
        $msg_type = 'danger';
    }
}

// Mensagem via GET
if (isset($_GET['mensagem'])) {
    $msg = $_GET['mensagem'];
    $msg_type = isset($_GET['tipo']) ? $_GET['tipo'] : 'success';
}

// Filtros
$busca = isset($_GET['busca']) ? $_GET['busca'] : '';
$where = "WHERE 1=1";
$params = [];

if ($busca) {
    $where .= " AND nome LIKE ?";
    $params[] = "%$busca%";
}

// Buscar itens
// Tentando ordenar por ordem, se não existir, ordena por nome (fallback)
try {
    $sql = "SELECT * FROM itens_retirar $where ORDER BY ordem ASC, nome ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Se der erro (provavelmente coluna ordem não existe), tenta sem ordem
    $sql = "SELECT * FROM itens_retirar $where ORDER BY nome ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Itens para Retirar</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Retirar</li>
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
            <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between mb-20">
                <div>
                    <h6 class="mb-2 fw-bold text-lg mb-0">Itens para Retirar</h6>
                    <p class="text-sm text-secondary-light mb-0">Gerencie os ingredientes que podem ser retirados dos produtos</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-sm fw-medium text-secondary-light"><?php echo count($itens); ?> item(ns)</span>
                    <a href="itens_retirar_add.php" class="btn btn-primary text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:add-circle-outline" class="icon text-lg"></iconify-icon>
                        <span>Adicionar Novo</span>
                    </a>
                </div>
            </div>
            
            <!-- Filtros -->
            <form method="GET" action="itens_retirar.php" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="busca" class="form-label fw-semibold text-sm mb-2">Buscar por Nome</label>
                        <input type="text" 
                               class="form-control radius-8" 
                               id="busca" 
                               name="busca" 
                               value="<?php echo htmlspecialchars($busca); ?>" 
                               placeholder="Digite o nome do item...">
                    </div>
                    <div class="col-md-6 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary radius-8 px-4 d-flex align-items-center gap-2">
                            <iconify-icon icon="solar:magnifer-outline"></iconify-icon>
                            Filtrar
                        </button>
                        <?php if ($busca): ?>
                            <a href="itens_retirar.php" class="btn btn-outline-secondary radius-8 px-4 d-flex align-items-center gap-2">
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
                            <th scope="col">Nome</th>
                            <th scope="col">Descrição</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($itens)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">Nenhum item encontrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($itens as $item): ?>
                            <tr>
                                <td class="fw-bold text-primary-600"><?php echo isset($item['ordem']) ? $item['ordem'] : 0; ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($item['nome']); ?></td>
                                <td><?php echo htmlspecialchars($item['descricao']); ?></td>
                                <td>
                                    <?php if ($item['ativo']): ?>
                                        <span class="bg-success-focus text-success-main px-24 py-4 rounded-pill fw-medium text-sm">Ativo</span>
                                    <?php else: ?>
                                        <span class="bg-danger-focus text-danger-main px-24 py-4 rounded-pill fw-medium text-sm">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center gap-10 justify-content-center">
                                        <a href="itens_retirar_edit.php?id=<?php echo $item['id']; ?>" 
                                           class="bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle"
                                           title="Editar">
                                            <iconify-icon icon="lucide:edit" class="icon text-xl"></iconify-icon>
                                        </a>
                                        <a href="itens_retirar.php?action=delete&id=<?php echo $item['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                           class="bg-danger-focus text-danger-600 bg-hover-danger-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle"
                                           onclick="return confirm('Tem certeza que deseja excluir este item?');"
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