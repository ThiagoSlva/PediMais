<?php
require_once '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci a Senha - Cliente</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: 'Poppins', sans-serif; 
            background: #f5f6fa; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
        }
        .forgot-box { 
            background: white; 
            padding: 30px; 
            border-radius: 15px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            width: 90%; 
            max-width: 400px; 
            text-align: center; 
        }
        .forgot-box h2 { 
            margin-bottom: 10px; 
            color: #333; 
        }
        .forgot-box p.subtitle { 
            color: #666; 
            margin-bottom: 20px; 
            font-size: 14px; 
        }
        .form-group { 
            margin-bottom: 15px; 
            text-align: left; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 5px; 
            color: #666; 
            font-size: 0.9rem; 
        }
        .form-group input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #eee; 
            border-radius: 8px; 
            box-sizing: border-box; 
            background: #f9f9f9; 
        }
        .btn { 
            width: 100%; 
            padding: 12px; 
            background: #9C27B0; 
            color: white; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 16px; 
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn:hover { 
            background: #7B1FA2; 
        }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .alert { 
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 15px; 
            font-size: 14px;
            display: none;
        }
        .alert-success { 
            background: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb;
        }
        .alert-danger { 
            background: #fde8e7; 
            color: #e74c3c; 
            border: 1px solid #f5c6cb;
        }
        .info-box {
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #2e7d32;
            text-align: left;
        }
        .info-box i {
            margin-right: 8px;
            color: #25d366;
        }
        .links { 
            margin-top: 20px; 
            font-size: 0.9rem; 
        }
        .links a { 
            color: #9C27B0; 
            text-decoration: none; 
        }
        .links a:hover {
            text-decoration: underline;
        }
        .spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid #fff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="forgot-box">
        <h2><i class="fa-solid fa-key" style="color: #9C27B0;"></i> Esqueci a Senha</h2>
        <p class="subtitle">Informe seu e-mail para receber uma nova senha</p>
        
        <div class="info-box">
            <i class="fa-solid fa-paper-plane"></i>
            A nova senha será enviada via <strong>WhatsApp</strong> e <strong>E-mail</strong>.
        </div>
        
        <div id="alert-success" class="alert alert-success">
            <i class="fa-solid fa-check-circle"></i> <span id="success-msg"></span>
        </div>
        
        <div id="alert-error" class="alert alert-danger">
            <i class="fa-solid fa-exclamation-circle"></i> <span id="error-msg"></span>
        </div>
        
        <form id="forgot-form">
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" name="email" id="email" required placeholder="seu@email.com">
            </div>
            <button type="submit" class="btn" id="submit-btn">
                <i class="fa-solid fa-paper-plane"></i>
                <span id="btn-text">Enviar Nova Senha</span>
                <div class="spinner" id="spinner"></div>
            </button>
        </form>
        
        <div class="links">
            <p><a href="login.php"><i class="fa-solid fa-arrow-left"></i> Voltar para o Login</a></p>
            <p><a href="../index.php">Voltar ao Cardápio</a></p>
        </div>
    </div>
    
    <script>
        document.getElementById('forgot-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const btn = document.getElementById('submit-btn');
            const btnText = document.getElementById('btn-text');
            const spinner = document.getElementById('spinner');
            const alertSuccess = document.getElementById('alert-success');
            const alertError = document.getElementById('alert-error');
            
            // Reset alerts
            alertSuccess.style.display = 'none';
            alertError.style.display = 'none';
            
            // Show loading
            btn.disabled = true;
            btnText.textContent = 'Enviando...';
            spinner.style.display = 'inline-block';
            
            try {
                const response = await fetch('../api/cliente_esqueci_senha.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email: email })
                });
                
                const data = await response.json();
                
                if (data.sucesso) {
                    document.getElementById('success-msg').textContent = data.mensagem;
                    alertSuccess.style.display = 'block';
                    document.getElementById('email').value = '';
                } else {
                    document.getElementById('error-msg').textContent = data.erro || 'Erro ao processar solicitação';
                    alertError.style.display = 'block';
                }
            } catch (error) {
                document.getElementById('error-msg').textContent = 'Erro de conexão. Tente novamente.';
                alertError.style.display = 'block';
            } finally {
                // Reset button
                btn.disabled = false;
                btnText.textContent = 'Enviar Nova Senha';
                spinner.style.display = 'none';
            }
        });
    </script>
</body>
</html>
