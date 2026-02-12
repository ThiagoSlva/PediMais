<?php
include 'includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';
require_once '../includes/csrf.php';

verificar_login();

$msg = '';
$msg_tipo = '';

// Executar migration se tabelas não existirem
try {
    $pdo->query("SELECT 1 FROM gateway_settings LIMIT 1");
}
catch (PDOException $e) {
    // Executar migration
    include '../migrations/create_asaas_tables.php';
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validar_csrf()) {
        $msg = 'Token de segurança inválido. Recarregue a página.';
        $msg_tipo = 'danger';
    }
    else {
        $acao = $_POST['acao'] ?? '';

        try {
            if ($acao == 'salvar_gateway_ativo') {
                $gateway_ativo = $_POST['gateway_ativo'] ?? 'none';

                $stmt = $pdo->prepare("UPDATE gateway_settings SET gateway_ativo = ? WHERE id = 1");
                $stmt->execute([$gateway_ativo]);

                $msg = 'Gateway ativo atualizado com sucesso!';
                $msg_tipo = 'success';

            }
            elseif ($acao == 'salvar_mercadopago') {
                $ativo = isset($_POST['mp_ativo']) ? 1 : 0;
                $nome = $_POST['mp_nome'];
                $public_key = $_POST['mp_public_key'];
                $access_token = $_POST['mp_access_token'];
                $sandbox_mode = isset($_POST['mp_sandbox_mode']) ? 1 : 0;
                $prazo = (int)$_POST['mp_prazo'];

                $stmt = $pdo->query("SELECT id FROM mercadopago_config LIMIT 1");
                $config = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($config) {
                    $sql = "UPDATE mercadopago_config SET 
                        ativo = ?, nome = ?, public_key = ?, access_token = ?, 
                        sandbox_mode = ?, prazo_pagamento_minutos = ?, atualizado_em = NOW()
                        WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$ativo, $nome, $public_key, $access_token, $sandbox_mode, $prazo, $config['id']]);
                }
                else {
                    $sql = "INSERT INTO mercadopago_config (ativo, nome, public_key, access_token, sandbox_mode, prazo_pagamento_minutos) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$ativo, $nome, $public_key, $access_token, $sandbox_mode, $prazo]);
                }

                $msg = 'Configurações do Mercado Pago salvas!';
                $msg_tipo = 'success';

            }
            elseif ($acao == 'salvar_asaas') {
                $ativo = isset($_POST['asaas_ativo']) ? 1 : 0;
                $nome = $_POST['asaas_nome'];
                $access_token = $_POST['asaas_access_token'];
                $address_key = $_POST['asaas_address_key'];
                $sandbox_mode = isset($_POST['asaas_sandbox_mode']) ? 1 : 0;
                $prazo = (int)$_POST['asaas_prazo'];

                $stmt = $pdo->query("SELECT id FROM asaas_config LIMIT 1");
                $config = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($config) {
                    $sql = "UPDATE asaas_config SET 
                        ativo = ?, nome = ?, access_token = ?, address_key = ?, 
                        sandbox_mode = ?, prazo_pagamento_minutos = ?, atualizado_em = NOW()
                        WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$ativo, $nome, $access_token, $address_key, $sandbox_mode, $prazo, $config['id']]);
                }
                else {
                    $sql = "INSERT INTO asaas_config (ativo, nome, access_token, address_key, sandbox_mode, prazo_pagamento_minutos) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$ativo, $nome, $access_token, $address_key, $sandbox_mode, $prazo]);
                }

                $msg = 'Configurações do Asaas salvas!';
                $msg_tipo = 'success';
            }
        }
        catch (PDOException $e) {
            $msg = 'Erro ao salvar: ' . $e->getMessage();
            $msg_tipo = 'danger';
        }
    } // fecha else validar_csrf
}

// Buscar configurações atuais
$gateway_settings = $pdo->query("SELECT * FROM gateway_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$gateway_ativo = $gateway_settings['gateway_ativo'] ?? 'none';

$mp_config = $pdo->query("SELECT * FROM mercadopago_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$mp_config) {
    $mp_config = ['ativo' => 0, 'nome' => 'PIX Online', 'public_key' => '', 'access_token' => '', 'sandbox_mode' => 1, 'prazo_pagamento_minutos' => 30];
}

try {
    $asaas_config = $pdo->query("SELECT * FROM asaas_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}
catch (PDOException $e) {
    $asaas_config = null;
}
if (!$asaas_config) {
    $asaas_config = ['ativo' => 0, 'nome' => 'PIX Asaas', 'access_token' => '', 'address_key' => '', 'sandbox_mode' => 1, 'prazo_pagamento_minutos' => 30];
}

include 'includes/header.php';
?>

<style>
/* Dark mode support */
[data-theme="dark"] .nav-tabs .nav-link {
    color: rgba(255, 255, 255, 0.7) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}
[data-theme="dark"] .nav-tabs .nav-link.active {
    color: #fff !important;
    background-color: rgba(255, 255, 255, 0.1) !important;
    border-color: rgba(255, 255, 255, 0.2) !important;
}
[data-theme="dark"] .form-control,
[data-theme="dark"] .form-select {
    background-color: rgba(255, 255, 255, 0.05) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}
.gateway-selector {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}
.gateway-option {
    flex: 1;
    min-width: 200px;
    padding: 1.5rem;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}
.gateway-option:hover {
    border-color: #6366f1;
    background-color: rgba(99, 102, 241, 0.05);
}
.gateway-option.active {
    border-color: #6366f1;
    background-color: rgba(99, 102, 241, 0.1);
}
.gateway-option.none-option {
    flex: 0.5;
}
.gateway-option img {
    height: 40px;
    margin-bottom: 0.5rem;
}
.gateway-option h6 {
    margin: 0;
    font-weight: 600;
}
.gateway-option small {
    color: #666;
}
[data-theme="dark"] .gateway-option {
    border-color: rgba(255, 255, 255, 0.1);
}
[data-theme="dark"] .gateway-option:hover,
[data-theme="dark"] .gateway-option.active {
    border-color: #6366f1;
    background-color: rgba(99, 102, 241, 0.2);
}
</style>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div>
            <h6 class="fw-semibold mb-0">💳 Gateways de Pagamento</h6>
            <p class="text-sm text-secondary mb-0">Configure os gateways de pagamento online (PIX)</p>
        </div>
        <?php if ($gateway_ativo !== 'none'): ?>
        <span class="badge bg-success-600 px-3 py-2">
            <i class="fa-solid fa-circle-check"></i> 
            <?php echo $gateway_ativo === 'mercadopago' ? 'Mercado Pago' : 'Asaas'; ?> ATIVO
        </span>
        <?php
else: ?>
        <span class="badge bg-warning-600 px-3 py-2">
            <i class="fa-solid fa-exclamation-triangle"></i> NENHUM GATEWAY ATIVO
        </span>
        <?php
endif; ?>
    </div>
    <ul class="d-flex align-items-center gap-2">
        <li class="fw-medium">
            <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                Dashboard
            </a>
        </li>
        <li>-</li>
        <li class="fw-medium">Gateways</li>
    </ul>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?php echo $msg_tipo; ?> alert-dismissible fade show" role="alert">
    <?php echo $msg; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php
endif; ?>

<!-- Card: Selecionar Gateway Ativo -->
<div class="card h-100 p-0 radius-12 mb-24">
    <div class="card-header border-bottom bg-base py-16 px-24">
        <h6 class="text-lg fw-semibold mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:settings-bold-duotone" class="text-primary-600"></iconify-icon>
            Gateway Ativo
        </h6>
    </div>
    <div class="card-body p-24">
        <p class="text-secondary-light mb-3">
            Selecione qual gateway de pagamento será usado no checkout. Apenas um pode estar ativo por vez.
        </p>
        
        <form method="POST">
            <?php echo campo_csrf(); ?>
            <input type="hidden" name="acao" value="salvar_gateway_ativo">
            
            <div class="gateway-selector mb-3">
                <label class="gateway-option <?php echo $gateway_ativo === 'mercadopago' ? 'active' : ''; ?>" onclick="selectGateway('mercadopago')">
                    <input type="radio" name="gateway_ativo" value="mercadopago" class="d-none" <?php echo $gateway_ativo === 'mercadopago' ? 'checked' : ''; ?>>
                    <iconify-icon icon="logos:mercadopago" style="font-size: 40px;"></iconify-icon>
                    <h6>Mercado Pago</h6>
                    <small>PIX via API oficial</small>
                </label>
                
                <label class="gateway-option <?php echo $gateway_ativo === 'asaas' ? 'active' : ''; ?>" onclick="selectGateway('asaas')">
                    <input type="radio" name="gateway_ativo" value="asaas" class="d-none" <?php echo $gateway_ativo === 'asaas' ? 'checked' : ''; ?>>
                    <iconify-icon icon="solar:wallet-money-bold-duotone" style="font-size: 40px; color: #00c853;"></iconify-icon>
                    <h6>Asaas</h6>
                    <small>PIX estático</small>
                </label>
                
                <label class="gateway-option none-option <?php echo $gateway_ativo === 'none' ? 'active' : ''; ?>" onclick="selectGateway('none')">
                    <input type="radio" name="gateway_ativo" value="none" class="d-none" <?php echo $gateway_ativo === 'none' ? 'checked' : ''; ?>>
                    <iconify-icon icon="solar:close-circle-bold-duotone" style="font-size: 40px; color: #9e9e9e;"></iconify-icon>
                    <h6>Nenhum</h6>
                    <small>Desativado</small>
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary-600 radius-8 px-24 py-11 d-flex align-items-center gap-2">
                <iconify-icon icon="solar:check-circle-bold-duotone" class="icon"></iconify-icon>
                Salvar Gateway Ativo
            </button>
        </form>
    </div>
</div>

<!-- Card: Configurações por Gateway (Abas) -->
<div class="card h-100 p-0 radius-12 mb-24">
    <div class="card-header border-bottom bg-base py-16 px-24">
        <ul class="nav nav-tabs border-0" id="gatewayTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active d-flex align-items-center gap-2" id="mp-tab" data-bs-toggle="tab" data-bs-target="#mp-content" type="button" role="tab">
                    <iconify-icon icon="logos:mercadopago" style="font-size: 20px;"></iconify-icon>
                    Mercado Pago
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link d-flex align-items-center gap-2" id="asaas-tab" data-bs-toggle="tab" data-bs-target="#asaas-content" type="button" role="tab">
                    <iconify-icon icon="solar:wallet-money-bold-duotone" style="font-size: 20px; color: #00c853;"></iconify-icon>
                    Asaas
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body p-24">
        <div class="tab-content" id="gatewayTabsContent">
            
            <!-- Tab Mercado Pago -->
            <div class="tab-pane fade show active" id="mp-content" role="tabpanel">
                <form method="POST">
                    <?php echo campo_csrf(); ?>
                    <input type="hidden" name="acao" value="salvar_mercadopago">
                    
                    <div class="row gy-4">
                        <div class="col-lg-6">
                            <div class="border radius-8 p-16 h-100">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="mp_ativo" id="mp_ativo" <?php echo $mp_config['ativo'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold" for="mp_ativo">Habilitar Mercado Pago</label>
                                </div>
                                <small class="text-secondary-light">Permite configurar, mas só funciona se for o gateway ativo.</small>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <label for="mp_nome" class="form-label fw-semibold">Nome no Checkout</label>
                            <input type="text" class="form-control" name="mp_nome" id="mp_nome" value="<?php echo htmlspecialchars($mp_config['nome']); ?>" required>
                        </div>
                        
                        <div class="col-lg-6">
                            <label for="mp_public_key" class="form-label fw-semibold">Public Key</label>
                            <input type="text" class="form-control" name="mp_public_key" id="mp_public_key" value="<?php echo htmlspecialchars($mp_config['public_key']); ?>" placeholder="APP_USR-...">
                        </div>
                        
                        <div class="col-lg-6">
                            <label for="mp_access_token" class="form-label fw-semibold">Access Token</label>
                            <input type="password" class="form-control" name="mp_access_token" id="mp_access_token" value="<?php echo htmlspecialchars($mp_config['access_token']); ?>" placeholder="APP_USR-...">
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="border radius-8 p-16 h-100">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="mp_sandbox_mode" id="mp_sandbox_mode" <?php echo $mp_config['sandbox_mode'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold" for="mp_sandbox_mode">Modo Sandbox</label>
                                </div>
                                <small class="text-secondary-light">Desative em produção.</small>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <label for="mp_prazo" class="form-label fw-semibold">Prazo para Pagamento (min)</label>
                            <input type="number" class="form-control" name="mp_prazo" id="mp_prazo" value="<?php echo $mp_config['prazo_pagamento_minutos']; ?>" min="5" max="120">
                        </div>
                        
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary-600 radius-8 px-24 py-11 d-flex align-items-center gap-2 ms-auto">
                                <iconify-icon icon="solar:floppy-disk-bold-duotone" class="icon"></iconify-icon>
                                Salvar Mercado Pago
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Tab Asaas -->
            <div class="tab-pane fade" id="asaas-content" role="tabpanel">
                <form method="POST">
                    <?php echo campo_csrf(); ?>
                    <input type="hidden" name="acao" value="salvar_asaas">
                    
                    <div class="row gy-4">
                        <div class="col-lg-6">
                            <div class="border radius-8 p-16 h-100">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="asaas_ativo" id="asaas_ativo" <?php echo $asaas_config['ativo'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold" for="asaas_ativo">Habilitar Asaas</label>
                                </div>
                                <small class="text-secondary-light">Permite configurar, mas só funciona se for o gateway ativo.</small>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <label for="asaas_nome" class="form-label fw-semibold">Nome no Checkout</label>
                            <input type="text" class="form-control" name="asaas_nome" id="asaas_nome" value="<?php echo htmlspecialchars($asaas_config['nome']); ?>" required>
                        </div>
                        
                        <div class="col-12">
                            <div class="alert alert-info">
                                <iconify-icon icon="solar:info-circle-bold" class="me-2"></iconify-icon>
                                <strong>Como obter as credenciais:</strong> Acesse o painel Asaas → Integrações → API → Gerar Token. A chave PIX é o email/CPF/CNPJ cadastrado como chave PIX na sua conta Asaas.
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <label for="asaas_access_token" class="form-label fw-semibold">Access Token</label>
                            <input type="password" class="form-control" name="asaas_access_token" id="asaas_access_token" value="<?php echo htmlspecialchars($asaas_config['access_token']); ?>" placeholder="$aact_...">
                            <small class="text-secondary-light">Token de acesso da API Asaas</small>
                        </div>
                        
                        <div class="col-lg-6">
                            <label for="asaas_address_key" class="form-label fw-semibold">Chave PIX (Address Key)</label>
                            <input type="text" class="form-control" name="asaas_address_key" id="asaas_address_key" value="<?php echo htmlspecialchars($asaas_config['address_key']); ?>" placeholder="email@exemplo.com ou CPF/CNPJ">
                            <small class="text-secondary-light">Chave PIX cadastrada na sua conta Asaas</small>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="border radius-8 p-16 h-100">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="asaas_sandbox_mode" id="asaas_sandbox_mode" <?php echo $asaas_config['sandbox_mode'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-semibold" for="asaas_sandbox_mode">Modo Sandbox</label>
                                </div>
                                <small class="text-secondary-light">Use sandbox.asaas.com para testes.</small>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <label for="asaas_prazo" class="form-label fw-semibold">Prazo para Pagamento (min)</label>
                            <input type="number" class="form-control" name="asaas_prazo" id="asaas_prazo" value="<?php echo $asaas_config['prazo_pagamento_minutos']; ?>" min="5" max="120">
                        </div>
                        
                        <div class="col-lg-4">
                            <label class="form-label fw-semibold">URL Webhook</label>
                            <div class="input-group">
                                <input type="text" class="form-control" value="<?php echo SITE_URL; ?>/api/asaas_webhook.php" readonly id="webhook_url">
                                <button type="button" class="btn btn-outline-primary" onclick="copyWebhook()">
                                    <iconify-icon icon="solar:copy-bold"></iconify-icon>
                                </button>
                            </div>
                            <small class="text-secondary-light">Cadastre esta URL no painel Asaas</small>
                        </div>
                        
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-success-600 radius-8 px-24 py-11 d-flex align-items-center gap-2 ms-auto">
                                <iconify-icon icon="solar:floppy-disk-bold-duotone" class="icon"></iconify-icon>
                                Salvar Asaas
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
        </div>
    </div>
</div>

<!-- Card: Mensagens PIX -->
<div class="card h-100 p-0 radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24">
        <h6 class="text-lg fw-semibold mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:chat-line-bold-duotone" class="text-warning-600"></iconify-icon>
            Mensagens do WhatsApp
        </h6>
    </div>
    <div class="card-body p-24">
        <p class="text-secondary-light mb-3">
            Personalize as mensagens enviadas via WhatsApp durante o processo de pagamento PIX.
        </p>
        <a href="mercadopago_mensagens.php" class="btn btn-warning-600 radius-8 px-20 py-11 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:pen-bold-duotone" class="icon"></iconify-icon>
            Editar Mensagens
        </a>
    </div>
</div>

<script>
function selectGateway(gateway) {
    document.querySelectorAll('.gateway-option').forEach(el => el.classList.remove('active'));
    document.querySelector(`input[value="${gateway}"]`).checked = true;
    document.querySelector(`input[value="${gateway}"]`).closest('.gateway-option').classList.add('active');
}

function copyWebhook() {
    const url = document.getElementById('webhook_url').value;
    navigator.clipboard.writeText(url).then(() => {
        alert('URL copiada!');
    });
}
</script>

<?php include 'includes/footer.php'; ?>
