<?php
include 'includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';
require_once '../includes/csrf.php';

verificar_login();

$msg = '';
$msg_tipo = '';
$qrcode_base64 = '';

// Migration: Add missing columns to whatsapp_config table
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM whatsapp_config");
    $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $columns_needed = [
        'base_url' => 'VARCHAR(500)',
        'apikey' => 'VARCHAR(255)',
        'instance_name' => 'VARCHAR(255)',
        'enviar_comprovante' => 'TINYINT(1) DEFAULT 0',
        'notificar_status_pedido' => 'TINYINT(1) DEFAULT 1',
        'enviar_link_acompanhamento' => 'TINYINT(1) DEFAULT 1',
        'popup_finalizacao_ativo' => 'TINYINT(1) DEFAULT 1',
        'whatsapp_estabelecimento' => 'VARCHAR(50)',
        'usar_mercadopago' => 'TINYINT(1) DEFAULT 1',
        'ativo' => 'TINYINT(1) DEFAULT 1',
        'atualizado_em' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
    ];

    foreach ($columns_needed as $col => $type) {
        if (!in_array($col, $existing_columns)) {
            $pdo->exec("ALTER TABLE whatsapp_config ADD COLUMN $col $type");
        }
    }
}
catch (PDOException $e) {
// Ignore migration errors
}

// Default values for config
$defaults = [
    'id' => null,
    'ativo' => 1,
    'base_url' => '',
    'apikey' => '',
    'instance_name' => '',
    'enviar_comprovante' => 0,
    'notificar_status_pedido' => 1,
    'enviar_link_acompanhamento' => 1,
    'popup_finalizacao_ativo' => 1,
    'whatsapp_estabelecimento' => '',
    'usar_mercadopago' => 1
];

// Carregar configurações ANTES do POST handler
$stmt = $pdo->query("SELECT * FROM whatsapp_config LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);
$config = array_merge($defaults, $config ?: []);

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao'])) {
    if (!validar_csrf()) {
        $msg = 'Token de segurança inválido. Recarregue a página.';
        $msg_tipo = 'danger';
    }
    elseif ($_POST['acao'] == 'salvar_config') {
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        $base_url = $_POST['api_url']; // Mapeando api_url para base_url
        $apikey = $_POST['api_token']; // Mapeando api_token para apikey
        $enviar_comprovante = isset($_POST['enviar_comprovante']) ? 1 : 0;
        $notificar_status_pedido = isset($_POST['enviar_status']) ? 1 : 0; // Mapeando enviar_status
        $enviar_link_acompanhamento = isset($_POST['enviar_link_acompanhamento']) ? 1 : 0;
        $popup_finalizacao_ativo = isset($_POST['popup_finalizacao_ativo']) ? 1 : 0;
        $whatsapp_estabelecimento = $_POST['whatsapp_estabelecimento'];
        $usar_mercadopago = isset($_POST['usar_mercadopago']) ? 1 : 0;

        try {
            // Verificar se já existe configuração
            $stmt = $pdo->query("SELECT id FROM whatsapp_config LIMIT 1");
            $config = $stmt->fetch();

            if ($config) {
                $stmt = $pdo->prepare("UPDATE whatsapp_config SET 
                                       ativo = ?,
                                       base_url = ?, 
                                       apikey = ?, 
                                       enviar_comprovante = ?,
                                       notificar_status_pedido = ?,
                                       enviar_link_acompanhamento = ?,
                                       popup_finalizacao_ativo = ?,
                                       whatsapp_estabelecimento = ?,
                                       usar_mercadopago = ?,
                                       atualizado_em = NOW() 
                                       WHERE id = ?");
                $stmt->execute([$ativo, $base_url, $apikey, $enviar_comprovante, $notificar_status_pedido, $enviar_link_acompanhamento, $popup_finalizacao_ativo, $whatsapp_estabelecimento, $usar_mercadopago, $config['id']]);
            }
            else {
                $stmt = $pdo->prepare("INSERT INTO whatsapp_config (ativo, base_url, apikey, enviar_comprovante, notificar_status_pedido, enviar_link_acompanhamento, popup_finalizacao_ativo, whatsapp_estabelecimento, usar_mercadopago) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$ativo, $base_url, $apikey, $enviar_comprovante, $notificar_status_pedido, $enviar_link_acompanhamento, $popup_finalizacao_ativo, $whatsapp_estabelecimento, $usar_mercadopago]);
            }
            $msg = 'Configurações salvas com sucesso!';
            $msg_tipo = 'success';
        }
        catch (PDOException $e) {
            $msg = 'Erro ao salvar: ' . $e->getMessage();
            $msg_tipo = 'danger';
        }
    }
    elseif ($_POST['acao'] == 'selecionar_instancia') {
        $instance_name = $_POST['nome_instancia'];
        try {
            // Atualizar apenas o nome da instância
            $stmt = $pdo->query("SELECT id FROM whatsapp_config LIMIT 1");
            $config = $stmt->fetch();

            if ($config) {
                $stmt = $pdo->prepare("UPDATE whatsapp_config SET instance_name = ? WHERE id = ?");
                $stmt->execute([$instance_name, $config['id']]);
            }
            else {
                $stmt = $pdo->prepare("INSERT INTO whatsapp_config (instance_name) VALUES (?)");
                $stmt->execute([$instance_name]);
            }
            $msg = 'Instância selecionada com sucesso!';
            $msg_tipo = 'success';
        }
        catch (PDOException $e) {
            $msg = 'Erro ao selecionar instância: ' . $e->getMessage();
            $msg_tipo = 'danger';
        }
    }
    elseif ($_POST['acao'] == 'criar_e_conectar') {
        // Criar nova instância e gerar QR Code
        $base_url = $config['base_url'] ?? '';
        $apikey = $config['apikey'] ?? '';

        if (empty($base_url) || empty($apikey)) {
            $msg = 'Erro: Configure a URL da API e o Token antes de criar uma instância.';
            $msg_tipo = 'danger';
        }
        else {
            $novo_nome = 'cardapix_' . uniqid();

            // Chamar Evolution API para criar instância
            $url = rtrim($base_url, '/') . '/instance/create';
            $headers = [
                'Content-Type: application/json',
                'apikey: ' . $apikey
            ];
            $data = [
                'instanceName' => $novo_nome,
                'qrcode' => true,
                'integration' => 'WHATSAPP-BAILEYS'
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = json_decode($response, true);

            if ($http_code == 201 || $http_code == 200) {
                // Salvar nome da instância
                $stmt = $pdo->query("SELECT id FROM whatsapp_config LIMIT 1");
                $conf = $stmt->fetch();
                if ($conf) {
                    $stmt = $pdo->prepare("UPDATE whatsapp_config SET instance_name = ? WHERE id = ?");
                    $stmt->execute([$novo_nome, $conf['id']]);
                }

                // Se retornou QR Code
                if (isset($result['qrcode']['base64'])) {
                    $qrcode_base64 = $result['qrcode']['base64'];
                    $msg = 'Instância criada! Escaneie o QR Code abaixo:';
                    $msg_tipo = 'success';
                }
                else {
                    $msg = 'Instância criada com sucesso! Acesse a página de QR Code para conectar.';
                    $msg_tipo = 'success';
                }

                // Reload config
                $stmt = $pdo->query("SELECT * FROM whatsapp_config LIMIT 1");
                $config = array_merge($defaults, $stmt->fetch(PDO::FETCH_ASSOC) ?: []);
            }
            else {
                $msg = 'Erro ao criar instância: ' . ($result['message'] ?? $result['error'] ?? 'Erro desconhecido');
                $msg_tipo = 'danger';
            }
        }
    }
    elseif ($_POST['acao'] == 'reiniciar') {
        $base_url = $config['base_url'] ?? '';
        $apikey = $config['apikey'] ?? '';
        $instance_name = $config['instance_name'] ?? '';

        if (empty($instance_name)) {
            $msg = 'Erro: Nenhuma instância configurada.';
            $msg_tipo = 'danger';
        }
        else {
            $url = rtrim($base_url, '/') . '/instance/restart/' . urlencode($instance_name);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'apikey: ' . $apikey]);
            curl_setopt($ch, CURLOPT_PUT, true);
            curl_exec($ch);
            curl_close($ch);

            $msg = 'Conexão reiniciada com sucesso!';
            $msg_tipo = 'success';
        }
    }
    elseif ($_POST['acao'] == 'desconectar') {
        $base_url = $config['base_url'] ?? '';
        $apikey = $config['apikey'] ?? '';
        $instance_name = $config['instance_name'] ?? '';

        if (empty($instance_name)) {
            $msg = 'Erro: Nenhuma instância configurada.';
            $msg_tipo = 'danger';
        }
        else {
            $url = rtrim($base_url, '/') . '/instance/logout/' . urlencode($instance_name);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'apikey: ' . $apikey]);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_exec($ch);
            curl_close($ch);

            $msg = 'Desconectado com sucesso!';
            $msg_tipo = 'warning';
        }
    }
    elseif ($_POST['acao'] == 'deletar') {
        $base_url = $config['base_url'] ?? '';
        $apikey = $config['apikey'] ?? '';
        $instance_name = $config['instance_name'] ?? '';

        if (empty($instance_name)) {
            $msg = 'Erro: Nenhuma instância configurada.';
            $msg_tipo = 'danger';
        }
        else {
            $url = rtrim($base_url, '/') . '/instance/delete/' . urlencode($instance_name);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'apikey: ' . $apikey]);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_exec($ch);
            curl_close($ch);

            // Limpar nome da instância do banco
            $stmt = $pdo->prepare("UPDATE whatsapp_config SET instance_name = '' WHERE id = ?");
            $stmt->execute([$config['id']]);

            $msg = 'Instância deletada com sucesso!';
            $msg_tipo = 'success';

            // Reload config
            $stmt = $pdo->query("SELECT * FROM whatsapp_config LIMIT 1");
            $config = array_merge($defaults, $stmt->fetch(PDO::FETCH_ASSOC) ?: []);
        }
    }
    elseif ($_POST['acao'] == 'verificar_status') {
    // Verificar status não precisa fazer nada aqui - será verificado abaixo
    }
}

// Verificar status da conexão automaticamente se tiver instância configurada
$connection_status = 'desconhecido';
$status_badge_class = 'bg-secondary-600';
$status_text = 'Desconhecido';

if (!empty($config['instance_name']) && !empty($config['base_url']) && !empty($config['apikey'])) {
    $url = rtrim($config['base_url'], '/') . '/instance/connectionState/' . urlencode($config['instance_name']);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'apikey: ' . $config['apikey']]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200) {
        $result = json_decode($response, true);
        $state = $result['instance']['state'] ?? $result['state'] ?? '';

        if ($state === 'open' || $state === 'connected') {
            $connection_status = 'conectado';
            $status_badge_class = 'bg-success-600';
            $status_text = 'Conectado ✓';
        }
        elseif ($state === 'connecting') {
            $connection_status = 'conectando';
            $status_badge_class = 'bg-warning-600';
            $status_text = 'Conectando...';
        }
        elseif ($state === 'close' || $state === 'closed') {
            $connection_status = 'desconectado';
            $status_badge_class = 'bg-danger-600';
            $status_text = 'Desconectado';
        }
    }
}

// Carregar configurações
$stmt = $pdo->query("SELECT * FROM whatsapp_config LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Default values
$defaults = [
    'ativo' => 1,
    'base_url' => '',
    'apikey' => '',
    'instance_name' => '',
    'enviar_comprovante' => 0,
    'notificar_status_pedido' => 1,
    'enviar_link_acompanhamento' => 1,
    'popup_finalizacao_ativo' => 1,
    'whatsapp_estabelecimento' => '',
    'usar_mercadopago' => 1
];

// Merge config with defaults to prevent undefined array key warnings
$config = array_merge($defaults, $config ?: []);


include 'includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
    <div>
        <h6 class="fw-semibold mb-0">WhatsApp Evolution API</h6>
        <p class="text-sm text-secondary mb-0">
            <a href="testar_evolution_api.php" class="text-primary d-flex align-items-center gap-1">
                <iconify-icon icon="solar:bug-minimalistic-bold-duotone" class="icon"></iconify-icon>
                Testar Conexão com API
            </a>
        </p>
    </div>
    <ul class="d-flex align-items-center gap-2">
        <li class="fw-medium">
            <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                Dashboard
            </a>
        </li>
        <li>-</li>
        <li class="fw-medium">WhatsApp Evolution</li>
    </ul>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?php echo $msg_tipo; ?> alert-dismissible fade show" role="alert">
    <?php echo $msg; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php
endif; ?>

<?php if (!empty($qrcode_base64)): ?>
<!-- QR Code Display -->
<div class="card h-100 p-0 radius-12 mb-24">
    <div class="card-header border-bottom bg-success-100 py-16 px-24">
        <h6 class="text-lg fw-semibold mb-0 d-flex align-items-center gap-2 text-success-600">
            <iconify-icon icon="solar:qr-code-bold-duotone" class="text-xl"></iconify-icon>
            Escaneie o QR Code para conectar
        </h6>
    </div>
    <div class="card-body p-24 text-center">
        <p class="text-secondary-light mb-3">Abra o WhatsApp no seu celular, vá em Menu > Dispositivos conectados > Conectar dispositivo</p>
        <img src="<?php echo $qrcode_base64; ?>" alt="QR Code" style="max-width: 300px; height: auto;" class="border radius-8 p-3">
        <p class="text-warning-600 mt-3 small"><strong>Atenção:</strong> O QR Code expira em alguns minutos. Se expirar, clique em "Criar Nova Instância + QR Code" novamente.</p>
    </div>
</div>
<?php
endif; ?>

<!-- Card de Configuração da API -->
<div class="card h-100 p-0 radius-12 mb-24">
    <div class="card-header border-bottom bg-base py-16 px-24">
        <h6 class="text-lg fw-semibold mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:settings-bold-duotone" class="text-primary-600" style="font-size: 1.25em; line-height: 1;"></iconify-icon>
            Configuração da API
        </h6>
    </div>
    <div class="card-body p-24">
        <form method="POST">
            <?php echo campo_csrf(); ?>
            <input type="hidden" name="acao" value="salvar_config">
            
            <div class="row gy-4">
                <div class="col-12">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-16 border radius-8 bg-base-soft">
                        <div>
                            <h6 class="text-uppercase text-secondary mb-1" style="letter-spacing: 0.05em;">Status Geral</h6>
                            <p class="mb-0 text-secondary-light">Ative para permitir o envio automático de mensagens via Evolution API.</p>
                        </div>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="ativo" 
                                   id="ativo"
                                   <?php echo $config['ativo'] ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-semibold" for="ativo">
                                Sistema Ativo
                            </label>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <h6 class="text-uppercase text-primary-600 fw-semibold small mb-3">Credenciais da API</h6>
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                        URL da Evolution API
                        <span class="text-danger-600">*</span>
                    </label>
                    <input type="url" 
                           name="api_url" 
                           class="form-control radius-8" 
                           value="<?php echo htmlspecialchars($config['base_url']); ?>"
                           placeholder="https://sua-api.com" 
                           required>
                    <small class="text-secondary-light">Exemplo: https://evolution.exemplo.com</small>
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label fw-semibold text-primary-light text-sm mb-8 d-flex align-items-center gap-2">
                        Token Global (API Key)
                        <span class="text-danger-600">*</span>
                        <button type="button" class="btn btn-sm btn-link p-0" onclick="toggleTokenVisibility()" id="toggleTokenBtn">
                            <iconify-icon icon="solar:eye-bold-duotone" id="toggleTokenIcon"></iconify-icon>
                        </button>
                    </label>
                    <div class="input-group">
                        <input type="password" 
                               name="api_token" 
                               class="form-control radius-8" 
                               id="api_token_input"
                               value="<?php echo htmlspecialchars($config['apikey']); ?>"
                               placeholder="seu_token_aqui" 
                               required>
                    </div>
                    <small class="text-secondary-light">Token de autenticação fornecido pela Evolution API (oculto por segurança)</small>
                </div>

                <script>
                function toggleTokenVisibility() {
                    const input = document.getElementById('api_token_input');
                    const icon = document.getElementById('toggleTokenIcon');
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.setAttribute('icon', 'solar:eye-closed-bold-duotone');
                    } else {
                        input.type = 'password';
                        icon.setAttribute('icon', 'solar:eye-bold-duotone');
                    }
                }
                </script>

                <div class="col-12">
                    <hr class="my-2">
                    <h6 class="text-uppercase text-primary-600 fw-semibold small mb-3">Automação de Mensagens</h6>
                </div>

                <div class="col-md-4">
                    <div class="border radius-8 p-16 h-100">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="enviar_comprovante" 
                                   id="enviar_comprovante"
                                   <?php echo $config['enviar_comprovante'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="enviar_comprovante">
                                Enviar comprovante
                            </label>
                        </div>
                        <small class="text-secondary-light">Dispara mensagem após finalizar o pedido.</small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border radius-8 p-16 h-100">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="enviar_status" 
                                   id="enviar_status"
                                   <?php echo $config['notificar_status_pedido'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="enviar_status">
                                Notificar mudança de status
                            </label>
                        </div>
                        <small class="text-secondary-light">Atualiza o cliente quando o pedido avança.</small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border radius-8 p-16 h-100">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="enviar_link_acompanhamento" 
                                   id="enviar_link_acompanhamento"
                                   <?php echo $config['enviar_link_acompanhamento'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="enviar_link_acompanhamento">
                                Enviar link de acompanhamento
                            </label>
                        </div>
                        <small class="text-secondary-light">Envia o link da área do cliente para acompanhar o pedido.</small>
                    </div>
                </div>

                <div class="col-12">
                    <hr class="my-2">
                    <h6 class="text-uppercase text-primary-600 fw-semibold small mb-3">Popup de Finalização</h6>
                </div>
                <div class="col-md-6">
                    <div class="border radius-8 p-16 h-100">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="popup_finalizacao_ativo" 
                                   id="popup_finalizacao_ativo"
                                   <?php echo $config['popup_finalizacao_ativo'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="popup_finalizacao_ativo">
                                Ativar popup com WhatsApp
                            </label>
                        </div>
                        <small class="text-secondary-light">Exibe um popup com recibo e botão do WhatsApp logo após o cliente finalizar o pedido.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border radius-8 p-16 h-100">
                        <label for="whatsapp_estabelecimento" class="form-label fw-semibold">WhatsApp do estabelecimento</label>
                        <input type="text" 
                               class="form-control" 
                               name="whatsapp_estabelecimento" 
                               id="whatsapp_estabelecimento"
                               value="<?php echo htmlspecialchars($config['whatsapp_estabelecimento']); ?>"
                               placeholder="5573988742045"
                               maxlength="20">
                        <small class="text-secondary-light">Formato esperado: código do país + DDD + número (ex.: 5573999998888).</small>
                    </div>
                </div>
                
                <div class="col-12">
                    <hr class="my-2">
                    <h6 class="text-uppercase text-primary-600 fw-semibold small mb-3">Integração com Pagamentos Online</h6>
                </div>
                <div class="col-12">
                    <div class="border radius-8 p-16">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="usar_mercadopago" 
                                   id="usar_mercadopago"
                                   <?php echo $config['usar_mercadopago'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="usar_mercadopago">
                                Enviar mensagens automáticas do Mercado Pago
                            </label>
                        </div>
                        <small class="text-secondary-light d-block mt-2">
                            Quando ativo, envia via WhatsApp as mensagens de pagamento ("Aguardando" e "Recebido") para pedidos com checkout online.
                        </small>
                    </div>
                </div>
                
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary-600 radius-8 px-24 py-11 d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:floppy-disk-bold-duotone" style="font-size: 1.1em; line-height: 1;"></iconify-icon>
                        Salvar configuração
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Card de Selecionar Instância Existente -->
<div class="card h-100 p-0 radius-12 mb-24">
    <div class="card-header border-bottom bg-base py-16 px-24">
        <h6 class="text-lg fw-semibold mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:list-bold-duotone" class="text-info-600"></iconify-icon>
            Usar Instância Existente
        </h6>
    </div>
    <div class="card-body p-24">
        <p class="text-secondary-light mb-3">
            Se você já criou uma instância na Evolution API, pode selecioná-la aqui:
        </p>
        
        <form method="POST" class="row gy-3">
            <?php echo campo_csrf(); ?>
            <input type="hidden" name="acao" value="selecionar_instancia">
            
            <div class="col-12">
                <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                    Nome da Instância Existente
                </label>
                <input type="text" 
                       name="nome_instancia" 
                       class="form-control radius-8" 
                       placeholder="Ex: ORPpkmI0bEnkrObXCkseJLAh"
                       value="<?php echo htmlspecialchars($config['instance_name']); ?>">
                <small class="text-secondary-light">
                    Cole o nome exato da instância que aparece na Evolution API ou 
                    <a href="testar_evolution_api.php" target="_blank">veja suas instâncias aqui</a>
                </small>
            </div>
            
            <div class="col-12">
                <button type="submit" class="btn btn-info-600 radius-8 px-20 py-11 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:check-circle-bold-duotone"></iconify-icon>
                    Usar Esta Instância
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Card de Gerenciamento da Instância -->
<div class="card h-100 p-0 radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24">
        <h6 class="text-lg fw-semibold mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="logos:whatsapp-icon" class="text-xl"></iconify-icon>
            Gerenciamento da Instância
        </h6>
    </div>
    <div class="card-body p-24">
                        
        <!-- Status da Conexão -->
        <div class="alert alert-info mb-24">
            <div class="d-flex align-items-center gap-3">
                <div class="flex-grow-1">
                    <h6 class="mb-1">Instância: <strong><?php echo htmlspecialchars($config['instance_name'] ?: 'Não configurada'); ?></strong></h6>
                    <p class="mb-0">
                        Status: 
                        <span class="badge <?php echo $status_badge_class; ?>"><?php echo $status_text; ?></span>
                    </p>
                </div>
                <form method="POST" class="d-inline">
                    <?php echo campo_csrf(); ?>
                    <input type="hidden" name="acao" value="verificar_status">
                    <button type="submit" class="btn btn-outline-info-600 btn-sm d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:refresh-bold"></iconify-icon>
                        Atualizar Status
                    </button>
                </form>
            </div>
        </div>

        <!-- Ações -->
        <div class="row g-3">
            <div class="col-12 mb-3">
                <form method="POST" class="d-inline w-100">
                    <?php echo campo_csrf(); ?>
                    <input type="hidden" name="acao" value="criar_e_conectar">
                    <button type="submit" class="btn btn-success-600 w-100 btn-lg d-flex align-items-center justify-content-center gap-2">
                        <iconify-icon icon="solar:qr-code-bold-duotone"></iconify-icon>
                        🚀 Criar Nova Instância + QR Code
                    </button>
                </form>
                <small class="text-secondary-light d-block mt-2 text-center">
                    Cria uma instância nova e gera QR Code automaticamente
                </small>
            </div>
            
            <div class="col-md-6">
                <form method="POST" class="d-inline w-100" onsubmit="return confirm('Deseja reiniciar a conexão?')">
                    <?php echo campo_csrf(); ?>
                    <input type="hidden" name="acao" value="reiniciar">
                    <button type="submit" class="btn btn-warning-600 w-100 d-flex align-items-center justify-content-center gap-2">
                        <iconify-icon icon="solar:restart-bold"></iconify-icon>
                        Reiniciar Conexão
                    </button>
                </form>
            </div>
            
            <div class="col-md-6">
                <form method="POST" class="d-inline w-100" onsubmit="return confirm('Deseja desconectar?')">
                    <?php echo campo_csrf(); ?>
                    <input type="hidden" name="acao" value="desconectar">
                    <button type="submit" class="btn btn-danger-600 w-100 d-flex align-items-center justify-content-center gap-2">
                        <iconify-icon icon="solar:logout-3-bold"></iconify-icon>
                        Desconectar
                    </button>
                </form>
            </div>
            
            <div class="col-md-6">
                <form method="POST" class="d-inline w-100" onsubmit="return confirm('ATENÇÃO: Isso irá deletar permanentemente a instância. Deseja continuar?')">
                    <?php echo campo_csrf(); ?>
                    <input type="hidden" name="acao" value="deletar">
                    <button type="submit" class="btn btn-outline-danger-600 w-100 d-flex align-items-center justify-content-center gap-2">
                        <iconify-icon icon="solar:trash-bin-trash-bold"></iconify-icon>
                        Deletar Instância
                    </button>
                </form>
            </div>
        </div>
        
    </div>
</div>

<?php include 'includes/footer.php'; ?>