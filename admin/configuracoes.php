<?php
include 'includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';
include '../includes/image_optimization.php';

verificar_login();

$msg = '';
$msg_tipo = '';

// --- MIGRATION LOGIC ---
try {
    // Check if table exists, if not create it (basic structure)
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome_site VARCHAR(255),
        descricao_site TEXT,
        logo VARCHAR(255),
        favicon VARCHAR(255),
        capa VARCHAR(255),
        cep VARCHAR(20),
        rua VARCHAR(255),
        numero VARCHAR(50),
        complemento VARCHAR(255),
        bairro VARCHAR(100),
        cidade VARCHAR(100),
        estado VARCHAR(2),
        whatsapp VARCHAR(20),
        email_contato VARCHAR(255),
        facebook VARCHAR(255),
        instagram VARCHAR(255),
        tema VARCHAR(50) DEFAULT 'roxo',
        cor_principal VARCHAR(20),
        cor_secundaria VARCHAR(20)
    )");

    // Ensure ID 1 exists
    $stmt = $pdo->query("SELECT id FROM configuracoes WHERE id = 1");
    if (!$stmt->fetch()) {
        $pdo->exec("INSERT INTO configuracoes (id, nome_site, tema) VALUES (1, 'CardapiX', 'roxo')");
    }

    // Add missing columns if any (safe migration)
    $columns_needed = [
        'descricao_site' => 'TEXT',
        'logo' => 'VARCHAR(255)',
        'favicon' => 'VARCHAR(255)',
        'capa' => 'VARCHAR(255)',
        'cep' => 'VARCHAR(20)',
        'rua' => 'VARCHAR(255)',
        'numero' => 'VARCHAR(50)',
        'complemento' => 'VARCHAR(255)',
        'bairro' => 'VARCHAR(100)',
        'cidade' => 'VARCHAR(100)',
        'estado' => 'VARCHAR(2)',
        'whatsapp' => 'VARCHAR(20)',
        'email_contato' => 'VARCHAR(255)',
        'facebook' => 'VARCHAR(255)',
        'instagram' => 'VARCHAR(255)',
        'tema' => "VARCHAR(50) DEFAULT 'roxo'",
        'cor_principal' => 'VARCHAR(20)',
        'cor_secundaria' => 'VARCHAR(20)',
        'tema_layout' => "VARCHAR(50) DEFAULT 'default'",
        'impressao_automatica' => 'TINYINT(1) DEFAULT 0'
    ];

    $stmt = $pdo->query("DESCRIBE configuracoes");
    $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($columns_needed as $col => $type) {
        if (!in_array($col, $existing_columns)) {
            $pdo->exec("ALTER TABLE configuracoes ADD COLUMN $col $type");
        }
    }

} catch (PDOException $e) {
    // Log error or ignore if just checking
}
// -----------------------

// Processar Formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar_config'])) {
    try {
        // Campos de Texto
        $nome_site = $_POST['site_titulo'];
        $descricao_site = $_POST['site_descricao'];
        $cep = $_POST['endereco_cep'];
        $rua = $_POST['endereco_rua'];
        $numero = $_POST['endereco_numero'];
        $complemento = $_POST['endereco_complemento'];
        $bairro = $_POST['endereco_bairro'];
        $cidade = $_POST['endereco_cidade'];
        $estado = $_POST['endereco_estado'];
        $whatsapp = $_POST['contato_whatsapp'];
        $email_contato = $_POST['contato_email'];
        $facebook = $_POST['social_facebook'];
        $instagram = $_POST['social_instagram'];
        $tema = $_POST['cardapio_tema'];
        $cor_principal = $_POST['cor_principal_custom'];
        $cor_secundaria = $_POST['cor_secundaria_custom'];
        $tema_layout = $_POST['tema_layout'] ?? 'default';
        $impressao_automatica = isset($_POST['impressao_automatica']) ? 1 : 0;

        // Uploads - save to root uploads directory so sidebar and all pages can find it
        $upload_dir = dirname(__DIR__) . '/uploads/config/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $updates = [];
        $params = [
            $nome_site, $descricao_site, 
            $cep, $rua, $numero, $complemento, $bairro, $cidade, $estado,
            $whatsapp, $email_contato, $facebook, $instagram,
            $tema, $cor_principal, $cor_secundaria, $tema_layout, $impressao_automatica
        ];

        $sql = "UPDATE configuracoes SET 
            nome_site = ?, descricao_site = ?, 
            cep = ?, rua = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?, estado = ?,
            whatsapp = ?, email_contato = ?, facebook = ?, instagram = ?,
            tema = ?, cor_principal = ?, cor_secundaria = ?, tema_layout = ?, impressao_automatica = ?";

        // Handle Files
        if (!empty($_FILES['site_logo']['name'])) {
            $file_base = $upload_dir . 'logo';
            $compress_result = compressAndOptimizeImage($_FILES['site_logo']['tmp_name'], $file_base, 80, 400, 400);
            
            if ($compress_result['success']) {
                $filename = basename($compress_result['file']);
                $sql .= ", logo = ?";
                $params[] = $filename;
            }
        }

        if (!empty($_FILES['site_favicon']['name'])) {
            $file_base = $upload_dir . 'favicon';
            $compress_result = compressAndOptimizeImage($_FILES['site_favicon']['tmp_name'], $file_base, 80, 128, 128);
            
            if ($compress_result['success']) {
                $filename = basename($compress_result['file']);
                $sql .= ", favicon = ?";
                $params[] = $filename;
            }
        }

        if (!empty($_FILES['site_capa']['name'])) {
            $file_base = $upload_dir . 'capa';
            $compress_result = compressAndOptimizeImage($_FILES['site_capa']['tmp_name'], $file_base, 75, 1920, 600);
            
            if ($compress_result['success']) {
                $filename = basename($compress_result['file']);
                $sql .= ", capa = ?";
                $params[] = $filename;
            }
        }

        $sql .= " WHERE id = 1";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $msg = 'Configurações salvas com sucesso!';
        $msg_tipo = 'success';

    } catch (Exception $e) {
        $msg = 'Erro ao salvar configurações: ' . $e->getMessage();
        $msg_tipo = 'danger';
    }
}

// Buscar Configurações Atuais
$stmt = $pdo->query("SELECT * FROM configuracoes WHERE id = 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Default values if empty
if (!$config) $config = [];
$config['nome_site'] = $config['nome_site'] ?? 'CardapiX';
$config['tema'] = $config['tema'] ?? 'roxo';
$config['logo'] = $config['logo'] ?? 'logo.png';
$config['favicon'] = $config['favicon'] ?? 'favicon.ico';
$config['capa'] = $config['capa'] ?? 'capa.jpg';

include 'includes/header.php';
?>

<style>
/* Dark mode support for configuracoes page */
[data-theme="dark"] .card,
html[data-theme="dark"] .card {
    background-color: var(--base-soft, #1e1e2e) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

/* Estilos MUITO específicos para card-header - forçar background escuro */
[data-theme="dark"] div.card-header,
[data-theme="dark"] .card > .card-header,
[data-theme="dark"] .card .card-header,
[data-theme="dark"] .card-header.bg-base,
[data-theme="dark"] .card-header.border-bottom,
[data-theme="dark"] .card-header:not([class*="bg-"]),
[data-theme="dark"] .card .card-header:not([class*="bg-"]),
html[data-theme="dark"] div.card-header,
html[data-theme="dark"] .card > .card-header,
html[data-theme="dark"] .card .card-header,
html[data-theme="dark"] .card-header.bg-base,
html[data-theme="dark"] .card-header.border-bottom,
html[data-theme="dark"] .card-header:not([class*="bg-"]),
html[data-theme="dark"] .card .card-header:not([class*="bg-"]) {
    background-color: #1a1a2e !important;
    background: #1a1a2e !important;
    background-image: none !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

/* Forçar background escuro mesmo com classes do Bootstrap */
[data-theme="dark"] .card-header.bg-white,
[data-theme="dark"] .card-header.bg-light,
[data-theme="dark"] .card-header.bg-base-50,
html[data-theme="dark"] .card-header.bg-white,
html[data-theme="dark"] .card-header.bg-light,
html[data-theme="dark"] .card-header.bg-base-50 {
    background-color: #1a1a2e !important;
    background: #1a1a2e !important;
    background-image: none !important;
}

/* Forçar texto branco em todos os elementos do header */
[data-theme="dark"] .card-header *,
[data-theme="dark"] .card-header h6,
[data-theme="dark"] .card-header .fw-semibold,
[data-theme="dark"] .card-header .mb-0,
[data-theme="dark"] .card-header iconify-icon,
html[data-theme="dark"] .card-header *,
html[data-theme="dark"] .card-header h6,
html[data-theme="dark"] .card-header .fw-semibold,
html[data-theme="dark"] .card-header .mb-0,
html[data-theme="dark"] .card-header iconify-icon {
    color: rgba(255, 255, 255, 0.9) !important;
    background-color: transparent !important;
    background: transparent !important;
}

/* Sobrescrever qualquer estilo inline ou classe que force background branco */
[data-theme="dark"] .card-header[style*="background"],
[data-theme="dark"] .card-header[style*="background-color"],
html[data-theme="dark"] .card-header[style*="background"],
html[data-theme="dark"] .card-header[style*="background-color"] {
    background-color: #1a1a2e !important;
    background: #1a1a2e !important;
}

/* Estilo universal para garantir que TODOS os card-headers fiquem escuros */
html[data-theme="dark"] .card-header {
    background: #1a1a2e !important;
    background-color: #1a1a2e !important;
    background-image: none !important;
}

html[data-theme="dark"] .card-header h6,
html[data-theme="dark"] .card-header .mb-0,
html[data-theme="dark"] .card-header .fw-semibold {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .card-body,
html[data-theme="dark"] .card-body {
    background-color: var(--base-soft, #1e1e2e) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .form-control,
[data-theme="dark"] .form-select,
[data-theme="dark"] input[type="text"],
[data-theme="dark"] input[type="email"],
[data-theme="dark"] input[type="color"],
html[data-theme="dark"] .form-control,
html[data-theme="dark"] .form-select,
html[data-theme="dark"] input[type="text"],
html[data-theme="dark"] input[type="email"],
html[data-theme="dark"] input[type="color"] {
    background-color: rgba(255, 255, 255, 0.05) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .form-control:focus,
[data-theme="dark"] .form-select:focus,
html[data-theme="dark"] .form-control:focus,
html[data-theme="dark"] .form-select:focus {
    background-color: rgba(255, 255, 255, 0.08) !important;
    border-color: #487FFF !important;
    color: rgba(255, 255, 255, 0.9) !important;
    box-shadow: 0 0 0 0.2rem rgba(72, 127, 255, 0.25);
}

[data-theme="dark"] .form-control::placeholder,
html[data-theme="dark"] .form-control::placeholder {
    color: rgba(255, 255, 255, 0.5) !important;
    opacity: 0.6;
}

[data-theme="dark"] .form-label,
html[data-theme="dark"] .form-label {
    color: rgba(255, 255, 255, 0.9) !important;
    font-weight: 600;
}

[data-theme="dark"] .input-group-text,
html[data-theme="dark"] .input-group-text {
    background-color: rgba(255, 255, 255, 0.1) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .upload-zone,
[data-theme="dark"] .upload-zone-small,
html[data-theme="dark"] .upload-zone,
html[data-theme="dark"] .upload-zone-small {
    background-color: rgba(255, 255, 255, 0.03) !important;
    border-color: rgba(255, 255, 255, 0.2) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .upload-zone:hover,
[data-theme="dark"] .upload-zone-small:hover,
html[data-theme="dark"] .upload-zone:hover,
html[data-theme="dark"] .upload-zone-small:hover {
    background-color: rgba(255, 255, 255, 0.05) !important;
    border-color: rgba(102, 126, 234, 0.5) !important;
}

[data-theme="dark"] .upload-zone.dragover,
[data-theme="dark"] .upload-zone-small.dragover,
html[data-theme="dark"] .upload-zone.dragover,
html[data-theme="dark"] .upload-zone-small.dragover {
    background-color: rgba(102, 126, 234, 0.1) !important;
    border-color: #667eea !important;
}

[data-theme="dark"] .text-secondary-light,
[data-theme="dark"] small,
html[data-theme="dark"] .text-secondary-light,
html[data-theme="dark"] small {
    color: rgba(255, 255, 255, 0.6) !important;
}

[data-theme="dark"] .color-option-card strong,
html[data-theme="dark"] .color-option-card strong {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .alert-info,
html[data-theme="dark"] .alert-info {
    background-color: rgba(13, 110, 253, 0.15) !important;
    border-color: rgba(13, 110, 253, 0.3) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .loading-content,
html[data-theme="dark"] .loading-content {
    background-color: var(--base-soft, #1e1e2e) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .thumbnail-preview,
html[data-theme="dark"] .thumbnail-preview {
    background-color: rgba(255, 255, 255, 0.05) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

[data-theme="dark"] .img-thumbnail,
html[data-theme="dark"] .img-thumbnail {
    background-color: rgba(255, 255, 255, 0.05) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

[data-theme="dark"] .config-steps::before,
html[data-theme="dark"] .config-steps::before {
    background: rgba(255, 255, 255, 0.1) !important;
}

[data-theme="dark"] .step-circle,
html[data-theme="dark"] .step-circle {
    background: rgba(255, 255, 255, 0.1) !important;
    color: rgba(255, 255, 255, 0.7) !important;
}

[data-theme="dark"] .step-label,
html[data-theme="dark"] .step-label {
    color: rgba(255, 255, 255, 0.6) !important;
}

[data-theme="dark"] .step-item.active .step-label,
html[data-theme="dark"] .step-item.active .step-label {
    color: rgba(102, 126, 234, 0.9) !important;
}

[data-theme="dark"] .color-preview,
html[data-theme="dark"] .color-preview {
    border-color: rgba(255, 255, 255, 0.2) !important;
}

[data-theme="dark"] .color-preview[style*="border: 3px solid #333"],
[data-theme="dark"] .color-preview[style*="border: 3px solid"],
html[data-theme="dark"] .color-preview[style*="border: 3px solid #333"],
html[data-theme="dark"] .color-preview[style*="border: 3px solid"] {
    border-color: rgba(255, 255, 255, 0.5) !important;
}

[data-theme="dark"] .color-preview[style*="box-shadow"],
html[data-theme="dark"] .color-preview[style*="box-shadow"] {
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.3), 0 0 0 4px rgba(102, 126, 234, 0.5) !important;
}

[data-theme="dark"] .color-option-card strong[style*="color: #333"],
html[data-theme="dark"] .color-option-card strong[style*="color: #333"] {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .btn-success,
html[data-theme="dark"] .btn-success {
    background-color: #10b981 !important;
    border-color: #10b981 !important;
}

[data-theme="dark"] .btn-success:hover,
html[data-theme="dark"] .btn-success:hover {
    background-color: #059669 !important;
    border-color: #059669 !important;
}

[data-theme="dark"] .alert,
html[data-theme="dark"] .alert {
    border-color: rgba(255, 255, 255, 0.1) !important;
}

[data-theme="dark"] .alert-success,
html[data-theme="dark"] .alert-success {
    background-color: rgba(16, 185, 129, 0.15) !important;
    border-color: rgba(16, 185, 129, 0.3) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .alert-danger,
html[data-theme="dark"] .alert-danger {
    background-color: rgba(239, 68, 68, 0.15) !important;
    border-color: rgba(239, 68, 68, 0.3) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .alert-warning,
html[data-theme="dark"] .alert-warning {
    background-color: rgba(245, 158, 11, 0.15) !important;
    border-color: rgba(245, 158, 11, 0.3) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .btn-close,
html[data-theme="dark"] .btn-close {
    filter: invert(1);
    opacity: 0.7;
}

[data-theme="dark"] .btn-close:hover,
html[data-theme="dark"] .btn-close:hover {
    opacity: 1;
}

.config-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
    position: relative;
}

.config-steps::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 0;
    right: 0;
    height: 2px;
    background: #e5e7eb;
    z-index: 0;
}

.step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    position: relative;
    z-index: 1;
}

.step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e5e7eb;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-bottom: 8px;
    transition: all 0.3s;
}

.step-item.active .step-circle {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.step-item.completed .step-circle {
    background: #10b981;
    color: white;
}

.step-label {
    font-size: 12px;
    color: #6b7280;
    text-align: center;
}

.step-item.active .step-label {
    color: #667eea;
    font-weight: 600;
}

.step-content {
    display: none;
}

.step-content.active {
    display: block;
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.upload-preview {
    margin-top: 15px;
    text-align: center;
}

.upload-preview img {
    max-width: 200px;
    max-height: 200px;
    border-radius: 8px;
    border: 2px dashed #e5e7eb;
    padding: 10px;
}

.loading-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.loading-overlay.active {
    display: flex;
}

.loading-content {
    background: white;
    padding: 30px;
    border-radius: 12px;
    text-align: center;
    min-width: 300px;
}

.spinner {
    border: 4px solid #f3f4f6;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
    margin: 0 auto 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.upload-zone {
    border: 2px dashed #e5e7eb;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.upload-zone:hover {
    border-color: #667eea;
    background: #f9fafb;
}

.upload-zone.dragover {
    border-color: #667eea;
    background: #eef2ff;
}

.upload-zone-small {
    border: 2px dashed #e5e7eb;
    border-radius: 8px;
    padding: 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 0.85rem;
}

.upload-zone-small:hover {
    border-color: #667eea;
    background: #f9fafb;
}

.thumbnail-preview img {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.color-option-card {
    display: block;
    transition: transform 0.2s;
}
.color-option-card:hover {
    transform: translateY(-4px);
}
.color-option-card input[type="radio"] {
    display: none;
}
.color-option-card.selected .color-preview {
    border-color: #333 !important;
    box-shadow: 0 0 0 2px white, 0 0 0 4px #333 !important;
}
</style>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Configurações do Site</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Configurações</li>
        </ul>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_tipo; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <h5>Processando...</h5>
            <p class="text-secondary-light mb-0" id="loadingText">Salvando configurações</p>
        </div>
    </div>

    <!-- Formulário -->
    <form method="POST" enctype="multipart/form-data" id="configForm">
        <?php 
        // Generate CSRF token if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        ?>
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <input type="hidden" name="salvar_config" value="1">
        
        <!-- Informações Básicas -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:document-text-outline"></iconify-icon>
                    Informações Básicas
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Título do Site *</label>
                        <input type="text" name="site_titulo" class="form-control" 
                               value="<?php echo htmlspecialchars($config['nome_site']); ?>" 
                               required placeholder="Ex: Restaurante Sabor & Arte">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" name="site_descricao" class="form-control" 
                               value="<?php echo htmlspecialchars($config['descricao_site'] ?? ''); ?>" 
                               placeholder="Ex: Os melhores pratos da região">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Imagens -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:gallery-outline"></iconify-icon>
                    Imagens do Site
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Logo -->
                    <div class="col-md-4 mb-4">
                        <label class="form-label fw-semibold">Logo do Site</label>
                        <div class="d-flex align-items-start gap-3">
                            <div class="thumbnail-preview">
                                <img src="../uploads/config/<?php echo htmlspecialchars($config['logo']); ?>" alt="Logo" class="img-thumbnail" style="max-width: 80px; max-height: 80px; object-fit: contain;" onerror="this.style.display='none'">
                            </div>
                            <div class="flex-grow-1">
                                <div class="upload-zone-small" onclick="document.getElementById('logo-input').click()">
                                    <iconify-icon icon="solar:cloud-upload-outline" class="text-xl"></iconify-icon>
                                    <small class="d-block mt-1">Clique para upload</small>
                                    <small class="text-secondary-light">PNG, JPG ou SVG (máx 2MB)</small>
                                </div>
                                <input type="file" id="logo-input" name="site_logo" accept="image/*" style="display: none;" onchange="previewImage(this, 'logo-preview')">
                                <div class="upload-preview" id="logo-preview"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Favicon -->
                    <div class="col-md-4 mb-4">
                        <label class="form-label fw-semibold">Favicon</label>
                        <div class="d-flex align-items-start gap-3">
                            <div class="thumbnail-preview">
                                <img src="../uploads/config/<?php echo htmlspecialchars($config['favicon']); ?>" alt="Favicon" class="img-thumbnail" style="max-width: 64px; max-height: 64px; object-fit: contain;" onerror="this.style.display='none'">
                            </div>
                            <div class="flex-grow-1">
                                <div class="upload-zone-small" onclick="document.getElementById('favicon-input').click()">
                                    <iconify-icon icon="solar:cloud-upload-outline" class="text-xl"></iconify-icon>
                                    <small class="d-block mt-1">Clique para upload</small>
                                    <small class="text-secondary-light">ICO ou PNG 32x32</small>
                                </div>
                                <input type="file" id="favicon-input" name="site_favicon" accept="image/*" style="display: none;" onchange="previewImage(this, 'favicon-preview')">
                                <div class="upload-preview" id="favicon-preview"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Capa -->
                    <div class="col-md-4 mb-4">
                        <label class="form-label fw-semibold">Imagem de Capa</label>
                        <div class="d-flex align-items-start gap-3">
                            <div class="thumbnail-preview">
                                <img src="../uploads/config/<?php echo htmlspecialchars($config['capa']); ?>" alt="Capa" class="img-thumbnail" style="max-width: 80px; max-height: 80px; object-fit: cover;" onerror="this.style.display='none'">
                            </div>
                            <div class="flex-grow-1">
                                <div class="upload-zone-small" onclick="document.getElementById('capa-input').click()">
                                    <iconify-icon icon="solar:cloud-upload-outline" class="text-xl"></iconify-icon>
                                    <small class="d-block mt-1">Clique para upload</small>
                                    <small class="text-secondary-light">JPG ou PNG (máx 5MB)</small>
                                </div>
                                <input type="file" id="capa-input" name="site_capa" accept="image/*" style="display: none;" onchange="previewImage(this, 'capa-preview')">
                                <div class="upload-preview" id="capa-preview"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Endereço -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:map-point-outline"></iconify-icon>
                    Endereço Completo
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">CEP</label>
                        <input type="text" name="endereco_cep" id="cep" class="form-control" 
                               value="<?php echo htmlspecialchars($config['cep'] ?? ''); ?>" 
                               placeholder="00000-000" maxlength="9">
                        <small class="text-secondary-light">Busca automática</small>
                    </div>
                    
                    <div class="col-md-7 mb-3">
                        <label class="form-label">Rua/Avenida</label>
                        <input type="text" name="endereco_rua" id="rua" class="form-control" 
                               value="<?php echo htmlspecialchars($config['rua'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-md-2 mb-3">
                        <label class="form-label">Número</label>
                        <input type="text" name="endereco_numero" id="numero" class="form-control" 
                               value="<?php echo htmlspecialchars($config['numero'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Complemento</label>
                        <input type="text" name="endereco_complemento" id="complemento" class="form-control" 
                               value="<?php echo htmlspecialchars($config['complemento'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Bairro</label>
                        <input type="text" name="endereco_bairro" id="bairro" class="form-control" 
                               value="<?php echo htmlspecialchars($config['bairro'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Cidade</label>
                        <input type="text" name="endereco_cidade" id="cidade" class="form-control" 
                               value="<?php echo htmlspecialchars($config['cidade'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-md-1 mb-3">
                        <label class="form-label">UF</label>
                        <input type="text" name="endereco_estado" id="estado" class="form-control" 
                               value="<?php echo htmlspecialchars($config['estado'] ?? ''); ?>" 
                               maxlength="2">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contato & Redes -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:chat-dots-outline"></iconify-icon>
                    Contato & Redes Sociais
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label d-flex align-items-center gap-2">
                            <iconify-icon icon="solar:whatsapp-outline" class="text-success text-lg"></iconify-icon>
                            WhatsApp
                        </label>
                        <input type="text" name="contato_whatsapp" id="whatsapp" class="form-control" 
                               value="<?php echo htmlspecialchars($config['whatsapp'] ?? ''); ?>" 
                               placeholder="(11) 99999-9999">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label d-flex align-items-center gap-2">
                            <iconify-icon icon="solar:letter-outline" class="text-primary text-lg"></iconify-icon>
                            E-mail
                        </label>
                        <input type="email" name="contato_email" class="form-control" 
                               value="<?php echo htmlspecialchars($config['email_contato'] ?? ''); ?>" 
                               placeholder="contato@restaurante.com">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label d-flex align-items-center gap-2">
                            <iconify-icon icon="solar:facebook-outline" class="text-info text-lg"></iconify-icon>
                            Facebook
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">facebook.com/</span>
                            <input type="text" name="social_facebook" class="form-control" 
                                   value="<?php echo htmlspecialchars($config['facebook'] ?? ''); ?>" 
                                   placeholder="seu.restaurante">
                        </div>
                        <small class="text-secondary-light">Informe apenas o usuário, sem https:// ou @.</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label d-flex align-items-center gap-2">
                            <iconify-icon icon="solar:instagram-outline" class="text-danger text-lg"></iconify-icon>
                            Instagram
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">@</span>
                            <input type="text" name="social_instagram" class="form-control" 
                                   value="<?php echo htmlspecialchars($config['instagram'] ?? ''); ?>" 
                                   placeholder="seu.restaurante">
                        </div>
                        <small class="text-secondary-light">Informe apenas o usuário do Instagram, sem adicionar o link completo.</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Layout do Cardápio (NOVO) -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:layers-outline"></iconify-icon>
                    Layout do Cardápio
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-4 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:info-circle-outline"></iconify-icon>
                    Escolha o estilo visual completo do seu cardápio digital. Cada tema muda completamente a aparência da página de vendas.
                </div>
                
                <div class="row g-4">
                    <?php $tema_layout_atual = $config['tema_layout'] ?? 'default'; ?>
                    
                    <!-- Tema Default (Dark) -->
                    <div class="col-md-6 col-lg-4">
                        <label class="theme-layout-card <?php echo $tema_layout_atual === 'default' ? 'selected' : ''; ?>" style="cursor: pointer; display: block;">
                            <input type="radio" name="tema_layout" value="default" 
                                   <?php echo $tema_layout_atual === 'default' ? 'checked' : ''; ?>
                                   style="display: none;" onchange="handleLayoutChange(this);">
                            <div class="theme-preview" style="
                                background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
                                height: 180px;
                                border-radius: 16px;
                                padding: 15px;
                                position: relative;
                                overflow: hidden;
                                border: 3px solid <?php echo $tema_layout_atual === 'default' ? '#4a66f9' : 'transparent'; ?>;
                                transition: all 0.3s ease;
                            ">
                                <!-- Mini preview elements -->
                                <div style="background: linear-gradient(135deg, #667eea, #764ba2); height: 50px; border-radius: 0 0 15px 15px; margin: -15px -15px 10px -15px;"></div>
                                <div style="display: flex; gap: 8px; margin-bottom: 10px;">
                                    <div style="width: 35px; height: 35px; background: #2d3446; border-radius: 50%;"></div>
                                    <div style="width: 35px; height: 35px; background: #2d3446; border-radius: 50%;"></div>
                                    <div style="width: 35px; height: 35px; background: #2d3446; border-radius: 50%;"></div>
                                </div>
                                <div style="background: #2d3446; height: 25px; border-radius: 8px; margin-bottom: 8px;"></div>
                                <div style="background: #2d3446; height: 25px; border-radius: 8px; width: 70%;"></div>
                                
                                <?php if ($tema_layout_atual === 'default'): ?>
                                <div style="position: absolute; top: 10px; right: 10px; background: #4a66f9; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">✓</div>
                                <?php endif; ?>
                            </div>
                            <div class="text-center mt-3">
                                <strong style="font-size: 1rem;">🌙 Padrão (Dark)</strong>
                                <p class="text-secondary-light mb-0" style="font-size: 0.85rem;">Tema escuro elegante</p>
                            </div>
                        </label>
                    </div>
                    
                    <!-- Tema Shioki (Light/Cream) -->
                    <div class="col-md-6 col-lg-4">
                        <label class="theme-layout-card <?php echo $tema_layout_atual === 'shioki' ? 'selected' : ''; ?>" style="cursor: pointer; display: block;">
                            <input type="radio" name="tema_layout" value="shioki" 
                                   <?php echo $tema_layout_atual === 'shioki' ? 'checked' : ''; ?>
                                   style="display: none;" onchange="handleLayoutChange(this);">
                            <div class="theme-preview" style="
                                background: linear-gradient(135deg, #FFF8F0 0%, #FFF5E6 100%);
                                height: 180px;
                                border-radius: 16px;
                                padding: 15px;
                                position: relative;
                                overflow: hidden;
                                border: 3px solid <?php echo $tema_layout_atual === 'shioki' ? '#E76F51' : 'transparent'; ?>;
                                transition: all 0.3s ease;
                            ">
                                <!-- Mini preview elements -->
                                <div style="background: linear-gradient(135deg, #E76F51, #F4A261); height: 50px; border-radius: 0 0 15px 15px; margin: -15px -15px 10px -15px;"></div>
                                <div style="display: flex; gap: 8px; margin-bottom: 10px;">
                                    <div style="width: 35px; height: 35px; background: #fff; border-radius: 50%; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></div>
                                    <div style="width: 35px; height: 35px; background: #fff; border-radius: 50%; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></div>
                                    <div style="width: 35px; height: 35px; background: #fff; border-radius: 50%; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></div>
                                </div>
                                <div style="background: #fff; height: 25px; border-radius: 8px; margin-bottom: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);"></div>
                                <div style="background: #fff; height: 25px; border-radius: 8px; width: 70%; box-shadow: 0 2px 8px rgba(0,0,0,0.08);"></div>
                                
                                <?php if ($tema_layout_atual === 'shioki'): ?>
                                <div style="position: absolute; top: 10px; right: 10px; background: #E76F51; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">✓</div>
                                <?php endif; ?>
                            </div>
                            <div class="text-center mt-3">
                                <strong style="font-size: 1rem;">☀️ Shioki (Light)</strong>
                                <p class="text-secondary-light mb-0" style="font-size: 0.85rem;">Tema claro e moderno</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function handleLayoutChange(radio) {
            document.querySelectorAll('.theme-layout-card').forEach(card => {
                card.classList.remove('selected');
                card.querySelector('.theme-preview').style.borderColor = 'transparent';
                const checkmark = card.querySelector('.theme-preview > div:last-child');
                if (checkmark && checkmark.textContent === '✓') {
                    checkmark.remove();
                }
            });
            
            const selectedCard = radio.closest('.theme-layout-card');
            selectedCard.classList.add('selected');
            
            const preview = selectedCard.querySelector('.theme-preview');
            const color = radio.value === 'shioki' ? '#E76F51' : '#4a66f9';
            preview.style.borderColor = color;
            
            // Add checkmark
            const checkDiv = document.createElement('div');
            checkDiv.style.cssText = `position: absolute; top: 10px; right: 10px; background: ${color}; color: white; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;`;
            checkDiv.textContent = '✓';
            preview.appendChild(checkDiv);
        }
        </script>
        
        <!-- Cores do Cardápio -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:palette-outline"></iconify-icon>
                    Cores do Cardápio
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-4 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:info-circle-outline"></iconify-icon>
                    Escolha um tema predefinido ou defina cores personalizadas. As cores serão aplicadas em todo o cardápio digital.
                </div>
                
                <div class="row">
                    <div class="col-12 mb-4">
                        <label class="form-label fw-semibold mb-3">Temas Predefinidos</label>
                        <div class="color-options-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 15px; margin-bottom: 20px;">
                            <?php
                            $temas = [
                                'verde' => ['#4caf50', '#45a049', 'Verde'],
                                'azul' => ['#2196F3', '#1976D2', 'Azul'],
                                'roxo' => ['#9C27B0', '#7B1FA2', 'Roxo'],
                                'rosa' => ['#E91E63', '#C2185B', 'Rosa'],
                                'laranja' => ['#FF9800', '#F57C00', 'Laranja'],
                                'vermelho' => ['#F44336', '#D32F2F', 'Vermelho'],
                                'teal' => ['#009688', '#00796B', 'Teal'],
                                'indigo' => ['#3F51B5', '#303F9F', 'Índigo'],
                                'amber' => ['#FFC107', '#FFA000', 'Âmbar'],
                                'cyan' => ['#00BCD4', '#0097A7', 'Ciano'],
                                'deep-purple' => ['#673AB7', '#512DA8', 'Roxo Escuro'],
                                'pink' => ['#EC407A', '#C2185B', 'Rosa Claro']
                            ];
                            
                            foreach ($temas as $key => $colors):
                                $checked = ($config['tema'] == $key) ? 'checked' : '';
                                $selectedClass = ($config['tema'] == $key) ? 'selected' : '';
                                $checkMark = ($config['tema'] == $key) ? '<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 1.5rem; font-weight: bold; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">✓</div>' : '';
                            ?>
                            <label class="color-option-card <?php echo $selectedClass; ?>" style="cursor: pointer;">
                                <input type="radio" name="cardapio_tema" value="<?php echo $key; ?>" 
                                       <?php echo $checked; ?>
                                       onchange="handleTemaChange(this);">
                                <div class="color-preview" style="
                                    background: linear-gradient(135deg, <?php echo $colors[0]; ?> 0%, <?php echo $colors[1]; ?> 100%);
                                    height: 80px;
                                    border-radius: 12px;
                                    border: 3px solid transparent;
                                    box-shadow: none;
                                    margin-bottom: 8px;
                                    position: relative;
                                    transition: all 0.2s;
                                ">
                                    <?php echo $checkMark; ?>
                                </div>
                                <div class="text-center">
                                    <strong style="font-size: 0.9rem;"><?php echo $colors[2]; ?></strong>
                                </div>
                            </label>
                            <?php endforeach; ?>
                            
                            <!-- Custom -->
                            <label class="color-option-card <?php echo ($config['tema'] == 'custom') ? 'selected' : ''; ?>" style="cursor: pointer;">
                                <input type="radio" name="cardapio_tema" value="custom" 
                                       <?php echo ($config['tema'] == 'custom') ? 'checked' : ''; ?>
                                       onchange="handleTemaChange(this);">
                                <div class="color-preview" style="
                                    background: linear-gradient(135deg, #999 0%, #666 100%);
                                    height: 80px;
                                    border-radius: 12px;
                                    border: 3px solid transparent;
                                    box-shadow: none;
                                    margin-bottom: 8px;
                                    position: relative;
                                    transition: all 0.2s;
                                ">
                                    <?php echo ($config['tema'] == 'custom') ? '<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 1.5rem; font-weight: bold; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">✓</div>' : ''; ?>
                                </div>
                                <div class="text-center">
                                    <strong style="font-size: 0.9rem;">Personalizado</strong>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Cores Customizadas -->
                    <div class="col-md-6 mb-4" id="custom-colors-section" style="<?php echo ($config['tema'] == 'custom') ? 'display: block;' : 'display: none;'; ?>">
                        <label class="form-label fw-semibold mb-3">Cores Personalizadas</label>
                        <div class="mb-3">
                            <label class="form-label">Cor Principal</label>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="color" name="cor_principal_custom" id="cor_principal_custom" 
                                       value="<?php echo htmlspecialchars($config['cor_principal'] ?? '#9C27B0'); ?>" 
                                       class="form-control form-control-color" style="width: 80px; height: 50px;">
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($config['cor_principal'] ?? '#9C27B0'); ?>" 
                                       id="cor_principal_text" placeholder="#4caf50" maxlength="7"
                                       onchange="document.getElementById('cor_principal_custom').value = this.value;">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cor Secundária</label>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="color" name="cor_secundaria_custom" id="cor_secundaria_custom" 
                                       value="<?php echo htmlspecialchars($config['cor_secundaria'] ?? '#7B1FA2'); ?>" 
                                       class="form-control form-control-color" style="width: 80px; height: 50px;">
                                <input type="text" class="form-control" 
                                       value="<?php echo htmlspecialchars($config['cor_secundaria'] ?? '#7B1FA2'); ?>" 
                                       id="cor_secundaria_text" placeholder="#45a049" maxlength="7"
                                       onchange="document.getElementById('cor_secundaria_custom').value = this.value;">
                            </div>
                        </div>
                        <script>
                        document.getElementById('cor_principal_custom').addEventListener('input', function(e) {
                            document.getElementById('cor_principal_text').value = e.target.value;
                        });
                        document.getElementById('cor_secundaria_custom').addEventListener('input', function(e) {
                            document.getElementById('cor_secundaria_text').value = e.target.value;
                        });
                        </script>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Impressão Automática -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:printer-outline"></iconify-icon>
                    Impressão Automática de Pedidos
                </h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-4 d-flex align-items-start gap-2">
                    <iconify-icon icon="solar:info-circle-outline" class="mt-1"></iconify-icon>
                    <div>
                        <strong>Como funciona:</strong> Quando ativado, cada novo pedido abrirá automaticamente a janela de impressão. 
                        Ideal para impressoras térmicas. Mantenha o painel aberto para receber as impressões.
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check form-switch d-flex align-items-center gap-3" style="padding-left: 3rem;">
                            <input class="form-check-input" type="checkbox" name="impressao_automatica" 
                                   id="impressao_automatica" style="width: 50px; height: 26px;"
                                   <?php echo ($config['impressao_automatica'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-semibold" for="impressao_automatica">
                                <div class="d-flex align-items-center gap-2">
                                    <iconify-icon icon="solar:printer-bold-duotone" class="text-primary text-xl"></iconify-icon>
                                    Ativar Impressão Automática
                                </div>
                                <small class="text-secondary-light d-block mt-1">
                                    Imprime cada novo pedido automaticamente
                                </small>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-neutral-100 rounded p-3">
                            <h6 class="mb-2 fw-semibold">
                                <iconify-icon icon="solar:lightbulb-outline" class="text-warning"></iconify-icon>
                                Dicas:
                            </h6>
                            <ul class="mb-0 small text-secondary-light ps-3">
                                <li>Configure sua impressora térmica como padrão no sistema</li>
                                <li>Mantenha a aba do painel admin aberta</li>
                                <li>O navegador pode pedir permissão para pop-ups</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Botão Salvar -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-success btn-lg d-flex align-items-center gap-2" id="btnSalvar">
                        <iconify-icon icon="solar:check-circle-outline"></iconify-icon>
                        Salvar Todas as Configurações
                    </button>
                </div>
            </div>
        </div>
    </form>

    <script>
    let uploadingFiles = 0;

    // Função para mostrar/ocultar cores customizadas
    function handleTemaChange(radio) {
        const customSection = document.getElementById('custom-colors-section');
        const allCards = document.querySelectorAll('.color-option-card');
        
        // Detectar dark mode
        const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark' || 
                           document.documentElement.classList.contains('dark');
        
        // Remove seleção visual de todos
        allCards.forEach(card => {
            card.classList.remove('selected');
            const preview = card.querySelector('.color-preview');
            preview.style.border = '3px solid transparent';
            preview.style.boxShadow = 'none';
            const check = preview.querySelector('div');
            if (check) check.remove();
        });
        
        // Adiciona seleção visual ao selecionado
        const selectedCard = radio.closest('.color-option-card');
        selectedCard.classList.add('selected');
        const selectedPreview = selectedCard.querySelector('.color-preview');
        
        if (isDarkMode) {
            selectedPreview.style.border = '3px solid rgba(255, 255, 255, 0.5)';
            selectedPreview.style.boxShadow = '0 0 0 2px rgba(255, 255, 255, 0.3), 0 0 0 4px rgba(102, 126, 234, 0.5)';
        } else {
            selectedPreview.style.border = '3px solid #333';
            selectedPreview.style.boxShadow = '0 0 0 2px white, 0 0 0 4px #333';
        }
        
        // Adiciona checkmark
        const checkDiv = document.createElement('div');
        checkDiv.style.cssText = 'position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 1.5rem; font-weight: bold; text-shadow: 0 2px 4px rgba(0,0,0,0.3);';
        checkDiv.textContent = '✓';
        selectedPreview.appendChild(checkDiv);
        
        // Mostra/oculta seção de cores customizadas
        if (radio.value === 'custom') {
            customSection.style.display = 'block';
        } else {
            customSection.style.display = 'none';
        }
    }

    // Preview de imagem com loading e atualização de miniatura
    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        const thumbnail = input.closest('.card-body').querySelector('.thumbnail-preview');
        
        if (input.files && input.files[0]) {
            const file = input.files[0];
            
            // Validação de tamanho
            const maxSize = previewId === 'capa-preview' ? 5 * 1024 * 1024 : 2 * 1024 * 1024;
            if (file.size > maxSize) {
                alert('Arquivo muito grande! Máximo: ' + (maxSize / 1024 / 1024) + 'MB');
                input.value = '';
                return;
            }
            
            // Mostra loading
            showLoading('Processando imagem...');
            uploadingFiles++;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                // Atualiza miniatura se existir
                if (thumbnail) {
                    const img = thumbnail.querySelector('img') || document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'img-thumbnail';
                    img.style.cssText = input.id.includes('favicon') 
                        ? 'max-width: 64px; max-height: 64px; object-fit: contain;'
                        : 'max-width: 80px; max-height: 80px; object-fit: ' + (input.id.includes('capa') ? 'cover' : 'contain') + ';';
                    if (!thumbnail.querySelector('img')) {
                        thumbnail.appendChild(img);
                    }
                }
                
                preview.innerHTML = `
                    <img src="${e.target.result}" alt="Preview" class="fade-in mt-2" style="max-width: 100px; border-radius: 8px;">
                    <p class="text-sm text-success mt-2 mb-0 d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:check-circle-outline"></iconify-icon>
                        Imagem carregada
                    </p>
                `;
                
                uploadingFiles--;
                if (uploadingFiles === 0) {
                    hideLoading();
                }
            };
            
            reader.readAsDataURL(file);
        }
    }

    // Drag & Drop para upload zones
    document.addEventListener('DOMContentLoaded', function() {
        const uploadZones = document.querySelectorAll('.upload-zone, .upload-zone-small');
        
        uploadZones.forEach(zone => {
            zone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });
            
            zone.addEventListener('dragleave', function() {
                this.classList.remove('dragover');
            });
            
            zone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                
                const input = this.parentElement.querySelector('input[type="file"]');
                if (e.dataTransfer.files.length) {
                    input.files = e.dataTransfer.files;
                    input.dispatchEvent(new Event('change'));
                }
            });
        });
        
        // Máscara de CEP
        const cepInput = document.getElementById('cep');
        if (cepInput) {
            cepInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 5) {
                    value = value.substring(0, 5) + '-' + value.substring(5, 8);
                }
                e.target.value = value;
                
                // Busca CEP automaticamente
                if (value.replace(/\D/g, '').length === 8) {
                    buscarCEP(value.replace(/\D/g, ''));
                }
            });
        }
        
        // Máscara de WhatsApp
        const whatsappInput = document.getElementById('whatsapp');
        if (whatsappInput) {
            whatsappInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 10) {
                    value = '(' + value.substring(0, 2) + ') ' + value.substring(2, 7) + '-' + value.substring(7, 11);
                } else if (value.length > 6) {
                    value = '(' + value.substring(0, 2) + ') ' + value.substring(2, 6) + '-' + value.substring(6);
                } else if (value.length > 2) {
                    value = '(' + value.substring(0, 2) + ') ' + value.substring(2);
                }
                e.target.value = value;
            });
        }
        
        // Inicializar seleção visual do tema
        const checkedTheme = document.querySelector('input[name="cardapio_tema"]:checked');
        if (checkedTheme) {
            handleTemaChange(checkedTheme);
        }
    });

    // Buscar CEP via ViaCEP
    function buscarCEP(cep) {
        if (cep.length !== 8) return;
        
        showLoading('Buscando CEP...');
        
        fetch(`https://viacep.com.br/ws/${cep}/json/`)
            .then(response => response.json())
            .then(data => {
                if (!data.erro) {
                    document.getElementById('rua').value = data.logradouro || '';
                    document.getElementById('bairro').value = data.bairro || '';
                    document.getElementById('cidade').value = data.localidade || '';
                    document.getElementById('estado').value = data.uf || '';
                } else {
                    alert('CEP não encontrado!');
                }
                hideLoading();
            })
            .catch(error => {
                console.error('Erro ao buscar CEP:', error);
                hideLoading();
            });
    }

    // Loading overlay
    function showLoading(text = 'Carregando...') {
        const overlay = document.getElementById('loadingOverlay');
        const loadingText = document.getElementById('loadingText');
        loadingText.textContent = text;
        overlay.classList.add('active');
    }

    function hideLoading() {
        setTimeout(() => {
            const overlay = document.getElementById('loadingOverlay');
            overlay.classList.remove('active');
        }, 300);
    }

    // Submit do formulário
    document.getElementById('configForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validação final
        const titulo = document.querySelector('input[name="site_titulo"]').value.trim();
        if (!titulo) {
            alert('O título do site é obrigatório!');
            return;
        }
        
        // Mostra loading
        showLoading('Salvando configurações...');
        
        // Aguarda 500ms para garantir que os arquivos foram processados
        setTimeout(() => {
            this.submit();
        }, 500);
    });

    // Animação de fade in
    const style = document.createElement('style');
    style.textContent = `
        .fade-in {
            animation: fadeIn 0.5s;
        }
    `;
    document.head.appendChild(style);
    </script>

</div>

<?php include 'includes/footer.php'; ?>
