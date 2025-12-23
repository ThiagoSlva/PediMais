// Sistema de Atualização em Tempo Real para Todos os Painéis
class RealtimeUpdates {
    constructor() {
        this.interval = null;
        this.lastUpdate = null;
        this.updateInterval = 5000; // 5 segundos
        this.pedidosCache = new Map();
        this.reloadScheduled = false;
    }
    
    init() {
        // Verificar se estamos no index.php (dashboard) - não iniciar realtime lá
        if (this.isDashboardPage()) {
            console.log('Realtime desabilitado no dashboard');
            return; // Não iniciar realtime no dashboard
        }
        
        this.preloadCache();
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
    
    preloadCache() {
        document.querySelectorAll('[data-pedido-id]').forEach(row => {
            const id = parseInt(row.dataset.pedidoId, 10);
            if (!Number.isNaN(id)) {
                const status = row.dataset.pedidoStatus || null;
                this.pedidosCache.set(id, { status });
            }
        });
    }
    
    stopPolling() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    }
    
    isDashboardPage() {
        // Verificação simples e direta: se o pathname contém index.php, é dashboard
        const pathname = window.location.pathname;
        return pathname.includes('index.php') && pathname.includes('/admin/');
    }
    
    async checkUpdates() {
        // Proteção: não fazer nada se estiver no dashboard
        if (this.isDashboardPage()) {
            this.stopPolling(); // Parar polling se estiver no dashboard
            return;
        }
        
        try {
            const url = '/admin/api/get_pedidos_realtime.php' + 
                (this.lastUpdate ? '?last_update=' + encodeURIComponent(this.lastUpdate) : '');
            
            const response = await fetch(url);
            
            // Se a resposta não for OK, parar
            if (!response.ok) {
                return;
            }
            
            const data = await response.json();
            
            // Sempre atualizar o timestamp para evitar loop
            if (data.success && data.timestamp) {
                this.lastUpdate = data.timestamp;
            }
            
            // Só processar se houver pedidos novos
            if (data.success && data.pedidos && data.pedidos.length > 0) {
                this.processUpdates(data.pedidos);
            }
        } catch (error) {
            console.error('Erro ao verificar atualizações:', error);
            // Em caso de erro, parar o polling para evitar loop
            this.stopPolling();
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
            }
        });
    }
    
    updatePedidoInUI(pedido) {
        // Atualizar na lista de pedidos
        const pedidoRow = document.querySelector(`[data-pedido-id="${pedido.id}"]`);
        if (pedidoRow) {
            // Atualizar badge de status
            const statusBadge = pedidoRow.querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.textContent = this.getStatusLabel(pedido.status);
                statusBadge.className = 'status-badge badge ' + this.getStatusClass(pedido.status);
            }
            pedidoRow.dataset.pedidoStatus = pedido.status;
            this.pedidosCache.set(pedido.id, pedido);
            
            // Adicionar animação de atualização
            pedidoRow.classList.add('updated');
            setTimeout(() => {
                pedidoRow.classList.remove('updated');
            }, 1000);
        } else {
            // Se não encontrar o elemento, apenas atualizar cache (não fazer reload)
            this.pedidosCache.set(pedido.id, pedido);
            return;
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
            const response = await fetch(`/admin/api/get_pedido_status.php?id=${pedido.id}`);
            const data = await response.json();
            
            if (data.success) {
                // Atualizar elementos na página de detalhes
                const statusSelect = document.querySelector('select[name="novo_status"]');
                if (statusSelect) {
                    statusSelect.value = data.pedido.status;
                }
                
                // Atualizar badges de status rápido
                this.updateQuickStatusBadges(data.pedido);
            }
        } catch (error) {
            console.error('Erro ao atualizar detalhes:', error);
        }
    }
    
    updateQuickStatusBadges(pedido) {
        // Atualizar badges de status rápido (pago, em preparo, etc)
        const badges = {
            'pago': pedido.pago,
            'em_preparo': pedido.em_preparo,
            'saiu_entrega': pedido.saiu_entrega,
            'entregue': pedido.entregue
        };
        
        Object.keys(badges).forEach(field => {
            const badge = document.querySelector(`[data-field="${field}"]`);
            if (badge) {
                if (badges[field]) {
                    badge.classList.add('active');
                } else {
                    badge.classList.remove('active');
                }
            }
        });
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
    // Verificar se não é o dashboard antes de inicializar
    const pathname = window.location.pathname;
    
    // Não inicializar no dashboard (index.php)
    if (pathname.includes('index.php') && pathname.includes('/admin/')) {
        return;
    }
    
    window.realtimeUpdates = new RealtimeUpdates();
    window.realtimeUpdates.init();
});




