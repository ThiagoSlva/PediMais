<?php
require_once '../includes/config.php';
require_once 'includes/auth.php';

verificar_login_cliente();

$cliente_id = $_SESSION['cliente_id'];

// Get client data
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all addresses (max 5)
$stmt = $pdo->prepare("SELECT * FROM cliente_enderecos WHERE cliente_id = ? ORDER BY principal DESC, id DESC LIMIT 5");
$stmt->execute([$cliente_id]);
$enderecos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_enderecos = count($enderecos);
$pode_adicionar = $total_enderecos < 5;

$page_title = 'Meus Endereços';
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4 fade-in">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 0.875rem;">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color: var(--primary);">Início</a></li>
                <li class="breadcrumb-item active" style="color: var(--gray-500);">Endereços</li>
            </ol>
        </nav>
        <h4 class="mb-0" style="color: var(--gray-900);">
            <i class="fa-solid fa-location-dot me-2 text-primary"></i>Meus Endereços
        </h4>
    </div>
    <?php if ($pode_adicionar): ?>
    <button class="btn btn-premium btn-primary-gradient" onclick="abrirModalEndereco()">
        <i class="fa-solid fa-plus"></i>
        <span class="d-none d-sm-inline">Novo Endereço</span>
    </button>
    <?php endif; ?>
</div>

<!-- Info Card -->
<div class="alert d-flex align-items-center gap-3 mb-4 fade-in" style="background: linear-gradient(135deg, rgba(156, 39, 176, 0.05), rgba(224, 64, 251, 0.05)); border: 1px solid rgba(156, 39, 176, 0.1); border-radius: var(--radius-md);">
    <i class="fa-solid fa-info-circle fa-lg" style="color: var(--primary);"></i>
    <div style="font-size: 0.875rem; color: var(--gray-700);">
        Você pode cadastrar até <strong>5 endereços</strong>. Defina um como principal para facilitar seus pedidos.
        <span class="d-block mt-1" style="color: var(--gray-500);">
            <?php echo $total_enderecos; ?>/5 endereços cadastrados
        </span>
    </div>
</div>

<!-- Addresses Grid -->
<div class="row g-3" id="enderecosContainer">
    <?php if (empty($enderecos)): ?>
        <div class="col-12">
            <div class="empty-state py-5 fade-in">
                <div class="empty-icon">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <div class="empty-title">Nenhum endereço cadastrado</div>
                <div class="empty-text">Adicione endereços para facilitar seus pedidos</div>
                <button class="btn btn-premium btn-primary-gradient" onclick="abrirModalEndereco()">
                    <i class="fa-solid fa-plus me-2"></i>Adicionar Primeiro Endereço
                </button>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($enderecos as $index => $endereco): ?>
            <div class="col-md-6 fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s;" id="endereco-<?php echo $endereco['id']; ?>">
                <div class="address-card <?php echo $endereco['principal'] ? 'principal' : ''; ?>">
                    <?php if ($endereco['principal']): ?>
                        <div class="badge-principal">
                            <i class="fa-solid fa-star me-1"></i>Principal
                        </div>
                    <?php endif; ?>
                    
                    <div class="address-title">
                        <i class="fa-solid fa-house" style="color: var(--primary);"></i>
                        <?php echo htmlspecialchars($endereco['apelido'] ?: 'Endereço'); ?>
                    </div>
                    
                    <div class="address-text">
                        <?php echo htmlspecialchars($endereco['rua']); ?>, <?php echo htmlspecialchars($endereco['numero']); ?>
                        <?php if ($endereco['complemento']): ?>
                            - <?php echo htmlspecialchars($endereco['complemento']); ?>
                        <?php endif; ?>
                        <br>
                        <?php echo htmlspecialchars($endereco['bairro']); ?> - <?php echo htmlspecialchars($endereco['cidade']); ?>/<?php echo htmlspecialchars($endereco['estado']); ?>
                        <br>
                        <span style="color: var(--gray-500);">CEP: <?php echo htmlspecialchars($endereco['cep']); ?></span>
                    </div>
                    
                    <div class="address-actions">
                        <?php if (!$endereco['principal']): ?>
                            <button class="btn btn-sm btn-outline-premium" onclick="definirPrincipal(<?php echo $endereco['id']; ?>)">
                                <i class="fa-solid fa-star me-1"></i>Definir Principal
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-glass" onclick="editarEndereco(<?php echo $endereco['id']; ?>)">
                            <i class="fa-solid fa-pen me-1"></i>Editar
                        </button>
                        <button class="btn btn-sm btn-glass text-danger" onclick="excluirEndereco(<?php echo $endereco['id']; ?>)">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal Add/Edit Address -->
<div class="modal fade modal-premium" id="modalEndereco" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEnderecoTitle">
                    <i class="fa-solid fa-location-dot me-2 text-primary"></i>Novo Endereço
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="formEndereco" class="form-premium">
                    <input type="hidden" id="enderecoId" value="">
                    
                    <div class="mb-3">
                        <label class="form-label">Apelido (opcional)</label>
                        <input type="text" class="form-control" id="apelido" placeholder="Ex: Casa, Trabalho, Mãe...">
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-5">
                            <label class="form-label">CEP <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control cep-mask" id="cep" placeholder="00000-000" required>
                                <button type="button" class="btn btn-outline-premium" onclick="buscarCep()" id="btnBuscarCep">
                                    <i class="fa-solid fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-7">
                            <label class="form-label">Número <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="numero" placeholder="123" required>
                        </div>
                    </div>
                    
                    <div class="mb-3 mt-3">
                        <label class="form-label">Rua <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="rua" placeholder="Nome da rua" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Complemento (opcional)</label>
                        <input type="text" class="form-control" id="complemento" placeholder="Apto, Bloco, Casa...">
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Bairro <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="bairro" placeholder="Bairro" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Cidade <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="cidade" placeholder="Cidade" required>
                        </div>
                    </div>
                    
                    <div class="mb-3 mt-3">
                        <label class="form-label">Estado <span class="text-danger">*</span></label>
                        <select class="form-control" id="estado" required>
                            <option value="">Selecione...</option>
                            <option value="AC">Acre</option>
                            <option value="AL">Alagoas</option>
                            <option value="AP">Amapá</option>
                            <option value="AM">Amazonas</option>
                            <option value="BA">Bahia</option>
                            <option value="CE">Ceará</option>
                            <option value="DF">Distrito Federal</option>
                            <option value="ES">Espírito Santo</option>
                            <option value="GO">Goiás</option>
                            <option value="MA">Maranhão</option>
                            <option value="MT">Mato Grosso</option>
                            <option value="MS">Mato Grosso do Sul</option>
                            <option value="MG">Minas Gerais</option>
                            <option value="PA">Pará</option>
                            <option value="PB">Paraíba</option>
                            <option value="PR">Paraná</option>
                            <option value="PE">Pernambuco</option>
                            <option value="PI">Piauí</option>
                            <option value="RJ">Rio de Janeiro</option>
                            <option value="RN">Rio Grande do Norte</option>
                            <option value="RS">Rio Grande do Sul</option>
                            <option value="RO">Rondônia</option>
                            <option value="RR">Roraima</option>
                            <option value="SC">Santa Catarina</option>
                            <option value="SP">São Paulo</option>
                            <option value="SE">Sergipe</option>
                            <option value="TO">Tocantins</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-glass" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-premium btn-primary-gradient" onclick="salvarEndereco()" id="btnSalvarEndereco">
                    <i class="fa-solid fa-check me-2"></i>Salvar Endereço
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const modalEndereco = new bootstrap.Modal(document.getElementById('modalEndereco'));
let editandoId = null;

function abrirModalEndereco() {
    editandoId = null;
    document.getElementById('modalEnderecoTitle').innerHTML = '<i class="fa-solid fa-location-dot me-2 text-primary"></i>Novo Endereço';
    document.getElementById('formEndereco').reset();
    document.getElementById('enderecoId').value = '';
    modalEndereco.show();
}

function buscarCep() {
    const cep = document.getElementById('cep').value.replace(/\D/g, '');
    const btn = document.getElementById('btnBuscarCep');
    
    if (cep.length !== 8) {
        showToast('CEP inválido. Digite 8 números.', 'warning');
        return;
    }
    
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    btn.disabled = true;
    
    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
            if (data.erro) {
                showToast('CEP não encontrado', 'error');
            } else {
                document.getElementById('rua').value = data.logradouro || '';
                document.getElementById('bairro').value = data.bairro || '';
                document.getElementById('cidade').value = data.localidade || '';
                document.getElementById('estado').value = data.uf || '';
                document.getElementById('numero').focus();
                showToast('Endereço encontrado!', 'success');
            }
        })
        .catch(error => {
            showToast('Erro ao buscar CEP', 'error');
        })
        .finally(() => {
            btn.innerHTML = '<i class="fa-solid fa-search"></i>';
            btn.disabled = false;
        });
}

function editarEndereco(id) {
    editandoId = id;
    document.getElementById('modalEnderecoTitle').innerHTML = '<i class="fa-solid fa-pen me-2 text-primary"></i>Editar Endereço';
    
    // Fetch address data
    fetch(`api/enderecos.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const endereco = data.endereco;
                document.getElementById('enderecoId').value = endereco.id;
                document.getElementById('apelido').value = endereco.apelido || '';
                document.getElementById('cep').value = endereco.cep || '';
                document.getElementById('rua').value = endereco.rua || '';
                document.getElementById('numero').value = endereco.numero || '';
                document.getElementById('complemento').value = endereco.complemento || '';
                document.getElementById('bairro').value = endereco.bairro || '';
                document.getElementById('cidade').value = endereco.cidade || '';
                document.getElementById('estado').value = endereco.estado || '';
                modalEndereco.show();
            } else {
                showToast(data.message || 'Erro ao carregar endereço', 'error');
            }
        })
        .catch(error => {
            showToast('Erro ao carregar endereço', 'error');
        });
}

function salvarEndereco() {
    const form = document.getElementById('formEndereco');
    const btn = document.getElementById('btnSalvarEndereco');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    setLoading(btn, true);
    
    const dados = {
        id: document.getElementById('enderecoId').value || null,
        apelido: document.getElementById('apelido').value,
        cep: document.getElementById('cep').value.replace(/\D/g, ''),
        rua: document.getElementById('rua').value,
        numero: document.getElementById('numero').value,
        complemento: document.getElementById('complemento').value,
        bairro: document.getElementById('bairro').value,
        cidade: document.getElementById('cidade').value,
        estado: document.getElementById('estado').value
    };
    
    const method = dados.id ? 'PUT' : 'POST';
    
    fetch('api/enderecos.php', {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dados)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            modalEndereco.hide();
            setTimeout(() => location.reload(), 500);
        } else {
            showToast(data.message || 'Erro ao salvar endereço', 'error');
        }
    })
    .catch(error => {
        showToast('Erro ao salvar endereço', 'error');
    })
    .finally(() => {
        setLoading(btn, false);
    });
}

function definirPrincipal(id) {
    Swal.fire({
        title: 'Definir como principal?',
        text: 'Este endereço será usado como padrão nos seus pedidos.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#9C27B0',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Sim, definir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api/enderecos.php', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, action: 'set_principal' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Endereço definido como principal!', 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast(data.message || 'Erro ao definir principal', 'error');
                }
            })
            .catch(error => {
                showToast('Erro ao definir principal', 'error');
            });
        }
    });
}

function excluirEndereco(id) {
    confirmDelete('Este endereço será removido permanentemente.').then((result) => {
        if (result.isConfirmed) {
            fetch('api/enderecos.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Endereço removido com sucesso!', 'success');
                    document.getElementById('endereco-' + id).remove();
                    
                    // Check if no addresses left
                    if (document.querySelectorAll('[id^="endereco-"]').length === 0) {
                        location.reload();
                    }
                } else {
                    showToast(data.message || 'Erro ao remover endereço', 'error');
                }
            })
            .catch(error => {
                showToast('Erro ao remover endereço', 'error');
            });
        }
    });
}

// CEP mask on keyup
document.getElementById('cep').addEventListener('keyup', function(e) {
    if (this.value.replace(/\D/g, '').length === 8 && e.key !== 'Backspace') {
        buscarCep();
    }
});
</script>

<?php include 'includes/footer.php'; ?>