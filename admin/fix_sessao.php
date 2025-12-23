<?php
// Fix Sessao - Limpa e Reinicia a Sessão
session_start();
session_unset();
session_destroy();
session_start();

// Regenera o ID da sessão para segurança
session_regenerate_id(true);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessão Reparada</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; background-color: #f4f4f4; }
        .box { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 400px; margin: 0 auto; }
        h1 { color: #2ecc71; }
        a { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #3498db; color: #fff; text-decoration: none; border-radius: 5px; }
        a:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Sessão Reparada!</h1>
        <p>Sua sessão foi limpa e reiniciada com sucesso.</p>
        <p>Você pode tentar fazer login novamente.</p>
        <a href="index.php">Voltar ao Painel</a>
    </div>
</body>
</html>