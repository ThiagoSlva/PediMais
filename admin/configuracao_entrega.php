<?php
include 'includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';

verificar_login();

$msg = '';
$msg_tipo = '';

// Migration Logic
try {
    // configuracao_entrega
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracao_entrega (
        id INT AUTO_INCREMENT PRIMARY KEY,
        modo_gratis_valor_ativo TINYINT(1) DEFAULT 0,
        valor_minimo_gratis DECIMAL(10,2) DEFAULT 0.00,
        modo_gratis_todos_ativo TINYINT(1) DEFAULT 0,
        modo_valor_fixo_ativo TINYINT(1) DEFAULT 0,
        valor_fixo_entrega DECIMAL(10,2) DEFAULT 0.00,
        modo_por_bairro_ativo TINYINT(1) DEFAULT 1,
        aceita_retirada TINYINT(1) DEFAULT 1,
        taxa_retirada DECIMAL(10,2) DEFAULT 0.00
    )");

    // Ensure initial record
    $stmt = $pdo->query("SELECT id FROM configuracao_entrega LIMIT 1");
    if (!$stmt->fetch()) {
        $pdo->exec("INSERT INTO configuracao_entrega (modo_por_bairro_ativo, aceita_retirada) VALUES (1, 1)");
    }

    // cidades
    $pdo->exec("CREATE TABLE IF NOT EXISTS cidades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        estado VARCHAR(2) NOT NULL,
        ativo TINYINT(1) DEFAULT 1
    )");

    // bairros
    $pdo->exec("CREATE TABLE IF NOT EXISTS bairros (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cidade_id INT NOT NULL,
        nome VARCHAR(100) NOT NULL,
        valor_entrega DECIMAL(10,2) DEFAULT 0.00,
        gratis_acima_de DECIMAL(10,2) DEFAULT NULL,
        entrega_disponivel TINYINT(1) DEFAULT 1,
        FOREIGN KEY (cidade_id) REFERENCES cidades(id) ON DELETE CASCADE
    )");

} catch (PDOException $e) {
    // Ignore if tables exist
}

// Process Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['acao'])) {
            $acao = $_POST['acao'];

            if ($acao == 'config') {
                $modo_gratis_valor_ativo = isset($_POST['modo_gratis_valor_ativo']) ? 1 : 0;
                $valor_minimo_gratis = converterPreco($_POST['valor_minimo_gratis']);
                $modo_gratis_todos_ativo = isset($_POST['modo_gratis_todos_ativo']) ? 1 : 0;
                $modo_valor_fixo_ativo = isset($_POST['modo_valor_fixo_ativo']) ? 1 : 0;
                $valor_fixo_entrega = converterPreco($_POST['valor_fixo_entrega']);
                $modo_por_bairro_ativo = isset($_POST['modo_por_bairro_ativo']) ? 1 : 0;
                $aceita_retirada = isset($_POST['aceita_retirada']) ? 1 : 0;
                $taxa_retirada = converterPreco($_POST['taxa_retirada']);

                $stmt = $pdo->prepare("UPDATE configuracao_entrega SET 
                    modo_gratis_valor_ativo = ?, valor_minimo_gratis = ?,
                    modo_gratis_todos_ativo = ?,
                    modo_valor_fixo_ativo = ?, valor_fixo_entrega = ?,
                    modo_por_bairro_ativo = ?,
                    aceita_retirada = ?, taxa_retirada = ?
                    WHERE id = 1");
                $stmt->execute([
                    $modo_gratis_valor_ativo, $valor_minimo_gratis,
                    $modo_gratis_todos_ativo,
                    $modo_valor_fixo_ativo, $valor_fixo_entrega,
                    $modo_por_bairro_ativo,
                    $aceita_retirada, $taxa_retirada
                ]);
                $msg = 'Configurações de entrega atualizadas!';
                $msg_tipo = 'success';

            } elseif ($acao == 'save_cidade') {
                $nome = $_POST['cidade_nome'];
                $estado = strtoupper($_POST['cidade_estado']);
                $ativo = isset($_POST['cidade_ativa']) ? 1 : 0;
                $id = isset($_POST['cidade_id']) ? (int)$_POST['cidade_id'] : 0;

                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE cidades SET nome = ?, estado = ?, ativo = ? WHERE id = ?");
                    $stmt->execute([$nome, $estado, $ativo, $id]);
                    $msg = 'Cidade atualizada!';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO cidades (nome, estado, ativo) VALUES (?, ?, ?)");
                    $stmt->execute([$nome, $estado, $ativo]);
                    $msg = 'Cidade adicionada!';
                }
                $msg_tipo = 'success';

            } elseif ($acao == 'delete_cidade') {
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM cidades WHERE id = ?");
                $stmt->execute([$id]);
                $msg = 'Cidade excluída!';
                $msg_tipo = 'success';

            } elseif ($acao == 'add_bairro') {
                $nome = $_POST['bairro_nome'];
                $cidade_id = (int)$_POST['cidade_id'];
                $valor = converterPreco($_POST['bairro_valor']);
                $gratis_acima = !empty($_POST['gratis_acima_valor']) ? converterPreco($_POST['gratis_acima_valor']) : NULL;
                $disponivel = isset($_POST['entrega_disponivel']) ? 1 : 0;

                $stmt = $pdo->prepare("INSERT INTO bairros (cidade_id, nome, valor_entrega, gratis_acima_de, entrega_disponivel) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$cidade_id, $nome, $valor, $gratis_acima, $disponivel]);
                $msg = 'Bairro adicionado!';
                $msg_tipo = 'success';

            } elseif ($acao == 'delete_bairro') {
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM bairros WHERE id = ?");
                $stmt->execute([$id]);
                $msg = 'Bairro excluído!';
                $msg_tipo = 'success';
            }
        }
    } catch (PDOException $e) {
        $msg = 'Erro ao salvar: ' . $e->getMessage();
        $msg_tipo = 'danger';
    }
}

// Fetch Data
$stmt = $pdo->query("SELECT * FROM configuracao_entrega LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT * FROM cidades ORDER BY nome ASC");
$cidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT b.*, c.nome as cidade_nome, c.estado as cidade_estado 
                     FROM bairros b 
                     JOIN cidades c ON b.cidade_id = c.id 
                     ORDER BY c.nome ASC, b.nome ASC");
$bairros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if editing a city
$edit_cidade = null;
if (isset($_GET['editar_cidade'])) {
    $id = (int)$_GET['editar_cidade'];
    $stmt = $pdo->prepare("SELECT * FROM cidades WHERE id = ?");
    $stmt->execute([$id]);
    $edit_cidade = $stmt->fetch(PDO::FETCH_ASSOC);
}

include 'includes/header.php';
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Configuração de Entrega</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Entrega</li>
        </ul>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_tipo; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <style>
    /* Dark mode specific styles for configuracao_entrega page */
    [data-theme="dark"] .card,
    html[data-theme="dark"] .card {
        background-color: var(--base-soft, #1e1e2e) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: rgba(255, 255, 255, 0.9) !important;
    }
    </style>

    <form method="POST">
        <input type="hidden" name="acao" value="config">
        
        <!-- Modo 1: Grátis a partir de Valor -->
        <div class="card radius-12 mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:tag-price-outline"></iconify-icon>
                    Modo 1: Entrega Grátis a partir de X valor
                </h6>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="modo_gratis_valor_ativo" id="modo1" 
                           <?php echo $config['modo_gratis_valor_ativo'] ? 'checked' : ''; ?>
                           onchange="toggleModo('modo1-config')">
                </div>
            </div>
            <div class="card-body p-24" id="modo1-config" style="display: <?php echo $config['modo_gratis_valor_ativo'] ? 'block' : 'none'; ?>;">
                <div class="alert alert-info d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:info-circle-outline"></iconify-icon>
                    Entrega gratuita quando o valor do pedido for igual ou superior ao valor definido
                </div>
                <label class="form-label fw-semibold">Valor Mínimo para Entrega Grátis</label>
                <div class="input-group">
                    <span class="input-group-text">R$</span>
                    <input type="text" class="form-control money-mask" name="valor_minimo_gratis" 
                           value="<?php echo number_format($config['valor_minimo_gratis'], 2, ',', '.'); ?>" 
                           placeholder="50,00">
                </div>
            </div>
        </div>
        
        <!-- Modo 2: Grátis para Todos -->
        <div class="card radius-12 mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:gift-outline"></iconify-icon>
                    Modo 2: Entrega Grátis para Todos
                </h6>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="modo_gratis_todos_ativo" id="modo2" 
                           <?php echo $config['modo_gratis_todos_ativo'] ? 'checked' : ''; ?>>
                </div>
            </div>
            <div class="card-body p-24" style="display: none;">
                <div class="alert alert-success d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:check-circle-outline"></iconify-icon>
                    Entrega totalmente gratuita para todos os pedidos
                </div>
            </div>
        </div>
        
        <!-- Modo 3: Valor Fixo -->
        <div class="card radius-12 mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:dollar-outline"></iconify-icon>
                    Modo 3: Valor Fixo Único
                </h6>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="modo_valor_fixo_ativo" id="modo3" 
                           <?php echo $config['modo_valor_fixo_ativo'] ? 'checked' : ''; ?>
                           onchange="toggleModo('modo3-config')">
                </div>
            </div>
            <div class="card-body p-24" id="modo3-config" style="display: <?php echo $config['modo_valor_fixo_ativo'] ? 'block' : 'none'; ?>;">
                <div class="alert alert-info d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:info-circle-outline"></iconify-icon>
                    Valor único cobrado para todas as entregas
                </div>
                <label class="form-label fw-semibold">Valor da Entrega</label>
                <div class="input-group">
                    <span class="input-group-text">R$</span>
                    <input type="text" class="form-control money-mask" name="valor_fixo_entrega" 
                           value="<?php echo number_format($config['valor_fixo_entrega'], 2, ',', '.'); ?>" 
                           placeholder="5,00">
                </div>
            </div>
        </div>
        
        <!-- Modo 5: Por Bairro -->
        <div class="card radius-12 mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:city-outline"></iconify-icon>
                    Modo 5: Por Bairro/Região
                </h6>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="modo_por_bairro_ativo" id="modo5" 
                           <?php echo $config['modo_por_bairro_ativo'] ? 'checked' : ''; ?> 
                           onchange="toggleModo('modo5-config')">
                </div>
            </div>
            <div class="card-body p-24" id="modo5-config" style="display: <?php echo $config['modo_por_bairro_ativo'] ? 'block' : 'none'; ?>;">
                <div class="alert alert-warning d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:info-circle-outline"></iconify-icon>
                    Entrega restrita apenas aos bairros cadastrados. Gerencie cidades e bairros abaixo.
                </div>
            </div>
        </div>
        
        <!-- Retirada no Balcão -->
        <div class="card radius-12 mb-3">
            <div class="card-header">
                <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:shop-outline"></iconify-icon>
                    Retirada no Balcão
                </h6>
            </div>
            <div class="card-body p-24">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="aceita_retirada" id="aceita_retirada" 
                           <?php echo $config['aceita_retirada'] ? 'checked' : ''; ?> onchange="toggleTaxaRetirada()">
                    <label class="form-check-label fw-semibold">Aceitar Retirada no Balcão?</label>
                </div>
                
                <div id="taxa-retirada-div" style="display: <?php echo $config['aceita_retirada'] ? 'block' : 'none'; ?>;">
                    <label class="form-label">Taxa para Retirada (Opcional)</label>
                    <div class="input-group">
                        <span class="input-group-text">R$</span>
                        <input type="text" class="form-control money-mask" name="taxa_retirada" id="taxa_retirada" 
                               value="<?php echo number_format($config['taxa_retirada'], 2, ',', '.'); ?>" 
                               placeholder="0,00">
                    </div>
                    <small class="text-secondary-light">Deixe 0,00 para retirada gratuita</small>
                </div>
            </div>
        </div>
        
        <!-- Botão Salvar Config -->
        <div class="text-end mb-4">
            <button type="submit" class="btn btn-primary btn-lg d-flex align-items-center gap-2">
                <iconify-icon icon="solar:check-circle-outline"></iconify-icon>
                Salvar Configurações
            </button>
        </div>
    </form>

    <!-- Gerenciar Cidades e Bairros -->
    <div class="row">
        <!-- Gerenciar Cidades -->
        <div class="col-md-5 mb-3">
            <div class="card radius-12">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:city-outline"></iconify-icon>
                        <?php echo $edit_cidade ? 'Editar Cidade' : 'Adicionar Cidade'; ?>
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="acao" value="save_cidade">
                        <input type="hidden" name="cidade_id" value="<?php echo $edit_cidade ? $edit_cidade['id'] : '0'; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nome da Cidade <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="cidade_nome" value="<?php echo $edit_cidade ? htmlspecialchars($edit_cidade['nome']) : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Estado (UF) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="cidade_estado" value="<?php echo $edit_cidade ? htmlspecialchars($edit_cidade['estado']) : ''; ?>" maxlength="2" required placeholder="MG">
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="cidade_ativa" <?php echo (!$edit_cidade || $edit_cidade['ativo']) ? 'checked' : ''; ?>>
                                <label class="form-check-label">Ativa</label>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                                <iconify-icon icon="solar:check-circle-outline"></iconify-icon>
                                <?php echo $edit_cidade ? 'Atualizar' : 'Adicionar'; ?>
                            </button>
                            <?php if ($edit_cidade): ?>
                                <a href="configuracao_entrega.php" class="btn btn-outline-secondary">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Lista de Cidades -->
            <div class="card radius-12 mt-3">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold">Cidades Cadastradas (<?php echo count($cidades); ?>)</h6>
                </div>
                <div class="card-body p-24">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Cidade</th>
                                    <th>UF</th>
                                    <th>Status</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cidades as $c): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($c['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($c['estado']); ?></td>
                                    <td>
                                        <?php if ($c['ativo']): ?>
                                            <span class="badge bg-success">Ativa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inativa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="?editar_cidade=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                                <iconify-icon icon="lucide:edit"></iconify-icon>
                                            </a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Excluir esta cidade? Todos os bairros vinculados serão excluídos também.')">
                                                <input type="hidden" name="acao" value="delete_cidade">
                                                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                                    <iconify-icon icon="mingcute:delete-2-line"></iconify-icon>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gerenciar Bairros -->
        <div class="col-md-7 mb-3">
            <div class="card radius-12">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:map-outline"></iconify-icon>
                        Adicionar Bairro/Região
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="acao" value="add_bairro">
                        
                        <div class="row">
                            <div class="col-md-5 mb-3">
                                <label class="form-label fw-semibold">Nome do Bairro <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="bairro_nome" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Cidade <span class="text-danger">*</span></label>
                                <select class="form-select" name="cidade_id" required>
                                    <option value="">Selecione</option>
                                    <?php foreach ($cidades as $c): ?>
                                        <option value="<?php echo $c['id']; ?>">
                                            <?php echo htmlspecialchars($c['nome'] . ' - ' . $c['estado']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-semibold">Valor (R$) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control money-mask" name="bairro_valor" placeholder="5,00" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Grátis Acima de (Opcional)</label>
                                <input type="text" class="form-control money-mask" name="gratis_acima_valor" placeholder="50,00">
                                <small class="text-secondary-light">Deixe vazio se não houver</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label d-block fw-semibold">&nbsp;</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="entrega_disponivel" checked>
                                    <label class="form-check-label">Entrega Disponível</label>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                                    <iconify-icon icon="solar:add-circle-outline"></iconify-icon>
                                    Adicionar Bairro
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Lista de Bairros -->
            <div class="card radius-12 mt-3">
                <div class="card-header">
                    <h6 class="mb-0 fw-semibold">Bairros Cadastrados (<?php echo count($bairros); ?>)</h6>
                </div>
                <div class="card-body p-24">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Bairro</th>
                                    <th>Cidade</th>
                                    <th>Valor</th>
                                    <th>Grátis Acima</th>
                                    <th>Entrega</th>
                                    <th class="text-center">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bairros as $b): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($b['nome']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($b['cidade_nome'] . ' - ' . $b['cidade_estado']); ?></td>
                                    <td>R$ <?php echo number_format($b['valor_entrega'], 2, ',', '.'); ?></td>
                                    <td>
                                        <?php echo $b['gratis_acima_de'] ? 'R$ ' . number_format($b['gratis_acima_de'], 2, ',', '.') : '<span class="text-secondary">-</span>'; ?>
                                    </td>
                                    <td>
                                        <?php if ($b['entrega_disponivel']): ?>
                                            <span class="badge bg-success">Sim</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Não</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Excluir este bairro?')">
                                            <input type="hidden" name="acao" value="delete_bairro">
                                            <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <iconify-icon icon="mingcute:delete-2-line"></iconify-icon>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle modo
function toggleModo(id) {
    const element = document.getElementById(id);
    if (element) {
        const checkbox = document.getElementById(id.replace('-config', ''));
        element.style.display = checkbox.checked ? 'block' : 'none';
    }
}

// Toggle taxa de retirada
function toggleTaxaRetirada() {
    const aceita = document.getElementById('aceita_retirada').checked;
    document.getElementById('taxa-retirada-div').style.display = aceita ? 'block' : 'none';
}

// Máscaras
document.addEventListener('DOMContentLoaded', function() {
    // Máscara moeda
    const moneyInputs = document.querySelectorAll('.money-mask');
    moneyInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value) {
                value = (parseInt(value) / 100).toFixed(2);
                e.target.value = value.replace('.', ',');
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>