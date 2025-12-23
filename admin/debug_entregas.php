<?php
include 'includes/header.php';

// Buscar bairros de entrega
$stmt = $pdo->query("SELECT * FROM bairros_entrega ORDER BY nome ASC");
$bairros_entrega = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar bairros (tabela simples, se existir)
$bairros_simples = [];
try {
    $stmt = $pdo->query("SELECT * FROM bairros ORDER BY nome ASC");
    $bairros_simples = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Tabela pode não existir ou ter outro nome
}

?>

<div class="dashboard-main-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Debug - Taxas de Entrega</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Debug Entregas</li>
        </ul>
    </div>

    <div class="row gy-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tabela: bairros_entrega</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($bairros_entrega)): ?>
                        <div class="alert alert-warning">Nenhum bairro de entrega configurado.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Taxa</th>
                                        <th>Ativo</th>
                                        <th>Cidade ID</th>
                                        <th>Ordem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bairros_entrega as $bairro): ?>
                                    <tr>
                                        <td><?php echo $bairro['id']; ?></td>
                                        <td><?php echo htmlspecialchars($bairro['nome']); ?></td>
                                        <td>R$ <?php echo number_format($bairro['taxa'], 2, ',', '.'); ?></td>
                                        <td>
                                            <?php if ($bairro['ativo']): ?>
                                                <span class="badge bg-success">Sim</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Não</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $bairro['cidade_id']; ?></td>
                                        <td><?php echo $bairro['ordem']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($bairros_simples)): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tabela: bairros (Referência)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Outros Campos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bairros_simples as $bairro): ?>
                                <tr>
                                    <td><?php echo $bairro['id']; ?></td>
                                    <td><?php echo htmlspecialchars($bairro['nome']); ?></td>
                                    <td>
                                        <?php 
                                        foreach ($bairro as $k => $v) {
                                            if ($k != 'id' && $k != 'nome') {
                                                echo "<strong>$k:</strong> $v <br>";
                                            }
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>