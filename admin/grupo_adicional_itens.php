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

// Handle deletion
if (isset($_POST['delete_id'])) {
    if (!validar_csrf()) {
        // Token inválido, redirecionar
        header("Location: grupo_adicional_itens.php?grupo_id=$grupo_id&msg=csrf_error");
        exit;
    }
    $id = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM grupo_adicional_itens WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: grupo_adicional_itens.php?grupo_id=$grupo_id&msg=deleted");
        exit;
    }
}

// Fetch items
$sql = "SELECT * FROM grupo_adicional_itens WHERE grupo_id = ? ORDER BY ordem ASC, nome ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$grupo_id]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Itens do Grupo - Admin</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
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
                    <li class="breadcrumb-item active" aria-current="page">Itens: <?php echo htmlspecialchars($grupo['nome']); ?></li>
                </ol>
            </nav>

            <div class="card card-custom">
                <div class="card-header-custom">
                    <h5 class="mb-0">Itens do Grupo: <?php echo htmlspecialchars($grupo['nome']); ?></h5>
                    <div>
                        <a href="grupos_adicionais.php" class="btn btn-light btn-sm text-primary me-2">
                             <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                        <a href="grupo_adicional_itens_add.php?grupo_id=<?php echo $grupo_id; ?>" class="btn btn-light btn-sm text-primary">
                            <i class="fas fa-plus"></i> Novo Item
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                        <div class="alert alert-success">Item excluído com sucesso!</div>
                    <?php
endif; ?>

                    <div class="alert alert-info">
                        <strong>Informações do Grupo:</strong> 
                        <?php echo htmlspecialchars($grupo['descricao']); ?> | 
                        Tipo: <?php echo ucfirst($grupo['tipo_escolha']); ?> | 
                        Escolha: <?php echo $grupo['minimo_escolha'] . ' a ' . $grupo['maximo_escolha']; ?>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Ordem</th>
                                    <th>Nome</th>
                                    <th>Preço Adicional</th>
                                    <th>Status</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($itens as $item): ?>
                                <tr>
                                    <td><?php echo $item['ordem']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['nome']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($item['descricao']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($item['preco_adicional'] > 0): ?>
                                            <span class="text-primary fw-bold">+ R$ <?php echo number_format($item['preco_adicional'], 2, ',', '.'); ?></span>
                                        <?php
    else: ?>
                                            <span class="text-success">Grátis</span>
                                        <?php
    endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($item['ativo']): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php
    else: ?>
                                            <span class="badge bg-danger">Inativo</span>
                                        <?php
    endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="grupo_adicional_itens_edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary me-1" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este item?');">
                                            <?php echo campo_csrf(); ?>
                                            <input type="hidden" name="delete_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php
endforeach; ?>
                                
                                <?php if (empty($itens)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">Nenhum item cadastrado neste grupo.</td>
                                </tr>
                                <?php
endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>