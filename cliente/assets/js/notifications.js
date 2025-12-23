// Sistema de notificações em tempo real

let notificationCheckInterval;

// Verificar notificações a cada 30 segundos
function iniciarVerificacaoNotificacoes() {
    verificarNotificacoes();
    notificationCheckInterval = setInterval(verificarNotificacoes, 30000);
}

async function verificarNotificacoes() {
    try {
        const response = await fetch('api/get_notificacoes.php');
        const data = await response.json();
        
        if (data.sucesso) {
            // Atualizar badge
            const badge = document.getElementById('badge-pedidos');
            if (badge && data.nao_lidas > 0) {
                badge.textContent = data.nao_lidas;
                badge.style.display = 'inline-block';
            } else if (badge) {
                badge.style.display = 'none';
            }
            
            // Mostrar notificações novas
            if (data.novas && data.novas.length > 0) {
                data.novas.forEach(notif => {
                    mostrarNotificacaoNativa(notif);
                });
            }
        }
    } catch (error) {
        console.error('Erro ao verificar notificações:', error);
    }
}

function mostrarNotificacaoNativa(notif) {
    // Verificar se o navegador suporta notificações
    if ('Notification' in window && Notification.permission === 'granted') {
        const notification = new Notification(notif.titulo, {
            body: notif.mensagem,
            icon: '../admin/uploads/config/logo.png',
            badge: '../admin/uploads/config/logo.png',
            tag: 'pedido-' + notif.pedido_id,
            requireInteraction: true
        });
        
        notification.onclick = function() {
            window.focus();
            window.location.href = 'pedido_detalhe.php?id=' + notif.pedido_id;
        };
    } else {
        // Fallback: toast notification
        showToast(notif.titulo + ': ' + notif.mensagem, 'info');
    }
}

// Solicitar permissão para notificações
async function solicitarPermissaoNotificacoes() {
    if ('Notification' in window && Notification.permission === 'default') {
        const permission = await Notification.requestPermission();
        if (permission === 'granted') {
            console.log('Permissão concedida para notificações');
            // Registrar Service Worker para PWA
            registrarServiceWorker();
        }
    }
}

// Registrar Service Worker para PWA (sem push)
async function registrarServiceWorker() {
    if ('serviceWorker' in navigator) {
        try {
            const registration = await navigator.serviceWorker.register('/cliente/sw.js');
            console.log('[Cliente] Service Worker registrado para PWA');
        } catch (error) {
            console.error('Erro ao registrar Service Worker:', error);
        }
    }
}

// Iniciar quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    iniciarVerificacaoNotificacoes();
    
    // Registrar Service Worker para PWA
    registrarServiceWorker();
});

// Parar verificação ao sair da página
window.addEventListener('beforeunload', function() {
    if (notificationCheckInterval) {
        clearInterval(notificationCheckInterval);
    }
});
