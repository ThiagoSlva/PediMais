<?php
include 'includes/auth.php';
include 'includes/header.php';
include '../includes/config.php';
include '../includes/functions.php';
require_once '../includes/csrf.php';

// Migration Logic (Inline)
try {
    // Tabela de Configuração
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracao_avaliacoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ativo TINYINT(1) DEFAULT 1,
        mostrar_no_site TINYINT(1) DEFAULT 0,
        mensagem_avaliacao TEXT
    )");

    // Verificar colunas
    $stmt = $pdo->query("DESCRIBE configuracao_avaliacoes");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('mostrar_no_site', $columns)) {
        $pdo->exec("ALTER TABLE configuracao_avaliacoes ADD COLUMN mostrar_no_site TINYINT(1) DEFAULT 0");
    }
    if (!in_array('mensagem_avaliacao', $columns)) {
        $pdo->exec("ALTER TABLE configuracao_avaliacoes ADD COLUMN mensagem_avaliacao TEXT");
    }

    // Garantir registro inicial
    $stmt = $pdo->query("SELECT id FROM configuracao_avaliacoes LIMIT 1");
    if (!$stmt->fetch()) {
        $default_msg = "⭐ *Avalie seu pedido!*\n\n🔗 Clique no link para avaliar:\n{link}";
        $stmt = $pdo->prepare("INSERT INTO configuracao_avaliacoes (ativo, mostrar_no_site, mensagem_avaliacao) VALUES (1, 0, ?)");
        $stmt->execute([$default_msg]);
    }

    // Tabela de Avaliações - schema atualizado
    $pdo->exec("CREATE TABLE IF NOT EXISTS avaliacoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pedido_id INT,
        cliente_nome VARCHAR(255),
        nota INT,
        comentario TEXT,
        token VARCHAR(64) UNIQUE,
        data_avaliacao DATETIME DEFAULT CURRENT_TIMESTAMP,
        ativo TINYINT(1) DEFAULT 1
    )");

    // Verificar e adicionar colunas novas se não existirem
    $stmt = $pdo->query("DESCRIBE avaliacoes");
    $av_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('cliente_nome', $av_columns)) {
        $pdo->exec("ALTER TABLE avaliacoes ADD COLUMN cliente_nome VARCHAR(255)");
    }
    if (!in_array('token', $av_columns)) {
        $pdo->exec("ALTER TABLE avaliacoes ADD COLUMN token VARCHAR(64) UNIQUE");
    }
    if (!in_array('produto_id', $av_columns)) {
        $pdo->exec("ALTER TABLE avaliacoes ADD COLUMN produto_id INT NULL AFTER pedido_id");
        $pdo->exec("ALTER TABLE avaliacoes ADD INDEX idx_produto_id (produto_id)");
    }

}
catch (PDOException $e) {
// Silently handle migration errors or log them
}

$msg = '';
$msg_tipo = '';

// Processar Ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validar_csrf()) {
        $msg = 'Token de segurança inválido. Recarregue a página.';
        $msg_tipo = 'danger';
    }
    elseif (isset($_POST['action'])) {
        if ($_POST['action'] == 'config') {
            $ativo = isset($_POST['ativo']) ? 1 : 0;
            $mostrar_no_site = isset($_POST['mostrar_no_site']) ? 1 : 0;
            $mensagem = $_POST['mensagem_avaliacao'];

            try {
                $stmt = $pdo->prepare("UPDATE configuracao_avaliacoes SET ativo = ?, mostrar_no_site = ?, mensagem_avaliacao = ? WHERE id = 1");
                $stmt->execute([$ativo, $mostrar_no_site, $mensagem]);

                if ($stmt->rowCount() == 0) {
                    $check = $pdo->query("SELECT id FROM configuracao_avaliacoes LIMIT 1")->fetch();
                    if (!$check) {
                        $stmt = $pdo->prepare("INSERT INTO configuracao_avaliacoes (ativo, mostrar_no_site, mensagem_avaliacao) VALUES (?, ?, ?)");
                        $stmt->execute([$ativo, $mostrar_no_site, $mensagem]);
                    }
                }

                $msg = 'Configurações salvas com sucesso!';
                $msg_tipo = 'success';
            }
            catch (PDOException $e) {
                $msg = 'Erro ao salvar: ' . $e->getMessage();
                $msg_tipo = 'danger';
            }
        }
    }
}

// Ações GET (Delete)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM avaliacoes WHERE id = ?");
        $stmt->execute([$id]);
        $msg = 'Avaliação excluída com sucesso!';
        $msg_tipo = 'success';
    }
    catch (PDOException $e) {
        $msg = 'Erro ao excluir: ' . $e->getMessage();
        $msg_tipo = 'danger';
    }
}

// Buscar Configurações
$stmt = $pdo->query("SELECT * FROM configuracao_avaliacoes LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar Avaliações - join com pedidos e produtos
$where = "1=1";
$params = [];

if (isset($_GET['busca']) && !empty($_GET['busca'])) {
    $busca = $_GET['busca'];
    $where .= " AND (a.cliente_nome LIKE ? OR a.descricao LIKE ? OR pr.nome LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

$sql = "SELECT a.*, p.codigo_pedido, pr.nome as produto_nome 
        FROM avaliacoes a 
        LEFT JOIN pedidos p ON a.pedido_id = p.id 
        LEFT JOIN produtos pr ON a.produto_id = pr.id
        WHERE $where 
        ORDER BY a.data_avaliacao DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$avaliacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
    <h6 class="fw-semibold mb-0">Avaliações</h6>
    <ul class="d-flex align-items-center gap-2">
        <li class="fw-medium">
            <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                Dashboard
            </a>
        </li>
        <li>-</li>
        <li class="fw-medium">Avaliações</li>
    </ul>
</div>

<?php if ($msg): ?>
<div class="alert alert-<?php echo $msg_tipo; ?> alert-dismissible fade show" role="alert">
    <?php echo $msg; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php
endif; ?>

<div class="card h-100 p-0 radius-12 mb-4">
    <div class="card-header">
        <h6 class="mb-0 fw-semibold">Configurações de Avaliações</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="avaliacoes.php">
            <?php echo campo_csrf(); ?>
            <input type="hidden" name="action" value="config">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" 
                               <?php echo $config['ativo'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="ativo">
                            Sistema de Avaliações Ativo
                        </label>
                        <small class="d-block text-secondary-light mt-1">
                            Quando ativo, clientes receberão link de avaliação após entrega
                        </small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="mostrar_no_site" name="mostrar_no_site" value="1" 
                               <?php echo $config['mostrar_no_site'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="mostrar_no_site">
                            Mostrar Avaliações no Site
                        </label>
                        <small class="d-block text-secondary-light mt-1">
                            Exibe as avaliações em slide no final do cardápio
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="mensagem_avaliacao" class="form-label fw-semibold text-sm mb-2">
                    Mensagem de Avaliação (com Link)
                </label>
                <textarea class="form-control radius-8" 
                          id="mensagem_avaliacao" 
                          name="mensagem_avaliacao" 
                          rows="6"
                          placeholder="Digite a mensagem que será enviada com o link de avaliação..."><?php echo htmlspecialchars($config['mensagem_avaliacao']); ?></textarea>
                <small class="text-secondary-light mt-1 d-block">
                    <strong>Variáveis disponíveis:</strong><br>
                    <code>{codigo_pedido}</code> - Código do pedido<br>
                    <code>{link}</code> - Link de avaliação<br>
                    <br>
                    <strong>Nota:</strong> Esta mensagem será enviada mesmo se a mensagem de "pedido entregue" estiver desativada.
                </small>
            </div>
            
            <button type="submit" class="btn btn-primary radius-8 d-flex align-items-center gap-2">
                <iconify-icon icon="solar:diskette-outline"></iconify-icon>
                Salvar Configurações
            </button>
        </form>
    </div>
</div>

<div class="card h-100 p-0 radius-12">
    <div class="card-body p-24">
        <div class="d-flex align-items-center flex-wrap gap-2 justify-content-between mb-20">
            <h6 class="mb-2 fw-bold text-lg mb-0">Lista de Avaliações</h6>
            <div class="d-flex align-items-center gap-2">
                <span class="text-sm fw-medium text-secondary-light"><?php echo count($avaliacoes); ?> avaliação(ões)</span>
            </div>
        </div>
        
        <!-- Filtros -->
        <form method="GET" action="avaliacoes.php" class="mb-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="busca" class="form-label fw-semibold text-sm mb-2">Buscar por Nome</label>
                    <input type="text" 
                           class="form-control radius-8" 
                           id="busca" 
                           name="busca" 
                           value="<?php echo isset($_GET['busca']) ? htmlspecialchars($_GET['busca']) : ''; ?>" 
                           placeholder="Digite o nome...">
                </div>
                <div class="col-md-6 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary radius-8 px-4 d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:magnifer-outline"></iconify-icon>
                        Filtrar
                    </button>
                    <?php if (isset($_GET['busca'])): ?>
                        <a href="avaliacoes.php" class="btn btn-outline-secondary radius-8 px-4">Limpar</a>
                    <?php
endif; ?>
                </div>
            </div>
        </form>
        
        <div class="table-responsive scroll-sm">
            <table class="table bordered-table sm-table mb-0">
                <thead>
                    <tr>
                        <th scope="col">Cliente</th>
                        <th scope="col">Produto</th>
                        <th scope="col">Avaliação</th>
                        <th scope="col">Descrição</th>
                        <th scope="col">Pedido</th>
                        <th scope="col">Data</th>
                        <th scope="col" class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($avaliacoes) > 0): ?>
                        <?php foreach ($avaliacoes as $av): ?>
                        <tr>
                            <td class="fw-semibold"><?php echo htmlspecialchars($av['cliente_nome'] ?? 'Cliente Removido'); ?></td>
                            <td>
                                <?php if (!empty($av['produto_nome'])): ?>
                                    <span class="badge bg-info-50 text-info-600"><?php echo htmlspecialchars($av['produto_nome']); ?></span>
                                <?php
        else: ?>
                                    <span class="text-secondary-light">Geral</span>
                                <?php
        endif; ?>
                            </td>
                            <td>
                                <div style="display: inline-flex; align-items: center; gap: 4px;">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <iconify-icon icon="solar:star-bold" style="color: <?php echo $i <= ($av['avaliacao'] ?? 0) ? '#ffc107' : '#e0e0e0'; ?>; font-size: 1.2rem; display: inline-block;"></iconify-icon>
                                    <?php
        endfor; ?>
                                    <span class="ms-2 text-secondary-light">(<?php echo $av['avaliacao'] ?? 0; ?>/5)</span>
                                </div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($av['descricao'] ?? ''); ?>
                            </td>
                            <td>
                                <?php if ($av['codigo_pedido']): ?>
                                    <span class="badge bg-primary-50 text-primary-600">#<?php echo $av['codigo_pedido']; ?></span>
                                <?php
        else: ?>
                                    <span class="text-secondary-light">-</span>
                                <?php
        endif; ?>
                            </td>
                            <td>
                                <?php echo date('d/m/Y H:i', strtotime($av['data_avaliacao'])); ?>
                            </td>
                            <td class="text-center">
                                <a href="avaliacoes.php?action=delete&id=<?php echo $av['id']; ?>" 
                                   class="bg-danger-focus text-danger-600 bg-hover-danger-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle"
                                   onclick="return confirm('Tem certeza que deseja excluir esta avaliação?');"
                                   title="Excluir">
                                    <iconify-icon icon="mingcute:delete-2-line" class="icon text-xl"></iconify-icon>
                                </a>
                            </td>
                        </tr>
                        <?php
    endforeach; ?>
                    <?php
else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">Nenhuma avaliação encontrada.</td>
                        </tr>
                    <?php
endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
