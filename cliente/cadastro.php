<?php
require_once '../includes/config.php';
session_start();

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    // Validações
    if (empty($nome) || empty($email) || empty($telefone) || empty($senha)) {
        $erro = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres.';
    } elseif ($senha !== $confirmar_senha) {
        $erro = 'As senhas não conferem.';
    } else {
        // Limpar telefone primeiro
        $telefone_limpo = preg_replace('/[^0-9]/', '', $telefone);
        
        // Verificar se telefone já existe (ANTES do email para priorizar)
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE telefone = ? OR telefone = ?");
        $stmt->execute([$telefone_limpo, $telefone]);
        if ($stmt->fetch()) {
            $erro = 'Este telefone já está cadastrado. <a href="login.php">Faça login</a>';
        } else {
            // Verificar se e-mail já existe
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $erro = 'Este e-mail já está cadastrado. <a href="login.php">Faça login</a>';
            } else {
                // Criar cliente
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO clientes (nome, email, telefone, senha, ativo, criado_em) VALUES (?, ?, ?, ?, 1, NOW())");
                
                if ($stmt->execute([$nome, $email, $telefone_limpo, $senha_hash])) {
                    $sucesso = 'Conta criada com sucesso! Você já pode fazer login.';
                } else {
                    $erro = 'Erro ao criar conta. Tente novamente.';
                }
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
    <title>Cadastro - Cliente</title>
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
            margin-bottom: 25px; 
            color: #333; 
            font-size: 1.6rem;
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
    </style>
</head>
<body>
    <div class="register-box">
        <div class="icon-header">
            <i class="fa-solid fa-user-plus"></i>
        </div>
        <h2>Criar Conta</h2>
        
        <?php if ($erro): ?>
            <div class="error"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <?php if ($sucesso): ?>
            <div class="success"><?php echo $sucesso; ?></div>
            <div class="links">
                <p><a href="login.php"><i class="fa-solid fa-sign-in-alt"></i> Fazer Login</a></p>
            </div>
        <?php else: ?>
        
        <form method="POST">
            <div class="form-group">
                <label><i class="fa-solid fa-user"></i> Nome Completo *</label>
                <input type="text" name="nome" required placeholder="Seu nome" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label><i class="fa-solid fa-envelope"></i> E-mail *</label>
                <input type="email" name="email" required placeholder="seu@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label><i class="fa-solid fa-phone"></i> Telefone *</label>
                <input type="tel" name="telefone" required placeholder="(11) 99999-9999" value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label><i class="fa-solid fa-lock"></i> Senha *</label>
                <input type="password" name="senha" required placeholder="Mínimo 6 caracteres" minlength="6">
            </div>
            <div class="form-group">
                <label><i class="fa-solid fa-lock"></i> Confirmar Senha *</label>
                <input type="password" name="confirmar_senha" required placeholder="Repita a senha">
            </div>
            <button type="submit" class="btn"><i class="fa-solid fa-check"></i> Criar Conta</button>
        </form>
        
        <?php endif; ?>
        
        <div class="links">
            <p>Já tem uma conta? <a href="login.php">Faça login</a></p>
            <p><a href="../index.php"><i class="fa-solid fa-arrow-left"></i> Voltar ao Cardápio</a></p>
        </div>
    </div>
</body>
</html>
