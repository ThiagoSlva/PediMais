<?php
require_once '../includes/config.php';
require_once 'includes/auth.php';

verificar_login_cliente();

$cliente_id = $_SESSION['cliente_id'];

// Get client data
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

// Get fidelity config
$stmt = $pdo->query("SELECT * FROM fidelidade_config WHERE id = 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

$fidelidade_ativo = $config['ativo'] ?? 0;
$pedidos_para_resgate = $config['quantidade_pedidos'] ?? 10;

// Redirect if fidelity is not active
if (!$fidelidade_ativo) {
    header('Location: index.php');
    exit;
}

// Count active points
$stmt = $pdo->prepare("SELECT COUNT(*) FROM fidelidade_pontos WHERE cliente_id = ? AND status = 'ativo'");
$stmt->execute([$cliente_id]);
$pontos_ativos = $stmt->fetchColumn();

// Get points history
$stmt = $pdo->prepare("
    SELECT fp.*, p.codigo_pedido, p.data_pedido, p.valor_total
    FROM fidelidade_pontos fp
    JOIN pedidos p ON fp.pedido_id = p.id
    WHERE fp.cliente_id = ?
    ORDER BY fp.criado_em DESC
    LIMIT 20
");
$stmt->execute([$cliente_id]);
$historico_pontos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get redemption history
$stmt = $pdo->prepare("
    SELECT fr.*, p.codigo_pedido
    FROM fidelidade_resgates fr
    LEFT JOIN pedidos p ON fr.pedido_id = p.id
    WHERE fr.cliente_id = ?
    ORDER BY fr.criado_em DESC
    LIMIT 10
");
$stmt->execute([$cliente_id]);
$historico_resgates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available rewards
$stmt = $pdo->query("
    SELECT fp.*, p.nome, p.descricao, p.preco, p.imagem_path
    FROM fidelidade_produtos fp
    JOIN produtos p ON fp.produto_id = p.id
    WHERE fp.ativo = 1
    ORDER BY fp.ordem ASC
");
$recompensas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if can redeem
$pode_resgatar = $pontos_ativos >= $pedidos_para_resgate;
$progresso = min(100, ($pontos_ativos / $pedidos_para_resgate) * 100);
$faltam = max(0, $pedidos_para_resgate - $pontos_ativos);

$page_title = 'Programa de Fidelidade';
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4 fade-in">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 0.875rem;">
                <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color: var(--primary);">Início</a></li>
                <li class="breadcrumb-item active" style="color: var(--gray-500);">Fidelidade</li>
            </ol>
        </nav>
        <h4 class="mb-0" style="color: var(--gray-900);">
            <i class="fa-solid fa-crown me-2 text-warning"></i>Programa de Fidelidade
        </h4>
    </div>
</div>

<!-- Fidelity Progress Card -->
<div class="fidelity-card mb-4 fade-in" style="animation-delay: 0.1s; padding: 2rem;">
    <div class="row align-items-center">
        <div class="col-md-8">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-star fa-2x text-white"></i>
                </div>
                <div>
                    <h5 class="mb-0 text-white">Seus Pontos</h5>
                    <p class="mb-0 text-white-50">Acumule pontos e ganhe recompensas!</p>
                </div>
            </div>
            
            <div class="fidelity-points mb-3" style="font-size: 2rem;">
                <?php echo $pontos_ativos; ?> / <?php echo $pedidos_para_resgate; ?> pontos
            </div>
            
            <div class="fidelity-progress mb-3" style="height: 12px; border-radius: 6px;">
                <div class="progress-bar" style="width: <?php echo $progresso; ?>%; height: 100%; border-radius: 6px;"></div>
            </div>
            
            <div class="fidelity-text">
                <?php if ($pode_resgatar): ?>
                    🎉 Você pode resgatar <?php echo floor($pontos_ativos / $pedidos_para_resgate); ?> recompensa(s)!
                <?php else: ?>
                    Faltam <strong><?php echo $faltam; ?></strong> pontos para sua próxima recompensa
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-4 text-center d-none d-md-block">
            <i class="fa-solid fa-gift" style="font-size: 6rem; color: rgba(255,255,255,0.3);"></i>
        </div>
    </div>
</div>

<!-- How it Works -->
<div class="card-premium mb-4 fade-in" style="animation-delay: 0.15s;">
    <div class="card-header">
        <i class="fa-solid fa-info-circle me-2 text-info"></i>Como Funciona
    </div>
    <div class="card-body">
        <div class="row g-4 text-center">
            <div class="col-4">
                <div style="width: 50px; height: 50px; background: var(--primary-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem;">
                    <i class="fa-solid fa-cart-shopping" style="color: var(--primary);"></i>
                </div>
                <h6 style="color: var(--gray-800); font-size: 0.875rem;">Faça Pedidos</h6>
                <p style="color: var(--gray-500); font-size: 0.75rem; margin: 0;">A cada pedido confirmado você ganha 1 ponto</p>
            </div>
            <div class="col-4">
                <div style="width: 50px; height: 50px; background: var(--warning-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem;">
                    <i class="fa-solid fa-star" style="color: var(--warning);"></i>
                </div>
                <h6 style="color: var(--gray-800); font-size: 0.875rem;">Acumule Pontos</h6>
                <p style="color: var(--gray-500); font-size: 0.75rem; margin: 0;">Junte <?php echo $pedidos_para_resgate; ?> pontos para liberar o resgate</p>
            </div>
            <div class="col-4">
                <div style="width: 50px; height: 50px; background: var(--success-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 0.75rem;">
                    <i class="fa-solid fa-gift" style="color: var(--success);"></i>
                </div>
                <h6 style="color: var(--gray-800); font-size: 0.875rem;">Ganhe Prêmios</h6>
                <p style="color: var(--gray-500); font-size: 0.75rem; margin: 0;">Escolha sua recompensa grátis!</p>
            </div>
        </div>
    </div>
</div>

<!-- Available Rewards -->
<div class="card-premium mb-4 fade-in" style="animation-delay: 0.2s;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fa-solid fa-gift me-2 text-success"></i>Recompensas Disponíveis</span>
        <?php if ($pode_resgatar): ?>
            <span class="badge bg-success px-3 py-2">Você pode resgatar!</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($recompensas)): ?>
            <div class="text-center py-4">
                <i class="fa-solid fa-gift fa-3x mb-3" style="color: var(--gray-300);"></i>
                <p style="color: var(--gray-500);">Nenhuma recompensa disponível no momento.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($recompensas as $recompensa): ?>
                    <div class="col-md-4 col-6">
                        <div class="border rounded-3 p-3 h-100 text-center <?php echo $pode_resgatar ? 'border-success' : ''; ?>" style="background: var(--gray-50);">
                            <?php if (!empty($recompensa['imagem_path']) && file_exists('../' . $recompensa['imagem_path'])): ?>
                                <img src="../<?php echo htmlspecialchars($recompensa['imagem_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($recompensa['nome']); ?>"
                                     class="rounded mb-2"
                                     style="width: 80px; height: 80px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded mb-2 d-flex align-items-center justify-content-center mx-auto" 
                                     style="width: 80px; height: 80px; background: var(--gray-200);">
                                    <i class="fa-solid fa-utensils fa-2x" style="color: var(--gray-400);"></i>
                                </div>
                            <?php endif; ?>
                            
                            <h6 class="mb-1" style="color: var(--gray-800); font-size: 0.875rem;">
                                <?php echo htmlspecialchars($recompensa['nome']); ?>
                            </h6>
                            <small style="color: var(--gray-500);">
                                <?php echo $recompensa['quantidade']; ?>x unidade(s)
                            </small>
                            
                            <?php if ($pode_resgatar): ?>
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-success" onclick="confirmarResgate(<?php echo $recompensa['produto_id']; ?>, '<?php echo htmlspecialchars(addslashes($recompensa['nome'])); ?>')">
                                        <i class="fa-solid fa-check me-1"></i>Resgatar
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Points History -->
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card-premium fade-in" style="animation-delay: 0.25s;">
            <div class="card-header">
                <i class="fa-solid fa-history me-2 text-primary"></i>Histórico de Pontos
            </div>
            <div class="card-body p-0">
                <?php if (empty($historico_pontos)): ?>
                    <div class="text-center py-4">
                        <i class="fa-solid fa-star fa-2x mb-2" style="color: var(--gray-300);"></i>
                        <p style="color: var(--gray-500); margin: 0;">Nenhum ponto acumulado ainda.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($historico_pontos as $ponto): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center" style="border-color: var(--gray-100);">
                                <div>
                                    <span class="fw-medium" style="color: var(--gray-800);">
                                        Pedido #<?php echo htmlspecialchars($ponto['codigo_pedido']); ?>
                                    </span>
                                    <br>
                                    <small style="color: var(--gray-500);">
                                        <?php echo date('d/m/Y', strtotime($ponto['criado_em'])); ?>
                                    </small>
                                </div>
                                <span class="status-badge <?php echo $ponto['status']; ?>">
                                    <?php 
                                    echo match($ponto['status']) {
                                        'ativo' => '+1 ⭐',
                                        'resgatado' => 'Usado',
                                        'cancelado' => 'Cancelado',
                                        default => $ponto['status']
                                    };
                                    ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card-premium fade-in" style="animation-delay: 0.3s;">
            <div class="card-header">
                <i class="fa-solid fa-gift me-2 text-danger"></i>Histórico de Resgates
            </div>
            <div class="card-body p-0">
                <?php if (empty($historico_resgates)): ?>
                    <div class="text-center py-4">
                        <i class="fa-solid fa-gift fa-2x mb-2" style="color: var(--gray-300);"></i>
                        <p style="color: var(--gray-500); margin: 0;">Nenhum resgate realizado.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($historico_resgates as $resgate): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center" style="border-color: var(--gray-100);">
                                <div>
                                    <span class="fw-medium" style="color: var(--gray-800);">
                                        <?php echo $resgate['pontos_usados']; ?> pontos usados
                                    </span>
                                    <br>
                                    <small style="color: var(--gray-500);">
                                        <?php echo date('d/m/Y \à\s H:i', strtotime($resgate['criado_em'])); ?>
                                    </small>
                                </div>
                                <span class="status-badge <?php echo $resgate['status']; ?>">
                                    <?php 
                                    echo match($resgate['status']) {
                                        'pendente' => '⏳ Pendente',
                                        'resgatado' => '✅ Resgatado',
                                        'cancelado' => '❌ Cancelado',
                                        default => $resgate['status']
                                    };
                                    ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- CTA -->
<?php if (!$pode_resgatar): ?>
<div class="text-center mt-4 fade-in" style="animation-delay: 0.35s;">
    <a href="../index.php" class="btn btn-premium btn-primary-gradient">
        <i class="fa-solid fa-cart-plus me-2"></i>Fazer um Pedido
    </a>
    <p class="mt-2" style="color: var(--gray-500); font-size: 0.875rem;">
        Cada pedido vale 1 ponto!
    </p>
</div>
<?php endif; ?>

<script>
function confirmarResgate(produtoId, produtoNome) {
    Swal.fire({
        title: 'Confirmar Resgate',
        html: `Você deseja resgatar <strong>${produtoNome}</strong>?<br><br>
               Isso usará <?php echo $pedidos_para_resgate; ?> pontos.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10B981',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Sim, resgatar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            realizarResgate(produtoId);
        }
    });
}

function realizarResgate(produtoId) {
    Swal.fire({
        title: 'Processando...',
        text: 'Aguarde enquanto processamos seu resgate.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch('api/resgatar_fidelidade.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ produto_id: produtoId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Resgate Realizado!',
                text: data.message || 'Sua recompensa foi resgatada com sucesso!',
                icon: 'success',
                confirmButtonColor: '#10B981'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                title: 'Erro',
                text: data.message || 'Não foi possível realizar o resgate.',
                icon: 'error',
                confirmButtonColor: '#EF4444'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            title: 'Erro',
            text: 'Ocorreu um erro ao processar o resgate.',
            icon: 'error',
            confirmButtonColor: '#EF4444'
        });
    });
}
</script>

<?php include 'includes/footer.php'; ?>