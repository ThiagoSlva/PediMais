// Sistema de Validação: Obriga Selecionar Entregador Antes de Enviar
console.log('🚀 Carregando validar-entregador-obrigatorio.js');

let sistemaAtivo = false;
let totalEntregadores = 0;
let requerSelecao = false;

// Verificar configurações
fetch('api/verificar_entregadores_disponiveis.php')
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            sistemaAtivo = data.sistema_ativo === 1;
            totalEntregadores = data.total_entregadores;
            requerSelecao = data.requer_selecao
            
            console.log('🎛️ Sistema ativo:', sistemaAtivo);
            console.log('👥 Total entregadores:', totalEntregadores);
            console.log('📋 Requer seleção:', requerSelecao);
            
            if (requerSelecao) {
                inicializarValidacao();
            }
        }
    })
    .catch(err => console.error('Erro ao verificar:', err));

function inicializarValidacao() {
    console.log('✅ Inicializando validação de entregador...');
    
    // Interceptar cliques em "Saiu para Entrega"
    document.addEventListener('click', function(e) {
        const link = e.target.closest('.link-saiu-entrega');
        
        if (link) {
            const pedidoId = link.dataset.pedidoId;
            const tipoEntrega = link.dataset.tipoEntrega;
            const temEntregador = link.dataset.temEntregador === '1';
            
            console.log('🔍 Clicou em "Saiu para Entrega"');
            console.log('   Pedido:', pedidoId);
            console.log('   Tipo:', tipoEntrega);
            console.log('   Tem entregador?', temEntregador);
            
            // Se é delivery E não tem entregador → BLOQUEAR
            if (tipoEntrega === 'delivery' && !temEntregador) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                console.log('🚫 BLOQUEADO: Precisa selecionar entregador primeiro!');
                
                // Mostrar alerta
                const toast = document.createElement('div');
                toast.className = 'position-fixed top-0 end-0 p-3';
                toast.style.zIndex = '9999';
                toast.style.marginTop = '70px';
                toast.innerHTML = `
                    <div class="toast show align-items-center text-white bg-warning border-0">
                        <div class="d-flex">
                            <div class="toast-body">
                                <strong>⚠️ Selecione um entregador antes!</strong>
                                <br>Clique no botão 👤 primeiro.
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.position-fixed').remove()"></button>
                        </div>
                    </div>
                `;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 4000);
                
                // Destacar botão de atribuir
                const botaoAtribuir = document.querySelector(`button[data-pedido-id="${pedidoId}"][data-tem-entregador]`);
                if (botaoAtribuir) {
                    botaoAtribuir.style.animation = 'pulse 0.5s ease-in-out 3';
                    botaoAtribuir.style.transform = 'scale(1.2)';
                    setTimeout(() => {
                        botaoAtribuir.style.transform = '';
                    }, 1500);
                }
                
                return false;
            }
            
            console.log('✅ Permitido: Tem entregador ou não é delivery');
        }
    }, true);
    
    console.log('✅ Validação ativa!');
}

// CSS para animação
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.2); }
    }
`;
document.head.appendChild(style);

console.log('✅ validar-entregador-obrigatorio.js carregado');
