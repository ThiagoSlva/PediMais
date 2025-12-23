// PWA Install Prompt
let deferredPrompt;
let installButton = null;

// Criar botão de instalação
function createInstallButton() {
    // Verificar se já existe
    if (document.getElementById('pwa-install-btn')) {
        return;
    }
    
    // Criar botão
    const btn = document.createElement('button');
    btn.id = 'pwa-install-btn';
    btn.className = 'btn btn-primary-600 btn-sm d-none d-flex align-items-center justify-content-center gap-2';
    btn.innerHTML = '<iconify-icon icon="solar:smartphone-outline" style="font-size: 1.1em; line-height: 1; display: inline-flex;"></iconify-icon> <span>Instalar App</span>';
    btn.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.15); padding: 12px 20px; border-radius: 8px;';
    
    btn.addEventListener('click', async () => {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            console.log(`User response: ${outcome}`);
            deferredPrompt = null;
            btn.classList.add('d-none');
        }
    });
    
    document.body.appendChild(btn);
    installButton = btn;
}

// Evento quando o navegador está pronto para instalar
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    if (installButton) {
        installButton.classList.remove('d-none');
    } else {
        createInstallButton();
        installButton.classList.remove('d-none');
    }
});

// Evento quando o app é instalado
window.addEventListener('appinstalled', () => {
    console.log('PWA instalado');
    deferredPrompt = null;
    if (installButton) {
        installButton.classList.add('d-none');
    }
});

// Verificar se já está instalado
if (window.matchMedia('(display-mode: standalone)').matches) {
    console.log('App já está instalado');
}

// Criar botão na inicialização
document.addEventListener('DOMContentLoaded', () => {
    createInstallButton();
});




