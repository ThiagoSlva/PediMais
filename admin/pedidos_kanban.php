<?php
require_once 'includes/header.php';

// Gerar token CSRF se não existir
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Kanban - Pedidos</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Kanban</li>
        </ul>
    </div>

    <style>
    .kanban-board {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        padding-bottom: 20px;
        min-height: calc(100vh - 250px);
    }

    .kanban-lane {
        flex: 0 0 320px;
        background: var(--card-bg);
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 200px);
    }

    .kanban-lane-header {
        padding: 16px;
        border-bottom: 2px solid;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }

    .kanban-lane-body {
        padding: 12px;
        overflow-y: auto;
        flex: 1;
        min-height: 200px;
    }

    .kanban-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 12px;
        cursor: move;
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .kanban-card.no-drag {
        cursor: default !important;
    }

    .fullscreen-hint {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: rgba(0,0,0,0.8);
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        font-size: 14px;
        z-index: 9999;
        animation: fadeIn 0.3s;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .kanban-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }

    .kanban-card.dragging {
        opacity: 0.5;
        transform: rotate(2deg);
    }

    .kanban-card-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 8px;
    }

    .kanban-card-id {
        font-weight: 700;
        font-size: 14px;
        color: #1f2937;
    }

    [data-theme="dark"] .kanban-card-id {
        color: #f3f4f6 !important;
    }

    .kanban-card-time {
        font-size: 11px;
        color: #6b7280;
    }

    .kanban-card-customer {
        font-weight: 600;
        font-size: 13px;
        color: #374151;
        margin-bottom: 4px;
    }

    .kanban-card-items {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 8px;
        max-height: 40px;
        overflow: hidden;
        text-overflow: ellipsis;
    }


    .kanban-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
    }

    .kanban-card-total {
        font-weight: 700;
        color: #059669;
        font-size: 14px;
    }

    .kanban-card-badges {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
    }

    .kanban-badge {
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: 600;
    }

    .kanban-card-actions {
        display: flex;
        gap: 4px;
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid #e5e7eb;
    }

    .kanban-btn {
        flex: 1;
        font-size: 11px;
        padding: 4px 8px;
        border-radius: 4px;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
    }

    .lane-empty {
        text-align: center;
        padding: 40px 20px;
        color: #9ca3af;
        font-size: 14px;
    }

    [data-theme="dark"] .kanban-card {
        background: #1f2937;
        border-color: #374151;
    }

    [data-theme="dark"] .kanban-card-id,
    [data-theme="dark"] .kanban-card-customer {
        color: #f3f4f6 !important;
    }

    [data-theme="dark"] .kanban-card-time {
        color: #9ca3af !important;
    }

    [data-theme="dark"] .kanban-card-items {
        color: #d1d5db !important;
    }

    [data-theme="dark"] code,
    [data-theme="dark"] .kanban-codigo-pedido {
        background-color: rgba(255, 255, 255, 0.1) !important;
        color: #f3f4f6 !important;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    [data-theme="dark"] .kanban-card-footer {
        border-top-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-theme="dark"] .kanban-card-actions {
        border-top-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-theme="dark"] .kanban-card-phone,
    [data-theme="dark"] .kanban-card-endereco {
        color: #9ca3af !important;
    }

    [data-theme="dark"] .lane-empty {
        color: #6b7280;
    }
    </style>

    <div class="card">
        <div class="card-header border-bottom bg-base py-16 px-24 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="text-lg fw-semibold mb-0">Kanban - Gestão de Pedidos</h6>
                <p class="text-sm text-secondary-light mb-0 mt-1">
                    Arraste os cards para mudar o status dos pedidos
                </p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalGerenciarLanes">
                    <iconify-icon icon="solar:settings-outline" class="text-lg"></iconify-icon>
                    Gerenciar Lanes
                </button>
                <a href="pedidos.php" class="btn btn-outline-secondary btn-sm">
                    <iconify-icon icon="solar:list-outline" class="text-lg"></iconify-icon>
                    Ver Lista
                </a>
            </div>
        </div>
        
        <div class="card-body p-24">
            <div class="kanban-board" id="kanban-board">
                <!-- Lanes serão carregadas via JS -->
                <div class="text-center w-100 py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Gerenciar Lanes -->
    <div class="modal fade" id="modalGerenciarLanes" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gerenciar Lanes do Kanban</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <button type="button" class="btn btn-primary btn-sm mb-3" onclick="showAddLaneForm()">
                        <iconify-icon icon="solar:add-circle-outline"></iconify-icon>
                        Adicionar Lane
                    </button>
                    
                    <div id="addLaneForm" style="display: none;" class="mb-3 p-3 border rounded">
                        <form id="formAddLane">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <div class="row gy-2">
                                <div class="col-md-4">
                                    <input type="text" name="nome" class="form-control" placeholder="Nome da Lane" required>
                                </div>
                                <div class="col-md-2">
                                    <input type="color" name="cor" class="form-control" value="#6c757d" title="Cor da lane">
                                </div>
                                <div class="col-md-4">
                                    <select name="acao" class="form-select" title="Ação ao mover pedido para esta lane">
                                        <option value="">Nenhuma ação</option>
                                        <option value="em_preparo">Marcar Em Preparo</option>
                                        <option value="pronto">Marcar Pronto</option>
                                        <option value="saiu_entrega">Marcar Saiu Entrega</option>
                                        <option value="entregue">Marcar Entregue</option>
                                        <option value="finalizar">Finalizar/Arquivar</option>
                                        <option value="cancelar">Cancelar Pedido</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-success w-100">Salvar</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Ordem</th>
                                    <th>Nome</th>
                                    <th>Cor</th>
                                    <th>Ação Automática</th>
                                    <th>Excluir</th>
                                </tr>
                            </thead>
                            <tbody id="lanesTableBody">
                                <!-- Preenchido via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Permissões do usuário
const userLevel = '<?php echo $_SESSION['usuario_nivel'] ?? 'admin'; ?>';
const canDrag = true;
const isCozinha = false;
const isEntregador = false;
const isAdminGerente = true;
const sistemaEntregadoresAtivo = true;

let draggedCard = null;
let isDragging = false;
let isUpdatingKanban = false;
let ultimoSnapshot = null;
let kanbanIntervalId = null;
const KANBAN_REFRESH_INTERVAL = 10000; // 10s

const currencyFormatter = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL'
});

function handleDragStart(e) {
    draggedCard = this;
    this.classList.add('dragging');
    isDragging = true;
    e.dataTransfer.effectAllowed = 'move';
}

function handleDragEnd() {
    this.classList.remove('dragging');
    draggedCard = null;
    isDragging = false;
}

function inicializarCartoesArrastaveis() {
    document.querySelectorAll('.kanban-card').forEach(card => {
        card.removeEventListener('dragstart', handleDragStart);
        card.removeEventListener('dragend', handleDragEnd);

        if (!canDrag || isEntregador || card.classList.contains('no-drag')) {
            card.setAttribute('draggable', 'false');
            return;
        }

        card.setAttribute('draggable', 'true');
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Inicializar listeners para lanes (delegation)
    const board = document.getElementById('kanban-board');
    if (board) {
        board.addEventListener('dragover', function(e) {
            if (e.target.closest('.kanban-lane-body')) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            }
        });

        board.addEventListener('drop', function(e) {
            const laneBody = e.target.closest('.kanban-lane-body');
            if (laneBody) {
                e.preventDefault();
                if (draggedCard) {
                    const draggedId = parseInt(draggedCard.dataset.pedidoId, 10);

                    const cards = Array.from(laneBody.querySelectorAll('.kanban-card:not(.lane-empty)'));
                    let insertBefore = null;

                    for (const card of cards) {
                        const cardId = parseInt(card.dataset.pedidoId, 10);
                        if (cardId > draggedId) {
                            insertBefore = card;
                            break;
                        }
                    }

                    if (insertBefore) {
                        laneBody.insertBefore(draggedCard, insertBefore);
                    } else {
                        laneBody.appendChild(draggedCard);
                    }

                    const emptyMsg = laneBody.querySelector('.lane-empty');
                    if (emptyMsg) emptyMsg.remove();

                    const pedidoId = draggedCard.dataset.pedidoId;
                    const newLaneId = laneBody.dataset.laneId;

                    updatePedidoLane(pedidoId, newLaneId);
                }
            }
        });
    }
});

function updatePedidoLane(pedidoId, laneId) {
    fetch('api/update_pedido_kanban.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({pedido_id: pedidoId, lane_id: laneId})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const card = document.querySelector(`[data-pedido-id="${pedidoId}"]`);
            if (card) {
                atualizarBadgesCard(card, laneId);
            }
            solicitarAtualizacaoKanban(600);
        } else {
            alert('Erro ao atualizar pedido');
            solicitarAtualizacaoKanban(1000);
        }
    })
    .catch(() => {
        alert('Erro ao atualizar pedido');
        solicitarAtualizacaoKanban(1000);
    });
}

function atualizarBadgesCard(card, laneId) {
    const laneBody = document.querySelector(`.kanban-lane-body[data-lane-id="${laneId}"]`);
    if (!laneBody) return;

    const laneHeader = laneBody.previousElementSibling;
    const laneName = laneHeader ? laneHeader.querySelector('h6').textContent.toLowerCase() : '';

    const badgesContainer = card.querySelector('.kanban-card-badges');
    if (!badgesContainer) return;

    const badges = Array.from(badgesContainer.querySelectorAll('.kanban-badge'));
    const pagoBadge = badges.find(b => b.textContent.includes('💰'));

    badgesContainer.innerHTML = '';

    if (pagoBadge) {
        badgesContainer.appendChild(pagoBadge.cloneNode(true));
    }

    if (laneName.includes('entregue') || laneName.includes('concluí') || laneName.includes('finalizado')) {
        const badge = document.createElement('span');
        badge.className = 'kanban-badge bg-success-focus text-success-main';
        badge.textContent = '✅ Entregue';
        badgesContainer.appendChild(badge);
    } else if (laneName.includes('saiu') || (laneName.includes('entrega') && !laneName.includes('entregue'))) {
        const badge = document.createElement('span');
        badge.className = 'kanban-badge bg-primary-focus text-primary-main';
        badge.textContent = '🚚 Saiu';
        badgesContainer.appendChild(badge);
    } else if (laneName.includes('pronto')) {
        const badge = document.createElement('span');
        badge.className = 'kanban-badge bg-success-focus text-success-main';
        badge.textContent = '✅ Pronto';
        badgesContainer.appendChild(badge);
    } else if (laneName.includes('preparo')) {
        const badge = document.createElement('span');
        badge.className = 'kanban-badge bg-warning-focus text-warning-main';
        badge.textContent = '👨‍🍳 Preparo';
        badgesContainer.appendChild(badge);
    }
}

function toggleStatus(pedidoId, field, value) {
    fetch('api/update_pedido_status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({pedido_id: pedidoId, field: field, value: value})
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            alert('Erro ao alterar status do pedido.');
        }
        solicitarAtualizacaoKanban(400);
    })
    .catch(() => {
        alert('Erro ao alterar status do pedido.');
        solicitarAtualizacaoKanban(800);
    });
}

function marcarPreparo(pedidoId) {
    fetch('api/marcar_preparo.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({pedido_id: pedidoId})
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            alert('Erro ao marcar pedido em preparo');
        }
        solicitarAtualizacaoKanban(400);
    })
    .catch(() => {
        alert('Erro ao marcar pedido em preparo');
        solicitarAtualizacaoKanban(800);
    });
}

function marcarEntregue(pedidoId) {
    fetch('api/marcar_entregue.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({pedido_id: pedidoId})
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            alert('Erro ao marcar pedido como entregue');
        }
        solicitarAtualizacaoKanban(400);
    })
    .catch(() => {
        alert('Erro ao marcar pedido como entregue');
        solicitarAtualizacaoKanban(800);
    });
}

function deletePedido(pedidoId) {
    if (!confirm('Tem certeza que deseja deletar este pedido?')) return;

    fetch('api/delete_pedido.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({pedido_id: pedidoId})
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            alert('Erro ao deletar pedido');
        }
        solicitarAtualizacaoKanban(400);
    })
    .catch(() => {
        alert('Erro ao deletar pedido');
        solicitarAtualizacaoKanban(800);
    });
}

function concluirPedido(pedidoId) {
    if (!confirm('Concluir este pedido? Ele será arquivado e removido do Kanban.')) return;

    fetch('api/concluir_pedido.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({pedido_id: pedidoId})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Mostrar notificação de sucesso
            const toast = document.createElement('div');
            toast.className = 'position-fixed top-0 end-0 p-3';
            toast.style.zIndex = '9999';
            toast.style.marginTop = '70px';
            toast.innerHTML = `
                <div class="toast show align-items-center text-white bg-success border-0">
                    <div class="d-flex">
                        <div class="toast-body">
                            <strong>📦 Pedido #${pedidoId} concluído e arquivado!</strong>
                            ${data.whatsapp_enviado ? '<br><small>✅ Cliente notificado via WhatsApp</small>' : ''}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.position-fixed').remove()"></button>
                    </div>
                </div>
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        } else {
            alert('Erro ao concluir pedido: ' + (data.error || 'Erro desconhecido'));
        }
        solicitarAtualizacaoKanban(400);
    })
    .catch((err) => {
        console.error('Erro:', err);
        alert('Erro ao concluir pedido');
        solicitarAtualizacaoKanban(800);
    });
}

function showAddLaneForm() {
    const form = document.getElementById('addLaneForm');
    if (form) {
        form.style.display = 'block';
    }
}

document.getElementById('formAddLane')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<iconify-icon icon="solar:loading-circle-bold-duotone"></iconify-icon> Salvando...';

    fetch('api/add_lane.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Limpar formulário
            this.reset();
            document.getElementById('addLaneForm').style.display = 'none';
            
            // Recarregar página para atualizar tabela
            location.reload();
        } else {
            alert('Erro ao adicionar lane: ' + (data.error || 'Erro desconhecido'));
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch((error) => {
        alert('Erro ao adicionar lane: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

function deleteLane(laneId) {
    if (!confirm('Deletar esta lane? Os pedidos não serão deletados, mas serão movidos para a primeira lane.')) return;

    fetch('api/delete_lane.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({lane_id: laneId})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Recarregar página para atualizar tabela
            location.reload();
        } else {
            alert('Erro ao deletar lane: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch((error) => {
        alert('Erro ao deletar lane: ' + error.message);
    });
}

// Funções para editar lane inline
function updateLaneNome(laneId, nome) {
    updateLane(laneId, { nome: nome });
}

function updateLaneCor(laneId, cor) {
    updateLane(laneId, { cor: cor });
}

function updateLaneOrdem(laneId, ordem) {
    updateLane(laneId, { ordem: parseInt(ordem) });
}

function updateLane(laneId, data) {
    // Buscar dados atuais da lane
    const row = document.querySelector(`tr[data-lane-id="${laneId}"]`);
    if (!row) return;
    
    const nomeInput = row.querySelector('input[type="text"]');
    const corInput = row.querySelector('input[type="color"]');
    const ordemInput = row.querySelector('input[type="number"]');
    const acaoSelect = row.querySelector('select');
    
    const payload = {
        lane_id: laneId,
        nome: data.nome ?? nomeInput?.value ?? '',
        cor: data.cor ?? corInput?.value ?? '#6c757d',
        ordem: data.ordem ?? parseInt(ordemInput?.value) ?? 0,
        acao: data.acao ?? acaoSelect?.value ?? null
    };
    
    fetch('api/edit_lane.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            // Mostrar feedback visual
            const inputs = row.querySelectorAll('input, select');
            inputs.forEach(input => {
                input.style.borderColor = '#10b981';
                setTimeout(() => { input.style.borderColor = ''; }, 1500);
            });
            // Atualizar Kanban após alteração de lane
            solicitarAtualizacaoKanban(500);
        } else {
            alert('Erro ao atualizar lane: ' + (result.error || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        alert('Erro ao atualizar lane: ' + error.message);
    });
}

// Função para atualizar ação da lane
function updateLaneAcao(laneId, acao) {
    updateLane(laneId, { acao: acao });
}


function formatCurrency(value) {
    const numero = typeof value === 'number' ? value : parseFloat(value || 0);
    if (Number.isNaN(numero)) {
        return 'R$ 0,00';
    }
    return currencyFormatter.format(numero);
}

function formatHora(dataString) {
    if (!dataString) return '';
    if (dataString.length >= 16) {
        return dataString.substring(11, 16);
    }
    const date = new Date(dataString);
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

function truncarTexto(texto, limite = 50) {
    if (!texto) return '';
    return texto.length > limite ? `${texto.substring(0, limite)}...` : texto;
}

function limparElemento(elemento) {
    while (elemento.firstChild) {
        elemento.removeChild(elemento.firstChild);
    }
}

function criarLaneVazia() {
    const vazio = document.createElement('div');
    vazio.className = 'lane-empty';

    const icon = document.createElement('iconify-icon');
    icon.setAttribute('icon', 'solar:box-outline');
    icon.className = 'text-4xl mb-2';
    vazio.appendChild(icon);

    const texto = document.createElement('p');
    texto.className = 'mb-0';
    texto.textContent = 'Nenhum pedido';
    vazio.appendChild(texto);

    return vazio;
}

function criarBadge(container, classes, texto) {
    const badge = document.createElement('span');
    badge.className = `kanban-badge ${classes}`;
    badge.textContent = texto;
    container.appendChild(badge);
}

function criarKanbanCard(pedido, laneId) {
    const card = document.createElement('div');
    card.className = 'kanban-card' + (isEntregador ? ' no-drag' : '');
    card.dataset.pedidoId = pedido.id;

    if (canDrag && !isEntregador) {
        card.setAttribute('draggable', 'true');
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
    } else {
        card.setAttribute('draggable', 'false');
    }

    const header = document.createElement('div');
    header.className = 'kanban-card-header';

    const idSpan = document.createElement('span');
    idSpan.className = 'kanban-card-id';
    idSpan.textContent = `#${pedido.id}`;
    header.appendChild(idSpan);

    const timeSpan = document.createElement('span');
    timeSpan.className = 'kanban-card-time';
    timeSpan.textContent = formatHora(pedido.data_pedido);
    header.appendChild(timeSpan);

    card.appendChild(header);

    if (pedido.codigo_pedido) {
        const codigoWrapper = document.createElement('div');
        codigoWrapper.className = 'mb-1';
        const codigo = document.createElement('code');
        codigo.className = 'kanban-codigo-pedido';
        codigo.style.fontSize = '10px';
        codigo.style.background = '#f3f4f6';
        codigo.style.padding = '2px 6px';
        codigo.style.borderRadius = '4px';
        codigo.textContent = pedido.codigo_pedido;
        codigoWrapper.appendChild(codigo);
        card.appendChild(codigoWrapper);
    }

    const customer = document.createElement('div');
    customer.className = 'kanban-card-customer';
    customer.textContent = pedido.cliente_nome || 'Cliente';
    card.appendChild(customer);

    if (pedido.cliente_telefone) {
        const phoneWrapper = document.createElement('div');
        phoneWrapper.className = 'mb-1 kanban-card-phone d-flex align-items-center gap-1';
        phoneWrapper.style.fontSize = '11px';
        phoneWrapper.style.color = '#6b7280';

        const icon = document.createElement('iconify-icon');
        icon.setAttribute('icon', 'logos:whatsapp-icon');
        icon.style.fontSize = '14px';
        icon.style.lineHeight = '1';
        icon.style.display = 'inline-flex';
        phoneWrapper.appendChild(icon);
        
        const phoneText = document.createElement('span');
        phoneText.textContent = pedido.cliente_telefone;
        phoneWrapper.appendChild(phoneText);

        card.appendChild(phoneWrapper);
    }

    if (pedido.mesa) {
        const mesaWrapper = document.createElement('div');
        mesaWrapper.className = 'mb-2';
        const badge = document.createElement('span');
        badge.className = 'badge bg-info-focus text-info-main text-xs';
        const icon = document.createElement('iconify-icon');
        icon.setAttribute('icon', 'solar:map-point-outline');
        badge.appendChild(icon);
        badge.appendChild(document.createTextNode(` Mesa ${pedido.mesa}`));
        mesaWrapper.appendChild(badge);
        card.appendChild(mesaWrapper);
    } else if (pedido.cliente_endereco) {
        const enderecoWrapper = document.createElement('div');
        enderecoWrapper.className = 'mb-2 kanban-card-endereco';
        enderecoWrapper.style.fontSize = '10px';
        enderecoWrapper.style.color = '#6b7280';
        enderecoWrapper.style.maxHeight = '32px';
        enderecoWrapper.style.overflow = 'hidden';
        enderecoWrapper.style.textOverflow = 'ellipsis';

        const icon = document.createElement('iconify-icon');
        icon.setAttribute('icon', 'solar:map-point-outline');
        icon.style.fontSize = '12px';
        enderecoWrapper.appendChild(icon);
        enderecoWrapper.appendChild(document.createTextNode(` ${truncarTexto(pedido.cliente_endereco, 50)}`));
        card.appendChild(enderecoWrapper);
    }

    const itens = document.createElement('div');
    itens.className = 'kanban-card-items';
    itens.textContent = pedido.itens || 'Sem itens';
    card.appendChild(itens);

    if (pedido.tem_retirados) {
        const badgeWrapper = document.createElement('div');
        badgeWrapper.className = 'mb-2';
        const badge = document.createElement('span');
        badge.className = 'badge bg-danger-focus text-danger-main text-xs';
        badge.textContent = '❌ Itens para Retirar';
        badgeWrapper.appendChild(badge);
        card.appendChild(badgeWrapper);
    }

    const footer = document.createElement('div');
    footer.className = 'kanban-card-footer';

    const total = document.createElement('span');
    total.className = 'kanban-card-total';
    total.textContent = formatCurrency(pedido.valor_total);
    footer.appendChild(total);

    const badgesContainer = document.createElement('div');
    badgesContainer.className = 'kanban-card-badges';

    if (pedido.pago) {
        criarBadge(badgesContainer, 'bg-success-focus text-success-main', '💰 Pago');
    }
    if (pedido.em_preparo) {
        criarBadge(badgesContainer, 'bg-warning-focus text-warning-main', '👨‍🍳 Preparo');
    }
    if (pedido.saiu_entrega) {
        criarBadge(badgesContainer, 'bg-primary-focus text-primary-main', '🚚 Saiu');
    }
    if (pedido.entregue) {
        criarBadge(badgesContainer, 'bg-success-focus text-success-main', '✅ Entregue');
    }

    footer.appendChild(badgesContainer);
    card.appendChild(footer);

    const actions = document.createElement('div');
    actions.className = 'kanban-card-actions';

    if (isCozinha) {
        const btn = document.createElement('button');
        btn.className = 'kanban-btn bg-warning-focus text-warning-main w-100';
        btn.textContent = pedido.em_preparo ? '👨‍🍳 Em Preparo' : '👨‍🍳 Iniciar Preparo';
        btn.addEventListener('click', () => marcarPreparo(pedido.id));
        actions.appendChild(btn);
    } else if (isEntregador) {
        const btn = document.createElement('button');
        btn.className = 'kanban-btn bg-success-focus text-success-main w-100';
        btn.textContent = pedido.entregue ? '✅ Entregue' : '✅ Marcar Entregue';
        btn.addEventListener('click', () => marcarEntregue(pedido.id));
        actions.appendChild(btn);
    } else if (isAdminGerente) {
        const btnPago = document.createElement('button');
        btnPago.className = 'kanban-btn bg-info-focus text-info-main';
        btnPago.textContent = '💰';
        btnPago.title = 'Marcar como pago';
        btnPago.addEventListener('click', () => toggleStatus(pedido.id, 'pago', !pedido.pago));
        actions.appendChild(btnPago);

        const btnPreparo = document.createElement('button');
        btnPreparo.className = 'kanban-btn bg-warning-focus text-warning-main';
        btnPreparo.textContent = '👨‍🍳';
        btnPreparo.title = 'Em preparo';
        btnPreparo.addEventListener('click', () => toggleStatus(pedido.id, 'em_preparo', !pedido.em_preparo));
        actions.appendChild(btnPreparo);

        if (sistemaEntregadoresAtivo && pedido.tipo_entrega === 'delivery' && !pedido.entregue && typeof mostrarModalEntregador === 'function') {
            const btnEntregador = document.createElement('button');
            btnEntregador.className = 'kanban-btn bg-primary-focus text-primary-main';
            btnEntregador.textContent = '👤';
            btnEntregador.title = 'Atribuir Entregador';
            btnEntregador.addEventListener('click', (event) => {
                event.stopPropagation();
                mostrarModalEntregador(pedido.id);
            });
            actions.appendChild(btnEntregador);
        }

        const btnSaiu = document.createElement('button');
        btnSaiu.className = 'kanban-btn bg-primary-focus text-primary-main';
        btnSaiu.textContent = '🚚';
        btnSaiu.title = 'Saiu para entrega';
        btnSaiu.addEventListener('click', () => toggleStatus(pedido.id, 'saiu_entrega', !pedido.saiu_entrega));
        actions.appendChild(btnSaiu);

        const btnEntregue = document.createElement('button');
        btnEntregue.className = 'kanban-btn bg-success-focus text-success-main';
        btnEntregue.textContent = '✅';
        btnEntregue.title = 'Entregue';
        btnEntregue.addEventListener('click', () => toggleStatus(pedido.id, 'entregue', !pedido.entregue));
        actions.appendChild(btnEntregue);

        // Botão Concluir Pedido (arquiva e remove do Kanban)
        const btnConcluir = document.createElement('button');
        btnConcluir.className = 'kanban-btn bg-success-focus text-success-main';
        btnConcluir.textContent = '📦';
        btnConcluir.title = 'Concluir Pedido (arquivar)';
        btnConcluir.addEventListener('click', () => concluirPedido(pedido.id));
        actions.appendChild(btnConcluir);

        const btnExcluir = document.createElement('button');
        btnExcluir.className = 'kanban-btn bg-danger-focus text-danger-main';
        btnExcluir.textContent = '🗑️';
        btnExcluir.title = 'Deletar';
        btnExcluir.addEventListener('click', () => deletePedido(pedido.id));
        actions.appendChild(btnExcluir);
    }

    if (actions.childElementCount > 0) {
        card.appendChild(actions);
    }

    return card;
}

function solicitarAtualizacaoKanban(delay = 500) {
    setTimeout(() => atualizarKanban(true), delay);
}

async function atualizarKanban(force = false) {
    if (isUpdatingKanban) {
        if (force) {
            solicitarAtualizacaoKanban(600);
        }
        return;
    }

    if (isDragging) {
        if (force) {
            solicitarAtualizacaoKanban(800);
        }
        return;
    }

    isUpdatingKanban = true;

    try {
        const response = await fetch('api/get_kanban_snapshot.php', {
            cache: 'no-store',
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Erro desconhecido');
        }

        const snapshotString = JSON.stringify(data.lanes);
        if (!force && snapshotString === ultimoSnapshot) {
            return;
        }

        ultimoSnapshot = snapshotString;
        
        // Atualizar tabela de lanes se o modal estiver aberto (opcional)
        const lanesTableBody = document.getElementById('lanesTableBody');
        if (lanesTableBody && data.lanes) {
            lanesTableBody.innerHTML = '';
            data.lanes.forEach((lane, index) => {
                const tr = document.createElement('tr');
                tr.dataset.laneId = lane.id;
                
                const acaoAtual = lane.acao || '';
                const acoesOptions = [
                    { value: '', label: 'Nenhuma ação' },
                    { value: 'em_preparo', label: 'Marcar Em Preparo' },
                    { value: 'pronto', label: 'Marcar Pronto' },
                    { value: 'saiu_entrega', label: 'Marcar Saiu Entrega' },
                    { value: 'entregue', label: 'Marcar Entregue' },
                    { value: 'finalizar', label: 'Finalizar/Arquivar' },
                    { value: 'cancelar', label: 'Cancelar Pedido' }
                ];
                
                const acaoOptionsHtml = acoesOptions.map(opt => 
                    `<option value="${opt.value}" ${acaoAtual === opt.value ? 'selected' : ''}>${opt.label}</option>`
                ).join('');
                
                tr.innerHTML = `
                    <td>
                        <input type="number" class="form-control form-control-sm" 
                               style="width:60px; background:#1f2937; color:#f3f4f6; border-color:#374151;" 
                               value="${lane.ordem || index + 1}" 
                               onchange="updateLaneOrdem(${lane.id}, this.value)"
                               title="Ordem de exibição">
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm" 
                               value="${lane.nome}" 
                               onchange="updateLaneNome(${lane.id}, this.value)"
                               style="min-width:120px; background:#1f2937; color:#f3f4f6; border-color:#374151;"
                               title="Nome da lane">
                    </td>
                    <td>
                        <input type="color" class="form-control form-control-sm form-control-color" 
                               value="${lane.cor}" 
                               onchange="updateLaneCor(${lane.id}, this.value)"
                               style="width:40px; height:32px; padding:2px"
                               title="Cor da lane">
                    </td>
                    <td>
                        <select class="form-select form-select-sm"
                                style="min-width:140px; background:#1f2937; color:#f3f4f6; border-color:#374151;"
                                onchange="updateLaneAcao(${lane.id}, this.value)"
                                title="Ação executada ao mover pedido">
                            ${acaoOptionsHtml}
                        </select>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="deleteLane(${lane.id})" title="Deletar Lane">
                            <iconify-icon icon="solar:trash-bin-outline" style="font-size: 18px;"></iconify-icon>
                        </button>
                    </td>
                `;
                lanesTableBody.appendChild(tr);
            });
        }

        const board = document.getElementById('kanban-board');
        if (!board) return;

        // Limpar board (ou atualizar inteligentemente)
        // Aqui vamos reconstruir para simplificar, mas mantendo o scroll se possível
        // Para evitar piscar, podemos atualizar lane por lane
        
        // Verificar se as lanes mudaram (adicionadas/removidas)
        // Se sim, reconstruir tudo. Se não, atualizar conteúdo.
        
        // Implementação simplificada: Reconstruir
        board.innerHTML = '';
        
        data.lanes.forEach(lane => {
            const laneDiv = document.createElement('div');
            laneDiv.className = 'kanban-lane';
            laneDiv.dataset.laneId = lane.id;
            
            laneDiv.innerHTML = `
                <div class="kanban-lane-header" style="border-color: ${lane.cor}">
                    <div class="d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:box-outline" style="color: ${lane.cor}" class="text-xl"></iconify-icon>
                        <h6 class="mb-0 fw-semibold">${lane.nome}</h6>
                    </div>
                    <span class="badge" style="background-color: ${lane.cor}">
                        ${lane.total}
                    </span>
                </div>
                <div class="kanban-lane-body" data-lane-id="${lane.id}">
                    <!-- Cards -->
                </div>
            `;
            
            const laneBody = laneDiv.querySelector('.kanban-lane-body');
            
            if (!lane.pedidos || lane.pedidos.length === 0) {
                laneBody.appendChild(criarLaneVazia());
            } else {
                lane.pedidos.forEach(pedido => {
                    laneBody.appendChild(criarKanbanCard(pedido, lane.id));
                });
            }
            
            board.appendChild(laneDiv);
        });

        inicializarCartoesArrastaveis();
    } catch (error) {
        console.error('Erro ao atualizar Kanban:', error);
    } finally {
        isUpdatingKanban = false;
    }
}

function iniciarAutoRefresh() {
    if (kanbanIntervalId) {
        clearInterval(kanbanIntervalId);
    }

    atualizarKanban(true);

    kanbanIntervalId = setInterval(() => {
        if (!document.hidden) {
            atualizarKanban();
        }
    }, KANBAN_REFRESH_INTERVAL);
}

document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        atualizarKanban();
    }
});

window.addEventListener('focus', () => atualizarKanban());

document.addEventListener('DOMContentLoaded', () => {
    inicializarCartoesArrastaveis();
    iniciarAutoRefresh();
});

// Disponibiliza função global para outros scripts (ex: seleção de entregador)
window.atualizarKanbanBoard = atualizarKanban;
</script>

<?php require_once 'includes/footer.php'; ?>