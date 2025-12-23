<?php
require_once '../includes/config.php';
require_once 'includes/auth.php';

verificar_login_cliente();
$cliente = get_cliente_atual();

// Buscar últimos pedidos
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE cliente_id = ? ORDER BY data_pedido DESC LIMIT 5");
$stmt->execute([$cliente['id']]);
$pedidos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f5f6fa; margin: 0; padding-bottom: 80px; }
        .header { background: white; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .header h2 { margin: 0; color: #333; font-size: 1.2rem; }
        .container { padding: 20px; max-width: 800px; margin: 0 auto; }
        .card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .card h3 { margin-top: 0; color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; font-size: 1.1rem; }
        .pedido-item { border-bottom: 1px solid #f0f0f0; padding: 15px 0; }
        .pedido-item:last-child { border-bottom: none; }
        .pedido-header { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .pedido-status { font-size: 0.8rem; padding: 3px 8px; border-radius: 10px; background: #eee; }
        .status-pendente { background: #fff3cd; color: #856404; }
        .status-concluido { background: #d4edda; color: #155724; }
        .btn-sair { color: #e74c3c; text-decoration: none; font-weight: 600; }
        .nav-bottom { position: fixed; bottom: 0; left: 0; width: 100%; background: white; display: flex; justify-content: space-around; padding: 15px 0; box-shadow: 0 -2px 10px rgba(0,0,0,0.05); }
        .nav-item { text-align: center; color: #999; text-decoration: none; font-size: 0.8rem; }
        .nav-item.active { color: #9C27B0; }
        .nav-item i { display: block; font-size: 1.2rem; margin-bottom: 3px; }
    </style>
</head>
<body>

<div class="header">
    <h2>Olá, <?php echo htmlspecialchars($cliente['nome']); ?></h2>
    <a href="logout.php" class="btn-sair"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
</div>

<div class="container">
    <div class="card">
        <h3>Meus Pedidos Recentes</h3>
        <?php if (empty($pedidos)): ?>
            <p style="color: #999; text-align: center; padding: 20px;">Você ainda não fez nenhum pedido.</p>
        <?php else: ?>
            <?php foreach ($pedidos as $pedido): ?>
            <div class="pedido-item">
                <div class="pedido-header">
                    <strong>#<?php echo $pedido['id']; ?></strong>
                    <span class="pedido-status status-<?php echo $pedido['status']; ?>"><?php echo ucfirst($pedido['status']); ?></span>
                </div>
                <div style="font-size: 0.9rem; color: #666;">
                    <?php echo date('d/m/Y H:i', strtotime($pedido['data_pedido'])); ?>
                </div>
                <div style="margin-top: 5px; font-weight: 600; color: #9C27B0;">
                    R$ <?php echo number_format($pedido['valor_total'], 2, ',', '.'); ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="nav-bottom">
    <a href="../index.php" class="nav-item">
        <i class="fa-solid fa-house"></i>
        Início
    </a>
    <a href="#" class="nav-item active">
        <i class="fa-regular fa-user"></i>
        Perfil
    </a>
</div>

</body>
</html>
