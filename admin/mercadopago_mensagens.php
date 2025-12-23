<?php
include 'includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';

verificar_login();

$msg = '';
$msg_tipo = '';

// --- MIGRATION LOGIC (Ensure DB is ready) ---
try {
    // Ensure message templates exist for Mercado Pago (IDs 149 and 150)
    $templates = [
        149 => [
            'tipo' => 'aguardando_pagamento', 
            'titulo' => 'Aguardando Pagamento PIX', 
            'mensagem' => "Olá, {nome}! Recebemos o seu pedido e estamos aguardando o pagamento 😃.\n\nVocê tem {minutos} minutos para pagar o valor de R$ {valor} usando o Pix Copia e Cola ou o QR Code abaixo. Após esse prazo, o pedido será cancelado automaticamente."
        ],
        150 => [
            'tipo' => 'pagamento_recebido', 
            'titulo' => 'Pagamento Recebido', 
            'mensagem' => "🎉 O pagamento do seu pedido foi confirmado! Em breve vamos iniciar a preparação e manter você atualizado(a)."
        ]
    ];

    foreach ($templates as $id => $data) {
        // Check if ID exists
        $stmt = $pdo->prepare("SELECT id FROM whatsapp_mensagens WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            // Insert with specific ID
            $stmt = $pdo->prepare("INSERT INTO whatsapp_mensagens (id, tipo, titulo, mensagem, ativo) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$id, $data['tipo'], $data['titulo'], $data['mensagem']]);
        } else {
            // Ensure 'titulo' column exists (handled in previous file, but safe to double check or assume it's there now)
            // Update title if needed to match our internal logic, but respect user content
             $pdo->prepare("UPDATE whatsapp_mensagens SET titulo = ? WHERE id = ?")->execute([$data['titulo'], $id]);
        }
    }

} catch (PDOException $e) {
    // Ignore errors or log them
}
// ---------------------------------------------

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    try {
        $id = (int)$_POST['id'];
        $mensagem = $_POST['mensagem'];
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        
        $stmt = $pdo->prepare("UPDATE whatsapp_mensagens SET mensagem = ?, ativo = ?, atualizado_em = NOW() WHERE id = ?");
        $stmt->execute([$mensagem, $ativo, $id]);
        
        $msg = 'Mensagem atualizada com sucesso!';
        $msg_tipo = 'success';
    } catch (PDOException $e) {
        $msg = 'Erro ao atualizar mensagem: ' . $e->getMessage();
        $msg_tipo = 'danger';
    }
}

// Buscar mensagens do Mercado Pago (IDs 149 e 150)
$stmt = $pdo->query("SELECT * FROM whatsapp_mensagens WHERE id IN (149, 150) ORDER BY id ASC");
$mensagens_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
$mensagens = [];
foreach ($mensagens_db as $m) {
    $mensagens[$m['id']] = $m;
}

include 'includes/header.php';
?>

<style>
/* Dark mode support for form inputs and text */
[data-theme="dark"] .card,
html[data-theme="dark"] .card {
    background-color: var(--base-soft, #1e1e2e) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

[data-theme="dark"] .card-body,
html[data-theme="dark"] .card-body {
    background-color: var(--base-soft, #1e1e2e) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .card .form-control,
[data-theme="dark"] .card textarea,
[data-theme="dark"] .card input[type="text"],
[data-theme="dark"] .card input[type="number"],
html[data-theme="dark"] .card .form-control,
html[data-theme="dark"] .card textarea,
html[data-theme="dark"] .card input[type="text"],
html[data-theme="dark"] .card input[type="number"] {
    background-color: rgba(255, 255, 255, 0.05) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .card .form-control:focus,
[data-theme="dark"] .card textarea:focus,
[data-theme="dark"] .card input[type="text"]:focus,
[data-theme="dark"] .card input[type="number"]:focus,
html[data-theme="dark"] .card .form-control:focus,
html[data-theme="dark"] .card textarea:focus,
html[data-theme="dark"] .card input[type="text"]:focus,
html[data-theme="dark"] .card input[type="number"]:focus {
    background-color: rgba(255, 255, 255, 0.08) !important;
    border-color: #487FFF !important;
    color: rgba(255, 255, 255, 0.9) !important;
    box-shadow: 0 0 0 0.2rem rgba(72, 127, 255, 0.25);
}

[data-theme="dark"] .card .form-control::placeholder,
[data-theme="dark"] .card textarea::placeholder,
html[data-theme="dark"] .card .form-control::placeholder,
html[data-theme="dark"] .card textarea::placeholder {
    color: rgba(255, 255, 255, 0.5) !important;
    opacity: 0.6;
}

[data-theme="dark"] .form-label,
html[data-theme="dark"] .form-label {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] code,
html[data-theme="dark"] code {
    background-color: rgba(255, 255, 255, 0.1) !important;
    color: #f3f4f6 !important;
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 2px 6px;
    border-radius: 4px;
}

[data-theme="dark"] .text-secondary-light,
html[data-theme="dark"] .text-secondary-light {
    color: rgba(255, 255, 255, 0.6) !important;
}

[data-theme="dark"] .text-secondary,
html[data-theme="dark"] .text-secondary {
    color: rgba(255, 255, 255, 0.7) !important;
}

[data-theme="dark"] .list-unstyled li,
html[data-theme="dark"] .list-unstyled li {
    color: rgba(255, 255, 255, 0.8) !important;
}

[data-theme="dark"] .alert-info,
html[data-theme="dark"] .alert-info {
    background-color: rgba(13, 110, 253, 0.15) !important;
    border-color: rgba(13, 110, 253, 0.3) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .alert-warning,
html[data-theme="dark"] .alert-warning {
    background-color: rgba(255, 193, 7, 0.15) !important;
    border-color: rgba(255, 193, 7, 0.3) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .form-check-label,
html[data-theme="dark"] .form-check-label {
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] small,
html[data-theme="dark"] small {
    color: rgba(255, 255, 255, 0.6) !important;
}
</style>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">💬 Mensagens do Mercado Pago</h6>
            <p class="text-sm text-secondary mb-0">Edite as mensagens enviadas via WhatsApp durante o processo de pagamento</p>
        </div>
        <a href="mercadopago_config.php" class="btn btn-outline-primary radius-8 px-20 py-11 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:arrow-left-bold" class="icon"></iconify-icon>
            Voltar
        </a>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_tipo; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <!-- Variáveis Disponíveis -->
    <div class="card h-100 p-0 radius-12 mb-24">
        <div class="card-header border-bottom bg-base py-16 px-24">
            <h6 class="text-lg fw-semibold mb-0 d-flex align-items-center gap-2">
                <iconify-icon icon="solar:code-bold-duotone" class="text-info-600"></iconify-icon>
                Variáveis Disponíveis
            </h6>
        </div>
        <div class="card-body p-24">
            <p class="text-secondary-light mb-3">
                Use estas variáveis nas suas mensagens. Elas serão substituídas automaticamente:
            </p>
            <div class="row g-2">
                <div class="col-md-4"><code>{nome}</code> - Nome do cliente</div>
                <div class="col-md-4"><code>{telefone}</code> - Telefone do cliente</div>
                <div class="col-md-4"><code>{codigo_pedido}</code> - Código do pedido</div>
                <div class="col-md-4"><code>{valor}</code> - Valor total (formatado)</div>
                <div class="col-md-4"><code>{minutos}</code> - Prazo em minutos</div>
                <div class="col-md-4"><code>{data_pedido}</code> - Data e hora</div>
            </div>
        </div>
    </div>

    <!-- Templates de Mensagens -->
    <?php 
    $displayOrder = [149, 150];
    foreach ($displayOrder as $id): 
        if (!isset($mensagens[$id])) continue;
        $m = $mensagens[$id];
        $badgeClass = $m['ativo'] ? 'bg-success' : 'bg-danger';
        $badgeText = $m['ativo'] ? 'Ativo' : 'Inativo';
        
        // Icon logic
        $icon = 'solar:chat-round-line-bold-duotone';
        $iconColor = 'text-primary-600';
        if ($id == 149) {
            $icon = 'solar:clock-circle-bold-duotone';
            $iconColor = 'text-warning-600';
        } elseif ($id == 150) {
            $icon = 'solar:check-circle-bold-duotone';
            $iconColor = 'text-success-600';
        }
        
        // Description logic
        $description = '';
        if ($id == 149) {
            $description = 'Enviada após finalizar pedido, aguardando pagamento PIX';
        } elseif ($id == 150) {
            $description = 'Enviada automaticamente quando o pagamento é confirmado';
        }
    ?>
    <div class="card h-100 p-0 radius-12 mb-24">
        <div class="card-header border-bottom bg-base py-16 px-24">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="text-lg fw-semibold mb-0 d-flex align-items-center gap-2">
                    <iconify-icon icon="<?php echo $icon; ?>" class="<?php echo $iconColor; ?>"></iconify-icon>
                    <?php echo htmlspecialchars($m['titulo']); ?>
                </h6>
                <span class="badge <?php echo $badgeClass; ?>">
                    <?php echo $badgeText; ?>
                </span>
            </div>
        </div>
        <div class="card-body p-24">
            <form method="POST" action="">
                <?php 
                // Generate CSRF token if not exists
                if (!isset($_SESSION['csrf_token'])) {
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                }
                ?>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                
                <div class="mb-3">
                    <label class="form-label">Tipo da Mensagem</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($m['tipo']); ?>" disabled>
                    <small class="text-secondary-light">
                        <?php echo $description; ?>
                    </small>
                </div>

                <div class="mb-3">
                    <label for="mensagem_<?php echo $id; ?>" class="form-label">Mensagem *</label>
                    <textarea class="form-control" 
                              name="mensagem" 
                              id="mensagem_<?php echo $id; ?>" 
                              rows="8" 
                              required><?php echo htmlspecialchars($m['mensagem']); ?></textarea>
                    <small class="text-secondary-light">Use as variáveis disponíveis acima</small>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" 
                           type="checkbox" 
                           name="ativo" 
                           id="ativo_<?php echo $id; ?>"
                           <?php echo $m['ativo'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="ativo_<?php echo $id; ?>">
                        Mensagem ativa
                    </label>
                </div>

                <button type="submit" class="btn btn-primary-600 radius-8 px-20 py-11 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:floppy-disk-bold-duotone" class="icon"></iconify-icon>
                    Salvar Mensagem
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
    
</div>

<!-- Toggles Script (Inline as requested) -->


<?php include 'includes/footer.php'; ?>