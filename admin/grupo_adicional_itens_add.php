<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

verificar_login();

$grupo_id = filter_input(INPUT_GET, 'grupo_id', FILTER_VALIDATE_INT);
if (!$grupo_id) {
    header("Location: grupos_adicionais.php");
    exit;
}

// Fetch group info
$stmt = $pdo->prepare("SELECT * FROM grupos_adicionais WHERE id = ?");
$stmt->execute([$grupo_id]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    header("Location: grupos_adicionais.php");
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validar_csrf()) {
        $msg = 'Token de segurança inválido. Recarregue a página.';
    }
    else {
        $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
        $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_SPECIAL_CHARS);
        $preco_adicional = str_replace(',', '.', str_replace('.', '', $_POST['preco_adicional']));
        $ordem = filter_input(INPUT_POST, 'ordem', FILTER_VALIDATE_INT) ?? 0;
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($nome) {
            $sql = "INSERT INTO grupo_adicional_itens (grupo_id, nome, descricao, preco_adicional, ordem, ativo) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$grupo_id, $nome, $descricao, $preco_adicional, $ordem, $ativo])) {
                header("Location: grupo_adicional_itens.php?grupo_id=$grupo_id&msg=success");
                exit;
            }
            else {
                $msg = "Erro ao cadastrar item.";
            }
        }
        else {
            $msg = "Preencha o nome do item.";
        }
    } // fecha else validar_csrf
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Item - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.7/dist/iconify-icon.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <style>
        .card-custom {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: none;
        }
        .card-header-custom {
            background: linear-gradient(45deg, #4b6cb7, #182848);
            color: white;
            padding: 15px 20px;
            border-radius: 15px 15px 0 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include 'includes/header.php'; ?>

        <div class="container-fluid py-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="grupos_adicionais.php">Grupos</a></li>
                    <li class="breadcrumb-item"><a href="grupo_adicional_itens.php?grupo_id=<?php echo $grupo_id; ?>">Itens: <?php echo htmlspecialchars($grupo['nome']); ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Novo Item</li>
                </ol>
            </nav>

            <div class="card card-custom">
                <div class="card-header-custom">
                    <h5 class="mb-0">Novo Item para: <?php echo htmlspecialchars($grupo['nome']); ?></h5>
                </div>
                <div class="card-body p-4">
                    <?php if ($msg): ?>
                        <div class="alert alert-danger"><?php echo $msg; ?></div>
                    <?php
endif; ?>

                    <form method="POST">
                        <?php echo campo_csrf(); ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nome do Item</label>
                                <input type="text" name="nome" class="form-control" required placeholder="Ex: Bacon, Queijo Extra">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Preço Adicional (R$)</label>
                                <input type="text" name="preco_adicional" class="form-control money" value="0,00">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Ordem</label>
                                <input type="number" name="ordem" class="form-control" value="0">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descrição (Opcional)</label>
                            <input type="text" name="descricao" class="form-control" placeholder="Ex: Fatias crocantes">
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="ativo" id="ativo" checked>
                                <label class="form-check-label" for="ativo">Ativo</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Salvar Item</button>
                        <a href="grupo_adicional_itens.php?grupo_id=<?php echo $grupo_id; ?>" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function(){
            $('.money').mask('000.000.000.000.000,00', {reverse: true});
        });
    </script>
</body>
</html>