<?php
include 'includes/header.php';

// Buscar configurações do Mercado Pago
$stmt = $pdo->query("SELECT mercadopago_access_token, mercadopago_public_key FROM configuracoes LIMIT 1");
$mp_config = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar configurações do WhatsApp
$stmt = $pdo->query("SELECT * FROM whatsapp_config LIMIT 1");
$wa_config = $stmt->fetch(PDO::FETCH_ASSOC);

// Testar conexão Mercado Pago
$mp_status = 'Não testado';
$mp_class = 'secondary';
if (!empty($mp_config['mercadopago_access_token'])) {
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.mercadopago.com/v1/payment_methods',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $mp_config['mercadopago_access_token']
        ),
    ));
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($http_code == 200) {
        $mp_status = 'Conectado (HTTP 200)';
        $mp_class = 'success';
    } else {
        $mp_status = 'Erro (HTTP ' . $http_code . ')';
        $mp_class = 'danger';
    }
} else {
    $mp_status = 'Token não configurado';
    $mp_class = 'warning';
}

// Testar conexão WhatsApp (Evolution API)
$wa_status = 'Não testado';
$wa_class = 'secondary';
if (!empty($wa_config['api_url']) && !empty($wa_config['api_token']) && !empty($wa_config['instance_name'])) {
    $url = rtrim($wa_config['api_url'], '/') . '/instance/fetchInstances';
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'apikey: ' . $wa_config['api_token']
        ),
    ));
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($http_code == 200) {
        $wa_status = 'Conectado (HTTP 200)';
        $wa_class = 'success';
    } else {
        $wa_status = 'Erro (HTTP ' . $http_code . ')';
        $wa_class = 'danger';
    }
} else {
    $wa_status = 'Configuração incompleta';
    $wa_class = 'warning';
}

?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Debug - Integrações</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Debug MP & WhatsApp</li>
        </ul>
    </div>

    <div class="row gy-4">
        <!-- Mercado Pago -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Mercado Pago</h5>
                    <span class="badge bg-<?php echo $mp_class; ?>"><?php echo $mp_status; ?></span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Access Token</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($mp_config['mercadopago_access_token'] ?? ''); ?>" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Public Key</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($mp_config['mercadopago_public_key'] ?? ''); ?>" readonly>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <strong>Teste de Conexão:</strong> Tenta listar os métodos de pagamento usando o Access Token configurado.
                    </div>
                </div>
            </div>
        </div>

        <!-- WhatsApp -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">WhatsApp (Evolution API)</h5>
                    <span class="badge bg-<?php echo $wa_class; ?>"><?php echo $wa_status; ?></span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">API URL</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($wa_config['api_url'] ?? ''); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">API Token</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($wa_config['api_token'] ?? ''); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Instance Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($wa_config['instance_name'] ?? ''); ?>" readonly>
                    </div>
                    <div class="alert alert-info">
                        <strong>Teste de Conexão:</strong> Tenta buscar as instâncias na Evolution API usando a URL e Token configurados.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>