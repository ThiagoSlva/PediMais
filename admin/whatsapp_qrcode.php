<?php
include 'includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';

verificar_login();

// Carregar configurações
$stmt = $pdo->query("SELECT * FROM whatsapp_config LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

$base_url = $config['base_url'] ?? '';
$apikey = $config['apikey'] ?? '';
$instance_name = $config['instance_name'] ?? '';

// Helper para chamadas API
function evolution_request($endpoint, $method = 'GET', $data = [], $base_url, $apikey) {
    $url = rtrim($base_url, '/') . $endpoint;
    $headers = [
        'Content-Type: application/json',
        'apikey: ' . $apikey
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $http_code, 'body' => json_decode($response, true)];
}

$status = 'desconhecido';
$qrcode_base64 = '';
$msg = '';
$msg_tipo = '';

// Ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['acao']) && $_POST['acao'] == 'criar_instancia') {
        $novo_nome = $_POST['nome_instancia'] ?: 'cardapix_' . uniqid();
        
        $res = evolution_request('/instance/create', 'POST', [
            'instanceName' => $novo_nome,
            'qrcode' => true,
            'integration' => 'WHATSAPP-BAILEYS'
        ], $base_url, $apikey);

        if ($res['code'] == 201 || $res['code'] == 200) {
            // Salvar nome da instância
            $stmt = $pdo->prepare("UPDATE whatsapp_config SET instance_name = ? WHERE id = ?");
            $stmt->execute([$novo_nome, $config['id']]);
            $instance_name = $novo_nome;
            
            // Se retornou QR Code direto
            if (isset($res['body']['qrcode']['base64'])) {
                $qrcode_base64 = $res['body']['qrcode']['base64'];
            }
            $msg = 'Instância criada com sucesso!';
            $msg_tipo = 'success';
        } else {
            $msg = 'Erro ao criar instância: ' . ($res['body']['message'] ?? 'Erro desconhecido');
            $msg_tipo = 'danger';
        }
    } elseif (isset($_POST['acao']) && $_POST['acao'] == 'logout') {
        $res = evolution_request("/instance/logout/$instance_name", 'DELETE', [], $base_url, $apikey);
        $msg = 'Desconectado com sucesso.';
        $msg_tipo = 'warning';
    }
}

// Verificar Status e Buscar QR Code se necessário
if ($base_url && $apikey && $instance_name) {
    // 1. Verificar Status da Conexão
    $res = evolution_request("/instance/connectionState/$instance_name", 'GET', [], $base_url, $apikey);
    
    if ($res['code'] == 200) {
        $state = $res['body']['instance']['state'] ?? 'close';
        
        if ($state === 'open') {
            $status = 'conectado';
        } elseif ($state === 'close' || $state === 'connecting') {
            $status = 'desconectado';
            
            // 2. Buscar QR Code (se não tiver vindo da criação)
            if (empty($qrcode_base64)) {
                $res_qr = evolution_request("/instance/connect/$instance_name", 'GET', [], $base_url, $apikey);
                if ($res_qr['code'] == 200 && isset($res_qr['body']['base64'])) {
                    $qrcode_base64 = $res_qr['body']['base64'];
                }
            }
        }
    } else {
        $status = 'erro_api';
        $msg = 'Erro ao verificar status da instância. Verifique a URL e API Key.';
        $msg_tipo = 'danger';
    }
}

include 'includes/header.php';
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Conexão WhatsApp</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">WhatsApp</li>
        </ul>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_tipo; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row gy-4">
        <!-- Configuração -->
        <div class="col-md-4">
            <div class="card h-100 p-0 radius-12">
                <div class="card-header border-bottom bg-base py-16 px-24">
                    <h6 class="text-lg fw-semibold mb-0">Configuração</h6>
                </div>
                <div class="card-body p-24">
                    <p><strong>URL API:</strong> <?php echo htmlspecialchars($base_url ?: 'Não configurado'); ?></p>
                    <p><strong>Instância:</strong> <?php echo htmlspecialchars($instance_name ?: 'Nenhuma'); ?></p>
                    <p><strong>Status:</strong> 
                        <?php if ($status == 'conectado'): ?>
                            <span class="badge bg-success">Conectado</span>
                        <?php elseif ($status == 'desconectado'): ?>
                            <span class="badge bg-warning">Desconectado</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Erro/Desconhecido</span>
                        <?php endif; ?>
                    </p>
                    
                    <div class="mt-4">
                        <a href="whatsapp_config.php" class="btn btn-outline-primary w-100 mb-2">
                            <iconify-icon icon="solar:settings-bold-duotone"></iconify-icon>
                            Editar Configurações
                        </a>
                        
                        <?php if ($status == 'conectado'): ?>
                        <form method="POST">
                            <input type="hidden" name="acao" value="logout">
                            <button type="submit" class="btn btn-danger w-100">
                                <iconify-icon icon="solar:logout-3-bold-duotone"></iconify-icon>
                                Desconectar
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Área Principal (QR Code ou Status) -->
        <div class="col-md-8">
            <div class="card h-100 p-0 radius-12">
                <div class="card-header border-bottom bg-base py-16 px-24">
                    <h6 class="text-lg fw-semibold mb-0">Conexão</h6>
                </div>
                <div class="card-body p-24 text-center d-flex flex-column align-items-center justify-content-center" style="min-height: 400px;">
                    
                    <?php if (!$base_url || !$apikey): ?>
                        <iconify-icon icon="solar:danger-circle-bold-duotone" class="text-warning text-6xl mb-3" style="font-size: 4rem;"></iconify-icon>
                        <h5>API Não Configurada</h5>
                        <p class="text-secondary-light">Configure a URL e o Token da Evolution API primeiro.</p>
                        <a href="whatsapp_config.php" class="btn btn-primary">Ir para Configurações</a>

                    <?php elseif (!$instance_name): ?>
                        <iconify-icon icon="logos:whatsapp-icon" class="text-6xl mb-3" style="font-size: 4rem;"></iconify-icon>
                        <h5>Nenhuma Instância Criada</h5>
                        <p class="text-secondary-light">Crie uma instância para gerar o QR Code.</p>
                        
                        <form method="POST" class="w-100 max-w-sm" style="max-width: 300px;">
                            <input type="hidden" name="acao" value="criar_instancia">
                            <div class="mb-3 text-start">
                                <label class="form-label">Nome da Instância (Opcional)</label>
                                <input type="text" name="nome_instancia" class="form-control" placeholder="Ex: cardapix_loja1">
                            </div>
                            <button type="submit" class="btn btn-success w-100">
                                Criar Instância
                            </button>
                        </form>

                    <?php elseif ($status == 'conectado'): ?>
                        <iconify-icon icon="solar:check-circle-bold-duotone" class="text-success text-6xl mb-3" style="font-size: 5rem;"></iconify-icon>
                        <h4 class="text-success">WhatsApp Conectado!</h4>
                        <p class="text-secondary-light">O sistema está pronto para enviar mensagens.</p>

                    <?php elseif ($qrcode_base64): ?>
                        <h5 class="mb-3">Escaneie o QR Code</h5>
                        <div class="p-3 bg-white border radius-8 d-inline-block">
                            <img src="<?php echo $qrcode_base64; ?>" alt="QR Code WhatsApp" style="max-width: 250px;">
                        </div>
                        <p class="text-secondary-light mt-3">Abra o WhatsApp > Configurações > Aparelhos conectados > Conectar aparelho</p>
                        <script>
                            // Auto-reload para verificar conexão
                            setTimeout(function() {
                                window.location.reload();
                            }, 10000);
                        </script>

                    <?php else: ?>
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <h5>Carregando...</h5>
                        <p class="text-secondary-light">Buscando QR Code ou status da conexão.</p>
                        <script>
                            setTimeout(function() {
                                window.location.reload();
                            }, 3000);
                        </script>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
