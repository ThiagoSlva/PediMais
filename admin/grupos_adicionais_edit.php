<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';

verificar_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: grupos_adicionais.php");
    exit;
}

$msg = '';

// Fetch existing data
$stmt = $pdo->prepare("SELECT * FROM grupos_adicionais WHERE id = ?");
$stmt->execute([$id]);
$grupo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$grupo) {
    header("Location: grupos_adicionais.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validar_csrf()) {
        $msg = 'Token de segurança inválido. Recarregue a página.';
    }
    else {
        $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
        $descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_SPECIAL_CHARS);
        $tipo_escolha = filter_input(INPUT_POST, 'tipo_escolha', FILTER_SANITIZE_SPECIAL_CHARS);
        $minimo_escolha = filter_input(INPUT_POST, 'minimo_escolha', FILTER_VALIDATE_INT);
        $maximo_escolha = filter_input(INPUT_POST, 'maximo_escolha', FILTER_VALIDATE_INT);
        $obrigatorio = isset($_POST['obrigatorio']) ? 1 : 0;
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        $ordem = filter_input(INPUT_POST, 'ordem', FILTER_VALIDATE_INT) ?? 0;

        if ($nome) {
            $sql = "UPDATE grupos_adicionais SET nome = ?, descricao = ?, tipo_escolha = ?, minimo_escolha = ?, maximo_escolha = ?, obrigatorio = ?, ordem = ?, ativo = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([$nome, $descricao, $tipo_escolha, $minimo_escolha, $maximo_escolha, $obrigatorio, $ordem, $ativo, $id])) {
                header("Location: grupos_adicionais.php?msg=updated");
                exit;
            }
            else {
                $msg = "Erro ao atualizar grupo.";
            }
        }
        else {
            $msg = "Preencha o nome do grupo.";
        }
    } // fecha else validar_csrf
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Grupo - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.7/dist/iconify-icon.min.js"></script>
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
                    <li class="breadcrumb-item active" aria-current="page">Editar Grupo</li>
                </ol>
            </nav>

            <div class="card card-custom">
                <div class="card-header-custom">
                    <h5 class="mb-0">Editar Grupo: <?php echo htmlspecialchars($grupo['nome']); ?></h5>
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
                                <label class="form-label">Nome do Grupo</label>
                                <input type="text" name="nome" class="form-control" required value="<?php echo htmlspecialchars($grupo['nome']); ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Ordem</label>
                                <input type="number" name="ordem" class="form-control" value="<?php echo $grupo['ordem']; ?>">
                            </div>
                            <div class="col-md-3 mb-3 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="ativo" id="ativo" <?php echo $grupo['ativo'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="ativo">Ativo</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea name="descricao" class="form-control" rows="2"><?php echo htmlspecialchars($grupo['descricao']); ?></textarea>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tipo de Escolha</label>
                                <select name="tipo_escolha" class="form-select" id="tipo_escolha">
                                    <option value="unico" <?php echo $grupo['tipo_escolha'] == 'unico' ? 'selected' : ''; ?>>Única (Radio)</option>
                                    <option value="multiplo" <?php echo $grupo['tipo_escolha'] == 'multiplo' ? 'selected' : ''; ?>>Múltipla (Checkbox)</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Mínimo de Opções</label>
                                <input type="number" name="minimo_escolha" id="minimo_escolha" class="form-control" value="<?php echo $grupo['minimo_escolha']; ?>" min="0">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Máximo de Opções</label>
                                <input type="number" name="maximo_escolha" id="maximo_escolha" class="form-control" value="<?php echo $grupo['maximo_escolha']; ?>" min="1" <?php echo $grupo['tipo_escolha'] == 'unico' ? 'readonly' : ''; ?>>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="obrigatorio" id="obrigatorio" <?php echo $grupo['obrigatorio'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="obrigatorio">Escolha Obrigatória?</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Salvar Alterações</button>
                        <a href="grupos_adicionais.php" class="btn btn-secondary">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const tipoSelect = document.getElementById('tipo_escolha');
        const maxInput = document.getElementById('maximo_escolha');

        tipoSelect.addEventListener('change', function() {
            if (this.value === 'unico') {
                maxInput.value = 1;
                maxInput.readOnly = true;
            } else {
                maxInput.readOnly = false;
            }
        });
    </script>
</body>
</html>