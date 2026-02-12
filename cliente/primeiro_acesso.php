<?php
require_once '../includes/config.php';
require_once '../includes/validar_senha.php';
require_once '../includes/security_headers.php';
require_once '../includes/rate_limiter.php';
require_once '../includes/csrf.php';
session_start();

$erro = '';
$sucesso = '';
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validar_csrf()) {
        $erro = 'Token de segurança inválido. Recarregue a página.';
    }
    // Rate limiting: máx 5 tentativas a cada 15 minutos
    elseif (!check_rate_limit('primeiro_acesso', $client_ip, 5, 900)) {
        $remaining = get_remaining_time('primeiro_acesso', $client_ip, 900);
        $min = ceil($remaining / 60);
        $erro = "Muitas tentativas. Tente novamente em {$min} minutos.";
    }
    else {
        $telefone = trim($_POST['telefone'] ?? '');
        $telefone_limpo = preg_replace('/[^0-9]/', '', $telefone);
        $email = trim($_POST['email'] ?? '');
        $nova_senha = $_POST['nova_senha'] ?? '';
        $confirmar_senha = $_POST['confirmar_senha'] ?? '';

        if (empty($telefone_limpo) || empty($email) || empty($nova_senha)) {
            $erro = 'Por favor, preencha todos os campos obrigatórios.';
        }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'E-mail inválido.';
        }
        elseif (!senha_atende_requisitos($nova_senha)) {
            $erros_senha = validar_senha($nova_senha);
            $erro = implode(' ', $erros_senha);
        }
        elseif ($nova_senha !== $confirmar_senha) {
            $erro = 'As senhas não conferem.';
        }
        else {
            // Buscar cliente pelo telefone
            $stmt = $pdo->prepare("SELECT id, nome, senha FROM clientes WHERE (telefone = ? OR telefone = ?) AND ativo = 1 LIMIT 1");
            $stmt->execute([$telefone_limpo, $telefone]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cliente) {
                $erro = 'Telefone não encontrado. <a href="cadastro.php">Cadastre-se</a>';
            }
            elseif (!empty($cliente['senha'])) {
                // Já tem senha - redirecionar para login
                $erro = 'Esta conta já possui uma senha. <a href="login.php">Faça login</a> ou <a href="esqueci_senha.php">recupere sua senha</a>.';
            }
            else {
                // Verificar se e-mail já está em uso por outro cliente
                $stmt_email = $pdo->prepare("SELECT id FROM clientes WHERE email = ? AND id != ?");
                $stmt_email->execute([$email, $cliente['id']]);
                if ($stmt_email->fetch()) {
                    $erro = 'Este e-mail já está cadastrado por outro cliente. Use outro e-mail.';
                }
                else {
                    // Definir senha e email pela primeira vez
                    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE clientes SET senha = ?, email = ? WHERE id = ?");

                    if ($stmt->execute([$senha_hash, $email, $cliente['id']])) {
                        // Login automático após criar senha
                        session_regenerate_id(true);
                        $_SESSION['cliente_id'] = $cliente['id'];
                        $_SESSION['cliente_nome'] = $cliente['nome'];
                        $_SESSION['cliente_email'] = $email;

                        $sucesso = 'Senha criada com sucesso! Redirecionando...';
                        header('Refresh: 2; url=index.php');
                    }
                    else {
                        $erro = 'Erro ao definir a senha. Tente novamente.';
                    }
                } // fecha else email duplicado
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Primeiro Acesso - Criar Senha</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
            padding: 20px;
            box-sizing: border-box;
        }
        .register-box { 
            background: white; 
            padding: 35px; 
            border-radius: 20px; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.2); 
            width: 100%; 
            max-width: 420px; 
            text-align: center; 
        }
        .register-box h2 { 
            margin-bottom: 10px; 
            color: #333; 
            font-size: 1.6rem;
        }
        .register-box .subtitle {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 25px;
            line-height: 1.5;
        }
        .form-group { 
            margin-bottom: 18px; 
            text-align: left; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 6px; 
            color: #555; 
            font-size: 0.9rem; 
            font-weight: 500;
        }
        .form-group input { 
            width: 100%; 
            padding: 14px; 
            border: 2px solid #eee; 
            border-radius: 10px; 
            box-sizing: border-box; 
            background: #f9f9f9; 
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #9C27B0;
            background: white;
        }
        .btn { 
            width: 100%; 
            padding: 14px; 
            background: linear-gradient(135deg, #9C27B0 0%, #7B1FA2 100%); 
            color: white; 
            border: none; 
            border-radius: 10px; 
            cursor: pointer; 
            font-size: 16px; 
            font-weight: 600; 
            margin-top: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(156, 39, 176, 0.4);
        }
        .error { 
            color: #e74c3c; 
            margin-bottom: 15px; 
            font-size: 14px; 
            background: #fde8e7; 
            padding: 12px; 
            border-radius: 8px; 
        }
        .error a { color: #9C27B0; font-weight: 600; text-decoration: none; }
        .error a:hover { text-decoration: underline; }
        .success { 
            color: #27ae60; 
            margin-bottom: 15px; 
            font-size: 14px; 
            background: #e8f8e8; 
            padding: 12px; 
            border-radius: 8px; 
        }
        .links { 
            margin-top: 25px; 
            font-size: 0.9rem; 
            color: #666;
        }
        .links a { 
            color: #9C27B0; 
            text-decoration: none; 
            font-weight: 600;
        }
        .links a:hover {
            text-decoration: underline;
        }
        .icon-header {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #9C27B0 0%, #7B1FA2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .icon-header i {
            font-size: 2rem;
            color: white;
        }
        .senha-requisitos {
            background: #f0f0f0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 18px;
            text-align: left;
            font-size: 0.8rem;
            color: #666;
        }
        .senha-requisitos ul {
            margin: 5px 0 0;
            padding-left: 18px;
        }
        .senha-requisitos li {
            margin-bottom: 3px;
        }
    </style>
</head>
<body>
    <div class="register-box">
        <div class="icon-header">
            <i class="fa-solid fa-key"></i>
        </div>
        <h2>Primeiro Acesso</h2>
        <p class="subtitle">Crie uma senha para acessar sua conta. Use o telefone cadastrado pela loja.</p>
        
        <?php if ($erro): ?>
            <div class="error"><?php echo $erro; ?></div>
        <?php
endif; ?>
        
        <?php if ($sucesso): ?>
            <div class="success"><i class="fa-solid fa-check-circle"></i> <?php echo $sucesso; ?></div>
        <?php
else: ?>
        
        <form method="POST">
            <?php echo campo_csrf(); ?>
            <div class="form-group">
                <label><i class="fa-solid fa-phone"></i> Telefone Cadastrado *</label>
                <input type="tel" name="telefone" required placeholder="(11) 99999-9999" 
                       value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label><i class="fa-solid fa-envelope"></i> E-mail *</label>
                <input type="email" name="email" required placeholder="seu@email.com" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="senha-requisitos">
                <strong>Requisitos da senha:</strong>
                <ul>
                    <li>Mínimo 8 caracteres</li>
                    <li>Pelo menos 1 letra maiúscula</li>
                    <li>Pelo menos 1 letra minúscula</li>
                    <li>Pelo menos 1 número</li>
                </ul>
            </div>

            <div class="form-group">
                <label><i class="fa-solid fa-lock"></i> Nova Senha *</label>
                <input type="password" name="nova_senha" required placeholder="Crie sua senha" minlength="8">
            </div>
            <div class="form-group">
                <label><i class="fa-solid fa-lock"></i> Confirmar Senha *</label>
                <input type="password" name="confirmar_senha" required placeholder="Repita a senha">
            </div>
            <button type="submit" class="btn"><i class="fa-solid fa-check"></i> Criar Senha</button>
        </form>
        
        <?php
endif; ?>
        
        <div class="links">
            <p>Já tem uma conta? <a href="login.php">Faça login</a></p>
            <p>Não tem cadastro? <a href="cadastro.php">Cadastre-se</a></p>
            <p><a href="../index.php"><i class="fa-solid fa-arrow-left"></i> Voltar ao Cardápio</a></p>
        </div>
    </div>

    <script>
    // Máscara de telefone
    document.querySelector('input[name="telefone"]').addEventListener('input', function(e) {
        let v = e.target.value.replace(/\D/g, '');
        if (v.length > 11) v = v.slice(0, 11);
        if (v.length > 6) v = '(' + v.slice(0,2) + ') ' + v.slice(2,7) + '-' + v.slice(7);
        else if (v.length > 2) v = '(' + v.slice(0,2) + ') ' + v.slice(2);
        else if (v.length > 0) v = '(' + v;
        e.target.value = v;
    });
    </script>
</body>
</html>