<?php
require_once '../includes/config.php';
require_once '../includes/security_headers.php';
require_once '../includes/rate_limiter.php';
require_once '../includes/csrf.php';
session_start();

$erro = '';
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validar_csrf()) {
        $erro = 'Token de segurança inválido. Recarregue a página.';
    }
    // Rate limiting: máx 5 tentativas a cada 15 minutos
    elseif (!check_rate_limit('cliente_login', $client_ip, 5, 900)) {
        $remaining = get_remaining_time('cliente_login', $client_ip, 900);
        $min = ceil($remaining / 60);
        $erro = "Muitas tentativas de login. Tente novamente em {$min} minutos.";
    }
    else {
        $email = $_POST['email'];
        $senha = $_POST['senha'];

        // Nota: O SQL original tem 'senha' na tabela clientes.
        // Assumindo que também usa password_hash.
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE email = ? AND ativo = 1 LIMIT 1");
        $stmt->execute([$email]);
        $cliente = $stmt->fetch();

        if ($cliente && password_verify($senha, $cliente['senha'])) {
            session_regenerate_id(true); // Prevenir session fixation
            $_SESSION['cliente_id'] = $cliente['id'];
            $_SESSION['cliente_nome'] = $cliente['nome'];
            $_SESSION['cliente_email'] = $cliente['email'];

            // Atualizar último login
            $pdo->prepare("UPDATE clientes SET ultimo_login = NOW() WHERE id = ?")->execute([$cliente['id']]);

            header('Location: index.php');
            exit;
        }
        else {
            $erro = 'E-mail ou senha incorretos.';
        }
    } // fecha else do rate limit
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cliente</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f5f6fa; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); width: 90%; max-width: 400px; text-align: center; }
        .login-box h2 { margin-bottom: 20px; color: #333; }
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; margin-bottom: 5px; color: #666; font-size: 0.9rem; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #eee; border-radius: 8px; box-sizing: border-box; background: #f9f9f9; }
        .btn { width: 100%; padding: 12px; background: #9C27B0; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; margin-top: 10px; }
        .btn:hover { background: #7B1FA2; }
        .error { color: #e74c3c; margin-bottom: 15px; font-size: 14px; background: #fde8e7; padding: 10px; border-radius: 5px; }
        .links { margin-top: 20px; font-size: 0.9rem; }
        .links a { color: #9C27B0; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Minha Conta</h2>
        <?php if ($erro): ?>
            <div class="error"><?php echo $erro; ?></div>
        <?php
endif; ?>
        <form method="POST">
            <?php echo campo_csrf(); ?>
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" name="email" required placeholder="seu@email.com">
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="senha" required placeholder="******">
            </div>
            <button type="submit" class="btn">Entrar</button>
        </form>
        <div class="links">
            <p><a href="esqueci_senha.php"><i class="fa-solid fa-key"></i> Esqueci minha senha</a></p>
            <p><a href="primeiro_acesso.php"><i class="fa-solid fa-user-plus"></i> Primeiro acesso? Crie sua senha</a></p>
            <p>Não tem uma conta? <a href="cadastro.php">Cadastre-se</a></p>
            <p><a href="../index.php">Voltar ao Cardápio</a></p>
        </div>
    </div>
</body>
</html>