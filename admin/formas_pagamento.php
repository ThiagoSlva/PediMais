<?php
include 'includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';
require_once '../includes/csrf.php';

verificar_login();

$msg = '';
$msg_tipo = '';

// Migration Logic
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS formas_pagamento (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        tipo VARCHAR(50) NOT NULL,
        aceita_troco TINYINT(1) DEFAULT 0,
        chave_pix VARCHAR(255),
        icone VARCHAR(100),
        ordem INT DEFAULT 0,
        ativo TINYINT(1) DEFAULT 1
    )");
}
catch (PDOException $e) {
// Table might already exist
}

// Process Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validar_csrf()) {
        $msg = 'Token de segurança inválido. Recarregue a página.';
        $msg_tipo = 'danger';
    }
    else {
        try {
            if (isset($_POST['acao'])) {
                $acao = $_POST['acao'];

                if ($acao == 'salvar') {
                    $id = (int)$_POST['id'];
                    $nome = $_POST['nome'];
                    $tipo = $_POST['tipo'];
                    $aceita_troco = isset($_POST['aceita_troco']) ? 1 : 0;
                    $chave_pix = $_POST['chave_pix'] ?? '';
                    $icone = $_POST['icone'];
                    $ordem = (int)$_POST['ordem'];
                    $ativo = isset($_POST['ativo']) ? 1 : 0;

                    if ($id > 0) {
                        // Update
                        $stmt = $pdo->prepare("UPDATE formas_pagamento SET nome = ?, tipo = ?, aceita_troco = ?, chave_pix = ?, icone = ?, ordem = ?, ativo = ? WHERE id = ?");
                        $stmt->execute([$nome, $tipo, $aceita_troco, $chave_pix, $icone, $ordem, $ativo, $id]);
                        $msg = 'Forma de pagamento atualizada com sucesso!';
                    }
                    else {
                        // Insert
                        $stmt = $pdo->prepare("INSERT INTO formas_pagamento (nome, tipo, aceita_troco, chave_pix, icone, ordem, ativo) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$nome, $tipo, $aceita_troco, $chave_pix, $icone, $ordem, $ativo]);
                        $msg = 'Forma de pagamento adicionada com sucesso!';
                    }
                    $msg_tipo = 'success';

                }
                elseif ($acao == 'deletar') {
                    $id = (int)$_POST['id'];
                    $stmt = $pdo->prepare("DELETE FROM formas_pagamento WHERE id = ?");
                    $stmt->execute([$id]);
                    $msg = 'Forma de pagamento excluída!';
                    $msg_tipo = 'success';
                }
            }
        }
        catch (PDOException $e) {
            $msg = 'Erro ao salvar: ' . $e->getMessage();
            $msg_tipo = 'danger';
        }
    } // fecha else validar_csrf
}

// Fetch Data for Edit
$edit_data = null;
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM formas_pagamento WHERE id = ?");
    $stmt->execute([$id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch All Items
$stmt = $pdo->query("SELECT * FROM formas_pagamento ORDER BY ordem ASC, id ASC");
$formas = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Formas de Pagamento</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Pagamento</li>
        </ul>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_tipo; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php
endif; ?>

    <style>
    /* Dark mode support */
    [data-theme="dark"] .card,
    html[data-theme="dark"] .card {
        background-color: var(--base-soft, #1e1e2e) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    
    [data-theme="dark"] .table {
        color: rgba(255, 255, 255, 0.9) !important;
    }
    
    [data-theme="dark"] .table tbody tr:hover {
        background-color: rgba(255, 255, 255, 0.05) !important;
    }
    
    [data-theme="dark"] .form-control,
    [data-theme="dark"] .form-select {
        background-color: rgba(255, 255, 255, 0.05) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: rgba(255, 255, 255, 0.9) !important;
    }
    
    [data-theme="dark"] .select-icon-btn {
        background-color: rgba(255, 255, 255, 0.05) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: rgba(255, 255, 255, 0.9) !important;
    }
    </style>

    <div class="row">
        <!-- Formulário -->
        <div class="col-md-5">
            <div class="card radius-12 mb-3">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:add-circle-outline"></iconify-icon>
                        <?php echo $edit_data ? 'Editar Forma de Pagamento' : 'Adicionar Forma de Pagamento'; ?>
                    </h6>
                </div>
                <div class="card-body p-24">
                    <form method="POST" action="formas_pagamento.php">
                        <?php echo campo_csrf(); ?>
                        <input type="hidden" name="acao" value="salvar">
                        <input type="hidden" name="id" value="<?php echo $edit_data ? $edit_data['id'] : '0'; ?>">
                        
                        <div class="mb-20">
                            <label class="form-label fw-semibold">Nome <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nome" 
                                   value="<?php echo $edit_data ? htmlspecialchars($edit_data['nome']) : ''; ?>" required>
                        </div>
                        
                        <div class="mb-20">
                            <label class="form-label fw-semibold">Tipo</label>
                            <select class="form-select" name="tipo" id="tipo" onchange="toggleCamposEspecificos()">
                                <option value="dinheiro" <?php echo($edit_data && $edit_data['tipo'] == 'dinheiro') ? 'selected' : ''; ?>>Dinheiro</option>
                                <option value="credito" <?php echo($edit_data && $edit_data['tipo'] == 'credito') ? 'selected' : ''; ?>>Cartão de Crédito</option>
                                <option value="debito" <?php echo($edit_data && $edit_data['tipo'] == 'debito') ? 'selected' : ''; ?>>Cartão de Débito</option>
                                <option value="pix" <?php echo($edit_data && $edit_data['tipo'] == 'pix') ? 'selected' : ''; ?>>PIX</option>
                                <option value="outro" <?php echo($edit_data && $edit_data['tipo'] == 'outro') ? 'selected' : ''; ?>>Outro</option>
                            </select>
                        </div>
                        
                        <div class="mb-20" id="campo-troco" style="display: none;">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="aceita_troco" id="aceita_troco" 
                                       <?php echo($edit_data && $edit_data['aceita_troco']) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Aceita Troco? (opcional para cliente)</label>
                            </div>
                        </div>
                        
                        <div class="mb-20" id="campo-pix" style="display: none;">
                            <label class="form-label fw-semibold">Chave PIX</label>
                            <input type="text" class="form-control" name="chave_pix" id="chave_pix" 
                                   value="<?php echo $edit_data ? htmlspecialchars($edit_data['chave_pix']) : ''; ?>" 
                                   placeholder="CPF, CNPJ, Email, Telefone ou Chave Aleatória">
                            <small class="text-secondary-light">Cliente poderá copiar esta chave</small>
                        </div>
                        
                        <div class="mb-20">
                            <label class="form-label fw-semibold">Ícone (Iconify)</label>
                            <div class="input-group">
                                <span class="input-group-text" id="icone-preview">
                                    <iconify-icon icon="<?php echo $edit_data ? $edit_data['icone'] : 'solar:wallet-outline'; ?>" id="icone-preview-icon"></iconify-icon>
                                </span>
                                <input type="text" class="form-control" name="icone" id="icone" 
                                       value="<?php echo $edit_data ? htmlspecialchars($edit_data['icone']) : 'solar:wallet-outline'; ?>" 
                                       placeholder="solar:wallet-outline" aria-describedby="icone-preview">
                                <button type="button" class="btn btn-outline-primary d-flex align-items-center gap-2" id="abrirIconPicker">
                                    <iconify-icon icon="solar:search-linear"></iconify-icon>
                                    Escolher Ícone
                                </button>
                            </div>
                            <small class="text-secondary-light">Pesquise e selecione qualquer ícone disponível na Iconify.</small>
                        </div>
                        
                        <div class="mb-20">
                            <label class="form-label fw-semibold">Ordem</label>
                            <input type="number" class="form-control" name="ordem" 
                                   value="<?php echo $edit_data ? $edit_data['ordem'] : '0'; ?>" min="0">
                        </div>
                        
                        <div class="mb-20">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="ativo" 
                                       <?php echo(!$edit_data || $edit_data['ativo']) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Ativo</label>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                                <iconify-icon icon="solar:check-circle-outline"></iconify-icon>
                                <?php echo $edit_data ? 'Salvar Alterações' : 'Adicionar'; ?>
                            </button>
                            <?php if ($edit_data): ?>
                                <a href="formas_pagamento.php" class="btn btn-outline-secondary">Cancelar</a>
                            <?php
endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Listagem -->
        <div class="col-md-7">
            <div class="card radius-12">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:wallet-outline"></iconify-icon>
                        Formas de Pagamento Cadastradas
                    </h6>
                </div>
                <div class="card-body p-24">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="min-width: 80px;">Ordem</th>
                                    <th style="min-width: 200px;">Nome</th>
                                    <th style="min-width: 120px;">Tipo</th>
                                    <th style="min-width: 150px;">Detalhes</th>
                                    <th style="min-width: 100px;">Status</th>
                                    <th style="min-width: 120px;" class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($formas as $f): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-primary-focus text-primary-main"><?php echo $f['ordem']; ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <iconify-icon icon="<?php echo $f['icone']; ?>" style="font-size: 1.25em; line-height: 1; display: inline-flex;"></iconify-icon>
                                            <strong><?php echo htmlspecialchars($f['nome']); ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-focus text-info-main d-inline-flex align-items-center gap-1">
                                            <?php echo ucfirst($f['tipo']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($f['tipo'] == 'dinheiro' && $f['aceita_troco']): ?>
                                            <div class="d-flex align-items-center gap-1">
                                                <iconify-icon icon="solar:check-circle-bold" style="font-size: 14px; color: #10b981;"></iconify-icon>
                                                <small class="text-success">Aceita troco</small>
                                            </div>
                                        <?php
    elseif ($f['tipo'] == 'pix' && !empty($f['chave_pix'])): ?>
                                            <div class="d-flex align-items-center gap-1">
                                                <iconify-icon icon="solar:qr-code-bold" style="font-size: 14px;"></iconify-icon>
                                                <small class="text-primary text-truncate" style="max-width: 150px;"><?php echo htmlspecialchars($f['chave_pix']); ?></small>
                                            </div>
                                        <?php
    else: ?>
                                            <span class="text-secondary-light">—</span>
                                        <?php
    endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($f['ativo']): ?>
                                            <span class="badge bg-success-focus text-success-main d-inline-flex align-items-center gap-1">
                                                <iconify-icon icon="solar:check-circle-bold" style="font-size: 12px;"></iconify-icon>
                                                Ativo
                                            </span>
                                        <?php
    else: ?>
                                            <span class="badge bg-danger-focus text-danger-main d-inline-flex align-items-center gap-1">
                                                <iconify-icon icon="solar:close-circle-bold" style="font-size: 12px;"></iconify-icon>
                                                Inativo
                                            </span>
                                        <?php
    endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 justify-content-end">
                                            <a href="?editar=<?php echo $f['id']; ?>" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center" title="Editar" style="width: 36px; height: 36px; padding: 0;">
                                                <iconify-icon icon="solar:pen-bold" style="font-size: 16px; line-height: 1;"></iconify-icon>
                                            </a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir esta forma de pagamento?')">
                                                <?php echo campo_csrf(); ?>
                                                <input type="hidden" name="acao" value="deletar">
                                                <input type="hidden" name="id" value="<?php echo $f['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center" title="Excluir" style="width: 36px; height: 36px; padding: 0;">
                                                    <iconify-icon icon="solar:trash-bin-trash-bold" style="font-size: 16px; line-height: 1;"></iconify-icon>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php
endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Escolher Ícone -->
    <div class="modal fade" id="iconPickerModal" tabindex="-1" aria-labelledby="iconPickerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2" id="iconPickerModalLabel">
                        <iconify-icon icon="solar:gallery-search-linear"></iconify-icon>
                        Selecionar Ícone
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3 align-items-end">
                        <div class="col-md-6">
                            <label for="iconSearch" class="form-label">Buscar por palavra-chave</label>
                            <input type="text" class="form-control" id="iconSearch" placeholder="Ex: card, wallet, money">
                        </div>
                        <div class="col-md-6">
                            <label for="iconSet" class="form-label">Coleção</label>
                            <select class="form-select" id="iconSet">
                                <option value="mdi" selected>Material Design Icons (mdi)</option>
                                <option value="tabler">Tabler Icons (tabler)</option>
                                <option value="bi">Bootstrap Icons (bi)</option>
                                <option value="solar">Solar Icons (solar)</option>
                                <option value="fa">Font Awesome (fa)</option>
                            </select>
                        </div>
                    </div>
                    <div class="text-secondary-light small mb-3">
                        Dica: pesquise termos em inglês (ex.: <em>credit card</em>, <em>cash</em>, <em>qr code</em>).
                    </div>
                    <div id="iconResults" class="row g-2" style="max-height: 420px; overflow-y: auto;">
                        <!-- Ícones carregados dinamicamente -->
                    </div>
                    <div id="iconLoading" class="text-center py-4 d-none">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 mb-0">Carregando ícones...</p>
                    </div>
                    <div id="iconEmpty" class="text-center py-4 d-none text-secondary-light">
                        <iconify-icon icon="solar:search-linear" style="font-size: 48px;"></iconify-icon>
                        <p class="mt-3 mb-0">Nenhum ícone encontrado. Tente outra palavra-chave.</p>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <a href="https://icon-sets.iconify.design/" target="_blank" class="btn btn-link">Ver biblioteca completa na Iconify</a>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function toggleCamposEspecificos() {
        const tipo = document.getElementById('tipo').value;
        const campoTroco = document.getElementById('campo-troco');
        const campoPix = document.getElementById('campo-pix');
        
        // Mostra campo de troco apenas para dinheiro
        if (tipo === 'dinheiro') {
            campoTroco.style.display = 'block';
        } else {
            campoTroco.style.display = 'none';
        }
        
        // Mostra campo de chave PIX apenas para pix
        if (tipo === 'pix') {
            campoPix.style.display = 'block';
        } else {
            campoPix.style.display = 'none';
        }
    }

    // Executar ao carregar
    document.addEventListener('DOMContentLoaded', toggleCamposEspecificos);

    // Modificar a inicialização para aguardar o DOM e scripts carregarem
    document.addEventListener('DOMContentLoaded', function() {
        const iconInput = document.getElementById('icone');
        const iconPreview = document.getElementById('icone-preview-icon');
        const openModalBtn = document.getElementById('abrirIconPicker');
        const iconSearch = document.getElementById('iconSearch');
        const iconSet = document.getElementById('iconSet');
        const iconResults = document.getElementById('iconResults');
        const iconLoading = document.getElementById('iconLoading');
        const iconEmpty = document.getElementById('iconEmpty');
        const modalElement = document.getElementById('iconPickerModal');
        
        // Verificar se bootstrap está disponível
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap não carregado');
            return;
        }

        const modal = modalElement ? new bootstrap.Modal(modalElement) : null;

        if (!iconInput || !iconPreview || !openModalBtn || !modal) {
            return;
        }

        function updatePreview(value) {
            const iconName = value && value.trim() !== '' ? value.trim() : 'solar:wallet-outline';
            iconPreview.setAttribute('icon', iconName);
        }

        iconInput.addEventListener('input', function() {
            updatePreview(this.value);
        });

        openModalBtn.addEventListener('click', function() {
            iconSearch.value = '';
            iconResults.innerHTML = '';
            iconEmpty.classList.add('d-none');
            modal.show();
            setTimeout(() => {
                if (iconSearch.value.trim() === '') {
                    iconSearch.value = 'wallet';
                }
                buscarIcones();
            }, 200);
        });

        async function buscarIcones() {
            const termo = iconSearch.value.trim();
            const set = iconSet.value.trim();
            if (termo.length < 2) {
                iconResults.innerHTML = '';
                iconEmpty.classList.remove('d-none');
                return;
            }

            iconLoading.classList.remove('d-none');
            iconEmpty.classList.add('d-none');
            iconResults.innerHTML = '';

            try {
                const response = await fetch(`https://api.iconify.design/search?query=${encodeURIComponent(termo)}&prefix=${encodeURIComponent(set)}&limit=60`);
                if (!response.ok) {
                    throw new Error('Falha na busca de ícones');
                }
                const data = await response.json();
                const icons = data.icons || [];
                iconResults.innerHTML = '';

                if (icons.length === 0) {
                    iconEmpty.classList.remove('d-none');
                    return;
                }

                icons.forEach(iconName => {
                    const col = document.createElement('div');
                    col.className = 'col-6 col-md-3';
                    col.innerHTML = `
                        <button type="button" class="btn w-100 border select-icon-btn" data-icon="${iconName}" title="${iconName}">
                            <div class="py-3 d-flex flex-column align-items-center gap-2">
                                <iconify-icon icon="${iconName}" style="font-size: 32px;"></iconify-icon>
                                <small class="text-truncate w-100">${iconName}</small>
                            </div>
                        </button>
                    `;
                    iconResults.appendChild(col);
                });
            } catch (error) {
                console.error(error);
                iconEmpty.classList.remove('d-none');
                iconEmpty.innerHTML = '<p class="mb-0">Erro ao carregar ícones. Tente novamente.</p>';
            } finally {
                iconLoading.classList.add('d-none');
            }
        }

        iconSearch.addEventListener('input', function() {
            if (this.dataset.timeout) {
                clearTimeout(this.dataset.timeout);
            }
            this.dataset.timeout = setTimeout(buscarIcones, 400);
        });

        iconSet.addEventListener('change', buscarIcones);

        iconResults.addEventListener('click', function(event) {
            const btn = event.target.closest('.select-icon-btn');
            if (!btn) return;
            const iconName = btn.getAttribute('data-icon');
            iconInput.value = iconName;
            updatePreview(iconName);
            modal.hide();
        });
    });
    </script>
</div>

<?php include 'includes/footer.php'; ?>