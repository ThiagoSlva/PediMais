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
    elseif (!check_rate_limit('admin_login', $client_ip, 5, 900)) {
        $remaining = get_remaining_time('admin_login', $client_ip, 900);
        $min = ceil($remaining / 60);
        $erro = "Muitas tentativas de login. Tente novamente em {$min} minutos.";
    }
    else {
        $email = $_POST['email'];
        $senha = $_POST['senha'];

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            session_regenerate_id(true); // Prevent session fixation
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['usuario_nivel'] = $usuario['nivel_acesso'];

            // Atualizar último acesso
            $pdo->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?")->execute([$usuario['id']]);

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
    <title>Login - Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        .login-box h2 { margin-bottom: 20px; color: #333; }
        .form-group { margin-bottom: 15px; text-align: left; }
        .form-group label { display: block; margin-bottom: 5px; color: #666; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 10px; background: #9C27B0; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #7B1FA2; }
        .error { color: red; margin-bottom: 15px; font-size: 14px; }
        .forgot-link { display: block; margin-top: 15px; color: #666; text-decoration: none; font-size: 14px; }
        .forgot-link:hover { color: #9C27B0; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Painel Administrativo</h2>
        <?php if ($erro): ?>
            <div class="error"><?php echo $erro; ?></div>
        <?php
endif; ?>
        <form method="POST">
            <?php echo campo_csrf(); ?>
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" name="email" required placeholder="admin@admin.com">
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="senha" required placeholder="******">
            </div>
            <button type="submit" class="btn">Entrar</button>
        </form>
        <a href="esqueci_senha.php" class="forgot-link">
            <i class="fa-solid fa-key"></i> Esqueci minha senha
        </a>
    </div>
</body>
</html>