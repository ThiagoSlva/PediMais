<?php
// cliente/includes/footer.php - Premium Footer
?>
</main>

<footer class="py-4 mt-5" style="background: var(--bg-secondary); border-top: 1px solid var(--gray-100);">
    <div class="container-dashboard">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <p class="mb-0" style="color: var(--gray-500); font-size: 0.875rem;">
                &copy; <?php echo date('Y'); ?> PedeMais. Todos os direitos reservados.
            </p>
            <div class="d-flex gap-3">
                <a href="../index.php" class="text-decoration-none" style="color: var(--gray-500); font-size: 0.875rem;">
                    <i class="fa-solid fa-store me-1"></i> Voltar ao Cardápio
                </a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- jQuery (for AJAX) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Input Mask -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

<script>
// Toast notification function
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast-premium ${type}`;
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-times-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    toast.innerHTML = `
        <i class="fa-solid ${icons[type]} text-${type === 'error' ? 'danger' : type}"></i>
        <span>${message}</span>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideInRight 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Format phone mask
$(document).ready(function() {
    $('.phone-mask').mask('(00) 00000-0000');
    $('.cep-mask').mask('00000-000');
    $('.cpf-mask').mask('000.000.000-00');
});

// Format currency
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

// Confirm delete
function confirmDelete(message) {
    return Swal.fire({
        title: 'Tem certeza?',
        text: message || 'Esta ação não pode ser desfeita.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#9C27B0',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar'
    });
}

// Loading state for buttons
function setLoading(button, loading) {
    if (loading) {
        button.disabled = true;
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Aguarde...';
    } else {
        button.disabled = false;
        button.innerHTML = button.dataset.originalText;
    }
}
</script>

</body>
</html>
