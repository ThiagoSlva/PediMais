    </div><!-- .dashboard-main-body -->

    <footer class="d-footer">
        <div class="row align-items-center justify-content-between">
            <div class="col-auto">
                <p class="mb-0">© <?php echo date('Y'); ?> CardapiX. Todos os direitos reservados.</p>
            </div>
            <div class="col-auto">
                <p class="mb-0">Feito com <span class="text-danger-600">❤</span> por <a href="#" class="text-primary-600">Thiago Silva</a></p>
            </div>
        </div>
    </footer>
</main>

<!-- jQuery library js -->
<script src="<?php echo SITE_URL; ?>/admin/assets/js/lib/jquery-3.7.1.min.js"></script>
<!-- Bootstrap js -->
<script src="<?php echo SITE_URL; ?>/admin/assets/js/lib/bootstrap.bundle.min.js"></script>
<!-- Data Table js -->
<script src="<?php echo SITE_URL; ?>/admin/assets/js/lib/dataTables.min.js"></script>
<!-- Iconify -->
<script src="<?php echo SITE_URL; ?>/admin/assets/js/lib/iconify-icon.min.js"></script>
<!-- Apex Charts -->
<script src="<?php echo SITE_URL; ?>/admin/assets/js/lib/apexcharts.min.js"></script>

<!-- Main js -->
<script src="<?php echo SITE_URL; ?>/admin/assets/js/app.js"></script>
<!-- Realtime Updates -->
<script src="<?php echo SITE_URL; ?>/admin/assets/js/realtime-updates.js"></script>
<!-- Notifications -->
<script src="<?php echo SITE_URL; ?>/admin/assets/js/notificacoes-sistema.js"></script>

<script>
    // Initialize DataTables if table exists
    $(document).ready(function() {
        if ($('.table').length > 0) {
            $('.table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
                },
                responsive: true,
                order: [[0, 'desc']] // Default sort by first column descending
            });
        }
    });
</script>

</body>
</html>