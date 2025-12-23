// Sistema de Seleção de Entregador
(function() {
    
    // Criar modal de seleção de entregador
    function criarModalEntregador() {
        if (document.getElementById('modal-selecionar-entregador')) {
            return;
        }
        
        const modalHTML = `
            <div class="modal fade" id="modal-selecionar-entregador" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <iconify-icon icon="solar:delivery-bold" style="font-size: 24px; vertical-align: middle;"></iconify-icon>
                                Selecionar Entregador
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-secondary-light mb-3">Escolha o entregador para este pedido:</p>
                            <div id="lista-entregadores" class="d-grid gap-2">
                                <div class="text-center py-3">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Carregando...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
    
    // Carregar lista de entregadores
    async function carregarEntregadores() {
        try {
            const response = await fetch('api/listar_entregadores.php');
            const data = await response.json();
            
            if (data.success && data.entregadores && data.entregadores.length > 0) {
                return data.entregadores;
            } else {
                return [];
            }
        } catch (error) {
            console.error('Erro ao carregar entregadores:', error);
            return [];
        }
    }
    
    // Mostrar modal de seleção
    async function mostrarModalEntregador(pedidoId, callback) {
        criarModalEntregador();
        
        const modal = new bootstrap.Modal(document.getElementById('modal-selecionar-entregador'));
        const lista = document.getElementById('lista-entregadores');
        
        // Mostrar modal
        modal.show();
        
        // Carregar entregadores
        const entregadores = await carregarEntregadores();
        
        if (entregadores.length === 0) {
            lista.innerHTML = `
                <div class="alert alert-warning mb-0">
                    <iconify-icon icon="solar:info-circle-bold" style="font-size: 20px; vertical-align: middle;"></iconify-icon>
                    Nenhum entregador cadastrado no sistema.
                </div>
            `;
            return;
        }
        
        // Renderizar lista de entregadores
        let html = '';
        entregadores.forEach(entregador => {
            html += `
                <button type="button" 
                        class="btn btn-outline-primary text-start d-flex align-items-center gap-3 p-3"
                        onclick="window.selecionarEntregador(${pedidoId}, ${entregador.id}, '${entregador.nome}')">
                    <div class="w-40-px h-40-px rounded-circle bg-primary-50 d-flex align-items-center justify-content-center flex-shrink-0">
                        <iconify-icon icon="solar:user-bold" class="text-primary text-xl"></iconify-icon>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0">${entregador.nome}</h6>
                        <small class="text-secondary-light">${entregador.email || ''}</small>
                    </div>
                    <iconify-icon icon="solar:arrow-right-linear" class="text-xl"></iconify-icon>
                </button>
            `;
        });
        
        lista.innerHTML = html;
    }
    
    // Atribuir entregador ao pedido
    async function atribuirEntregador(pedidoId, entregadorId, entregadorNome) {
        try {
            const response = await fetch('api/atribuir_entregador.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    pedido_id: pedidoId,
                    entregador_id: entregadorId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Fechar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('modal-selecionar-entregador'));
                if (modal) modal.hide();
                
                // Mostrar toast ao invés de alert
                const toast = document.createElement('div');
                toast.className = 'position-fixed top-0 end-0 p-3';
                toast.style.zIndex = '9999';
                toast.style.marginTop = '70px';
                toast.innerHTML = `
                    <div class="toast show align-items-center text-white bg-success border-0">
                        <div class="d-flex">
                            <div class="toast-body">
                                <strong>✅ Pedido #${pedidoId} atribuído para ${entregadorNome}!</strong>
                                <br><small>Status: Saiu para Entrega</small>
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.position-fixed').remove()"></button>
                        </div>
                    </div>
                `;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 4000);
                
                // Se não for redirecionamento automático, recarregar
                if (!window.urlAposSelecionarEntregador) {
                    if (typeof window.atualizarKanbanBoard === 'function') {
                        window.atualizarKanbanBoard(true);
                    } else if (window.location.href.includes('kanban') || window.location.href.includes('pedidos.php')) {
                        setTimeout(() => location.reload(), 1000);
                    }
                }
                
                return true;
            } else {
                alert('Erro ao atribuir entregador: ' + (data.error || 'Erro desconhecido'));
                return false;
            }
        } catch (error) {
            console.error('Erro ao atribuir entregador:', error);
            alert('Erro ao atribuir entregador. Tente novamente.');
            return false;
        }
    }
    
    // Exportar funções globais
    window.mostrarModalEntregador = mostrarModalEntregador;
    window.selecionarEntregador = atribuirEntregador;
    
    // Adicionar CSS do modal
    const style = document.createElement('style');
    style.textContent = `
        #modal-selecionar-entregador .btn-outline-primary:hover {
            transform: translateX(4px);
            transition: all 0.2s;
        }
        
        #lista-entregadores .btn {
            border-width: 2px;
        }
    `;
    document.head.appendChild(style);
    
})();
