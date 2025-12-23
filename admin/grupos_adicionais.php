<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

verificar_login();

// Build query
$sql = "SELECT * FROM grupos_adicionais ORDER BY ordem ASC, nome ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$base_url = SITE_URL . '/admin';

// Handle deletion
if (isset($_POST['delete_id'])) {
    $id = filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM grupos_adicionais WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: grupos_adicionais.php?msg=deleted");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grupos e Complementos - Admin</title>
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
            margin-bottom: 20px;
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
            <div class="card card-custom">
                <div class="card-header-custom">
                    <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i> Grupos de Complementos</h5>
                    <a href="grupos_adicionais_add.php" class="btn btn-light btn-sm text-primary">
                        <i class="fas fa-plus"></i> Novo Grupo
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                        <div class="alert alert-success">Grupo excluído com sucesso!</div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Ordem</th>
                                    <th>Nome</th>
                                    <th>Tipo</th>
                                    <th>Mín/Máx</th>
                                    <th>Obrigatório</th>
                                    <th>Status</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grupos as $grupo): ?>
                                <tr>
                                    <td><?php echo $grupo['ordem']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($grupo['nome']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($grupo['descricao']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo ucfirst($grupo['tipo_escolha']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $grupo['minimo_escolha'] . ' / ' . $grupo['maximo_escolha']; ?></td>
                                    <td>
                                        <?php if ($grupo['obrigatorio']): ?>
                                            <span class="badge bg-danger">Sim</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Não</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($grupo['ativo']): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="grupo_adicional_itens.php?grupo_id=<?php echo $grupo['id']; ?>" class="btn btn-sm btn-info text-white me-1" title="Gerenciar Itens">
                                            <i class="fas fa-list"></i> Itens
                                        </a>
                                        <a href="grupos_adicionais_edit.php?id=<?php echo $grupo['id']; ?>" class="btn btn-sm btn-primary me-1" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este grupo?');">
                                            <input type="hidden" name="delete_id" value="<?php echo $grupo['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($grupos)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">Nenhum grupo cadastrado.</td>
                                </tr>
                                <?php endif; ?>
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