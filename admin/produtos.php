<?php
require_once 'includes/header.php';
require_once __DIR__ . '/../includes/csrf.php';

$msg = '';
$msg_type = '';

// Handle bulk deletion via POST
if (isset($_POST['bulk_delete']) && isset($_POST['selected_ids'])) {
    if (!validar_csrf()) {
        $msg = 'Token de segurança inválido. Recarregue a página.';
        $msg_type = 'danger';
    }
    else {
        $selected_ids = $_POST['selected_ids'];
        if (is_array($selected_ids) && count($selected_ids) > 0) {
            $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));

            // Delete related data first
            $pdo->prepare("DELETE FROM produto_grupos WHERE produto_id IN ($placeholders)")->execute($selected_ids);
            $pdo->prepare("DELETE FROM produto_adicionais WHERE produto_id IN ($placeholders)")->execute($selected_ids);
            $pdo->prepare("DELETE FROM produto_itens_retirar WHERE produto_id IN ($placeholders)")->execute($selected_ids);

            // Then delete products
            $stmt = $pdo->prepare("DELETE FROM produtos WHERE id IN ($placeholders)");
            $stmt->execute($selected_ids);

            $count = count($selected_ids);
            $msg = "$count produto(s) excluído(s) com sucesso!";
            $msg_type = 'success';
        }
    } // fecha else validar_csrf
}

// Handle delete all
if (isset($_POST['delete_all'])) {
    if (!validar_csrf()) {
        $msg = 'Token de segurança inválido. Recarregue a página.';
        $msg_type = 'danger';
    }
    else {
        $pdo->exec("DELETE FROM produto_grupos");
        $pdo->exec("DELETE FROM produto_adicionais");
        $pdo->exec("DELETE FROM produto_itens_retirar");
        $pdo->exec("DELETE FROM produtos");
        $msg = "Todos os produtos foram excluídos!";
        $msg_type = 'success';
    } // fecha else validar_csrf
}

// Handle single deletion via GET
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM produto_grupos WHERE produto_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM produto_adicionais WHERE produto_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM produto_itens_retirar WHERE produto_id = ?")->execute([$id]);

    $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
    if ($stmt->execute([$id])) {
        $msg = 'Produto excluído com sucesso!';
        $msg_type = 'success';
    }
    else {
        $msg = 'Erro ao excluir produto.';
        $msg_type = 'danger';
    }
}

// Filtros
$busca = isset($_GET['busca']) ? $_GET['busca'] : '';
$categoria_id = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;

$where = "WHERE 1=1";
$params = [];

if ($busca) {
    $where .= " AND p.nome LIKE ?";
    $params[] = "%$busca%";
}

if ($categoria_id > 0) {
    $where .= " AND p.categoria_id = ?";
    $params[] = $categoria_id;
}

// Paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 30;
$inicio = ($pagina - 1) * $limite;

// Contar total
$sql_count = "SELECT COUNT(*) FROM produtos p $where";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_registros = $stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / $limite);

// Buscar produtos
$sql = "SELECT p.*, c.nome as categoria_nome 
        FROM produtos p 
        LEFT JOIN categorias c ON p.categoria_id = c.id 
        $where 
        ORDER BY p.ordem ASC, p.id DESC 
        LIMIT $inicio, $limite";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar categorias para o filtro
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// Mensagem via GET
if (isset($_GET['mensagem'])) {
    $msg = $_GET['mensagem'];
    $msg_type = isset($_GET['tipo']) ? $_GET['tipo'] : 'success';
}
?>

<style>
.bulk-actions {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px dashed #7c3aed;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 20px;
    display: none;
}
.bulk-actions.show {
    display: flex;
    align-items: center;
    justify-content: space-between;
    animation: slideDown 0.3s ease;
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.select-row {
    width: 18px;
    height: 18px;
    cursor: pointer;
}
.table tbody tr.selected {
    background-color: rgba(124, 58, 237, 0.08);
}
</style>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Produtos</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Produtos</li>
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
            <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between mb-20">
                <h6 class="mb-2 fw-bold text-lg mb-0">Lista de Produtos</h6>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="text-sm fw-medium text-secondary-light"><?php echo $total_registros; ?> produto(s)</span>
                    <?php if ($total_registros > 0): ?>
                    <button type="button" class="btn btn-outline-danger text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2" onclick="confirmarDeletarTodos()">
                        <iconify-icon icon="solar:trash-bin-2-bold" class="icon text-lg"></iconify-icon>
                        <span>Excluir Todos</span>
                    </button>
                    <?php
endif; ?>
                    <a href="produtos_exportar.php" class="btn btn-outline-primary text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2">
                        <iconify-icon icon="ri:export-line" class="icon text-lg"></iconify-icon>
                        <span>Exportar CSV</span>
                    </a>
                    <a href="produtos_add.php" class="btn btn-primary text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:add-circle-outline" class="icon text-lg"></iconify-icon>
                        <span>Adicionar Novo</span>
                    </a>
                </div>
            </div>
            
            <!-- Filtros -->
            <form method="GET" action="produtos.php" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="busca" class="form-label fw-semibold text-sm mb-2">Buscar por Nome</label>
                        <input type="text" 
                               class="form-control radius-8" 
                               id="busca" 
                               name="busca" 
                               value="<?php echo htmlspecialchars($busca); ?>" 
                               placeholder="Digite o nome do produto...">
                    </div>
                    <div class="col-md-4">
                        <label for="categoria" class="form-label fw-semibold text-sm mb-2">Filtrar por Categoria</label>
                        <select class="form-select radius-8" id="categoria" name="categoria">
                            <option value="0">Todas as categorias</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $categoria_id == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['nome']); ?>
                                </option>
                            <?php
endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary radius-8 px-4 d-flex align-items-center gap-2">
                            <iconify-icon icon="solar:magnifer-outline"></iconify-icon>
                            Filtrar
                        </button>
                        <?php if ($busca || $categoria_id): ?>
                            <a href="produtos.php" class="btn btn-outline-secondary radius-8 px-4 d-flex align-items-center gap-2">
                                <iconify-icon icon="solar:refresh-outline"></iconify-icon>
                                Limpar
                            </a>
                        <?php
endif; ?>
                    </div>
                </div>
            </form>

            <!-- Bulk Actions Bar -->
            <div class="bulk-actions" id="bulkActions">
                <div class="d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:check-square-bold" class="text-primary" style="font-size: 24px;"></iconify-icon>
                    <span class="fw-semibold"><span id="selectedCount">0</span> produto(s) selecionado(s)</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm radius-8" onclick="toggleSelectAll(false)">
                        <iconify-icon icon="solar:close-circle-outline" class="me-1"></iconify-icon>
                        Desmarcar
                    </button>
                    <button type="button" class="btn btn-danger btn-sm radius-8" onclick="confirmarExcluirSelecionados()">
                        <iconify-icon icon="solar:trash-bin-trash-bold" class="me-1"></iconify-icon>
                        Excluir Selecionados
                    </button>
                </div>
            </div>
            
            <form method="POST" id="bulkForm">
                <?php echo campo_csrf(); ?>
                <input type="hidden" name="bulk_delete" value="1">
                
                <div class="table-responsive scroll-sm">
                    <table class="table bordered-table sm-table mb-0">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 40px;">
                                    <input type="checkbox" class="form-check-input select-row" id="selectAll" onchange="toggleSelectAll(this.checked)">
                                </th>
                                <th scope="col">Ordem</th>
                                <th scope="col">Imagem</th>
                                <th scope="col">Nome</th>
                                <th scope="col">Categoria</th>
                                <th scope="col">Preço</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($produtos)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">Nenhum produto encontrado.</td>
                                </tr>
                            <?php
else: ?>
                                <?php foreach ($produtos as $prod): ?>
                                <tr data-id="<?php echo $prod['id']; ?>">
                                    <td>
                                        <input type="checkbox" class="form-check-input select-row item-checkbox" 
                                               name="selected_ids[]" value="<?php echo $prod['id']; ?>" 
                                               onchange="updateBulkActions()">
                                    </td>
                                    <td class="fw-bold text-primary-600"><?php echo $prod['ordem']; ?></td>
                                    <td>
                                        <?php
        $img_url = $prod['imagem_path'] ? str_replace('admin/', '', $prod['imagem_path']) : 'assets/images/sem-foto.jpg';
        // Correção para caminho relativo no admin
        if ($img_url && strpos($img_url, 'uploads/') === 0) {
            $img_url = '../' . $img_url;
        }
?>
                                        <img src="<?php echo htmlspecialchars($img_url); ?>" alt="<?php echo htmlspecialchars($prod['nome']); ?>" 
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;"
                                             onerror="this.src='assets/images/sem-foto.jpg'; this.onerror=null;">
                                    </td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($prod['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($prod['categoria_nome']); ?></td>
                                    <td>
                                        <?php
        if ($prod['preco_promocional'] > 0) {
            echo '<span class="text-decoration-line-through text-secondary-light text-sm">R$ ' . number_format($prod['preco'], 2, ',', '.') . '</span><br>';
            echo '<span class="fw-semibold text-primary-600">R$ ' . number_format($prod['preco_promocional'], 2, ',', '.') . '</span>';
        }
        else {
            echo '<span class="fw-semibold text-primary-600">R$ ' . number_format($prod['preco'], 2, ',', '.') . '</span>';
        }
?>
                                    </td>
                                    <td>
                                        <?php if ($prod['disponivel']): ?>
                                            <span class="bg-success-focus text-success-main px-24 py-4 rounded-pill fw-medium text-sm">Disponível</span>
                                        <?php
        else: ?>
                                            <span class="bg-danger-focus text-danger-main px-24 py-4 rounded-pill fw-medium text-sm">Indisponível</span>
                                        <?php
        endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center gap-10 justify-content-center">
                                            <a href="produtos_edit.php?id=<?php echo $prod['id']; ?>" 
                                               class="bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle"
                                               title="Editar">
                                                <iconify-icon icon="lucide:edit" class="icon text-xl"></iconify-icon>
                                            </a>
                                            <button type="button" 
                                               class="bg-danger-focus text-danger-600 bg-hover-danger-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle border-0"
                                               onclick="confirmarExcluir(<?php echo $prod['id']; ?>)"
                                               title="Excluir">
                                                <iconify-icon icon="mingcute:delete-2-line" class="icon text-xl"></iconify-icon>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php
    endforeach; ?>
                            <?php
endif; ?>
                        </tbody>
                    </table>
                </div>
            </form>
            
            <!-- Paginação -->
            <?php if ($total_paginas > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($pagina > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?>&busca=<?php echo urlencode($busca); ?>&categoria=<?php echo $categoria_id; ?>">Anterior</a>
                    </li>
                    <?php
    endif; ?>
                    
                    <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
                    <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $i; ?>&busca=<?php echo urlencode($busca); ?>&categoria=<?php echo $categoria_id; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php
    endfor; ?>
                    
                    <?php if ($pagina < $total_paginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?>&busca=<?php echo urlencode($busca); ?>&categoria=<?php echo $categoria_id; ?>">Próxima</a>
                    </li>
                    <?php
    endif; ?>
                </ul>
                <p class="text-center text-sm text-muted">
                    Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?> (<?php echo $total_registros; ?> produtos total)
                </p>
            </nav>
            <?php
endif; ?>
        </div>
    </div>
</div>

<!-- Hidden form for delete all -->
<form method="POST" id="deleteAllForm" style="display: none;">
    <?php echo campo_csrf(); ?>
    <input type="hidden" name="delete_all" value="1">
</form>

<script>
function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    
    selectedCount.textContent = checkboxes.length;
    
    if (checkboxes.length > 0) {
        bulkActions.classList.add('show');
    } else {
        bulkActions.classList.remove('show');
    }
    
    // Update row highlight
    document.querySelectorAll('tbody tr').forEach(row => {
        const checkbox = row.querySelector('.item-checkbox');
        if (checkbox && checkbox.checked) {
            row.classList.add('selected');
        } else {
            row.classList.remove('selected');
        }
    });
    
    // Update select all checkbox
    const allCheckboxes = document.querySelectorAll('.item-checkbox');
    const selectAll = document.getElementById('selectAll');
    selectAll.checked = checkboxes.length === allCheckboxes.length && allCheckboxes.length > 0;
    selectAll.indeterminate = checkboxes.length > 0 && checkboxes.length < allCheckboxes.length;
}

function toggleSelectAll(checked) {
    document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.checked = checked;
    });
    updateBulkActions();
}

function confirmarExcluirSelecionados() {
    const count = document.querySelectorAll('.item-checkbox:checked').length;
    if (confirm(`⚠️ Tem certeza que deseja excluir ${count} produto(s)?\n\nEsta ação não pode ser desfeita!`)) {
        document.getElementById('bulkForm').submit();
    }
}

function confirmarExcluir(id) {
    if (confirm('Tem certeza que deseja excluir este produto?')) {
        window.location.href = `produtos.php?action=delete&id=${id}`;
    }
}

function confirmarDeletarTodos() {
    if (confirm('⚠️ ATENÇÃO: Tem certeza que deseja excluir TODOS os produtos?\n\nEsta ação NÃO PODE ser desfeita!')) {
        if (confirm('⚠️ ÚLTIMA CONFIRMAÇÃO:\n\nVocê está prestes a excluir TODOS os produtos do sistema.\n\nTem certeza absoluta?')) {
            document.getElementById('deleteAllForm').submit();
        }
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>