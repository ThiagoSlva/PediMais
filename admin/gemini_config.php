<?php
require_once 'includes/header.php';
require_once __DIR__ . '/../includes/csrf.php';
verificar_permissao();

$msg = '';
$msg_type = '';

// Create/update table with both providers
$pdo->exec("CREATE TABLE IF NOT EXISTS ai_config (
    id INT PRIMARY KEY DEFAULT 1,
    provider VARCHAR(50) DEFAULT 'gemini',
    gemini_api_key VARCHAR(255) DEFAULT NULL,
    gemini_modelo VARCHAR(100) DEFAULT 'gemini-2.0-flash',
    openai_api_key VARCHAR(255) DEFAULT NULL,
    openai_modelo VARCHAR(100) DEFAULT 'gpt-4o-mini',
    ativo TINYINT(1) DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Migrate from old gemini_config if exists
try {
    $old_config = $pdo->query("SELECT * FROM gemini_config WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
    if ($old_config) {
        $pdo->exec("INSERT IGNORE INTO ai_config (id, provider, gemini_api_key, gemini_modelo, ativo) 
                    SELECT 1, 'gemini', api_key, COALESCE(modelo, 'gemini-2.0-flash'), ativo FROM gemini_config WHERE id = 1");
    }
}
catch (PDOException $e) {
// Old table doesn't exist, ignore
}

// Insert default row if not exists
$pdo->exec("INSERT IGNORE INTO ai_config (id) VALUES (1)");

// Available models
$gemini_modelos = [
    'gemini-2.0-flash' => 'Gemini 2.0 Flash (Rápido)',
    'gemini-2.0-flash-exp' => 'Gemini 2.0 Flash Experimental',
    'gemini-1.5-flash' => 'Gemini 1.5 Flash',
    'gemini-1.5-flash-latest' => 'Gemini 1.5 Flash (Último)',
    'gemini-1.5-pro' => 'Gemini 1.5 Pro (Avançado)',
    'gemini-pro' => 'Gemini Pro (Clássico)',
];

$openai_modelos = [
    'gpt-4o-mini' => 'GPT-4o Mini (Rápido e Barato)',
    'gpt-4o' => 'GPT-4o (Avançado)',
    'gpt-4-turbo' => 'GPT-4 Turbo',
    'gpt-4-vision-preview' => 'GPT-4 Vision Preview',
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validar_csrf()) {
        $msg = 'Token de segurança inválido. Recarregue a página.';
        $msg_type = 'danger';
    }
    else {
        $provider = $_POST['provider'] ?? 'gemini';
        $gemini_api_key = trim($_POST['gemini_api_key'] ?? '');
        $gemini_modelo = trim($_POST['gemini_modelo'] ?? 'gemini-2.0-flash');
        $openai_api_key = trim($_POST['openai_api_key'] ?? '');
        $openai_modelo = trim($_POST['openai_modelo'] ?? 'gpt-4o-mini');
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        try {
            $stmt = $pdo->prepare("UPDATE ai_config SET 
            provider = ?, gemini_api_key = ?, gemini_modelo = ?, 
            openai_api_key = ?, openai_modelo = ?, ativo = ? 
            WHERE id = 1");
            $stmt->execute([$provider, $gemini_api_key, $gemini_modelo, $openai_api_key, $openai_modelo, $ativo]);
            $msg = 'Configurações salvas com sucesso!';
            $msg_type = 'success';
        }
        catch (PDOException $e) {
            $msg = 'Erro ao salvar: ' . $e->getMessage();
            $msg_type = 'danger';
        }
    } // fecha else validar_csrf
}

// Load current config
$config = $pdo->query("SELECT * FROM ai_config WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
?>

<style>
.provider-card {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s;
    height: 100%;
}
.provider-card:hover {
    border-color: #7c3aed;
    background: #f8f4ff;
}
.provider-card.active {
    border-color: #7c3aed;
    background: linear-gradient(135deg, #f3e8ff 0%, #ddd6fe 100%);
    box-shadow: 0 4px 15px rgba(124, 58, 237, 0.2);
}
.provider-card .provider-icon {
    font-size: 48px;
    margin-bottom: 10px;
}
.provider-settings {
    display: none;
    animation: fadeIn 0.3s ease;
}
.provider-settings.active {
    display: block;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.gemini-gradient {
    background: linear-gradient(135deg, #4285f4, #ea4335, #fbbc05, #34a853);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.openai-color { color: #10a37f; }
</style>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">
            <iconify-icon icon="solar:magic-stick-3-bold-duotone" class="text-primary me-2"></iconify-icon>
            Configuração de IA para Cardápio
        </h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Configuração IA</li>
        </ul>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($msg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php
endif; ?>

    <form method="POST">
        <?php echo campo_csrf(); ?>
        <!-- Provider Selection -->
        <div class="row mb-4">
            <div class="col-12">
                <h6 class="fw-semibold mb-3">Escolha o Provedor de IA</h6>
            </div>
            <div class="col-md-6 mb-3">
                <div class="provider-card <?php echo($config['provider'] ?? 'gemini') === 'gemini' ? 'active' : ''; ?>" 
                     onclick="selectProvider('gemini')">
                    <div class="text-center">
                        <iconify-icon icon="ri:gemini-fill" class="provider-icon gemini-gradient"></iconify-icon>
                        <h5 class="fw-bold mb-2">Google Gemini</h5>
                        <p class="text-secondary-light small mb-0">
                            IA do Google com excelente análise de imagens
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="provider-card <?php echo($config['provider'] ?? '') === 'openai' ? 'active' : ''; ?>" 
                     onclick="selectProvider('openai')">
                    <div class="text-center">
                        <iconify-icon icon="simple-icons:openai" class="provider-icon openai-color"></iconify-icon>
                        <h5 class="fw-bold mb-2">OpenAI</h5>
                        <p class="text-secondary-light small mb-0">
                            GPT-4 Vision com alta precisão
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <input type="hidden" name="provider" id="providerInput" value="<?php echo htmlspecialchars($config['provider'] ?? 'gemini'); ?>">

        <div class="row">
            <div class="col-lg-8">
                <!-- Gemini Settings -->
                <div class="card radius-12 mb-4 provider-settings <?php echo($config['provider'] ?? 'gemini') === 'gemini' ? 'active' : ''; ?>" id="gemini-settings">
                    <div class="card-header border-bottom bg-base py-16 px-24">
                        <h6 class="text-lg fw-semibold mb-0">
                            <iconify-icon icon="ri:gemini-fill" class="gemini-gradient me-2"></iconify-icon>
                            Configuração do Google Gemini
                        </h6>
                    </div>
                    <div class="card-body p-24">
                        <div class="mb-20">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                                API Key do Gemini
                            </label>
                            <input type="password" class="form-control radius-8" name="gemini_api_key" id="gemini_api_key"
                                   value="<?php echo htmlspecialchars($config['gemini_api_key'] ?? ''); ?>"
                                   placeholder="AIzaSy...">
                            <small class="text-secondary-light">
                                Obtenha em: <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-primary">Google AI Studio</a>
                            </small>
                        </div>
                        <div class="mb-20">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Modelo</label>
                            <select class="form-select radius-8" name="gemini_modelo" id="gemini_modelo">
                                <?php foreach ($gemini_modelos as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo($config['gemini_modelo'] ?? '') === $value ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                                <?php
endforeach; ?>
                            </select>
                        </div>
                        <button type="button" class="btn btn-outline-info radius-8" onclick="testConnection('gemini')">
                            <iconify-icon icon="solar:play-bold" class="me-1"></iconify-icon>
                            Testar Gemini
                        </button>
                    </div>
                </div>

                <!-- OpenAI Settings -->
                <div class="card radius-12 mb-4 provider-settings <?php echo($config['provider'] ?? '') === 'openai' ? 'active' : ''; ?>" id="openai-settings">
                    <div class="card-header border-bottom bg-base py-16 px-24">
                        <h6 class="text-lg fw-semibold mb-0">
                            <iconify-icon icon="simple-icons:openai" class="openai-color me-2"></iconify-icon>
                            Configuração do OpenAI
                        </h6>
                    </div>
                    <div class="card-body p-24">
                        <div class="mb-20">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                                API Key do OpenAI
                            </label>
                            <input type="password" class="form-control radius-8" name="openai_api_key" id="openai_api_key"
                                   value="<?php echo htmlspecialchars($config['openai_api_key'] ?? ''); ?>"
                                   placeholder="sk-...">
                            <small class="text-secondary-light">
                                Obtenha em: <a href="https://platform.openai.com/api-keys" target="_blank" class="text-primary">OpenAI Platform</a>
                            </small>
                        </div>
                        <div class="mb-20">
                            <label class="form-label fw-semibold text-primary-light text-sm mb-8">Modelo</label>
                            <select class="form-select radius-8" name="openai_modelo" id="openai_modelo">
                                <?php foreach ($openai_modelos as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo($config['openai_modelo'] ?? '') === $value ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                                <?php
endforeach; ?>
                            </select>
                        </div>
                        <button type="button" class="btn btn-outline-info radius-8" onclick="testConnection('openai')">
                            <iconify-icon icon="solar:play-bold" class="me-1"></iconify-icon>
                            Testar OpenAI
                        </button>
                    </div>
                </div>

                <!-- Ativo switch & Save -->
                <div class="card radius-12">
                    <div class="card-body p-24">
                        <div class="form-check form-switch mb-20">
                            <input class="form-check-input" type="checkbox" id="ativo" name="ativo" 
                                   <?php echo($config['ativo'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-medium" for="ativo">
                                Integração de IA Ativa
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary radius-8 px-20 py-11">
                            <iconify-icon icon="solar:diskette-bold" class="me-2"></iconify-icon>
                            Salvar Configurações
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card radius-12">
                    <div class="card-header border-bottom bg-base py-16 px-24">
                        <h6 class="text-lg fw-semibold mb-0">
                            <iconify-icon icon="solar:info-circle-bold" class="text-info me-2"></iconify-icon>
                            Comparativo
                        </h6>
                    </div>
                    <div class="card-body p-24">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="text-center">Gemini</th>
                                    <th class="text-center">OpenAI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Velocidade</td>
                                    <td class="text-center">⚡⚡⚡</td>
                                    <td class="text-center">⚡⚡</td>
                                </tr>
                                <tr>
                                    <td>Precisão</td>
                                    <td class="text-center">⭐⭐⭐</td>
                                    <td class="text-center">⭐⭐⭐⭐</td>
                                </tr>
                                <tr>
                                    <td>Custo</td>
                                    <td class="text-center">💰</td>
                                    <td class="text-center">💰💰</td>
                                </tr>
                                <tr>
                                    <td>Tier Grátis</td>
                                    <td class="text-center">✅</td>
                                    <td class="text-center">❌</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Test Modal -->
<div id="testResult" class="modal fade" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Teste de Conexão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="testResultBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-3">Testando conexão...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectProvider(provider) {
    document.getElementById('providerInput').value = provider;
    
    // Update cards
    document.querySelectorAll('.provider-card').forEach(card => card.classList.remove('active'));
    event.currentTarget.classList.add('active');
    
    // Show/hide settings
    document.querySelectorAll('.provider-settings').forEach(s => s.classList.remove('active'));
    document.getElementById(provider + '-settings').classList.add('active');
}

function testConnection(provider) {
    const modal = new bootstrap.Modal(document.getElementById('testResult'));
    modal.show();
    
    let apiKey, modelo, endpoint;
    
    if (provider === 'gemini') {
        apiKey = document.getElementById('gemini_api_key').value;
        modelo = document.getElementById('gemini_modelo').value;
        endpoint = '../api/gemini_test.php';
    } else {
        apiKey = document.getElementById('openai_api_key').value;
        modelo = document.getElementById('openai_modelo').value;
        endpoint = '../api/openai_test.php';
    }
    
    fetch(endpoint, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({api_key: apiKey, modelo: modelo})
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('testResultBody').innerHTML = data.success 
            ? `<div class="text-center py-4">
                   <iconify-icon icon="solar:check-circle-bold" style="font-size: 64px; color: #10b981;"></iconify-icon>
                   <h5 class="mt-3 text-success">Conexão OK!</h5>
                   <p class="text-muted">${data.message || 'API funcionando'}</p>
               </div>`
            : `<div class="text-center py-4">
                   <iconify-icon icon="solar:close-circle-bold" style="font-size: 64px; color: #ef4444;"></iconify-icon>
                   <h5 class="mt-3 text-danger">Erro</h5>
                   <p class="text-muted">${data.message || 'Verifique sua API Key'}</p>
               </div>`;
    })
    .catch(err => {
        document.getElementById('testResultBody').innerHTML = `
            <div class="text-center py-4">
                <iconify-icon icon="solar:danger-triangle-bold" style="font-size: 64px; color: #f59e0b;"></iconify-icon>
                <h5 class="mt-3 text-warning">Erro de rede</h5>
                <p class="text-muted">${err.message}</p>
            </div>`;
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
