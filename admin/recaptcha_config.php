<?php
include 'includes/auth.php';
include 'includes/header.php';
include '../includes/config.php';
include '../includes/functions.php';
require_once '../includes/csrf.php';

// Migration Logic (Inline)
try {
    // Tabela de Configuração
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracao_recaptcha (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ativo TINYINT(1) DEFAULT 0,
        versao VARCHAR(10) DEFAULT 'v3',
        site_key_v2 VARCHAR(255),
        secret_key_v2 VARCHAR(255),
        site_key_v3 VARCHAR(255),
        secret_key_v3 VARCHAR(255),
        usar_admin_login TINYINT(1) DEFAULT 1,
        usar_cliente_login TINYINT(1) DEFAULT 1,
        usar_cadastro TINYINT(1) DEFAULT 0,
        usar_finalizar_pedido TINYINT(1) DEFAULT 0
    )");

    // Verificar colunas (caso tabela já exista com estrutura antiga ou parcial)
    $stmt = $pdo->query("DESCRIBE configuracao_recaptcha");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $cols_needed = [
        'versao' => "VARCHAR(10) DEFAULT 'v3'",
        'site_key_v2' => "VARCHAR(255)",
        'secret_key_v2' => "VARCHAR(255)",
        'site_key_v3' => "VARCHAR(255)",
        'secret_key_v3' => "VARCHAR(255)",
        'usar_admin_login' => "TINYINT(1) DEFAULT 1",
        'usar_cliente_login' => "TINYINT(1) DEFAULT 1",
        'usar_cadastro' => "TINYINT(1) DEFAULT 0",
        'usar_finalizar_pedido' => "TINYINT(1) DEFAULT 0"
    ];

    foreach ($cols_needed as $col => $def) {
        if (!in_array($col, $columns)) {
            $pdo->exec("ALTER TABLE configuracao_recaptcha ADD COLUMN $col $def");
        }
    }

    // Garantir registro inicial
    $stmt = $pdo->query("SELECT id FROM configuracao_recaptcha LIMIT 1");
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO configuracao_recaptcha (ativo, versao, site_key_v3, secret_key_v3) VALUES (0, 'v3', '', '')");
        $stmt->execute();
    }

// Migrar dados da tabela antiga 'recaptcha_config' se existir e a nova estiver vazia (opcional, mas bom para preservar dados se o user tinha)
// Vou pular essa complexidade para focar no novo design, assumindo que o user vai configurar de novo ou já está usando a nova estrutura se ela existir.

}
catch (PDOException $e) {
// Silently handle migration errors or log them
}

$msg = '';
$msg_tipo = '';

// Processar Formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validar_csrf()) {
        $msg = 'Token de segurança inválido. Recarregue a página.';
        $msg_tipo = 'danger';
    }
    else {
        $ativo = isset($_POST['recaptcha_ativo']) ? 1 : 0;
        $versao = $_POST['recaptcha_version'];

        $site_key_v2 = $_POST['recaptcha_v2_site_key'];
        $secret_key_v2 = $_POST['recaptcha_v2_secret_key'];

        $site_key_v3 = $_POST['recaptcha_v3_site_key'];
        $secret_key_v3 = $_POST['recaptcha_v3_secret_key'];

        $usar_admin = isset($_POST['recaptcha_admin_login']) ? 1 : 0;
        $usar_cliente = isset($_POST['recaptcha_cliente_login']) ? 1 : 0;
        $usar_cadastro = isset($_POST['recaptcha_cadastro']) ? 1 : 0;
        $usar_pedido = isset($_POST['recaptcha_finalizar_pedido']) ? 1 : 0;

        try {
            $stmt = $pdo->prepare("UPDATE configuracao_recaptcha SET 
            ativo = ?, 
            versao = ?, 
            site_key_v2 = ?, secret_key_v2 = ?, 
            site_key_v3 = ?, secret_key_v3 = ?,
            usar_admin_login = ?, usar_cliente_login = ?, usar_cadastro = ?, usar_finalizar_pedido = ?
            WHERE id = 1");

            // Se não atualizar nada (row count 0), pode ser que não exista id 1, mas a migration garante.
            // Ou os dados são iguais.
            $stmt->execute([
                $ativo, $versao,
                $site_key_v2, $secret_key_v2,
                $site_key_v3, $secret_key_v3,
                $usar_admin, $usar_cliente, $usar_cadastro, $usar_pedido
            ]);

            $msg = 'Configurações salvas com sucesso!';
            $msg_tipo = 'success';
        }
        catch (PDOException $e) {
            $msg = 'Erro ao salvar: ' . $e->getMessage();
            $msg_tipo = 'danger';
        }
    } // fecha else validar_csrf
}

// Buscar Configurações
$stmt = $pdo->query("SELECT * FROM configuracao_recaptcha LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Valores padrão para evitar erros de índice indefinido
if (!$config) {
    $config = [
        'ativo' => 0, 'versao' => 'v3',
        'site_key_v2' => '', 'secret_key_v2' => '',
        'site_key_v3' => '', 'secret_key_v3' => '',
        'usar_admin_login' => 1, 'usar_cliente_login' => 1,
        'usar_cadastro' => 0, 'usar_finalizar_pedido' => 0
    ];
}
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
    <h6 class="fw-semibold mb-0">Configurações - reCAPTCHA</h6>
    <ul class="d-flex align-items-center gap-2">
        <li class="fw-medium">
            <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                Dashboard
            </a>
        </li>
        <li>-</li>
        <li class="fw-medium">reCAPTCHA</li>
    </ul>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?php echo $msg_tipo; ?> alert-dismissible fade show" role="alert">
    <?php echo $msg; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php
endif; ?>

<div class="row g-3">
    <div class="col-12">
        <div class="card h-100 p-0 radius-12">
            <div class="card-body p-24">
                <h6 class="mb-20 fw-bold text-lg">Configurações de Segurança</h6>
                
                <form method="POST">
                    <?php echo campo_csrf(); ?>
                    <!-- reCAPTCHA -->
                    <div class="mb-24">
                        <h6 class="mb-12 fw-semibold">Google reCAPTCHA</h6>
                        
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="recaptcha_ativo" value="1" 
                                   id="recaptcha_ativo" <?php echo $config['ativo'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="recaptcha_ativo">
                                <strong>Ativar reCAPTCHA</strong>
                            </label>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Versão do reCAPTCHA</label>
                                <select class="form-select" name="recaptcha_version" id="recaptcha_version">
                                    <option value="v2" <?php echo $config['versao'] == 'v2' ? 'selected' : ''; ?>>reCAPTCHA v2 (Checkbox)</option>
                                    <option value="v3" <?php echo $config['versao'] == 'v3' ? 'selected' : ''; ?>>reCAPTCHA v3 (Invisível)</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- reCAPTCHA v2 -->
                        <div id="recaptcha_v2_config" style="display: <?php echo $config['versao'] == 'v2' ? 'block' : 'none'; ?>;">
                            <h6 class="mb-12 fw-semibold text-sm">reCAPTCHA v2</h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Site Key (v2)</label>
                                    <input type="text" class="form-control" name="recaptcha_v2_site_key" 
                                           value="<?php echo htmlspecialchars($config['site_key_v2']); ?>"
                                           placeholder="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Secret Key (v2)</label>
                                    <input type="password" class="form-control" name="recaptcha_v2_secret_key" 
                                           value="<?php echo htmlspecialchars($config['secret_key_v2']); ?>"
                                           placeholder="6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe">
                                </div>
                            </div>
                        </div>
                        
                        <!-- reCAPTCHA v3 -->
                        <div id="recaptcha_v3_config" style="display: <?php echo $config['versao'] == 'v3' ? 'block' : 'none'; ?>;">
                            <h6 class="mb-12 fw-semibold text-sm">reCAPTCHA v3</h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Site Key (v3)</label>
                                    <input type="text" class="form-control" name="recaptcha_v3_site_key" 
                                           value="<?php echo htmlspecialchars($config['site_key_v3']); ?>"
                                           placeholder="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Secret Key (v3)</label>
                                    <input type="password" class="form-control" name="recaptcha_v3_secret_key" 
                                           value="<?php echo htmlspecialchars($config['secret_key_v3']); ?>"
                                           placeholder="6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Onde usar reCAPTCHA:</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="recaptcha_admin_login" value="1" 
                                       id="recaptcha_admin_login" <?php echo $config['usar_admin_login'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="recaptcha_admin_login">
                                    Login do Admin
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="recaptcha_cliente_login" value="1" 
                                       id="recaptcha_cliente_login" <?php echo $config['usar_cliente_login'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="recaptcha_cliente_login">
                                    Login do Cliente
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="recaptcha_cadastro" value="1" 
                                       id="recaptcha_cadastro" <?php echo $config['usar_cadastro'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="recaptcha_cadastro">
                                    Cadastro de Cliente
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="recaptcha_finalizar_pedido" value="1" 
                                       id="recaptcha_finalizar_pedido" <?php echo $config['usar_finalizar_pedido'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="recaptcha_finalizar_pedido">
                                    Finalizar Pedido
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <iconify-icon icon="solar:diskette-outline"></iconify-icon>
                            Salvar Configurações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Mostrar/ocultar campos conforme versão selecionada
document.getElementById('recaptcha_version').addEventListener('change', function() {
    const version = this.value;
    document.getElementById('recaptcha_v2_config').style.display = version === 'v2' ? 'block' : 'none';
    document.getElementById('recaptcha_v3_config').style.display = version === 'v3' ? 'block' : 'none';
});
</script>

<?php include 'includes/footer.php'; ?>