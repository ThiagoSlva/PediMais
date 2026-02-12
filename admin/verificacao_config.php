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
    // Config table
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracao_verificacao (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ativo TINYINT(1) DEFAULT 1,
        tempo_expiracao INT DEFAULT 5,
        mensagem_codigo TEXT
    )");

    // Ensure initial record
    $stmt = $pdo->query("SELECT id FROM configuracao_verificacao LIMIT 1");
    if (!$stmt->fetch()) {
        $pdo->exec("INSERT INTO configuracao_verificacao (ativo, tempo_expiracao, mensagem_codigo) VALUES (1, 5, '')");
    }

    // Codes log table
    $pdo->exec("CREATE TABLE IF NOT EXISTS verificacao_codigos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT NOT NULL,
        codigo VARCHAR(6) NOT NULL,
        expira_em DATETIME NOT NULL,
        usado TINYINT(1) DEFAULT 0,
        data_envio DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Add column to clients if not exists
    $stmt = $pdo->query("DESCRIBE clientes");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('telefone_verificado', $columns)) {
        $pdo->exec("ALTER TABLE clientes ADD COLUMN telefone_verificado TINYINT(1) DEFAULT 0");
    }

}
catch (PDOException $e) {
// Ignore if tables exist
}

// Process Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validar_csrf()) {
        $msg = 'Token de segurança inválido. Recarregue a página.';
        $msg_tipo = 'danger';
    }
    else {
        try {
            if (isset($_POST['acao']) && $_POST['acao'] == 'salvar_config') {
                $ativo = isset($_POST['ativo']) ? 1 : 0;
                $tempo = (int)$_POST['tempo_expiracao'];
                $mensagem = $_POST['mensagem_codigo'];

                $stmt = $pdo->prepare("UPDATE configuracao_verificacao SET ativo = ?, tempo_expiracao = ?, mensagem_codigo = ? WHERE id = 1");
                $stmt->execute([$ativo, $tempo, $mensagem]);

                $msg = 'Configurações salvas com sucesso!';
                $msg_tipo = 'success';
            }
        }
        catch (PDOException $e) {
            $msg = 'Erro ao salvar: ' . $e->getMessage();
            $msg_tipo = 'danger';
        }
    } // fecha else validar_csrf
}

// Fetch Config
$stmt = $pdo->query("SELECT * FROM configuracao_verificacao LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch Stats
$stmt = $pdo->query("SELECT COUNT(*) FROM clientes WHERE telefone_verificado = 1");
$total_verificados = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM verificacao_codigos");
$total_codigos = $stmt->fetchColumn();

// Fetch Verified Clients (with search)
$where = "WHERE telefone_verificado = 1";
$params = [];

if (isset($_GET['busca_verificados']) && !empty($_GET['busca_verificados'])) {
    $busca = '%' . $_GET['busca_verificados'] . '%';
    $where .= " AND (nome LIKE ? OR telefone LIKE ? OR email LIKE ?)";
    $params = [$busca, $busca, $busca];
}

$stmt = $pdo->prepare("SELECT * FROM clientes $where ORDER BY id DESC LIMIT 50");
$stmt->execute($params);
$clientes_verificados = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h1 class="h3 mb-0">
                <iconify-icon icon="solar:shield-check-bold"></iconify-icon>
                Verificação de Primeiro Pedido
            </h1>
        </div>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Verificação</li>
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
    /* Dark mode support */
    [data-theme="dark"] .card,
    html[data-theme="dark"] .card {
        background-color: var(--base-soft, #1e1e2e) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    </style>

    <!-- Configuração Geral -->
    <div class="card mb-4 radius-12">
        <div class="card-header">
            <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                <iconify-icon icon="solar:settings-bold"></iconify-icon>
                Configurações
            </h6>
        </div>
        <div class="card-body p-24">
            <form method="POST">
                <?php echo campo_csrf(); ?>
                <input type="hidden" name="acao" value="salvar_config">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Status do Sistema</label>
                        <div class="form-check form-switch">
                            <input 
                                class="form-check-input" 
                                type="checkbox" 
                                name="ativo" 
                                id="ativo" 
                                value="1"
                                <?php echo $config['ativo'] ? 'checked' : ''; ?>
                            >
                            <label class="form-check-label" for="ativo">
                                <?php echo $config['ativo'] ? 'Ativo' : 'Inativo'; ?>
                            </label>
                        </div>
                        <small class="text-secondary-light">
                            Quando ativo, novos clientes precisarão verificar seu primeiro pedido via código WhatsApp.
                        </small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="tempo_expiracao" class="form-label fw-semibold">
                            Tempo de Expiração (minutos)
                        </label>
                        <input 
                            type="number" 
                            class="form-control" 
                            id="tempo_expiracao" 
                            name="tempo_expiracao" 
                            value="<?php echo $config['tempo_expiracao']; ?>"
                            min="1" 
                            max="60" 
                            required
                        >
                        <small class="text-secondary-light">
                            Tempo que o código de verificação permanece válido (1-60 minutos).
                        </small>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="mensagem_codigo" class="form-label fw-semibold">
                        Mensagem do Código WhatsApp
                    </label>
                    <textarea 
                        class="form-control" 
                        id="mensagem_codigo" 
                        name="mensagem_codigo" 
                        rows="6"
                        placeholder="🔐 *Código de Verificação*&#10;&#10;Seu código de verificação é: *{codigo}*&#10;&#10;Este código expira em {tempo} minutos.&#10;Digite este código para finalizar seu primeiro pedido."
                    ><?php echo htmlspecialchars($config['mensagem_codigo']); ?></textarea>
                    <small class="text-secondary-light">
                        Use <code>{codigo}</code> para o código de 6 dígitos e <code>{tempo}</code> para o tempo de expiração. Deixe em branco para usar mensagem padrão.
                    </small>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary-600">
                        <iconify-icon icon="solar:diskette-bold"></iconify-icon>
                        Salvar Configurações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Estatísticas -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card radius-12">
                <div class="card-body p-24">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-secondary-light mb-2">Clientes Verificados</h6>
                            <h3 class="mb-0"><?php echo $total_verificados; ?></h3>
                        </div>
                        <div class="text-primary-600" style="font-size: 3rem;">
                            <iconify-icon icon="solar:user-check-bold"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card radius-12">
                <div class="card-body p-24">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-secondary-light mb-2">Códigos Enviados</h6>
                            <h3 class="mb-0"><?php echo $total_codigos; ?></h3>
                        </div>
                        <div class="text-success-600" style="font-size: 3rem;">
                            <iconify-icon icon="solar:chat-round-check-bold"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Clientes Verificados -->
    <div class="card mb-4 radius-12">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                <iconify-icon icon="solar:users-group-rounded-bold"></iconify-icon>
                Clientes Verificados
            </h6>
            <span class="text-secondary-light text-sm"><?php echo count($clientes_verificados); ?> cliente(s)</span>
        </div>
        <div class="card-body p-24">
            <!-- Busca -->
            <form method="GET" class="mb-3">
                <div class="input-group">
                    <input 
                        type="text" 
                        name="busca_verificados" 
                        class="form-control" 
                        placeholder="Buscar por nome, telefone ou email..."
                        value="<?php echo isset($_GET['busca_verificados']) ? htmlspecialchars($_GET['busca_verificados']) : ''; ?>"
                    >
                    <button type="submit" class="btn btn-primary-600">
                        <iconify-icon icon="solar:magnifer-outline"></iconify-icon>
                        Buscar
                    </button>
                    <?php if (isset($_GET['busca_verificados']) && !empty($_GET['busca_verificados'])): ?>
                        <a href="verificacao_config.php" class="btn btn-outline-secondary">Limpar</a>
                    <?php
endif; ?>
                </div>
            </form>
            
            <?php if (empty($clientes_verificados)): ?>
                <div class="text-center py-5">
                    <iconify-icon icon="solar:users-group-rounded-outline" class="text-secondary-light" style="font-size: 60px;"></iconify-icon>
                    <p class="text-secondary-light mt-3">
                        <?php echo isset($_GET['busca_verificados']) ? 'Nenhum cliente encontrado.' : 'Nenhum cliente verificado ainda.'; ?>
                    </p>
                </div>
            <?php
else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Telefone</th>
                                <th>Email</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes_verificados as $c): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($c['nome']); ?></td>
                                <td><?php echo htmlspecialchars($c['telefone']); ?></td>
                                <td><?php echo htmlspecialchars($c['email']); ?></td>
                                <td>
                                    <span class="badge bg-success-600">Verificado</span>
                                </td>
                            </tr>
                            <?php
    endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php
endif; ?>
        </div>
    </div>

    <!-- Informações -->
    <div class="card mb-4 radius-12">
        <div class="card-header">
            <h6 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                <iconify-icon icon="solar:info-circle-bold"></iconify-icon>
                Como Funciona
            </h6>
        </div>
        <div class="card-body p-24">
            <ul class="mb-0">
                <li class="mb-2">
                    <strong>Novos Clientes:</strong> Quando um cliente faz seu primeiro pedido, um código de 6 dígitos é enviado automaticamente via WhatsApp.
                </li>
                <li class="mb-2">
                    <strong>Verificação:</strong> O cliente deve digitar o código recebido para finalizar o pedido.
                </li>
                <li class="mb-2">
                    <strong>Clientes Verificados:</strong> Após a primeira verificação, os próximos pedidos não precisam de código.
                </li>
                <li class="mb-2">
                    <strong>Remoção:</strong> Quando um cliente é excluído, sua validação é removida automaticamente. Você também pode remover manualmente na lista acima.
                </li>
                <li class="mb-0">
                    <strong>Segurança:</strong> Este sistema ajuda a prevenir pedidos falsos de números não verificados.
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
// Atualizar label quando checkbox mudar
document.getElementById('ativo').addEventListener('change', function() {
    const label = this.nextElementSibling;
    label.textContent = this.checked ? 'Ativo' : 'Inativo';
});
</script>

<?php include 'includes/footer.php'; ?>