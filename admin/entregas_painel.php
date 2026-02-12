<?php
include 'includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';
require_once '../includes/csrf.php';

verificar_login();

// Get user level for display purposes
$usuario = get_usuario_atual();
$nivel = $usuario['nivel'] ?? 'guest';

$msg = '';
$msg_tipo = '';

// Handle marking delivery as complete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['concluir_entrega'])) {
    if (!validar_csrf()) {
        $msg = 'Token de segurança inválido. Recarregue a página.';
        $msg_tipo = 'danger';
    }
    else {
        try {
            $pedido_id = (int)$_POST['pedido_id'];

            // Update order as delivered - use 'concluido' to match system standard
            $stmt = $pdo->prepare("UPDATE pedidos SET status = 'concluido', em_preparo = 1, saiu_entrega = 1, entregue = 1, data_conclusao = NOW() WHERE id = ?");
            $result = $stmt->execute([$pedido_id]);

            if (!$result) {
                throw new Exception("Falha ao atualizar pedido");
            }

            // Update Kanban lane to "Entregue" if exists
            $stmt_lane = $pdo->prepare("SELECT id FROM kanban_lanes WHERE LOWER(nome) LIKE '%entreg%' OR acao = 'entregue' LIMIT 1");
            $stmt_lane->execute();
            $lane = $stmt_lane->fetch();
            if ($lane) {
                $stmt = $pdo->prepare("UPDATE pedidos SET lane_id = ? WHERE id = ?");
                $stmt->execute([$lane['id'], $pedido_id]);
            }

            // Get order details for notifications
            $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
            $stmt->execute([$pedido_id]);
            $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

            // Send WhatsApp notifications
            if ($pedido && !empty($pedido['cliente_telefone'])) {
                try {
                    require_once '../includes/whatsapp_helper.php';
                    $whatsapp = new WhatsAppHelper($pdo);

                    // 1) PRIMEIRO: Criar token de avaliação (antes do sendStatusUpdate para {link} funcionar)
                    $stmt_config = $pdo->query("SELECT * FROM configuracao_avaliacoes LIMIT 1");
                    $config_aval = $stmt_config->fetch(PDO::FETCH_ASSOC);

                    if ($config_aval && $config_aval['ativo']) {
                        // Verificar se já não existe token para este pedido
                        $stmt_check = $pdo->prepare("SELECT id FROM avaliacoes WHERE pedido_id = ? LIMIT 1");
                        $stmt_check->execute([$pedido_id]);

                        if (!$stmt_check->fetch()) {
                            // Gerar token único para avaliação
                            $token = bin2hex(random_bytes(16));

                            // Inserir entrada de avaliação (usando nomes corretos das colunas)
                            $stmt_ins = $pdo->prepare("INSERT INTO avaliacoes (pedido_id, nome, cliente_nome, token, avaliacao) VALUES (?, ?, ?, ?, 0)");
                            $stmt_ins->execute([$pedido_id, $pedido['cliente_nome'], $pedido['cliente_nome'], $token]);
                        }
                    }
                    // 2) Enviar notificação de conclusão
                    $whatsapp->sendStatusUpdate($pedido, 'concluido');

                    // 3) Enviar link de avaliação como mensagem separada (clicável)
                    if (isset($token) && !empty($token)) {
                        sleep(1); // Pequeno delay entre mensagens
                        $rating_link = SITE_URL . '/avaliar_pedido.php?token=' . $token;
                        $link_msg = "⭐ Avalie seu pedido:\n" . $rating_link;
                        $whatsapp->sendMessage($pedido['cliente_telefone'], $link_msg);
                    }

                }
                catch (Exception $e) {
                    // Log error but don't fail the delivery marking
                    error_log("WhatsApp notification failed: " . $e->getMessage());
                }
            }

            $msg = 'Entrega concluída com sucesso!';
            $msg_tipo = 'success';

        }
        catch (Exception $e) {
            $msg = 'Erro: ' . $e->getMessage();
            $msg_tipo = 'danger';
        }
    } // fecha else validar_csrf
}

// Get deliveries - for entregador show ALL orders with status 'saiu_entrega'
// Admin/gerente also sees all pending deliveries
$stmt = $pdo->query("
    SELECT p.*, 
           c.nome as cliente_nome, 
           c.telefone as cliente_telefone,
           u.nome as entregador_nome
    FROM pedidos p
    LEFT JOIN clientes c ON p.cliente_id = c.id
    LEFT JOIN usuarios u ON p.entregador_id = u.id
    WHERE p.status = 'saiu_entrega'
      AND p.entregue = 0
    ORDER BY p.data_pedido DESC
");
$entregas = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<style>
.entrega-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    margin-bottom: 16px;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.entrega-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.12);
}

.entrega-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.entrega-body {
    padding: 20px;
}

.entrega-info {
    display: grid;
    gap: 12px;
}

.info-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.info-row iconify-icon {
    font-size: 20px;
    color: #667eea;
    flex-shrink: 0;
    margin-top: 2px;
}

.info-label {
    font-size: 0.75rem;
    color: #999;
    text-transform: uppercase;
    margin-bottom: 2px;
}

.info-value {
    font-weight: 600;
    color: #333;
}

.entrega-footer {
    padding: 16px 20px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 12px;
}

.btn-concluir {
    flex: 1;
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    border: none;
    color: white;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-concluir:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
}

.btn-whatsapp {
    background: #25D366;
    border: none;
    color: white;
    padding: 12px 16px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-whatsapp:hover {
    background: #128C7E;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-state iconify-icon {
    font-size: 64px;
    color: #ddd;
    margin-bottom: 16px;
}

/* Dark mode */
[data-theme="dark"] .entrega-card {
    background: var(--base-soft, #1e1e2e);
}

[data-theme="dark"] .entrega-body {
    color: rgba(255,255,255,0.9);
}

[data-theme="dark"] .info-value {
    color: rgba(255,255,255,0.9);
}

[data-theme="dark"] .entrega-footer {
    border-color: rgba(255,255,255,0.1);
}
</style>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <div>
            <h6 class="fw-semibold mb-0">🚴 Painel de Entregas</h6>
            <p class="text-sm text-secondary mb-0">
                <?php if ($nivel === 'entregador'): ?>
                    Gerencie suas entregas em andamento
                <?php
else: ?>
                    Todas as entregas em andamento
                <?php
endif; ?>
            </p>
        </div>
        <div class="badge bg-primary-600 px-3 py-2">
            <?php echo count($entregas); ?> entrega(s) pendente(s)
        </div>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?php echo $msg_tipo; ?> alert-dismissible fade show" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php
endif; ?>

    <?php if (empty($entregas)): ?>
    <div class="card radius-12">
        <div class="card-body">
            <div class="empty-state">
                <iconify-icon icon="solar:delivery-bold-duotone"></iconify-icon>
                <h5>Nenhuma entrega pendente</h5>
                <p>Quando houver pedidos para entregar, eles aparecerão aqui.</p>
            </div>
        </div>
    </div>
    <?php
else: ?>
    
    <div class="row">
        <?php foreach ($entregas as $entrega):
        $telefone = preg_replace('/[^0-9]/', '', $entrega['cliente_telefone'] ?? $entrega['telefone'] ?? '');
        // Adiciona código do país se não tiver
        if (strlen($telefone) <= 11 && substr($telefone, 0, 2) !== '55') {
            $telefone = '55' . $telefone;
        }
?>
        <div class="col-xl-6 col-lg-12">
            <div class="entrega-card">
                <div class="entrega-header">
                    <div>
                        <strong>Pedido #<?php echo $entrega['id']; ?></strong>
                        <br>
                        <small><?php echo date('d/m/Y H:i', strtotime($entrega['data_pedido'])); ?></small>
                    </div>
                    <div class="text-end">
                        <strong>R$ <?php echo number_format($entrega['valor_total'], 2, ',', '.'); ?></strong>
                        <br>
                        <span class="badge bg-warning text-dark"><?php echo ucfirst(str_replace('_', ' ', $entrega['status'])); ?></span>
                    </div>
                </div>
                
                <div class="entrega-body">
                    <div class="entrega-info">
                        <div class="info-row">
                            <iconify-icon icon="solar:user-bold-duotone"></iconify-icon>
                            <div>
                                <div class="info-label">Cliente</div>
                                <div class="info-value"><?php echo htmlspecialchars($entrega['cliente_nome'] ?? $entrega['nome'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <iconify-icon icon="solar:phone-bold-duotone"></iconify-icon>
                            <div>
                                <div class="info-label">Telefone</div>
                                <div class="info-value">
                                    <?php echo htmlspecialchars($entrega['cliente_telefone'] ?? $entrega['telefone'] ?? 'N/A'); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <iconify-icon icon="solar:map-point-bold-duotone"></iconify-icon>
                            <div>
                                <div class="info-label">Endereço</div>
                                <div class="info-value">
                                    <?php echo htmlspecialchars($entrega['cliente_endereco'] ?? $entrega['endereco'] ?? 'N/A'); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="info-row">
                            <iconify-icon icon="solar:wallet-money-bold-duotone"></iconify-icon>
                            <div>
                                <div class="info-label">Pagamento</div>
                                <div class="info-value"><?php echo htmlspecialchars($entrega['forma_pagamento'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        
                        <?php if (!empty($entrega['observacoes'])): ?>
                        <div class="info-row">
                            <iconify-icon icon="solar:notes-bold-duotone"></iconify-icon>
                            <div>
                                <div class="info-label">Observações</div>
                                <div class="info-value"><?php echo htmlspecialchars($entrega['observacoes']); ?></div>
                            </div>
                        </div>
                        <?php
        endif; ?>
                        
                        <?php if ($nivel !== 'entregador' && !empty($entrega['entregador_nome'])): ?>
                        <div class="info-row">
                            <iconify-icon icon="solar:delivery-bold-duotone"></iconify-icon>
                            <div>
                                <div class="info-label">Entregador</div>
                                <div class="info-value"><?php echo htmlspecialchars($entrega['entregador_nome']); ?></div>
                            </div>
                        </div>
                        <?php
        endif; ?>
                    </div>
                </div>
                
                <div class="entrega-footer">
                    <form method="POST" style="flex: 1;">
                        <?php echo campo_csrf(); ?>
                        <input type="hidden" name="pedido_id" value="<?php echo $entrega['id']; ?>">
                        <button type="submit" name="concluir_entrega" class="btn-concluir w-100" onclick="return confirm('Confirmar entrega realizada?')">
                            <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                            Marcar como Entregue
                        </button>
                    </form>
                    
                    <?php if ($telefone): ?>
                    <a href="https://wa.me/<?php echo $telefone; ?>" target="_blank" class="btn-whatsapp">
                        <iconify-icon icon="logos:whatsapp-icon"></iconify-icon>
                    </a>
                    <?php
        endif; ?>
                </div>
            </div>
        </div>
        <?php
    endforeach; ?>
    </div>
    
    <?php
endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
