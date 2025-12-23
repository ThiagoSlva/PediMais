<?php
include 'includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';
verificar_login();

$config = get_config();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $impressao_automatica = isset($_POST['impressao_automatica']) ? 1 : 0;
    
    // Atualizar configuração
    $pdo->prepare("UPDATE configuracoes SET impressao_automatica = ?")->execute([$impressao_automatica]);
    
    // Gerar novo token se solicitado
    if (isset($_POST['gerar_novo_token'])) {
        $novo_token = bin2hex(random_bytes(32));
        $pdo->prepare("UPDATE usuarios SET api_token = ? WHERE nivel_acesso = 'admin' LIMIT 1")->execute([$novo_token]);
    }
    
    header('Location: impressora.php?salvo=1');
    exit;
}

// Buscar token atual
$stmt = $pdo->query("SELECT api_token FROM usuarios WHERE nivel_acesso = 'admin' AND api_token IS NOT NULL LIMIT 1");
$token_row = $stmt->fetch();
$api_token = $token_row['api_token'] ?? '';

// Se não tem token, gerar
if (empty($api_token)) {
    $api_token = bin2hex(random_bytes(32));
    $pdo->prepare("UPDATE usuarios SET api_token = ? WHERE nivel_acesso = 'admin' LIMIT 1")->execute([$api_token]);
}

include 'includes/header.php';
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">🖨️ Configurações de Impressão</h6>
    </div>

    <?php if (isset($_GET['salvo'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Sucesso!</strong> Configurações salvas com sucesso.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <!-- Token API -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:key-outline"></iconify-icon>
                        Token de API para Desktop Printer
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <strong>📱 Use este token no aplicativo PedeMais Printer</strong><br>
                        Cole ele na configuração do app desktop para conectar automaticamente.
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Seu Token de API:</label>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace" 
                                   id="api_token" value="<?php echo htmlspecialchars($api_token); ?>" 
                                   readonly style="font-size: 12px;">
                            <button class="btn btn-outline-primary" type="button" onclick="copiarToken()">
                                <iconify-icon icon="solar:copy-outline"></iconify-icon> Copiar
                            </button>
                        </div>
                    </div>
                    
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="gerar_novo_token" value="1">
                        <button type="submit" class="btn btn-warning" 
                                onclick="return confirm('Gerar novo token? O token antigo deixará de funcionar!')">
                            <iconify-icon icon="solar:refresh-outline"></iconify-icon> Gerar Novo Token
                        </button>
                    </form>
                </div>
            </div>

            <!-- Impressão Automática Web -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:printer-outline"></iconify-icon>
                        Impressão Automática (Web)
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="alert alert-secondary mb-4">
                            <strong>💻 Como funciona:</strong> Quando ativado, ao receber novo pedido 
                            no painel admin, abrirá automaticamente uma janela de impressão.
                        </div>
                        
                        <div class="form-check form-switch mb-4" style="padding-left: 3rem;">
                            <input class="form-check-input" type="checkbox" name="impressao_automatica" 
                                   id="impressao_automatica" style="width: 50px; height: 26px;"
                                   <?php echo ($config['impressao_automatica'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-semibold" for="impressao_automatica">
                                Ativar Impressão Automática via Navegador
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            <iconify-icon icon="solar:check-circle-outline"></iconify-icon> Salvar
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Instruções -->
            <div class="card">
                <div class="card-header bg-primary-100">
                    <h6 class="mb-0 fw-semibold text-primary-600">
                        <iconify-icon icon="solar:info-circle-outline"></iconify-icon>
                        Como Usar
                    </h6>
                </div>
                <div class="card-body">
                    <h6 class="fw-semibold mb-2">📦 App Desktop (Recomendado)</h6>
                    <ol class="small mb-4">
                        <li>Baixe o PedeMais Printer</li>
                        <li>Cole a URL do site e o Token</li>
                        <li>Selecione sua impressora</li>
                        <li>Clique em Conectar</li>
                    </ol>
                    
                    <h6 class="fw-semibold mb-2">🌐 Impressão Web</h6>
                    <ol class="small mb-4">
                        <li>Ative a opção ao lado</li>
                        <li>Mantenha o painel admin aberto</li>
                        <li>Permita pop-ups no navegador</li>
                    </ol>
                    
                    <div class="alert alert-warning small mb-0">
                        <strong>Dica:</strong> O app desktop é mais confiável e não precisa manter o navegador aberto!
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copiarToken() {
    const input = document.getElementById('api_token');
    input.select();
    document.execCommand('copy');
    
    // Feedback visual
    const btn = event.target.closest('button');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<iconify-icon icon="solar:check-circle-outline"></iconify-icon> Copiado!';
    btn.classList.remove('btn-outline-primary');
    btn.classList.add('btn-success');
    
    setTimeout(() => {
        btn.innerHTML = originalHTML;
        btn.classList.remove('btn-success');
        btn.classList.add('btn-outline-primary');
    }, 2000);
}
</script>

<?php include 'includes/footer.php'; ?>
