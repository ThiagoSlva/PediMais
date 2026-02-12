<?php
include 'includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';
require_once '../includes/csrf.php';

verificar_login();

$msg = '';
$msg_tipo = '';

// --- MIGRATION LOGIC (Ensure DB is ready) ---
try {
    // 1. Check/Add columns to whatsapp_config
    $stmt = $pdo->query("DESCRIBE whatsapp_config");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('tempo_preparo_padrao', $columns)) {
        $pdo->exec("ALTER TABLE whatsapp_config ADD COLUMN tempo_preparo_padrao INT DEFAULT 30");
    }
    if (!in_array('tempo_entrega_padrao', $columns)) {
        $pdo->exec("ALTER TABLE whatsapp_config ADD COLUMN tempo_entrega_padrao INT DEFAULT 40");
    }

    // 2. Ensure all message templates exist
    $templates = [
        1 => ['tipo' => 'comprovante_pedido', 'titulo' => 'Comprovante do Pedido', 'mensagem' => "🧾 PEDIDO RECEBIDO!\n\n📋 Pedido: #{codigo_pedido}\n💰 Valor Total: R$ {total}\n\nAcompanhe seu pedido aqui: {link_acompanhamento}\n\nObrigado pela preferência! 😊"],
        2 => ['tipo' => 'pagamento_pix', 'titulo' => 'Pagamento PIX', 'mensagem' => "🔑 *Chave PIX:* {chave_pix}\n💰 *Valor:* R$ {total}\n"],
        3 => ['tipo' => 'pagamento_dinheiro', 'titulo' => 'Pagamento em Dinheiro', 'mensagem' => "💵 *PAGAMENTO EM DINHEIRO*\n\n💰 *Valor Total:* R$ {total}\n💸 *Troco para:* R$ {troco_para}\n🔄 *Troco:* R$ {troco}\n\n📋 *Pedido:* #{codigo_pedido}\n\nO entregador levará seu pedido e o troco! 🏍️"],
        4 => ['tipo' => 'pagamento_cartao', 'titulo' => 'Pagamento no Cartão', 'mensagem' => "💳 *PAGAMENTO NO CARTÃO*\n\n💰 *Valor Total:* R$ {total}\n💳 *Forma:* {forma_pagamento}\n\n📋 *Pedido:* #{codigo_pedido}\n\nO entregador levará a maquininha para pagamento! 🏍️"],
        46 => ['tipo' => 'confirmacao_pedido', 'titulo' => 'Confirmação do Pedido', 'mensagem' => "✅ *Pedido confirmado!*\n\nOlá, {nome}! 👋\nRecebemos o seu pedido.\n\n💰 Total: {total}\n\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏"],
        5 => ['tipo' => 'status_pendente', 'titulo' => 'Status: Pendente', 'mensagem' => "⏳ *PEDIDO RECEBIDO*\n\n📋 *Pedido:* #{codigo_pedido}\n\nSeu pedido foi recebido e está aguardando confirmação.\n\nEm breve você receberá atualizações! ⏰"],
        6 => ['tipo' => 'status_em_andamento', 'titulo' => 'Status: Em Preparo', 'mensagem' => "👨‍🍳 *PEDIDO EM PREPARO*\n\n📋 *Pedido:* #{codigo_pedido}\n\nSua refeição está sendo preparada com muito carinho!\n\n⏱️ Tempo estimado: {tempo_preparo} minutos\n\nAguarde mais um pouco! 😋"],
        7 => ['tipo' => 'status_pronto', 'titulo' => 'Status: Pronto', 'mensagem' => "✅ *PEDIDO PRONTO*\n\n📋 *Pedido:* #{codigo_pedido}\n\n{mensagem_pronto}\n\nObrigado pela preferência! 🎉"],
        8 => ['tipo' => 'status_saiu_entrega', 'titulo' => 'Status: Saiu para Entrega', 'mensagem' => "🛵 *{nome}*, seu pedido acabou de sair para entrega!"],
        9 => ['tipo' => 'status_concluido', 'titulo' => 'Status: Entregue', 'mensagem' => "🎉 *PEDIDO ENTREGUE*\n\n📋 *Pedido:* #{codigo_pedido}\n\nEsperamos que tenha gostado!\n\nSua opinião é muito importante para nós.\nAvalie nosso atendimento! ⭐⭐⭐⭐⭐\n\nVolte sempre! 😊"],
        10 => ['tipo' => 'status_cancelado', 'titulo' => 'Status: Cancelado', 'mensagem' => "❌ *PEDIDO CANCELADO*\n\n📋 *Pedido:* #{codigo_pedido}\n\nSeu pedido foi cancelado.\n{motivo_cancelamento}\n\nQualquer dúvida, entre em contato conosco.\n\nAté breve! 🙏"],
        11 => ['tipo' => 'link_acompanhamento', 'titulo' => 'Link Acompanhamento', 'mensagem' => "📱 *ACOMPANHE SEU PEDIDO*\n\n📋 *Pedido:* #{codigo_pedido}\n\n🔗 Clique no link abaixo para acompanhar em tempo real:\n{link_acompanhamento}\n\nVocê receberá notificações a cada mudança de status! 🔔"],
        45 => ['tipo' => 'status_cancelado_pix_expirado', 'titulo' => 'Cancelado: PIX Expirado', 'mensagem' => "❌ *PEDIDO CANCELADO POR FALTA DE PAGAMENTO*\n\n📋 *Pedido:* #{codigo_pedido}\n\n{motivo_cancelamento}\n\nSe ainda desejar, faça um novo pedido quando estiver pronto. Estamos à disposição! 😊"],
        12 => ['tipo' => 'status_finalizado', 'titulo' => 'Pedido Concluído/Finalizado', 'mensagem' => "📦 *PEDIDO FINALIZADO COM SUCESSO!*\n\nOlá, {nome}! 👋\n\n📋 *Pedido:* #{codigo_pedido}\n💰 *Total:* R$ {total}\n\n✅ Seu pedido foi concluído com sucesso!\n\nAgradecemos a preferência e esperamos vê-lo novamente em breve! 🙏\n\n⭐ Deixe sua avaliação e ajude-nos a melhorar!\n\nAté a próxima! 🎉"]
    ];

    foreach ($templates as $id => $data) {
        // Check if ID exists
        $stmt = $pdo->prepare("SELECT id FROM whatsapp_mensagens WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            // Insert with specific ID
            $stmt = $pdo->prepare("INSERT INTO whatsapp_mensagens (id, tipo, titulo, mensagem, ativo) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$id, $data['tipo'], $data['titulo'], $data['mensagem']]);
        }
        else {
            // Optional: Update title/type if needed, but usually we respect user changes to content
            // We ensure 'titulo' column exists first? 
            // The original schema might not have 'titulo'. Let's check.
            // If 'titulo' is missing, add it.
            $stmt = $pdo->query("DESCRIBE whatsapp_mensagens");
            $msgColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('titulo', $msgColumns)) {
                $pdo->exec("ALTER TABLE whatsapp_mensagens ADD COLUMN titulo VARCHAR(255) DEFAULT ''");
                // Update titles
                $pdo->prepare("UPDATE whatsapp_mensagens SET titulo = ? WHERE id = ?")->execute([$data['titulo'], $id]);
            }
        }
    }

}
catch (PDOException $e) {
// Ignore errors or log them (e.g. duplicate column)
}
// ---------------------------------------------

// Processar formulários
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao'])) {
    if (!validar_csrf()) {
        $msg = 'Token de segurança inválido. Recarregue a página.';
        $msg_tipo = 'danger';
    }
    else {

        // 1. Salvar Configurações de Tempo
        if ($_POST['acao'] == 'salvar_configs') {
            try {
                $tempo_preparo = (int)$_POST['whatsapp_tempo_preparo_padrao'];
                $tempo_entrega = (int)$_POST['whatsapp_tempo_entrega_padrao'];

                // Update the first row of whatsapp_config
                $stmt = $pdo->prepare("UPDATE whatsapp_config SET tempo_preparo_padrao = ?, tempo_entrega_padrao = ? LIMIT 1");
                $stmt->execute([$tempo_preparo, $tempo_entrega]);

                $msg = 'Configurações de tempo salvas com sucesso!';
                $msg_tipo = 'success';
            }
            catch (PDOException $e) {
                $msg = 'Erro ao salvar configurações: ' . $e->getMessage();
                $msg_tipo = 'danger';
            }
        }

        // 2. Atualizar Mensagem Individual
        elseif ($_POST['acao'] == 'atualizar_mensagem') {
            try {
                $id = (int)$_POST['id'];
                $titulo = $_POST['titulo'];
                $mensagem = $_POST['mensagem'];
                $ativo = isset($_POST['ativo']) ? 1 : 0;

                $stmt = $pdo->prepare("UPDATE whatsapp_mensagens SET titulo = ?, mensagem = ?, ativo = ?, atualizado_em = NOW() WHERE id = ?");
                $stmt->execute([$titulo, $mensagem, $ativo, $id]);

                $msg = 'Mensagem atualizada com sucesso!';
                $msg_tipo = 'success';
            }
            catch (PDOException $e) {
                $msg = 'Erro ao atualizar mensagem: ' . $e->getMessage();
                $msg_tipo = 'danger';
            }
        }
    } // fecha else validar_csrf
}

// Buscar configurações atuais
$stmt = $pdo->query("SELECT * FROM whatsapp_config LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$config) {
    // Default values if table empty
    $config = ['tempo_preparo_padrao' => 30, 'tempo_entrega_padrao' => 40];
}

// Buscar mensagens
$stmt = $pdo->query("SELECT * FROM whatsapp_mensagens ORDER BY id ASC");
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

[data-theme="dark"] .accordion-button,
html[data-theme="dark"] .accordion-button {
    background-color: var(--base-soft, #1e1e2e) !important;
    color: rgba(255, 255, 255, 0.9) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

[data-theme="dark"] .accordion-button:not(.collapsed),
html[data-theme="dark"] .accordion-button:not(.collapsed) {
    background-color: var(--base-soft, #1e1e2e) !important;
    color: rgba(255, 255, 255, 0.9) !important;
    box-shadow: none;
}

[data-theme="dark"] .accordion-button::after,
html[data-theme="dark"] .accordion-button::after {
    filter: invert(1);
    opacity: 0.7;
}

[data-theme="dark"] .accordion-body,
html[data-theme="dark"] .accordion-body {
    background-color: var(--base-soft, #1e1e2e) !important;
    color: rgba(255, 255, 255, 0.9) !important;
}

[data-theme="dark"] .accordion-item,
html[data-theme="dark"] .accordion-item {
    background-color: var(--base-soft, #1e1e2e) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
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
        <h6 class="fw-semibold mb-0">Mensagens Editáveis do WhatsApp</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Mensagens WhatsApp</li>
        </ul>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_tipo; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php
endif; ?>
    
    <!-- Card de Configurações Gerais -->
    <div class="card h-100 p-0 radius-12 mb-24">
        <div class="card-header border-bottom bg-base py-16 px-24">
            <h6 class="text-lg fw-semibold mb-0 d-flex align-items-center gap-2">
                <iconify-icon icon="solar:settings-bold-duotone" class="text-primary-600"></iconify-icon>
                Configurações de Pagamento e Tempo
            </h6>
        </div>
        <div class="card-body p-24">
            <form method="POST">
                <?php echo campo_csrf(); ?>
                <input type="hidden" name="acao" value="salvar_configs">
                
                <div class="row gy-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                            Tempo de Preparo Padrão (minutos)
                        </label>
                        <input type="number" 
                               name="whatsapp_tempo_preparo_padrao" 
                               class="form-control radius-8" 
                               value="<?php echo htmlspecialchars($config['tempo_preparo_padrao'] ?? 30); ?>"
                               min="5" max="120">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-primary-light text-sm mb-8">
                            Tempo de Entrega Padrão (minutos)
                        </label>
                        <input type="number" 
                               name="whatsapp_tempo_entrega_padrao" 
                               class="form-control radius-8" 
                               value="<?php echo htmlspecialchars($config['tempo_entrega_padrao'] ?? 40); ?>"
                               min="10" max="180">
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary-600 radius-8 px-20 py-11 d-flex align-items-center gap-2">
                            <iconify-icon icon="solar:floppy-disk-bold-duotone"></iconify-icon>
                            Salvar Configurações
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Card de Variáveis Disponíveis -->
    <div class="card h-100 p-0 radius-12 mb-24">
        <div class="card-header border-bottom bg-base py-16 px-24">
            <h6 class="text-lg fw-semibold mb-0 d-flex align-items-center gap-2">
                <iconify-icon icon="solar:code-bold-duotone" class="text-warning-600"></iconify-icon>
                Variáveis Disponíveis
            </h6>
        </div>
        <div class="card-body p-24">
            <div class="alert alert-info mb-0">
                <h6 class="mb-3">Use estas variáveis nas mensagens:</h6>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-unstyled mb-0">
                            <li><code>{nome}</code> - Nome do cliente</li>
                            <li><code>{telefone}</code> - Telefone do cliente</li>
                            <li><code>{endereco}</code> - Endereço completo</li>
                            <li><code>{codigo_pedido}</code> - Código do pedido</li>
                            <li><code>{data_pedido}</code> - Data e hora do pedido</li>
                            <li><code>{itens}</code> - Lista de itens do pedido</li>
                            <li><code>{adicionais}</code> - Adicionais do pedido</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-unstyled mb-0">
                            <li><code>{retirar}</code> - Itens para retirar</li>
                            <li><code>{observacoes}</code> - Observações do pedido</li>
                            <li><code>{subtotal}</code> - Subtotal formatado</li>
                            <li><code>{taxa_entrega}</code> - Taxa de entrega formatada</li>
                            <li><code>{total}</code> - Total formatado</li>
                            <li><code>{tipo_entrega}</code> - Delivery ou Retirada</li>
                            <li><code>{forma_pagamento}</code> - Forma de pagamento</li>
                            <li><code>{troco_para}</code> - Valor para troco</li>
                            <li><code>{troco}</code> - Valor do troco</li>
                            <li><code>{tempo_preparo}</code> - Tempo de preparo</li>
                            <li><code>{tempo_entrega}</code> - Tempo de entrega</li>
                            <li><code>{mensagem_pronto}</code> - Msg pronto (retirada/delivery)</li>
                            <li><code>{link_acompanhamento}</code> - Link área do cliente</li>
                            <li><code>{motivo_cancelamento}</code> - Motivo do cancelamento</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Accordion com Templates de Mensagens -->
    <div class="card h-100 p-0 radius-12">
        <div class="card-header border-bottom bg-base py-16 px-24">
            <h6 class="text-lg fw-semibold mb-0 d-flex align-items-center gap-2">
                <iconify-icon icon="solar:chat-round-dots-bold-duotone" class="text-success-600"></iconify-icon>
                Templates de Mensagens
            </h6>
        </div>
        <div class="card-body p-24">
            <div class="accordion" id="accordionMensagens">
                <?php
// Define order of IDs as per user request
$displayOrder = [1, 2, 3, 4, 46, 5, 6, 7, 8, 9, 12, 10, 11, 45];

foreach ($displayOrder as $id):
    if (!isset($mensagens[$id]))
        continue;
    $m = $mensagens[$id];
    $isOpen = ($id == 1) ? 'show' : '';
    $isCollapsed = ($id == 1) ? '' : 'collapsed';
    $badgeClass = $m['ativo'] ? 'bg-success-600' : 'bg-danger-600';
    $badgeText = $m['ativo'] ? 'Ativo' : 'Inativo';
?>
                <div class="accordion-item border radius-8 mb-3">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?php echo $isCollapsed; ?>" 
                                type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#collapse<?php echo $id; ?>">
                            <div class="d-flex align-items-center gap-3 w-100">
                                <iconify-icon icon="solar:chat-round-line-bold-duotone" class="text-xl"></iconify-icon>
                                <div class="flex-grow-1">
                                    <strong><?php echo htmlspecialchars($m['titulo']); ?></strong>
                                    <br>
                                    <small class="text-secondary-light"><?php echo htmlspecialchars($m['tipo']); ?></small>
                                </div>
                                <span class="badge <?php echo $badgeClass; ?> text-white"><?php echo $badgeText; ?></span>
                            </div>
                        </button>
                    </h2>
                    <div id="collapse<?php echo $id; ?>" 
                         class="accordion-collapse collapse <?php echo $isOpen; ?>" 
                         data-bs-parent="#accordionMensagens">
                        <div class="accordion-body">
                            <form method="POST">
                                <?php echo campo_csrf(); ?>
                                <input type="hidden" name="acao" value="atualizar_mensagem">
                                <input type="hidden" name="id" value="<?php echo $id; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Título</label>
                                    <input type="text" 
                                           name="titulo" 
                                           class="form-control radius-8" 
                                           value="<?php echo htmlspecialchars($m['titulo']); ?>"
                                           required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Mensagem</label>
                                    <textarea name="mensagem" 
                                              class="form-control radius-8" 
                                              rows="12" 
                                              required><?php echo htmlspecialchars($m['mensagem']); ?></textarea>
                                    <small class="text-secondary-light">Use as variáveis disponíveis acima</small>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="ativo"
                                               id="ativo_<?php echo $id; ?>"
                                               <?php echo $m['ativo'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="ativo_<?php echo $id; ?>">
                                            Template Ativo
                                        </label>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary-600 radius-8 d-flex align-items-center gap-2">
                                    <iconify-icon icon="solar:floppy-disk-bold-duotone"></iconify-icon>
                                    Salvar Mensagem
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php
endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Toggles Script (Inline as requested) -->


<?php include 'includes/footer.php'; ?>