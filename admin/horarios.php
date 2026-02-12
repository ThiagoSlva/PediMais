<?php
include 'includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';
require_once '../includes/csrf.php';

verificar_login();

$msg = '';
$msg_tipo = '';

// Migration Logic
try {
    // Ensure table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracao_horarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sistema_ativo TINYINT(1) DEFAULT 1,
        aberto_manual TINYINT(1) DEFAULT NULL,
        mensagem_fechado TEXT
    )");

    // Ensure columns exist and have correct types
    $stmt = $pdo->query("DESCRIBE configuracao_horarios");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('sistema_ativo', $columns)) {
        $pdo->exec("ALTER TABLE configuracao_horarios ADD COLUMN sistema_ativo TINYINT(1) DEFAULT 1");
    }

    // Ensure aberto_manual allows NULL
    if (in_array('aberto_manual', $columns)) {
        $pdo->exec("ALTER TABLE configuracao_horarios MODIFY aberto_manual TINYINT(1) DEFAULT NULL");
    }
    else {
        $pdo->exec("ALTER TABLE configuracao_horarios ADD COLUMN aberto_manual TINYINT(1) DEFAULT NULL");
    }

    // Ensure initial record exists
    $stmt = $pdo->query("SELECT id FROM configuracao_horarios LIMIT 1");
    if (!$stmt->fetch()) {
        $pdo->exec("INSERT INTO configuracao_horarios (sistema_ativo, aberto_manual, mensagem_fechado) VALUES (1, NULL, 'Estamos fechados no momento.')");
    }

    // Ensure horarios_funcionamento table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS horarios_funcionamento (
        id INT AUTO_INCREMENT PRIMARY KEY,
        dia_semana INT NOT NULL,
        horario_abertura TIME,
        horario_fechamento TIME,
        ativo TINYINT(1) DEFAULT 1
    )");


}
catch (PDOException $e) {
// Ignore errors if tables already exist/columns match
}

// Process Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validar_csrf()) {
        $msg = 'Token de segurança inválido. Recarregue a página.';
        $msg_tipo = 'danger';
    }
    else {
        try {
            if (isset($_POST['acao'])) {
                $acao = $_POST['acao'];

                if ($acao == 'config') {
                    $sistema_ativo = isset($_POST['sistema_ativo']) ? 1 : 0;
                    $mensagem_fechado = $_POST['mensagem_fechado'];

                    $stmt = $pdo->prepare("UPDATE configuracao_horarios SET sistema_ativo = ?, mensagem_fechado = ? WHERE id = 1");
                    $stmt->execute([$sistema_ativo, $mensagem_fechado]);

                    $msg = 'Configuração geral atualizada!';
                    $msg_tipo = 'success';

                }
                elseif ($acao == 'manual') {
                    $manual = $_POST['aberto_manual'];
                    $valor = null;

                    if ($manual === '1')
                        $valor = 1;
                    elseif ($manual === '0')
                        $valor = 0;
                    elseif ($manual === 'automatico')
                        $valor = null;

                    $stmt = $pdo->prepare("UPDATE configuracao_horarios SET aberto_manual = ? WHERE id = 1");
                    $stmt->execute([$valor]);

                    $msg = 'Status manual atualizado!';
                    $msg_tipo = 'success';

                }
                elseif ($acao == 'horario') {
                    $dia = $_POST['dia_semana'];
                    $abertura = $_POST['horario_abertura'];
                    $fechamento = $_POST['horario_fechamento'];
                    $ativo = isset($_POST['ativo']) ? 1 : 0;

                    // Check if exists
                    $stmt = $pdo->prepare("SELECT id FROM horarios_funcionamento WHERE dia_semana = ?");
                    $stmt->execute([$dia]);

                    if ($stmt->fetch()) {
                        $stmt = $pdo->prepare("UPDATE horarios_funcionamento SET horario_abertura = ?, horario_fechamento = ?, ativo = ? WHERE dia_semana = ?");
                        $stmt->execute([$abertura, $fechamento, $ativo, $dia]);
                    }
                    else {
                        $stmt = $pdo->prepare("INSERT INTO horarios_funcionamento (dia_semana, horario_abertura, horario_fechamento, ativo) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$dia, $abertura, $fechamento, $ativo]);
                    }

                    $msg = 'Horário atualizado com sucesso!';
                    $msg_tipo = 'success';
                }
            }
        }
        catch (PDOException $e) {
            $msg = 'Erro ao salvar: ' . $e->getMessage();
            $msg_tipo = 'danger';
        }
    } // fecha else validar_csrf
}

// Fetch Data
$stmt = $pdo->query("SELECT * FROM configuracao_horarios LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM horarios_funcionamento ORDER BY dia_semana ASC");
$horarios_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
$horarios = [];
foreach ($horarios_db as $h) {
    $horarios[$h['dia_semana']] = $h;
}

$dias_semana = [
    0 => 'Domingo',
    1 => 'Segunda-feira',
    2 => 'Terça-feira',
    3 => 'Quarta-feira',
    4 => 'Quinta-feira',
    5 => 'Sexta-feira',
    6 => 'Sábado'
];

include 'includes/header.php';
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Horários de Atendimento</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Horários</li>
        </ul>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_tipo; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php
endif; ?>

    <style>
    /* Dark mode specific styles for horarios page */
    [data-theme="dark"] .card,
    html[data-theme="dark"] .card {
        background-color: var(--base-soft, #1e1e2e) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: rgba(255, 255, 255, 0.9) !important;
    }
    
    [data-theme="dark"] div.card-header,
    [data-theme="dark"] .card > .card-header,
    [data-theme="dark"] .card .card-header,
    [data-theme="dark"] .card-header.bg-base,
    [data-theme="dark"] .card-header.border-bottom,
    [data-theme="dark"] .card-header:not([class*="bg-"]),
    html[data-theme="dark"] div.card-header,
    html[data-theme="dark"] .card > .card-header,
    html[data-theme="dark"] .card .card-header,
    html[data-theme="dark"] .card-header.bg-base,
    html[data-theme="dark"] .card-header.border-bottom,
    html[data-theme="dark"] .card-header:not([class*="bg-"]) {
        background-color: #1a1a2e !important;
        background: #1a1a2e !important;
        background-image: none !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: rgba(255, 255, 255, 0.9) !important;
    }

    [data-theme="dark"] .card-header h6,
    html[data-theme="dark"] .card-header h6,
    [data-theme="dark"] .card-header .fw-semibold,
    html[data-theme="dark"] .card-header .fw-semibold,
    [data-theme="dark"] .card-header iconify-icon,
    html[data-theme="dark"] .card-header iconify-icon {
        color: rgba(255, 255, 255, 0.9) !important;
        background-color: transparent !important;
        background: transparent !important;
    }

    [data-theme="dark"] .card-header.bg-warning-focus,
    html[data-theme="dark"] .card-header.bg-warning-focus {
        background-color: rgba(255, 193, 7, 0.1) !important;
        border-color: rgba(255, 193, 7, 0.2) !important;
    }

    [data-theme="dark"] .card-header.bg-warning-focus h6,
    html[data-theme="dark"] .card-header.bg-warning-focus h6 {
        color: #ffc107 !important;
    }

    [data-theme="dark"] .form-label,
    html[data-theme="dark"] .form-label,
    [data-theme="dark"] .form-check-label,
    html[data-theme="dark"] .form-check-label,
    [data-theme="dark"] label,
    html[data-theme="dark"] label {
        color: rgba(255, 255, 255, 0.9) !important;
    }

    [data-theme="dark"] .form-control,
    html[data-theme="dark"] .form-control,
    [data-theme="dark"] textarea.form-control,
    html[data-theme="dark"] textarea.form-control {
        background-color: rgba(255, 255, 255, 0.05) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: rgba(255, 255, 255, 0.9) !important;
    }

    [data-theme="dark"] .form-control:focus,
    html[data-theme="dark"] .form-control:focus,
    [data-theme="dark"] textarea.form-control:focus,
    html[data-theme="dark"] textarea.form-control:focus {
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

    [data-theme="dark"] strong,
    html[data-theme="dark"] strong,
    [data-theme="dark"] h6,
    html[data-theme="dark"] h6 {
        color: rgba(255, 255, 255, 0.9) !important;
    }

    [data-theme="dark"] .text-secondary-light,
    html[data-theme="dark"] .text-secondary-light,
    [data-theme="dark"] small.text-secondary-light,
    html[data-theme="dark"] small.text-secondary-light {
        color: rgba(255, 255, 255, 0.6) !important;
    }

    [data-theme="dark"] .badge.bg-success,
    html[data-theme="dark"] .badge.bg-success {
        background-color: #28a745 !important;
        color: #fff !important;
    }

    [data-theme="dark"] .badge.bg-secondary,
    html[data-theme="dark"] .badge.bg-secondary {
        background-color: #6c757d !important;
        color: #fff !important;
    }

    [data-theme="dark"] .accordion-button,
    html[data-theme="dark"] .accordion-button {
        background-color: var(--base-soft, #1e1e2e) !important;
        color: rgba(255, 255, 255, 0.9) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-theme="dark"] .accordion-button:not(.collapsed),
    html[data-theme="dark"] .accordion-button:not(.collapsed) {
        background-color: rgba(72, 127, 255, 0.1) !important;
        color: rgba(255, 255, 255, 0.9) !important;
        box-shadow: none;
    }

    [data-theme="dark"] .accordion-button strong,
    html[data-theme="dark"] .accordion-button strong {
        color: rgba(255, 255, 255, 0.9) !important;
    }

    [data-theme="dark"] .accordion-body,
    html[data-theme="dark"] .accordion-body {
        background-color: var(--base-soft, #1e1e2e) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: rgba(255, 255, 255, 0.8) !important;
    }

    [data-theme="dark"] .accordion-item,
    html[data-theme="dark"] .accordion-item {
        border-color: rgba(255, 255, 255, 0.1) !important;
        background-color: var(--base-soft, #1e1e2e) !important;
    }

    [data-theme="dark"] .alert-warning,
    html[data-theme="dark"] .alert-warning {
        background-color: rgba(255, 193, 7, 0.1) !important;
        color: #fff !important;
        border-color: rgba(255, 193, 7, 0.2) !important;
    }

    [data-theme="dark"] .alert-warning strong,
    html[data-theme="dark"] .alert-warning strong {
        color: #fff !important;
    }

    [data-theme="dark"] .alert-success,
    html[data-theme="dark"] .alert-success {
        background-color: rgba(40, 167, 69, 0.1) !important;
        color: #fff !important;
        border-color: rgba(40, 167, 69, 0.2) !important;
    }

    [data-theme="dark"] .alert-danger,
    html[data-theme="dark"] .alert-danger {
        background-color: rgba(220, 53, 69, 0.1) !important;
        color: #fff !important;
        border-color: rgba(220, 53, 69, 0.2) !important;
    }

    [data-theme="dark"] .bg-success-focus,
    html[data-theme="dark"] .bg-success-focus {
        background-color: rgba(40, 167, 69, 0.1) !important;
    }

    [data-theme="dark"] .bg-danger-focus,
    html[data-theme="dark"] .bg-danger-focus {
        background-color: rgba(220, 53, 69, 0.1) !important;
    }

    [data-theme="dark"] .text-success-600,
    html[data-theme="dark"] .text-success-600 {
        color: #28a745 !important;
    }

    [data-theme="dark"] .text-danger-600,
    html[data-theme="dark"] .text-danger-600 {
        color: #dc3545 !important;
    }

    [data-theme="dark"] .text-warning-600,
    html[data-theme="dark"] .text-warning-600 {
        color: #ffc107 !important;
    }
    </style>

    <!-- Configuração Geral -->
    <div class="card mb-3 radius-12">
        <div class="card-header">
            <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                <iconify-icon icon="solar:settings-outline"></iconify-icon>
                Configuração Geral
            </h6>
        </div>
        <div class="card-body p-24">
            <form method="POST">
                <?php echo campo_csrf(); ?>
                <input type="hidden" name="acao" value="config">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="d-flex align-items-center justify-content-between p-3 border rounded">
                            <div>
                                <h6 class="mb-1">Sistema de Horários</h6>
                                <small class="text-secondary-light">
                                    <?php echo($config['sistema_ativo'] ?? 1) ? 'Ativado - segue horários abaixo' : 'Desativado - sempre aberto'; ?>
                                </small>
                            </div>
                            <div class="form-switch">
                                <input class="form-check-input" type="checkbox" name="sistema_ativo" id="sistema_ativo" 
                                       <?php echo($config['sistema_ativo'] ?? 1) ? 'checked' : ''; ?>
                                       onchange="this.form.submit()">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Mensagem quando Fechado</label>
                        <textarea class="form-control" name="mensagem_fechado" rows="2" 
                                  placeholder="Ex: Estamos fechados..."><?php echo htmlspecialchars($config['mensagem_fechado'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                            <iconify-icon icon="solar:check-circle-outline"></iconify-icon>
                            Salvar Configuração
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Controle Manual -->
    <div class="card mb-3 radius-12">
        <div class="card-header bg-warning-focus">
            <h6 class="mb-0 fw-semibold text-warning-600 d-flex align-items-center gap-2">
                <iconify-icon icon="solar:hand-shake-outline"></iconify-icon>
                Controle Manual (Prioridade sobre Horários)
            </h6>
        </div>
        <div class="card-body p-24">
            <form method="POST">
                <?php echo campo_csrf(); ?>
                <input type="hidden" name="acao" value="manual">
                
                <div class="alert alert-warning mb-3 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:info-circle-outline"></iconify-icon>
                    <div>
                        <strong>Atenção:</strong> O controle manual tem prioridade sobre os horários automáticos. 
                        Use "Modo Automático" para voltar ao normal.
                    </div>
                </div>
                
                <?php $loja_manual = isset($config['aberto_manual']) ? $config['aberto_manual'] : null; ?>
                <div class="d-flex gap-3 flex-wrap">
                    <button type="submit" name="aberto_manual" value="1" 
                            class="btn btn-success d-flex align-items-center gap-2 <?php echo($loja_manual === 1 || $loja_manual === '1') ? 'active' : ''; ?>">
                        <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                        <span>ABRIR Agora</span>
                    </button>
                    
                    <button type="submit" name="aberto_manual" value="0" 
                            class="btn btn-danger d-flex align-items-center gap-2 <?php echo($loja_manual === 0 || $loja_manual === '0') ? 'active' : ''; ?>">
                        <iconify-icon icon="solar:close-circle-bold"></iconify-icon>
                        <span>FECHAR Agora</span>
                    </button>
                    
                    <button type="submit" name="aberto_manual" value="automatico" 
                            class="btn btn-secondary d-flex align-items-center gap-2 <?php echo($loja_manual === null) ? 'active' : ''; ?>">
                        <iconify-icon icon="solar:refresh-outline"></iconify-icon>
                        <span>Modo Automático</span>
                    </button>
                </div>
                
                <?php if ($loja_manual === 1): ?>
                    <div class="mt-3 p-3 rounded bg-success-focus">
                        <strong>Status Atual:</strong> 
                        <span class="text-success-600">🟢 ABERTO MANUALMENTE</span>
                    </div>
                <?php
elseif ($loja_manual === 0): ?>
                    <div class="mt-3 p-3 rounded bg-danger-focus">
                        <strong>Status Atual:</strong> 
                        <span class="text-danger-600">🔴 FECHADO MANUALMENTE</span>
                    </div>
                <?php
else: ?>
                    <div class="mt-3 p-3 rounded bg-primary-focus">
                        <strong>Status Atual:</strong> 
                        <span class="text-primary-600">🔄 MODO AUTOMÁTICO (Segue horários)</span>
                    </div>
                <?php
endif; ?>
            </form>
        </div>
    </div>

    <!-- Horários por Dia -->
    <div class="card radius-12">
        <div class="card-header">
            <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                <iconify-icon icon="solar:calendar-outline"></iconify-icon>
                Horários de Funcionamento
            </h6>
        </div>
        <div class="card-body p-24">
            <div class="accordion" id="accordionHorarios">
                <?php foreach ($dias_semana as $dia_num => $dia_nome):
    $h = isset($horarios[$dia_num]) ? $horarios[$dia_num] : ['horario_abertura' => '00:00', 'horario_fechamento' => '00:00', 'ativo' => 0];
    $isOpen = $h['ativo'] ?? 0;
    $statusBadge = $isOpen ? '<span class="badge bg-success">Aberto</span>' : '<span class="badge bg-secondary">Fechado</span>';
?>
                <div class="accordion-item mb-2">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" 
                                type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#dia<?php echo $dia_num; ?>">
                            <div class="d-flex align-items-center gap-3 w-100">
                                <?php echo $statusBadge; ?>
                                <strong><?php echo $dia_nome; ?></strong>
                                <?php if ($isOpen): ?>
                                    <span class="text-secondary-light ms-auto me-3 text-sm">
                                        <?php echo substr($h['horario_abertura'] ?? '00:00', 0, 5) . ' às ' . substr($h['horario_fechamento'] ?? '00:00', 0, 5); ?>
                                    </span>
                                <?php
    endif; ?>
                            </div>
                        </button>
                    </h2>
                    <div id="dia<?php echo $dia_num; ?>" 
                         class="accordion-collapse collapse">
                        <div class="accordion-body">
                            <form method="POST">
                                <?php echo campo_csrf(); ?>
                                <input type="hidden" name="acao" value="horario">
                                <input type="hidden" name="dia_semana" value="<?php echo $dia_num; ?>">
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Horário de Abertura</label>
                                        <input type="time" class="form-control" name="horario_abertura" 
                                               value="<?php echo htmlspecialchars($h['horario_abertura'] ?? '00:00'); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label class="form-label">Horário de Fechamento</label>
                                        <input type="time" class="form-control" name="horario_fechamento" 
                                               value="<?php echo htmlspecialchars($h['horario_fechamento'] ?? '00:00'); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label class="form-label">Status</label>
                                        <div class="form-switch pt-2">
                                            <input class="form-check-input" type="checkbox" name="ativo" 
                                                   <?php echo $isOpen ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Funcionando neste dia</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12 mt-3">
                                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                                            <iconify-icon icon="solar:check-circle-outline"></iconify-icon>
                                            Salvar Horário
                                        </button>
                                    </div>
                                </div>
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

<?php include 'includes/footer.php'; ?>