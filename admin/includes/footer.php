</div> <!-- End Content -->
    
    <!-- jQuery library js -->
    <!-- jQuery library js -->
    <script src="<?php echo SITE_URL; ?>/admin/assets/js/lib/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap js -->
    <script src="<?php echo SITE_URL; ?>/admin/assets/js/lib/bootstrap.bundle.min.js"></script>
    <!-- Apex Chart js -->
    <script src="<?php echo SITE_URL; ?>/admin/assets/js/lib/apexcharts.min.js"></script>
    <!-- Data Table js -->
    <script src="<?php echo SITE_URL; ?>/admin/assets/js/lib/dataTables.min.js"></script>
    <!-- Iconify Font js -->
    <script src="<?php echo SITE_URL; ?>/admin/assets/js/lib/iconify-icon.min.js"></script>
    <!-- jQuery UI js -->
    <script src="<?php echo SITE_URL; ?>/admin/assets/js/lib/jquery-ui.min.js"></script>
    <!-- main js -->
    <script src="<?php echo SITE_URL; ?>/admin/assets/js/app.js"></script>
    <!-- PWA Install -->
    <script src="<?php echo SITE_URL; ?>/admin/assets/js/pwa-install.js"></script>
    <!-- Realtime Updates -->
    <script src="<?php echo SITE_URL; ?>/admin/assets/js/realtime-updates.js"></script>
    
    <!-- ORDEM CORRETA DOS SCRIPTS (NÃO MUDAR) -->
    
    <!-- Sistema de Notificações -->
    <script src="<?php echo SITE_URL; ?>/admin/assets/js/notificacoes-sistema.js"></script>
    <!-- Sistema de Seleção de Entregador -->
    <script src="<?php echo SITE_URL; ?>/admin/assets/js/selecionar-entregador.js"></script>
    <!-- Validação: Obriga Selecionar Entregador -->
    <script src="<?php echo SITE_URL; ?>/admin/assets/js/validar-entregador-obrigatorio.js"></script>

    <!-- Auto dismiss alerts -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const alerts = document.querySelectorAll('.alert.alert-dismissible');
            alerts.forEach(alert => {
                const shouldKeep = alert.dataset.autodismiss === 'false' || alert.classList.contains('alert-sticky');
                if (shouldKeep) {
                    return;
                }

                const delay = Number(alert.dataset.dismissDelay || 6000);

                setTimeout(() => {
                    try {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                            bootstrap.Alert.getOrCreateInstance(alert).close();
                        } else if (typeof jQuery !== 'undefined' && typeof jQuery(alert).alert === 'function') {
                            jQuery(alert).alert('close');
                        } else {
                            alert.classList.remove('show');
                            alert.remove();
                        }
                    } catch (error) {
                        console.error('Erro ao fechar alerta automaticamente:', error);
                    }
                }, delay);
            });
        });
    </script>

    <!-- Toggle Script for Header Buttons -->
    <script>
    // Carregar status inicial dos toggles
    function carregarStatusToggles() {
        fetch('<?php echo SITE_URL; ?>/admin/api/get_status_sistema.php')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Toggle Entregadores
                    const toggleEntregadores = document.getElementById('toggleEntregadores');
                    if (toggleEntregadores) {
                        toggleEntregadores.checked = data.entregadores_ativo === 1;
                    }
                    
                    // Toggle Estabelecimento
                    const toggleEstabelecimento = document.getElementById('toggleEstabelecimento');
                    const iconEstabelecimento = document.getElementById('iconEstabelecimento');
                    if (toggleEstabelecimento) {
                        toggleEstabelecimento.checked = data.estabelecimento_aberto === 1;
                        
                        // Atualizar ícone e cor
                        if (iconEstabelecimento) {
                            if (data.estabelecimento_aberto === 1) {
                                iconEstabelecimento.classList.add('text-success-main');
                                iconEstabelecimento.classList.remove('text-danger-main');
                            } else {
                                iconEstabelecimento.classList.add('text-danger-main');
                                iconEstabelecimento.classList.remove('text-success-main');
                            }
                        }
                    }
                }
            })
            .catch(err => console.error('Erro ao carregar status:', err));
    }

    // Toggle Sistema de Entregadores
    const toggleEntregadores = document.getElementById('toggleEntregadores');
    if (toggleEntregadores) {
        toggleEntregadores.addEventListener('change', function() {
            const ativo = this.checked ? 1 : 0;
            
            fetch('<?php echo SITE_URL; ?>/admin/api/toggle_sistema.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    tipo: 'entregadores',
                    ativo: ativo
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Mostrar notificação
                    mostrarToast(data.mensagem, ativo ? 'success' : 'warning');
                } else {
                    alert('Erro: ' + (data.error || 'Desconhecido'));
                    this.checked = !this.checked; // Reverter
                }
            })
            .catch(err => {
                console.error('Erro:', err);
                alert('Erro ao atualizar sistema');
                this.checked = !this.checked; // Reverter
            });
        });
    }

    // Toggle Abrir/Fechar Estabelecimento
    const toggleEstabelecimento = document.getElementById('toggleEstabelecimento');
    if (toggleEstabelecimento) {
        toggleEstabelecimento.addEventListener('change', function() {
            const ativo = this.checked ? 1 : 0;
            const iconEstabelecimento = document.getElementById('iconEstabelecimento');
            
            fetch('<?php echo SITE_URL; ?>/admin/api/toggle_sistema.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    tipo: 'estabelecimento',
                    ativo: ativo
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Atualizar ícone
                    if (iconEstabelecimento) {
                        if (ativo) {
                            iconEstabelecimento.classList.add('text-success-main');
                            iconEstabelecimento.classList.remove('text-danger-main');
                        } else {
                            iconEstabelecimento.classList.add('text-danger-main');
                            iconEstabelecimento.classList.remove('text-success-main');
                        }
                    }
                    
                    // Mostrar notificação
                    mostrarToast(ativo ? '🟢 Estabelecimento ABERTO' : '🔴 Estabelecimento FECHADO', ativo ? 'success' : 'danger');
                } else {
                    alert('Erro: ' + (data.error || 'Desconhecido'));
                    this.checked = !this.checked; // Reverter
                }
            })
            .catch(err => {
                console.error('Erro:', err);
                alert('Erro ao atualizar estabelecimento');
                this.checked = !this.checked; // Reverter
            });
        });
    }

    // Função utilitária para mostrar toast
    function mostrarToast(mensagem, tipo = 'info') {
        const toast = document.createElement('div');
        toast.className = 'position-fixed top-0 end-0 p-3';
        toast.style.zIndex = '9999';
        toast.style.marginTop = '70px';
        toast.innerHTML = `
            <div class="toast show align-items-center text-white bg-${tipo} border-0">
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${mensagem}</strong>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.position-fixed').remove()"></button>
                </div>
            </div>
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    // Carregar ao iniciar
    document.addEventListener('DOMContentLoaded', carregarStatusToggles);
    </script>
</body>
</html>