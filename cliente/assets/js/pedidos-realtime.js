/**
 * Sistema de Atualização em Tempo Real de Pedidos
 * Verifica mudanças de status a cada 1 minuto
 */

(function() {
    'use strict';
    
    // Configurações
    const CONFIG = {
        intervaloAtualizacao: 60000, // 1 minuto em milissegundos
        urlAPI: 'api/verificar_atualizacoes.php',
        debug: false
    };
    
    // Estado atual dos pedidos
    let pedidosAtuais = {};
    let intervalId = null;
    
    /**
     * Inicializa o sistema de atualização
     */
    function inicializar() {
        // Carregar estado inicial dos pedidos
        carregarEstadoInicial();
        
        // Iniciar verificação periódica
        iniciarVerificacaoPeriodica();
        
        // Atualizar timestamp
        atualizarTimestamp();
        
        log('Sistema de atualização inicializado');
    }
    
    /**
     * Carrega o estado inicial dos pedidos da página
     */
    function carregarEstadoInicial() {
        const cards = document.querySelectorAll('[data-pedido-id]');
        cards.forEach(card => {
            const pedidoId = card.dataset.pedidoId;
            const statusElement = card.querySelector('[data-pedido-status]');
            if (statusElement) {
                pedidosAtuais[pedidoId] = {
                    status: statusElement.dataset.pedidoStatus,
                    elemento: card
                };
            }
        });
        log('Estado inicial carregado:', pedidosAtuais);
    }
    
    /**
     * Inicia a verificação periódica
     */
    function iniciarVerificacaoPeriodica() {
        // Limpar intervalo existente se houver
        if (intervalId) {
            clearInterval(intervalId);
        }
        
        // Criar novo intervalo
        intervalId = setInterval(() => {
            verificarAtualizacoes();
        }, CONFIG.intervaloAtualizacao);
        
        log('Verificação periódica iniciada (intervalo: ' + (CONFIG.intervaloAtualizacao / 1000) + 's)');
    }
    
    /**
     * Verifica atualizações na API
     */
    async function verificarAtualizacoes() {
        try {
            log('Verificando atualizações...');
            
            // Determinar limite baseado na página atual
            const isDashboard = window.location.pathname.includes('dashboard.php');
            const limite = isDashboard ? 5 : null;
            const url = CONFIG.urlAPI + (limite ? '?limite=' + limite : '');
            
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error('Erro na resposta da API: ' + response.status);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error('API retornou erro');
            }
            
            log('Dados recebidos:', data);
            
            // Processar atualizações
            processarAtualizacoes(data.pedidos);
            
            // Atualizar notificações se houver
            if (data.notificacoes_novas > 0) {
                atualizarNotificacoes(data.notificacoes_novas);
            }
            
            // Atualizar timestamp
            atualizarTimestamp(data.timestamp);
            
        } catch (erro) {
            console.error('Erro ao verificar atualizações:', erro);
        }
    }
    
    /**
     * Processa as atualizações recebidas
     */
    function processarAtualizacoes(pedidos) {
        pedidos.forEach(pedido => {
            const pedidoId = pedido.id.toString();
            const pedidoAnterior = pedidosAtuais[pedidoId];
            
            // Verificar se o pedido existe na página
            if (pedidoAnterior) {
                // Verificar se o status mudou
                if (pedidoAnterior.status !== pedido.status) {
                    log('Status alterado para pedido #' + pedido.codigo_pedido + ': ' + pedidoAnterior.status + ' → ' + pedido.status);
                    atualizarStatusPedido(pedidoId, pedido);
                    
                    // Mostrar notificação
                    mostrarNotificacaoMudanca(pedido);
                }
                
                // Atualizar estado atual
                pedidosAtuais[pedidoId].status = pedido.status;
            }
        });
    }
    
    /**
     * Atualiza o status visual de um pedido
     */
    function atualizarStatusPedido(pedidoId, pedido) {
        const card = document.querySelector(`[data-pedido-id="${pedidoId}"]`);
        if (!card) return;
        
        const badgeElement = card.querySelector('[data-pedido-status]');
        if (!badgeElement) return;
        
        // Adicionar animação de pulse
        card.classList.add('animate-pulse-once');
        
        // Remover classes antigas
        badgeElement.className = '';
        
        // Adicionar novas classes
        badgeElement.className = pedido.status_badge_class + ' px-12 py-6 rounded-pill fw-medium text-xs';
        badgeElement.dataset.pedidoStatus = pedido.status;
        badgeElement.textContent = pedido.status_label;
        
        // Remover animação após completar
        setTimeout(() => {
            card.classList.remove('animate-pulse-once');
        }, 1000);
    }
    
    /**
     * Mostra notificação de mudança de status
     */
    function mostrarNotificacaoMudanca(pedido) {
        // Verificar se as notificações do navegador estão disponíveis
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('Atualização do Pedido #' + pedido.codigo_pedido, {
                body: 'Status: ' + pedido.status_label,
                icon: '/admin/assets/images/logo-icon.png',
                tag: 'pedido-' + pedido.id
            });
        }
        
        // Notificação visual na página
        mostrarToast('Pedido #' + pedido.codigo_pedido + ' atualizado para: ' + pedido.status_label, 'info');
    }
    
    /**
     * Mostra uma notificação toast
     */
    function mostrarToast(mensagem, tipo = 'info') {
        // Usar sistema de toast do WowDash se disponível
        if (typeof toastr !== 'undefined') {
            toastr[tipo](mensagem);
        } else {
            // Fallback simples
            console.log('[TOAST]', tipo.toUpperCase() + ':', mensagem);
        }
    }
    
    /**
     * Atualiza o contador de notificações
     */
    function atualizarNotificacoes(total) {
        const badges = document.querySelectorAll('.notification-badge');
        badges.forEach(badge => {
            badge.textContent = total;
            if (total > 0) {
                badge.style.display = 'inline-block';
            }
        });
    }
    
    /**
     * Atualiza o timestamp da última atualização
     */
    function atualizarTimestamp(timestamp) {
        const elementos = document.querySelectorAll('#last-update');
        const texto = timestamp ? 'Atualizado às ' + timestamp : 'Atualizado agora';
        elementos.forEach(el => {
            el.textContent = texto;
        });
    }
    
    /**
     * Solicita permissão para notificações do navegador
     */
    function solicitarPermissaoNotificacoes() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().then(permission => {
                log('Permissão de notificação:', permission);
            });
        }
    }
    
    /**
     * Log para debug
     */
    function log(...args) {
        if (CONFIG.debug) {
            console.log('[Pedidos Realtime]', ...args);
        }
    }
    
    /**
     * Limpa o intervalo ao sair da página
     */
    function limpar() {
        if (intervalId) {
            clearInterval(intervalId);
            log('Verificação periódica finalizada');
        }
    }
    
    // Inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializar);
    } else {
        inicializar();
    }
    
    // Solicitar permissão para notificações após 3 segundos
    setTimeout(solicitarPermissaoNotificacoes, 3000);
    
    // Limpar ao sair da página
    window.addEventListener('beforeunload', limpar);
    
    // Expor funções globalmente para debug
    window.PedidosRealtime = {
        verificarAtualizacoes,
        iniciarVerificacaoPeriodica,
        limpar
    };
    
})();
