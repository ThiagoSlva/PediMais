// Sistema de Notificações SOMENTE para Admin
(function () {
    // Só funciona para admin e gerente
    const nivel = document.body.dataset.nivel || 'admin';

    if (nivel !== 'admin' && nivel !== 'gerente') {
        console.log('🔕 Notificações desabilitadas para: ' + nivel);
        return; // Sai da função para cozinha e entregador
    }

    // Configurações
    const CONFIG = {
        intervalo: 15000, // 15 segundos
        duracaoNotificacao: 5000, // 5 segundos
        nivel: nivel
    };

    // Rastrear pedidos já vistos (evita repetição no F5)
    let pedidosVistos = JSON.parse(localStorage.getItem('pedidos_vistos') || '[]');
    let ultimoTotal = 0;

    // Configuração de impressão automática
    let impressaoAutomatica = false;

    // Verificar configuração de impressão
    async function verificarConfigImpressao() {
        try {
            const response = await fetch('api/get_print_config.php');
            const data = await response.json();
            impressaoAutomatica = data.impressao_automatica || false;
            console.log('🖨️ Impressão automática:', impressaoAutomatica ? 'ATIVADA' : 'desativada');
        } catch (e) {
            console.log('Erro ao verificar config de impressão:', e);
        }
    }

    // Função para imprimir pedido automaticamente
    function imprimirPedidoAutomatico(pedidoId) {
        if (!impressaoAutomatica) return;

        console.log('🖨️ Abrindo impressão para pedido:', pedidoId);

        // Abrir janela de impressão
        const printWindow = window.open(
            `pedido_imprimir.php?id=${pedidoId}&auto=1`,
            `print_${pedidoId}`,
            'width=400,height=600,scrollbars=yes'
        );

        if (!printWindow) {
            mostrarNotificacao(
                '<strong>⚠️ Pop-up bloqueado!</strong><br>Permita pop-ups para impressão automática.',
                'warning'
            );
        }
    }

    // Inicializar verificação de impressão
    verificarConfigImpressao();
    // Re-verificar a cada 60 segundos (caso admin mude a config)
    setInterval(verificarConfigImpressao, 60000);

    // Sons
    const audioNotificacao = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBi2Czfbfkz0LEFaO7fGrcB8MOaPp8bllHQU2i9fzwWsSByl50fHWjT0MFle15O2qXxkNKZPZ8cBnGwc0hNb0w3AlBSlx1PbPfC4GHGq/7+inWRoLMKPq8qpbGwc1h9XzwHIrBiZv0fXUgjEIFmm94OynXBkLP6vq8KlbHAg2hNX0wXQpBCRt0fTRfzYKGWu+7O6nXRoLN53f86ZaGQo3h9X0wHUrBSR2zvPSf0cGGGu+7Ouo');

    const audioCozinha = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBi2Czfbfkz0LEFaO7fGrcB8MOaPp8bllHQU2i9fzwWsSByl50fHWjT0MFle15O2qXxkNKZPZ8cBnGwc0hNb0w3AlBSlx1PbPfC4GHGq/7+inWRoLMKPq8qpbGwc1h9XzwHIrBiZv0fXUgjEIFmm94OynXBkLP6vq8KlbHAg2hNX0wXQpBCRt0fTRfzYKGWu+7O6nXRoLN53f86ZaGQo3h9X0wHUrBSR2zvPSf0cGGGu+7Ouo');

    const audioEntregador = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBi2Czfbfkz0LEFaO7fGrcB8MOaPp8bllHQU2i9fzwWsSByl50fHWjT0MFle15O2qXxkNKZPZ8cBnGwc0hNb0w3AlBSlx1PbPfC4GHGq/7+inWRoLMKPq8qpbGwc1h9XzwHIrBiZv0fXUgjEIFmm94OynXBkLP6vq8KlbHAg2hNX0wXQpBCRt0fTRfzYKGWu+7O6nXRoLN53f86ZaGQo3h9X0wHUrBSR2zvPSf0cGGGu+7Ouo');

    // Criar container de notificações se não existir
    if (!document.getElementById('toast-container')) {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position: fixed; top: 80px; right: 20px; z-index: 9999; width: 350px;';
        document.body.appendChild(container);
    }

    // Função para mostrar notificação toast
    function mostrarNotificacao(mensagem, tipo = 'info') {
        const container = document.getElementById('toast-container');

        const toast = document.createElement('div');
        toast.className = `alert alert-${tipo} alert-dismissible fade show`;
        toast.style.cssText = 'box-shadow: 0 4px 12px rgba(0,0,0,0.15); animation: slideInRight 0.3s ease-out;';
        toast.innerHTML = `
            ${mensagem}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        container.appendChild(toast);

        // Remover após 5 segundos
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, CONFIG.duracaoNotificacao);
    }

    // Função para tocar som
    function tocarSom(tipo) {
        try {
            let audio;
            if (tipo === 'cozinha') {
                audio = audioCozinha.cloneNode();
            } else if (tipo === 'entregador') {
                audio = audioEntregador.cloneNode();
            } else {
                audio = audioNotificacao.cloneNode();
            }
            audio.play().catch(e => console.log('Erro ao tocar som:', e));
        } catch (e) {
            console.log('Erro ao tocar som:', e);
        }
    }

    // Verificar novos pedidos
    async function verificarNovosPedidos() {
        try {
            const response = await fetch('api/get_pedidos_pendentes.php');
            const data = await response.json();

            if (!data.erro && data.pedidos) {
                const totalGeral = parseInt(data.total) || 0;
                const pedidosAtuais = data.pedidos || [];

                // Verificar se há pedidos NOVOS (que não foram vistos antes)
                const pedidosNovos = pedidosAtuais.filter(p => !pedidosVistos.includes(p.id));

                // Se há pedidos novos E não é o primeiro carregamento
                if (pedidosNovos.length > 0 && ultimoTotal > 0) {
                    const novos = pedidosNovos.length;
                    mostrarNotificacao(
                        `<strong>🔔 Novo${novos > 1 ? 's' : ''} Pedido${novos > 1 ? 's' : ''}!</strong><br>${novos} pedido${novos > 1 ? 's' : ''} aguardando`,
                        'primary'
                    );
                    tocarSom('admin');

                    // 🖨️ IMPRESSÃO AUTOMÁTICA: Imprimir cada novo pedido
                    pedidosNovos.forEach(pedido => {
                        imprimirPedidoAutomatico(pedido.id);
                    });
                }

                // Atualizar lista de pedidos vistos
                pedidosVistos = pedidosAtuais.map(p => p.id);
                localStorage.setItem('pedidos_vistos', JSON.stringify(pedidosVistos));

                // Atualizar total
                ultimoTotal = totalGeral;

                // Atualizar badge e sino
                const badge = document.getElementById('notification-badge');
                const bell = document.getElementById('notification-bell');

                if (badge) {
                    // Garantir que seja um número válido, nunca undefined
                    const total = parseInt(totalGeral) || 0;
                    badge.textContent = total;
                }

                if (bell) {
                    // Adicionar classe para sino vermelho se tiver pedidos
                    if (totalGeral > 0) {
                        bell.classList.add('has-notification');
                        bell.style.background = '#ef4444'; // Vermelho
                    } else {
                        bell.classList.remove('has-notification');
                        bell.style.background = ''; // Volta ao normal
                    }
                }

                // POPULAR A LISTA DE NOTIFICAÇÕES
                const notificationList = document.getElementById('notification-list');
                if (notificationList) {
                    if (pedidosAtuais.length === 0) {
                        notificationList.innerHTML = `
                            <div class="px-24 py-12 text-center">
                                <p class="mb-0 text-sm text-secondary-light">Nenhum pedido pendente</p>
                            </div>
                        `;
                    } else {
                        let html = '';
                        pedidosAtuais.slice(0, 10).forEach(pedido => {
                            // Calcular tempo decorrido
                            const dataPedido = new Date(pedido.data_pedido);
                            const agora = new Date();
                            const diffMs = agora - dataPedido;
                            const diffMin = Math.floor(diffMs / 60000);
                            const diffH = Math.floor(diffMin / 60);
                            let tempoDecorrido = diffH > 0 ? `${diffH}h ${diffMin % 60}min` : `${diffMin}min`;

                            // Ícone por tipo de entrega
                            let icone = 'solar:bag-outline';
                            if (pedido.tipo_entrega === 'delivery') icone = 'solar:delivery-outline';
                            else if (pedido.tipo_entrega === 'mesa') icone = 'solar:widget-outline';

                            html += `
                                <a href="pedido_detalhe.php?id=${pedido.id}" class="px-24 py-12 d-flex align-items-start gap-3 mb-2 justify-content-between notification-item" style="border-radius: 8px;">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="w-40-px h-40-px bg-primary-100 rounded-circle d-flex justify-content-center align-items-center flex-shrink-0">
                                            <iconify-icon icon="${icone}" class="text-primary-600 text-xl"></iconify-icon>
                                        </div>
                                        <div>
                                            <h6 class="text-md fw-semibold mb-1 text-neutral-800">#${pedido.codigo_pedido || pedido.id}</h6>
                                            <p class="mb-0 text-sm text-secondary-light">${pedido.cliente_nome || 'Cliente'}</p>
                                            <span class="text-xs fw-medium text-success-600">R$ ${parseFloat(pedido.valor_total).toFixed(2).replace('.', ',')}</span>
                                        </div>
                                    </div>
                                    <span class="text-xs text-secondary-light flex-shrink-0">${tempoDecorrido}</span>
                                </a>
                            `;
                        });
                        notificationList.innerHTML = html;
                    }
                }
            }
        } catch (error) {
            console.error('Erro ao verificar pedidos:', error);
            const notificationList = document.getElementById('notification-list');
            if (notificationList) {
                notificationList.innerHTML = `
                    <div class="px-24 py-12 text-center">
                        <p class="mb-0 text-sm text-danger">Erro ao carregar pedidos</p>
                    </div>
                `;
            }
        }
    }

    // CSS para animação
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        #notification-bell.has-notification {
            animation: bellRing 1s ease-in-out infinite;
        }
        
        @keyframes bellRing {
            0%, 100% { transform: rotate(0deg); }
            10%, 30% { transform: rotate(-10deg); }
            20%, 40% { transform: rotate(10deg); }
            50% { transform: rotate(0deg); }
        }
    `;
    document.head.appendChild(style);

    // Iniciar verificação
    verificarNovosPedidos();
    setInterval(verificarNovosPedidos, CONFIG.intervalo);

})();

// Função global para limpar notificações (chamada pelo botão no header)
window.limparNotificacoes = function () {
    // Limpar localStorage de pedidos vistos
    localStorage.removeItem('pedidos_vistos');

    // Resetar badge
    const badge = document.getElementById('notification-badge');
    const bell = document.getElementById('notification-bell');
    const list = document.getElementById('notification-list');

    if (badge) badge.textContent = '0';
    if (bell) {
        bell.classList.remove('has-notification');
        bell.style.background = '';
    }
    if (list) {
        list.innerHTML = `
            <div class="px-24 py-12 text-center">
                <p class="mb-0 text-sm text-secondary-light">Notificações limpas</p>
            </div>
        `;
    }

    console.log('🗑️ Notificações limpas');
};
