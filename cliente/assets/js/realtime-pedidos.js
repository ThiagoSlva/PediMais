// Sistema de Atualização em Tempo Real para Cliente
class ClienteRealtimeUpdates {
    constructor() {
        this.interval = null;
        this.lastUpdate = null;
        this.updateInterval = 5000; // 5 segundos
        this.pedidosCache = new Map();
    }
    
    init() {
        // Iniciar atualizações em tempo real
        this.startPolling();
        
        // Atualizar quando a página ganha foco
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.checkUpdates();
            }
        });
        
        // Parar quando a página é fechada
        window.addEventListener('beforeunload', () => {
            this.stopPolling();
        });
    }
    
    startPolling() {
        // Primeira verificação imediata
        this.checkUpdates();
        
        // Verificar a cada X segundos
        this.interval = setInterval(() => {
            this.checkUpdates();
        }, this.updateInterval);
    }
    
    stopPolling() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    }
    
    async checkUpdates() {
        try {
            // Buscar pedidos do cliente
            const response = await fetch('/cliente/api/get_meus_pedidos.php' + 
                (this.lastUpdate ? '?last_update=' + encodeURIComponent(this.lastUpdate) : ''));
            const data = await response.json();
            
            if (data.success && data.pedidos && data.pedidos.length > 0) {
                this.processUpdates(data.pedidos);
                this.lastUpdate = data.timestamp || new Date().toISOString();
            }
        } catch (error) {
            console.error('Erro ao verificar atualizações:', error);
        }
    }
    
    processUpdates(pedidos) {
        pedidos.forEach(pedido => {
            const pedidoId = pedido.id;
            const cached = this.pedidosCache.get(pedidoId);
            
            // Se é um pedido novo ou status mudou
            if (!cached || cached.status !== pedido.status) {
                this.updatePedidoInUI(pedido);
                this.pedidosCache.set(pedidoId, pedido);
                
                // Mostrar notificação visual se status mudou
                if (cached && cached.status !== pedido.status) {
                    this.showStatusChangeNotification(pedido);
                }
            }
        });
    }
    
    updatePedidoInUI(pedido) {
        // Atualizar na lista de pedidos
        const pedidoCard = document.querySelector(`[data-pedido-id="${pedido.id}"]`);
        if (pedidoCard) {
            // Atualizar badge de status
            const statusBadge = pedidoCard.querySelector('.status-badge, .badge');
            if (statusBadge) {
                statusBadge.textContent = this.getStatusLabel(pedido.status);
                statusBadge.className = 'badge ' + this.getStatusClass(pedido.status);
            }
            
            // Adicionar animação de atualização
            pedidoCard.classList.add('updated');
            setTimeout(() => {
                pedidoCard.classList.remove('updated');
            }, 1000);
        }
        
        // Se estiver na página de detalhes do pedido, atualizar também
        const urlParams = new URLSearchParams(window.location.search);
        const currentPedidoId = urlParams.get('id');
        if (currentPedidoId && parseInt(currentPedidoId) === pedido.id) {
            this.updatePedidoDetail(pedido);
        }
        
        // Disparar evento customizado
        window.dispatchEvent(new CustomEvent('pedidoUpdated', {
            detail: { pedido }
        }));
    }
    
    async updatePedidoDetail(pedido) {
        try {
            const response = await fetch(`/cliente/api/get_pedido_status.php?id=${pedido.id}`);
            const data = await response.json();
            
            if (data.success) {
                // Atualizar elementos na página de detalhes
                const statusElement = document.querySelector('.pedido-status');
                if (statusElement) {
                    statusElement.textContent = this.getStatusLabel(data.pedido.status);
                    statusElement.className = 'pedido-status badge ' + this.getStatusClass(data.pedido.status);
                }
            }
        } catch (error) {
            console.error('Erro ao atualizar detalhes:', error);
        }
    }
    
    showStatusChangeNotification(pedido) {
        // Mostrar toast/notificação visual
        const message = `Status do pedido #${pedido.codigo_pedido} atualizado: ${this.getStatusLabel(pedido.status)}`;
        
        // Usar função de toast se existir
        if (typeof showToast === 'function') {
            showToast(message, 'info');
        } else {
            // Criar notificação simples
            const notification = document.createElement('div');
            notification.className = 'alert alert-info alert-dismissible fade show position-fixed top-0 end-0 m-3';
            notification.style.zIndex = '9999';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
    }
    
    getStatusLabel(status) {
        const labels = {
            'pendente': 'Pendente',
            'em_andamento': 'Em Preparo',
            'pronto': 'Pronto',
            'saiu_entrega': 'Saiu para Entrega',
            'concluido': 'Concluído',
            'cancelado': 'Cancelado'
        };
        return labels[status] || status;
    }
    
    getStatusClass(status) {
        const classes = {
            'pendente': 'bg-warning',
            'em_andamento': 'bg-info',
            'pronto': 'bg-success',
            'saiu_entrega': 'bg-primary',
            'concluido': 'bg-success',
            'cancelado': 'bg-danger'
        };
        return classes[status] || 'bg-secondary';
    }
}

// Inicializar quando a página carregar
document.addEventListener('DOMContentLoaded', () => {
    window.clienteRealtimeUpdates = new ClienteRealtimeUpdates();
    window.clienteRealtimeUpdates.init();
});




