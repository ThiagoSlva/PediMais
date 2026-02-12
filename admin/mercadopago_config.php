<?php
include 'includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';
require_once '../includes/csrf.php';

verificar_login();

$msg = '';
$msg_tipo = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['acao']) && $_POST['acao'] == 'salvar_config') {
    if (!validar_csrf()) {
        $msg = 'Token de segurança inválido. Recarregue a página.';
        $msg_tipo = 'danger';
    }
    else {
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        $nome = $_POST['nome'];
        $public_key = $_POST['public_key'];
        $access_token = $_POST['access_token'];
        $sandbox_mode = isset($_POST['sandbox_mode']) ? 1 : 0;
        $prazo_pagamento_minutos = (int)$_POST['prazo_pagamento_minutos'];

        try {
            // Verificar se já existe registro
            $stmt = $pdo->query("SELECT id FROM mercadopago_config LIMIT 1");
            $config = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($config) {
                $sql = "UPDATE mercadopago_config SET 
                    ativo = :ativo,
                    nome = :nome,
                    public_key = :public_key,
                    access_token = :access_token,
                    sandbox_mode = :sandbox_mode,
                    prazo_pagamento_minutos = :prazo_pagamento_minutos,
                    atualizado_em = NOW()
                    WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':ativo' => $ativo,
                    ':nome' => $nome,
                    ':public_key' => $public_key,
                    ':access_token' => $access_token,
                    ':sandbox_mode' => $sandbox_mode,
                    ':prazo_pagamento_minutos' => $prazo_pagamento_minutos,
                    ':id' => $config['id']
                ]);
            }
            else {
                $sql = "INSERT INTO mercadopago_config (ativo, nome, public_key, access_token, sandbox_mode, prazo_pagamento_minutos) 
                    VALUES (:ativo, :nome, :public_key, :access_token, :sandbox_mode, :prazo_pagamento_minutos)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':ativo' => $ativo,
                    ':nome' => $nome,
                    ':public_key' => $public_key,
                    ':access_token' => $access_token,
                    ':sandbox_mode' => $sandbox_mode,
                    ':prazo_pagamento_minutos' => $prazo_pagamento_minutos
                ]);
            }

            $msg = 'Configurações salvas com sucesso!';
            $msg_tipo = 'success';
        }
        catch (PDOException $e) {
            $msg = 'Erro ao salvar configurações: ' . $e->getMessage();
            $msg_tipo = 'danger';
        }
    } // fecha else validar_csrf
}

// Buscar configurações atuais
$stmt = $pdo->query("SELECT * FROM mercadopago_config LIMIT 1");
$mp_config = $stmt->fetch(PDO::FETCH_ASSOC);

// Valores padrão se não houver configuração
if (!$mp_config) {
    $mp_config = [
        'ativo' => 0,
        'nome' => 'Pix Online',
        'public_key' => '',
        'access_token' => '',
        'sandbox_mode' => 1,
        'prazo_pagamento_minutos' => 10
    ];
}

include 'includes/header.php';
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div>
            <h6 class="fw-semibold mb-0">💳 Mercado Pago - Pagamento Online PIX</h6>
            <p class="text-sm text-secondary mb-0">Configure o pagamento online via PIX com Mercado Pago</p>
        </div>
        <?php if ($mp_config['ativo']): ?>
        <span class="badge bg-success-600 px-3 py-2">
            <i class="fa-solid fa-circle-check"></i> SISTEMA ATIVO
        </span>
        <?php
else: ?>
        <span class="badge bg-danger-600 px-3 py-2">
            <i class="fa-solid fa-circle-xmark"></i> SISTEMA INATIVO
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
        <li class="fw-medium">Mercado Pago</li>
    </ul>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?php echo $msg_tipo; ?> alert-dismissible fade show" role="alert">
    <?php echo $msg; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php
endif; ?>

<!-- Card de Configuração -->
<div class="card h-100 p-0 radius-12 mb-24">
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center justify-content-between">
        <h6 class="text-lg fw-semibold mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:settings-bold-duotone" class="text-primary-600"></iconify-icon>
            Credenciais e Modo
        </h6>
        <?php if (!empty($mp_config['access_token']) && !empty($mp_config['public_key'])): ?>
        <span class="badge bg-success-600">
            <i class="fa-solid fa-check-circle"></i> Credenciais OK
        </span>
        <?php
else: ?>
        <span class="badge bg-warning-600">
            <i class="fa-solid fa-exclamation-triangle"></i> Credenciais Pendentes
        </span>
        <?php
endif; ?>
    </div>
    <div class="card-body p-24">
        <form method="POST" action="">
            <?php echo campo_csrf(); ?>
            <input type="hidden" name="acao" value="salvar_config">
            
            <div class="row gy-4">
                <div class="col-12">
                    <div class="border radius-10 p-20 bg-base-soft d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <h6 class="mb-1 fw-semibold text-primary-600">Status do Pagamento Online</h6>
                            <p class="mb-0 text-secondary-light">
                                <strong>Passos para ficar ativo:</strong> credenciais válidas + toggle "Ativar PIX Online" habilitado.
                            </p>
                        </div>
                        <?php if ($mp_config['ativo'] && !empty($mp_config['access_token'])): ?>
                        <span class="badge bg-success-600 px-3 py-2">
                            <i class="fa-solid fa-circle-check me-1"></i> PIX Online disponível no checkout
                        </span>
                        <?php
else: ?>
                        <span class="badge bg-secondary px-3 py-2">
                            <i class="fa-solid fa-circle-xmark me-1"></i> Indisponível
                        </span>
                        <?php
endif; ?>
                    </div>
                </div>

                
                <div class="col-12">
                    <hr class="my-1">
                    <h6 class="text-uppercase text-primary-600 fw-semibold small mb-3">Disponibilidade no Checkout</h6>
                </div>

                <div class="col-lg-4">
                    <div class="border radius-8 p-16 h-100">
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="ativo" 
                                   id="mp_ativo"
                                   <?php echo $mp_config['ativo'] ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-semibold" for="mp_ativo">
                                Ativar PIX Online
                            </label>
                        </div>
                        <small class="text-secondary-light">Mostra o Mercado Pago como opção no checkout assim que as credenciais estiverem completas.</small>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="border radius-8 p-16 h-100">
                        <label for="nome" class="form-label fw-semibold">Nome exibido no checkout *</label>
                        <input type="text" 
                               class="form-control" 
                               name="nome" 
                               id="nome"
                               value="<?php echo htmlspecialchars($mp_config['nome']); ?>"
                               maxlength="100"
                               placeholder="PIX Online"
                               required>
                        <small class="text-secondary-light">Aparece para o cliente nos cartões de pagamento online.</small>
                    </div>
                </div>

                <div class="col-12">
                    <hr class="my-1">
                    <h6 class="text-uppercase text-primary-600 fw-semibold small mb-3">Credenciais do Mercado Pago</h6>
                </div>

                <div class="col-12 col-lg-6">
                    <label for="public_key" class="form-label fw-semibold">Public Key *</label>
                    <input type="text" 
                           class="form-control" 
                           name="public_key" 
                           id="public_key"
                           value="<?php echo htmlspecialchars($mp_config['public_key']); ?>"
                           placeholder="APP_USR-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                           required>
                    <small class="text-secondary-light">Obtida no painel de desenvolvedor → Credenciais.</small>
                </div>

                <div class="col-12 col-lg-6">
                    <label for="access_token" class="form-label fw-semibold">Access Token *</label>
                    <input type="password" 
                           class="form-control" 
                           name="access_token" 
                           id="access_token"
                           value="<?php echo htmlspecialchars($mp_config['access_token']); ?>"
                           placeholder="APP_USR-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                           required>
                    <small class="text-secondary-light">Access Token da aplicação Mercado Pago.</small>
                </div>

                <div class="col-12">
                    <hr class="my-1">
                    <h6 class="text-uppercase text-primary-600 fw-semibold small mb-3">Opções Avançadas</h6>
                </div>

                <div class="col-md-4">
                    <div class="border radius-8 p-16 h-100">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="sandbox_mode" 
                                   id="mp_sandbox"
                                   <?php echo $mp_config['sandbox_mode'] ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-semibold" for="mp_sandbox">
                                Modo Sandbox (testes)
                            </label>
                        </div>
                        <small class="text-secondary-light">Desative em produção para cobrar clientes reais.</small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="border radius-8 p-16 h-100">
                        <label for="prazo_pagamento_minutos" class="form-label fw-semibold">Prazo para pagamento *</label>
                        <input type="number" 
                               class="form-control" 
                               name="prazo_pagamento_minutos" 
                               id="prazo_pagamento_minutos"
                               value="<?php echo htmlspecialchars($mp_config['prazo_pagamento_minutos']); ?>"
                               min="5"
                               max="120"
                               required>
                        <small class="text-secondary-light">Tempo em minutos que o cliente tem para pagar via PIX.</small>
                    </div>
                </div>

                
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary-600 radius-8 px-24 py-11 d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:floppy-disk-bold-duotone" class="icon"></iconify-icon>
                        Salvar configuração
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Card de Mensagens Editáveis -->
<div class="card h-100 p-0 radius-12 mb-24">
    <div class="card-header border-bottom bg-base py-16 px-24">
        <h6 class="text-lg fw-semibold mb-0 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:chat-line-bold-duotone" class="text-warning-600"></iconify-icon>
            Mensagens do WhatsApp
        </h6>
    </div>
    <div class="card-body p-24">
        <p class="text-secondary-light mb-3">
            Personalize as mensagens enviadas via WhatsApp em cada etapa do pagamento online.
        </p>

        <a href="mercadopago_mensagens.php" class="btn btn-warning-600 radius-8 px-20 py-11 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:pen-bold-duotone" class="icon"></iconify-icon>
            Editar Mensagens
        </a>

        <!-- Preview das mensagens -->
        <div class="row g-3 mt-3">
            <?php
// Buscar mensagens para preview
$stmt = $pdo->query("SELECT * FROM mercadopago_mensagens WHERE tipo IN ('aguardando_pagamento', 'pagamento_recebido')");
$mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($mensagens as $msg):
?>
            <div class="col-md-6">
                <div class="card border">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0"><?php echo htmlspecialchars($msg['titulo']); ?></h6>
                            <?php if ($msg['ativo']): ?>
                            <span class="badge bg-success">Ativo</span>
                            <?php
    else: ?>
                            <span class="badge bg-danger">Inativo</span>
                            <?php
    endif; ?>
                        </div>
                        <small class="text-secondary-light d-block mb-2">
                            Tipo: <code><?php echo htmlspecialchars($msg['tipo']); ?></code>
                        </small>
                        <p class="text-sm mb-0" style="white-space: pre-wrap; max-height: 100px; overflow: hidden;"><?php echo htmlspecialchars($msg['mensagem']); ?></p>
                    </div>
                </div>
            </div>
            <?php
endforeach; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>