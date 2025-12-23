<?php
require_once 'includes/header.php';

// Gerar token CSRF se não existir
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$msg = '';
$msg_type = '';

// Ações via GET (links na tabela)
if (isset($_GET['action']) && isset($_GET['token']) && $_GET['token'] === $_SESSION['csrf_token']) {
    $action = $_GET['action'];
    
    // Update Status
    if ($action === 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
        $pedido_id = (int)$_GET['id'];
        $novo_status = $_GET['status'];
        
        // Validar status permitidos
        $status_validos = ['pendente', 'em_andamento', 'pronto', 'saiu_entrega', 'concluido', 'cancelado', 'finalizado'];
        if (in_array($novo_status, $status_validos)) {
            try {
                // Atualizar status do pedido
                $stmt = $pdo->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
                $stmt->execute([$novo_status, $pedido_id]);
                
                // Mapear status para flags e lane
                $em_preparo = 0;
                $saiu_entrega = 0;
                $entregue = 0;
                $lane_nome = '';
                
                switch ($novo_status) {
                    case 'em_andamento':
                        $em_preparo = 1;
                        $lane_nome = 'preparo';
                        break;
                    case 'pronto':
                        $em_preparo = 1;
                        $lane_nome = 'pronto';
                        break;
                    case 'saiu_entrega':
                        $em_preparo = 1;
                        $saiu_entrega = 1;
                        $lane_nome = 'saiu';
                        break;
                    case 'concluido':
                        $em_preparo = 1;
                        $saiu_entrega = 1;
                        $entregue = 1;
                        $lane_nome = 'entregue';
                        break;
                    case 'cancelado':
                        $lane_nome = 'cancelado';
                        break;
                    default:
                        $lane_nome = 'novo';
                }
                
                // Atualizar flags
                $stmt = $pdo->prepare("UPDATE pedidos SET em_preparo = ?, saiu_entrega = ?, entregue = ? WHERE id = ?");
                $stmt->execute([$em_preparo, $saiu_entrega, $entregue, $pedido_id]);
                
                // Sincronizar com Kanban - buscar lane correspondente
                $stmt_lane = $pdo->prepare("SELECT id FROM kanban_lanes WHERE LOWER(nome) LIKE ? LIMIT 1");
                $stmt_lane->execute(['%' . $lane_nome . '%']);
                $lane = $stmt_lane->fetch();
                
                if ($lane) {
                    $stmt = $pdo->prepare("UPDATE pedidos SET lane_id = ? WHERE id = ?");
                    $stmt->execute([$lane['id'], $pedido_id]);
                }
                
                $msg = 'Status atualizado com sucesso!';
                $msg_type = 'success';
            } catch (PDOException $e) {
                $msg = 'Erro ao atualizar status: ' . $e->getMessage();
                $msg_type = 'danger';
            }
        } else {
            $msg = 'Status inválido.';
            $msg_type = 'danger';
        }
    }
    
    // Delete individual
    elseif ($action === 'delete' && isset($_GET['id'])) {
        $pedido_id = (int)$_GET['id'];
        try {
            // Deletar itens do pedido primeiro (se houver FK)
            $pdo->prepare("DELETE FROM pedido_itens WHERE pedido_id = ?")->execute([$pedido_id]);
            $pdo->prepare("DELETE FROM pedidos WHERE id = ?")->execute([$pedido_id]);
            $msg = 'Pedido deletado com sucesso!';
            $msg_type = 'success';
        } catch (PDOException $e) {
            $msg = 'Erro ao deletar pedido: ' . $e->getMessage();
            $msg_type = 'danger';
        }
    }
}

// Ações via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Deletar Todos
    if (isset($_POST['action']) && $_POST['action'] === 'delete_all_pedidos') {
        if (isset($_POST['token']) && $_POST['token'] === $_SESSION['csrf_token']) {
            try {
                $pdo->exec("DELETE FROM pedido_itens");
                $pdo->exec("DELETE FROM pedidos");
                // Resetar auto increment
                $pdo->exec("ALTER TABLE pedidos AUTO_INCREMENT = 1");
                $msg = 'Todos os pedidos foram excluídos com sucesso!';
                $msg_type = 'success';
            } catch (PDOException $e) {
                $msg = 'Erro ao excluir pedidos: ' . $e->getMessage();
                $msg_type = 'danger';
            }
        } else {
            $msg = 'Token de segurança inválido.';
            $msg_type = 'danger';
        }
    }
}

// Filtros
$busca = isset($_GET['busca']) ? $_GET['busca'] : '';
$status_filtro = isset($_GET['status']) ? $_GET['status'] : '';

$where = "WHERE 1=1";
$params = [];

if ($busca) {
    $where .= " AND (p.cliente_nome LIKE ? OR p.cliente_telefone LIKE ? OR p.id LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

if ($status_filtro) {
    $where .= " AND p.status = ?";
    $params[] = $status_filtro;
}

// Buscar pedidos
$sql = "SELECT p.*, f.nome as forma_pagamento 
        FROM pedidos p 
        LEFT JOIN formas_pagamento f ON p.forma_pagamento_id = f.id 
        $where 
        ORDER BY p.data_pedido DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Pedidos</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Pedidos</li>
        </ul>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($msg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <style>
    .admin-table-head {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(6px);
    }
    [data-theme="dark"] .admin-table-head {
        background: rgba(15, 23, 42, 0.92);
        color: rgba(226, 232, 240, 0.92);
    }
    [data-theme="dark"] .table tbody tr {
        color: rgba(226, 232, 240, 0.92);
    }
    [data-theme="dark"] code.bg-neutral-100 {
        background-color: rgba(148, 163, 184, 0.18) !important;
        color: #e2e8f0 !important;
    }
    .table-row-highlight {
        animation: pedidoHighlight 1.6s ease-out 2;
        position: relative;
    }
    @keyframes pedidoHighlight {
        0% { box-shadow: 0 0 0 rgba(72, 127, 255, 0.0); }
        50% { box-shadow: 0 0 0 4px rgba(72, 127, 255, 0.35); }
        100% { box-shadow: 0 0 0 rgba(72, 127, 255, 0.0); }
    }
    </style>

    <div class="card h-100 p-0 radius-12">
        <div class="card-body p-24">
            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center flex-wrap gap-2 justify-content-between mb-20">
                <h6 class="mb-2 mb-md-0 fw-bold text-lg">Gerenciar Pedidos</h6>
                <div class="d-flex flex-wrap gap-2 align-items-center w-100 w-md-auto">
                    <span class="text-sm fw-medium text-secondary-light"><?php echo count($pedidos); ?> pedido(s)</span>
                    <button class="btn btn-sm btn-outline-primary" onclick="location.reload()" title="Atualizar">
                        <iconify-icon icon="solar:refresh-outline"></iconify-icon>
                        <span class="d-none d-md-inline">Atualizar</span>
                    </button>
                    <a href="pedidos_kanban.php" class="btn btn-sm btn-outline-info" title="Ver Kanban">
                        <iconify-icon icon="solar:clipboard-list-outline"></iconify-icon>
                        <span class="d-none d-md-inline">Kanban</span>
                    </a>
                    <?php if (count($pedidos) > 0): ?>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmarDeletarTodos()" title="Deletar Todos">
                        <iconify-icon icon="solar:trash-bin-trash-linear"></iconify-icon>
                        <span class="d-none d-md-inline">Deletar Todos</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Formulário oculto para deletar todos -->
            <form id="form-deletar-todos" method="POST" style="display: none;">
                <input type="hidden" name="action" value="delete_all_pedidos">
                <input type="hidden" name="token" value="<?php echo $_SESSION['csrf_token']; ?>">
            </form>
            
            <!-- Filtros -->
            <form method="GET" class="mb-3">
                <div class="row g-3">
                    <div class="col-md-5">
                        <input type="text" name="busca" class="form-control" 
                               placeholder="🔍 Buscar por Nome, Telefone ou Código do Pedido" 
                               value="<?php echo htmlspecialchars($busca); ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">📋 Todos os Status</option>
                            <option value="pendente" <?php echo $status_filtro === 'pendente' ? 'selected' : ''; ?>>⏳ Pendente</option>
                            <option value="em_andamento" <?php echo $status_filtro === 'em_andamento' ? 'selected' : ''; ?>>👨‍🍳 Em Preparo</option>
                            <option value="pronto" <?php echo $status_filtro === 'pronto' ? 'selected' : ''; ?>>✅ Pronto</option>
                            <option value="saiu_entrega" <?php echo $status_filtro === 'saiu_entrega' ? 'selected' : ''; ?>>🏍️ Saiu para Entrega</option>
                            <option value="concluido" <?php echo $status_filtro === 'concluido' ? 'selected' : ''; ?>>🎉 Entregue</option>
                            <option value="finalizado" <?php echo $status_filtro === 'finalizado' ? 'selected' : ''; ?>>📦 Finalizado/Arquivado</option>
                            <option value="cancelado" <?php echo $status_filtro === 'cancelado' ? 'selected' : ''; ?>>❌ Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <iconify-icon icon="solar:magnifer-outline"></iconify-icon>
                            <span>Buscar</span>
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="pedidos.php" class="btn btn-outline-secondary w-100">
                            <iconify-icon icon="solar:refresh-outline"></iconify-icon>
                            <span>Limpar</span>
                        </a>
                    </div>
                </div>
            </form>
            
            <div class="table-responsive scroll-sm" style="overflow-x: auto; max-height: 70vh; overflow-y: auto;">
                <table class="table bordered-table sm-table mb-0" id="pedidosTable" style="min-width: 1400px;">
                    <thead class="sticky-top admin-table-head" style="z-index: 10;">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Código</th>
                            <th scope="col">Cliente</th>
                            <th scope="col">Telefone</th>
                            <th scope="col">Endereço</th>
                            <th scope="col">Frete</th>
                            <th scope="col">Total</th>
                            <th scope="col">Data</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-center" style="min-width: 350px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pedidos)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    Nenhum pedido encontrado.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pedidos as $pedido): ?>
                            <tr data-pedido-id="<?php echo $pedido['id']; ?>" data-pedido-status="<?php echo $pedido['status']; ?>">
                                <td class="fw-semibold">#<?php echo $pedido['id']; ?></td>
                                <td>
                                    <code class="bg-neutral-100 px-2 py-1 rounded"><?php echo htmlspecialchars($pedido['codigo_pedido'] ?? $pedido['id']); ?></code>
                                </td>
                                <td><?php echo htmlspecialchars($pedido['cliente_nome']); ?></td>
                                <td><?php 
                                    $tel = preg_replace('/[^0-9]/', '', $pedido['cliente_telefone'] ?? '');
                                    if (strlen($tel) == 11) {
                                        echo '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 5) . '-' . substr($tel, 7);
                                    } elseif (strlen($tel) == 10) {
                                        echo '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 4) . '-' . substr($tel, 6);
                                    } else {
                                        echo htmlspecialchars($pedido['cliente_telefone'] ?? '');
                                    }
                                ?></td>
                                <td>
                                    <?php echo htmlspecialchars($pedido['cliente_endereco'] ?? ''); ?>
                                </td>
                                <td>
                                    <?php 
                                    $frete = floatval($pedido['taxa_entrega'] ?? 0);
                                    if ($frete <= 0): ?>
                                        <span class="text-success fw-semibold">Grátis</span>
                                    <?php else: ?>
                                        R$ <?php echo number_format($frete, 2, ',', '.'); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold text-primary-600">
                                    R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?></td>
                                <td>
                                    <?php
                                    $status_class = match($pedido['status']) {
                                        'pendente' => 'bg-warning-focus text-warning-main',
                                        'em_andamento' => 'bg-info-focus text-info-main',
                                        'pronto' => 'bg-primary-focus text-primary-main',
                                        'saiu_entrega' => 'bg-secondary-focus text-secondary-main',
                                        'concluido' => 'bg-success-focus text-success-main',
                                        'finalizado' => 'bg-success-focus text-success-main',
                                        'cancelado' => 'bg-danger-focus text-danger-main',
                                        default => 'bg-neutral-200 text-neutral-600'
                                    };
                                    $status_label = match($pedido['status']) {
                                        'pendente' => 'Pendente',
                                        'em_andamento' => 'Em Preparo',
                                        'pronto' => 'Pronto',
                                        'saiu_entrega' => 'Saiu p/ Entrega',
                                        'concluido' => 'Entregue',
                                        'finalizado' => '📦 Finalizado',
                                        'cancelado' => 'Cancelado',
                                        default => $pedido['status']
                                    };
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?> px-24 py-4 rounded-pill fw-medium text-sm" data-status="<?php echo $pedido['status']; ?>">
                                        <?php echo $status_label; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex align-items-center gap-1 justify-content-center flex-wrap">
                                        <!-- Ver Detalhes -->
                                        <a href="pedido_detalhe.php?id=<?php echo $pedido['id']; ?>"
                                           class="bg-info-focus text-info-600 bg-hover-info-200 fw-medium w-32-px h-32-px d-flex justify-content-center align-items-center rounded-circle"
                                           title="Ver Detalhes">
                                            <iconify-icon icon="solar:eye-outline" class="text-lg"></iconify-icon>
                                        </a>
                                        
                                        <!-- Imprimir -->
                                        <a href="pedido_imprimir.php?id=<?php echo $pedido['id']; ?>" 
                                           target="_blank"
                                           class="bg-warning-focus text-warning-600 bg-hover-warning-200 fw-medium w-32-px h-32-px d-flex justify-content-center align-items-center rounded-circle"
                                           title="Imprimir Pedido">
                                            <iconify-icon icon="solar:printer-outline" class="text-lg"></iconify-icon>
                                        </a>
                                        
                                        <!-- Em Preparo -->
                                        <?php if ($pedido['status'] !== 'em_andamento' && $pedido['status'] !== 'concluido' && $pedido['status'] !== 'cancelado'): ?>
                                        <button type="button" onclick="atualizarStatus(<?php echo $pedido['id']; ?>, 'em_andamento', this)"
                                           class="btn p-0 bg-warning-focus text-warning-600 bg-hover-warning-200 fw-medium w-32-px h-32-px d-flex justify-content-center align-items-center rounded-circle border-0"
                                           title="Em Preparo">
                                            <iconify-icon icon="solar:chef-hat-outline" class="text-lg"></iconify-icon>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <!-- Pronto -->
                                        <?php if ($pedido['status'] !== 'pronto' && $pedido['status'] !== 'concluido' && $pedido['status'] !== 'cancelado'): ?>
                                        <button type="button" onclick="atualizarStatus(<?php echo $pedido['id']; ?>, 'pronto', this)"
                                           class="btn p-0 bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-32-px h-32-px d-flex justify-content-center align-items-center rounded-circle border-0"
                                           title="Pronto">
                                            <iconify-icon icon="solar:check-circle-outline" class="text-lg"></iconify-icon>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <!-- Saiu para Entrega -->
                                        <?php if ($pedido['status'] !== 'saiu_entrega' && $pedido['status'] !== 'concluido' && $pedido['status'] !== 'cancelado'): ?>
                                        <button type="button" onclick="atualizarStatus(<?php echo $pedido['id']; ?>, 'saiu_entrega', this)"
                                           class="btn p-0 link-saiu-entrega bg-primary-focus text-primary-600 bg-hover-primary-200 fw-medium w-32-px h-32-px d-flex justify-content-center align-items-center rounded-circle border-0"
                                           title="Saiu para Entrega"
                                           data-pedido-id="<?php echo $pedido['id']; ?>"
                                           data-tipo-entrega="<?php echo $pedido['tipo_entrega'] ?? 'delivery'; ?>">
                                            <iconify-icon icon="solar:delivery-outline" class="text-lg"></iconify-icon>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <!-- Concluído/Entregue -->
                                        <?php if ($pedido['status'] !== 'concluido' && $pedido['status'] !== 'cancelado'): ?>
                                        <button type="button" onclick="atualizarStatus(<?php echo $pedido['id']; ?>, 'concluido', this)"
                                           class="btn p-0 bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-32-px h-32-px d-flex justify-content-center align-items-center rounded-circle border-0"
                                           title="Marcar como Entregue">
                                            <iconify-icon icon="solar:check-read-outline" class="text-lg"></iconify-icon>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <!-- Cancelar -->
                                        <?php if ($pedido['status'] !== 'cancelado' && $pedido['status'] !== 'concluido'): ?>
                                        <button type="button" onclick="if(confirm('Tem certeza que deseja cancelar este pedido?')) atualizarStatus(<?php echo $pedido['id']; ?>, 'cancelado', this)"
                                           class="btn p-0 bg-danger-focus text-danger-600 bg-hover-danger-200 fw-medium w-32-px h-32-px d-flex justify-content-center align-items-center rounded-circle border-0"
                                           title="Cancelar">
                                            <iconify-icon icon="solar:close-circle-outline" class="text-lg"></iconify-icon>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <!-- Concluir/Arquivar Pedido -->
                                        <?php if ($pedido['status'] !== 'finalizado' && $pedido['status'] !== 'cancelado'): ?>
                                        <button type="button" onclick="concluirPedido(<?php echo $pedido['id']; ?>, this)"
                                           class="btn p-0 bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-32-px h-32-px d-flex justify-content-center align-items-center rounded-circle border-0"
                                           title="Concluir e Arquivar Pedido">
                                            <iconify-icon icon="solar:box-outline" class="text-lg"></iconify-icon>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <!-- Deletar -->
                                        <button type="button" onclick="deletarPedido(<?php echo $pedido['id']; ?>, this)"
                                           class="btn p-0 bg-danger-focus text-danger-600 bg-hover-danger-200 fw-medium w-32-px h-32-px d-flex justify-content-center align-items-center rounded-circle border-0"
                                           title="Deletar Pedido">
                                            <iconify-icon icon="solar:trash-bin-trash-outline" class="text-lg"></iconify-icon>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';

// Mapeamento de status para labels e classes
const statusMap = {
    'pendente': { label: 'Pendente', class: 'bg-warning-focus text-warning-main' },
    'em_andamento': { label: 'Em Preparo', class: 'bg-info-focus text-info-main' },
    'pronto': { label: 'Pronto', class: 'bg-primary-focus text-primary-main' },
    'saiu_entrega': { label: 'Saiu p/ Entrega', class: 'bg-secondary-focus text-secondary-main' },
    'concluido': { label: 'Entregue', class: 'bg-success-focus text-success-main' },
    'finalizado': { label: '📦 Finalizado', class: 'bg-success-focus text-success-main' },
    'cancelado': { label: 'Cancelado', class: 'bg-danger-focus text-danger-main' }
};

// Função para atualizar status via AJAX
async function atualizarStatus(pedidoId, novoStatus, btn) {
    // Desabilitar botão temporariamente
    btn.disabled = true;
    btn.style.opacity = '0.5';
    
    try {
        const response = await fetch('api/update_pedido_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                pedido_id: pedidoId,
                status: novoStatus,
                token: csrfToken
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Atualizar badge de status na linha
            const row = document.querySelector(`tr[data-pedido-id="${pedidoId}"]`);
            if (row) {
                row.dataset.pedidoStatus = novoStatus;
                const badge = row.querySelector('.status-badge');
                if (badge && statusMap[novoStatus]) {
                    badge.className = 'status-badge ' + statusMap[novoStatus].class + ' px-24 py-4 rounded-pill fw-medium text-sm';
                    badge.textContent = statusMap[novoStatus].label;
                    badge.dataset.status = novoStatus;
                }
            }
            
            // Mostrar toast de sucesso
            mostrarToast('Status atualizado com sucesso!', 'success');
            
            // Recarregar página após 1.5s para atualizar botões
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarToast('Erro: ' + (data.error || 'Desconhecido'), 'danger');
            btn.disabled = false;
            btn.style.opacity = '1';
        }
    } catch (error) {
        console.error('Erro:', error);
        mostrarToast('Erro ao atualizar status', 'danger');
        btn.disabled = false;
        btn.style.opacity = '1';
    }
}

// Função para deletar pedido via AJAX
async function deletarPedido(pedidoId, btn) {
    if (!confirm('⚠️ TEM CERTEZA QUE DESEJA DELETAR ESTE PEDIDO?\n\nEsta ação NÃO pode ser desfeita!')) {
        return;
    }
    
    btn.disabled = true;
    btn.style.opacity = '0.5';
    
    try {
        const response = await fetch(`pedidos.php?action=delete&id=${pedidoId}&token=${csrfToken}`);
        
        if (response.ok) {
            // Remover linha da tabela
            const row = document.querySelector(`tr[data-pedido-id="${pedidoId}"]`);
            if (row) {
                row.style.transition = 'opacity 0.3s';
                row.style.opacity = '0';
                setTimeout(() => row.remove(), 300);
            }
            mostrarToast('Pedido deletado com sucesso!', 'success');
        } else {
            mostrarToast('Erro ao deletar pedido', 'danger');
            btn.disabled = false;
            btn.style.opacity = '1';
        }
    } catch (error) {
        console.error('Erro:', error);
        mostrarToast('Erro ao deletar pedido', 'danger');
        btn.disabled = false;
        btn.style.opacity = '1';
    }
}

// Toast de notificação
function mostrarToast(mensagem, tipo) {
    const toast = document.createElement('div');
    toast.className = 'position-fixed top-0 end-0 p-3';
    toast.style.zIndex = '9999';
    toast.style.marginTop = '70px';
    toast.innerHTML = `
        <div class="toast show align-items-center text-white bg-${tipo} border-0">
            <div class="d-flex">
                <div class="toast-body"><strong>${mensagem}</strong></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.position-fixed').remove()"></button>
            </div>
        </div>
    `;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// Função para concluir e arquivar pedido
async function concluirPedido(pedidoId, btn) {
    if (!confirm('📦 Concluir este pedido?\n\nEle será arquivado e removido do Kanban.\nUma mensagem será enviada ao cliente.')) {
        return;
    }
    
    btn.disabled = true;
    btn.style.opacity = '0.5';
    
    try {
        const response = await fetch('api/concluir_pedido.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pedido_id: pedidoId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Atualizar badge de status na linha
            const row = document.querySelector(`tr[data-pedido-id="${pedidoId}"]`);
            if (row) {
                row.dataset.pedidoStatus = 'finalizado';
                const badge = row.querySelector('.status-badge');
                if (badge) {
                    badge.className = 'status-badge bg-success-focus text-success-main px-24 py-4 rounded-pill fw-medium text-sm';
                    badge.textContent = '📦 Finalizado';
                    badge.dataset.status = 'finalizado';
                }
            }
            
            mostrarToast(`📦 Pedido #${pedidoId} finalizado!` + (data.whatsapp_enviado ? ' ✅ WhatsApp enviado' : ''), 'success');
            
            // Recarregar página após 1.5s para atualizar botões
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarToast('Erro: ' + (data.error || 'Desconhecido'), 'danger');
            btn.disabled = false;
            btn.style.opacity = '1';
        }
    } catch (error) {
        console.error('Erro:', error);
        mostrarToast('Erro ao concluir pedido', 'danger');
        btn.disabled = false;
        btn.style.opacity = '1';
    }
}


// Função para confirmar deletar todos os pedidos
function confirmarDeletarTodos() {
    if (confirm('⚠️ ATENÇÃO: Esta ação irá deletar TODOS os pedidos e resetar o contador para começar do ID 1.\n\nEsta ação é IRREVERSÍVEL!\n\nDeseja realmente continuar?')) {
        if (confirm('⚠️ ÚLTIMA CONFIRMAÇÃO:\n\nVocê está prestes a deletar TODOS os pedidos do sistema.\n\nEsta ação NÃO PODE ser desfeita!\n\nTem certeza absoluta?')) {
            document.getElementById('form-deletar-todos').submit();
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-pedido-id]').forEach(row => {
        if (!row.dataset.pedidoStatus && row.getAttribute('data-pedido-status')) {
            row.dataset.pedidoStatus = row.getAttribute('data-pedido-status');
        }
    });
    
    const highlightRow = document.querySelector('.table-row-highlight');
    if (highlightRow) {
        highlightRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(() => {
            highlightRow.classList.remove('table-row-highlight');
        }, 4000);
    }
    
    const url = new URL(window.location.href);
    if (url.searchParams.has('highlight') || url.searchParams.has('action')) {
        url.searchParams.delete('highlight');
        url.searchParams.delete('action');
        url.searchParams.delete('id');
        url.searchParams.delete('status');
        url.searchParams.delete('token');
        window.history.replaceState({}, document.title, url.pathname);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>