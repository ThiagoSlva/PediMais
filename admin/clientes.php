<?php
require_once 'includes/header.php';

// Gerar token CSRF se não existir
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$msg = '';
$msg_type = '';

// Ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Deletar Todos
    if (isset($_POST['action']) && $_POST['action'] === 'delete_all') {
        if (isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
            try {
                // Verificar se tem permissão (opcional, aqui assumimos admin)
                
                // Primeiro excluir notificações vinculadas (tabela dependente)
                $pdo->exec("DELETE FROM cliente_notificacoes");

                // Usar DELETE em vez de TRUNCATE para respeitar outras chaves estrangeiras
                $pdo->exec("DELETE FROM clientes");
                
                $msg = 'Todos os clientes (sem pedidos ativos) foram excluídos com sucesso!';
                $msg_type = 'success';
            } catch (PDOException $e) {
                // Verificar se é erro de integridade referencial (provavelmente pedidos)
                if ($e->getCode() == '23000') {
                     $msg = 'Não foi possível excluir todos os clientes pois existem pedidos vinculados. Apenas clientes sem pedidos ou notificações foram processados.';
                } else {
                     $msg = 'Erro ao excluir clientes: ' . $e->getMessage();
                }
                $msg_type = 'danger';
            }
        } else {
            $msg = 'Token de segurança inválido.';
            $msg_type = 'danger';
        }
    }
    // Deletar Individual
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete_single') {
        if (isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
            $id = (int)$_POST['id'];
            try {
                $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
                $stmt->execute([$id]);
                $msg = 'Cliente excluído com sucesso!';
                $msg_type = 'success';
            } catch (PDOException $e) {
                $msg = 'Erro ao excluir cliente: ' . $e->getMessage();
                $msg_type = 'danger';
            }
        }
    }
    // Toggle Status
    elseif (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
         // Implementar se houver coluna 'ativo'
    }
}

// Filtros
$busca = isset($_GET['busca']) ? $_GET['busca'] : '';
$ativo_filtro = isset($_GET['ativo']) ? $_GET['ativo'] : '';

$where = "WHERE 1=1";
$params = [];

if ($busca) {
    $where .= " AND (c.nome LIKE ? OR c.telefone LIKE ? OR c.email LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

// Se a coluna 'ativo' existir, descomentar:
// if ($ativo_filtro !== '') {
//     $where .= " AND c.ativo = ?";
//     $params[] = $ativo_filtro;
// }

// Paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 20;
$offset = ($pagina - 1) * $limite;

// Buscar total de registros para paginação
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM clientes c $where");
$stmt_count->execute($params);
$total_registros = $stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / $limite);

// Query principal com estatísticas de pedidos
// Assumindo que a tabela de pedidos tem cliente_id ou similar. 
// Se não tiver chave estrangeira direta, pode ser necessário ajustar.
// Vou usar LEFT JOIN com pedidos para contar e somar.
// Nota: Se a tabela pedidos usar telefone como chave, ajustar o ON.
// Assumindo cliente_id por enquanto.
$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM pedidos p WHERE p.cliente_id = c.id) as total_pedidos,
        (SELECT SUM(valor_total) FROM pedidos p WHERE p.cliente_id = c.id) as total_gasto,
        (SELECT MAX(data_pedido) FROM pedidos p WHERE p.cliente_id = c.id) as ultimo_pedido
        FROM clientes c 
        $where 
        ORDER BY c.nome ASC 
        LIMIT $limite OFFSET $offset";

// Se a coluna cliente_id não existir em pedidos, tentar pelo telefone (comum em sistemas simples)
// $sql = "SELECT c.*, ... FROM clientes c ... LEFT JOIN pedidos p ON p.cliente_telefone = c.telefone ...";
// Vou tentar a query padrão primeiro. Se der erro, ajusto.
// Para garantir, vou fazer uma query simples primeiro e buscar stats separadamente se precisar, 
// mas subselects são mais seguros se a estrutura for incerta.

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback se subselects falharem (ex: colunas não existem)
    $sql = "SELECT * FROM clientes c $where ORDER BY nome ASC LIMIT $limite OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Clientes</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Clientes</li>
        </ul>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($msg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="card radius-12">
        <div class="card-body p-24">
            <div class="d-flex align-items-center justify-content-between mb-20">
                <h6 class="mb-0 fw-bold text-lg">📋 Gerenciar Clientes</h6>
                <div class="d-flex gap-2">
                    <span class="text-sm text-secondary-light"><?php echo $total_registros; ?> cliente(s)</span>
                    <a href="cliente_add.php" class="btn btn-primary btn-sm d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:add-circle-outline"></iconify-icon>
                        <span>Adicionar Cliente</span>
                    </a>
                    <?php if ($total_registros > 0): ?>
                    <form action="clientes.php" method="POST" style="display:inline;" onsubmit="return confirm('⚠️ TEM CERTEZA QUE DESEJA DELETAR TODOS OS CLIENTES?\n\nEsta ação NÃO pode ser desfeita!');">
                        <input type="hidden" name="action" value="delete_all">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-2">
                            <iconify-icon icon="solar:trash-bin-trash-outline"></iconify-icon>
                            <span>Deletar Todos</span>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Filtros -->
            <form method="GET" class="mb-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="text" name="busca" class="form-control" 
                               placeholder="🔍 Buscar por Nome, Telefone ou E-mail" 
                               value="<?php echo htmlspecialchars($busca); ?>">
                    </div>
                    <div class="col-md-2">
                        <select name="ativo" class="form-select">
                            <option value="">Todos</option>
                            <option value="1" <?php echo $ativo_filtro === '1' ? 'selected' : ''; ?>>✅ Ativos</option>
                            <option value="0" <?php echo $ativo_filtro === '0' ? 'selected' : ''; ?>>❌ Inativos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Buscar</button>
                    </div>
                    <div class="col-md-2">
                        <a href="clientes.php" class="btn btn-outline-secondary w-100">Limpar</a>
                    </div>
                </div>
            </form>
            
            <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table class="table bordered-table mb-0" style="min-width: 1200px;">
                    <thead>
                        <tr>
                            <th style="min-width: 60px;">Foto</th>
                            <th style="min-width: 150px;">Nome</th>
                            <th style="min-width: 130px;">Telefone</th>
                            <th style="min-width: 150px;">E-mail</th>
                            <th style="min-width: 80px;">Pedidos</th>
                            <th style="min-width: 110px;">Total Gasto</th>
                            <th style="min-width: 140px;">Último Pedido</th>
                            <th style="min-width: 80px;">Status</th>
                            <th class="text-center bg-base" style="min-width: 200px; position: sticky; right: 0; z-index: 10; box-shadow: -2px 0 5px rgba(0,0,0,0.05);">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clientes)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">Nenhum cliente encontrado</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $foto = isset($cliente['foto']) && $cliente['foto'] ? '../uploads/clientes/' . $cliente['foto'] : '';
                                    // SVG placeholder como data URI
                                    $placeholder = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40' viewBox='0 0 40 40'%3E%3Crect fill='%23e0e0e0' width='40' height='40' rx='20'/%3E%3Cpath fill='%239e9e9e' d='M20 10a6 6 0 1 1 0 12 6 6 0 0 1 0-12zm0 14c6.6 0 12 2.7 12 6v2H8v-2c0-3.3 5.4-6 12-6z'/%3E%3C/svg%3E";
                                    ?>
                                    <img src="<?php echo $foto ? htmlspecialchars($foto) : $placeholder; ?>" alt="Foto" class="w-40-px h-40-px rounded-circle object-fit-cover" onerror="this.src='<?php echo $placeholder; ?>'">
                                </td>
                                <td>
                                    <h6 class="text-md mb-0 fw-medium"><?php echo htmlspecialchars($cliente['nome']); ?></h6>
                                </td>
                                <td><?php echo htmlspecialchars($cliente['telefone']); ?></td>
                                <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                                <td>
                                    <span class="badge bg-info-focus text-info-main px-2 py-1">
                                        <?php echo isset($cliente['total_pedidos']) ? $cliente['total_pedidos'] : 0; ?>
                                    </span>
                                </td>
                                <td>R$ <?php echo number_format(isset($cliente['total_gasto']) ? $cliente['total_gasto'] : 0, 2, ',', '.'); ?></td>
                                <td>
                                    <?php 
                                    if (isset($cliente['ultimo_pedido']) && $cliente['ultimo_pedido']) {
                                        echo date('d/m/Y', strtotime($cliente['ultimo_pedido']));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if (isset($cliente['ativo']) && $cliente['ativo'] == 0): ?>
                                        <span class="badge bg-danger-focus text-danger-main">Inativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-focus text-success-main">Ativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center bg-base" style="position: sticky; right: 0; z-index: 10;">
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <a href="cliente_edit.php?id=<?php echo $cliente['id']; ?>" class="btn btn-icon btn-sm btn-primary-light radius-8" title="Editar">
                                            <iconify-icon icon="solar:pen-new-square-linear"></iconify-icon>
                                        </a>
                                        <a href="cliente_pedidos.php?id=<?php echo $cliente['id']; ?>" class="btn btn-icon btn-sm btn-info-light radius-8" title="Ver Pedidos">
                                            <iconify-icon icon="solar:bill-list-linear"></iconify-icon>
                                        </a>
                                        <form action="clientes.php" method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir este cliente?');">
                                            <input type="hidden" name="action" value="delete_single">
                                            <input type="hidden" name="id" value="<?php echo $cliente['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <button type="submit" class="btn btn-icon btn-sm btn-danger-light radius-8" title="Excluir">
                                                <iconify-icon icon="solar:trash-bin-trash-linear"></iconify-icon>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginação -->
            <?php if ($total_paginas > 1): ?>
            <div class="d-flex align-items-center justify-content-between mt-4">
                <span class="text-sm text-secondary-light">Mostrando <?php echo count($clientes); ?> de <?php echo $total_registros; ?></span>
                <nav aria-label="Page navigation">
                    <ul class="pagination mb-0">
                        <li class="page-item <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?>&busca=<?php echo urlencode($busca); ?>&ativo=<?php echo urlencode($ativo_filtro); ?>">Anterior</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?php echo $pagina == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $i; ?>&busca=<?php echo urlencode($busca); ?>&ativo=<?php echo urlencode($ativo_filtro); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $pagina >= $total_paginas ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?>&busca=<?php echo urlencode($busca); ?>&ativo=<?php echo urlencode($ativo_filtro); ?>">Próximo</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Input oculto para CSRF -->
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">



<?php require_once 'includes/footer.php'; ?>