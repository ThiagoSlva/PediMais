<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

verificar_login();

$msg = '';
$msg_type = '';

// Handle bulk deletion
if (isset($_POST['bulk_delete']) && isset($_POST['selected_ids'])) {
    if (!validar_csrf()) {
        $msg = 'Token de segurança inválido. Recarregue a página.';
        $msg_type = 'danger';
    }
    else {
        $selected_ids = $_POST['selected_ids'];
        if (is_array($selected_ids) && count($selected_ids) > 0) {
            $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));

            // First delete related items
            $stmt = $pdo->prepare("DELETE FROM grupo_adicional_itens WHERE grupo_id IN ($placeholders)");
            $stmt->execute($selected_ids);

            // Then delete groups
            $stmt = $pdo->prepare("DELETE FROM grupos_adicionais WHERE id IN ($placeholders)");
            $stmt->execute($selected_ids);

            $count = count($selected_ids);
            $msg = "$count grupo(s) excluído(s) com sucesso!";
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
        $pdo->exec("DELETE FROM grupo_adicional_itens");
        $pdo->exec("DELETE FROM grupos_adicionais");
        $msg = "Todos os grupos foram excluídos!";
        $msg_type = 'success';
    } // fecha else validar_csrf
}

// Handle single deletion
if (isset($_POST['delete_id'])) {
    if (!validar_csrf()) {
        $msg = 'Token de segurança inválido. Recarregue a página.';
        $msg_type = 'danger';
    }
    else {
        $id = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);
        if ($id) {
            $pdo->prepare("DELETE FROM grupo_adicional_itens WHERE grupo_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM grupos_adicionais WHERE id = ?")->execute([$id]);
            $msg = "Grupo excluído com sucesso!";
            $msg_type = 'success';
        }
    } // fecha else validar_csrf
}

// Build query
$sql = "SELECT * FROM grupos_adicionais ORDER BY ordem ASC, nome ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$base_url = SITE_URL . '/admin';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grupos e Complementos - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.7/dist/iconify-icon.min.js"></script>
    <style>
        .card-custom {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: none;
            margin-bottom: 20px;
        }
        .card-header-custom {
            background: linear-gradient(45deg, #4b6cb7, #182848);
            color: white;
            padding: 15px 20px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .bulk-actions {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 15px;
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
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .table tbody tr.selected {
            background-color: rgba(75, 108, 183, 0.1);
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>

        <div class="container-fluid py-4">
            <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php
endif; ?>

            <div class="card card-custom">
                <div class="card-header-custom">
                    <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i> Grupos de Complementos</h5>
                    <div class="d-flex gap-2">
                        <?php if (count($grupos) > 0): ?>
                        <button type="button" class="btn btn-outline-light btn-sm" onclick="confirmarDeletarTodos()">
                            <i class="fas fa-trash-alt"></i> Excluir Todos
                        </button>
                        <?php
endif; ?>
                        <a href="grupos_adicionais_add.php" class="btn btn-light btn-sm text-primary">
                            <i class="fas fa-plus"></i> Novo Grupo
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Bulk Actions Bar -->
                    <div class="bulk-actions" id="bulkActions">
                        <div>
                            <span id="selectedCount">0</span> item(s) selecionado(s)
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleSelectAll(false)">
                                <i class="fas fa-times"></i> Desmarcar Todos
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmarExcluirSelecionados()">
                                <i class="fas fa-trash"></i> Excluir Selecionados
                            </button>
                        </div>
                    </div>

                    <form method="POST" id="bulkForm">
                        <?php echo campo_csrf(); ?>
                        <input type="hidden" name="bulk_delete" value="1">
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40px;">
                                            <input type="checkbox" class="form-check-input select-row" id="selectAll" onchange="toggleSelectAll(this.checked)">
                                        </th>
                                        <th>Ordem</th>
                                        <th>Nome</th>
                                        <th>Tipo</th>
                                        <th>Mín/Máx</th>
                                        <th>Obrigatório</th>
                                        <th>Status</th>
                                        <th class="text-end">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grupos as $grupo): ?>
                                    <tr data-id="<?php echo $grupo['id']; ?>">
                                        <td>
                                            <input type="checkbox" class="form-check-input select-row item-checkbox" 
                                                   name="selected_ids[]" value="<?php echo $grupo['id']; ?>" 
                                                   onchange="updateBulkActions()">
                                        </td>
                                        <td><?php echo $grupo['ordem']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($grupo['nome']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($grupo['descricao']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo ucfirst($grupo['tipo_escolha']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $grupo['minimo_escolha'] . ' / ' . $grupo['maximo_escolha']; ?></td>
                                        <td>
                                            <?php if ($grupo['obrigatorio']): ?>
                                                <span class="badge bg-danger">Sim</span>
                                            <?php
    else: ?>
                                                <span class="badge bg-secondary">Não</span>
                                            <?php
    endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($grupo['ativo']): ?>
                                                <span class="badge bg-success">Ativo</span>
                                            <?php
    else: ?>
                                                <span class="badge bg-danger">Inativo</span>
                                            <?php
    endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="grupo_adicional_itens.php?grupo_id=<?php echo $grupo['id']; ?>" class="btn btn-sm btn-info text-white me-1" title="Gerenciar Itens">
                                                <i class="fas fa-list"></i> Itens
                                            </a>
                                            <a href="grupos_adicionais_edit.php?id=<?php echo $grupo['id']; ?>" class="btn btn-sm btn-primary me-1" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmarExcluir(<?php echo $grupo['id']; ?>)" title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php
endforeach; ?>
                                    
                                    <?php if (empty($grupos)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">Nenhum grupo cadastrado.</td>
                                    </tr>
                                    <?php
endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>

    <!-- Hidden form for single delete -->
    <form method="POST" id="singleDeleteForm" style="display: none;">
        <?php echo campo_csrf(); ?>
        <input type="hidden" name="delete_id" id="deleteId">
    </form>

    <!-- Hidden form for delete all -->
    <form method="POST" id="deleteAllForm" style="display: none;">
        <?php echo campo_csrf(); ?>
        <input type="hidden" name="delete_all" value="1">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
        if (confirm(`⚠️ Tem certeza que deseja excluir ${count} grupo(s)?\n\nOs itens de cada grupo também serão excluídos.\n\nEsta ação não pode ser desfeita!`)) {
            document.getElementById('bulkForm').submit();
        }
    }
    
    function confirmarExcluir(id) {
        if (confirm('Tem certeza que deseja excluir este grupo?\n\nOs itens deste grupo também serão excluídos.')) {
            document.getElementById('deleteId').value = id;
            document.getElementById('singleDeleteForm').submit();
        }
    }
    
    function confirmarDeletarTodos() {
        if (confirm('⚠️ ATENÇÃO: Tem certeza que deseja excluir TODOS os grupos?\n\nTodos os itens também serão excluídos.\n\nEsta ação NÃO PODE ser desfeita!')) {
            if (confirm('⚠️ ÚLTIMA CONFIRMAÇÃO:\n\nVocê está prestes a excluir TODOS os grupos de complementos.\n\nTem certeza absoluta?')) {
                document.getElementById('deleteAllForm').submit();
            }
        }
    }
    </script>

</body>
</html>