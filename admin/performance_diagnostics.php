<?php
/**
 * Performance Diagnostic Panel
 * Analisa e diagnostica problemas de performance
 * URL: /admin/performance_diagnostics.php
 */

require_once __DIR__ . '/../includes/security_headers.php';
session_start();
require_once 'includes/header.php';
require_once __DIR__ . '/../includes/csrf.php';

// Verificar permissão
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: /');
    exit;
}

// Incluir funções de otimização
require_once __DIR__ . '/../includes/image_optimization.php';

// Diagnósticos
$diagnostics = [];

// 1. Tamanho total de uploads
$upload_dirs = [
    'Produtos' => __DIR__ . '/uploads/produtos/',
    'Categorias' => __DIR__ . '/uploads/categorias/',
    'Config' => __DIR__ . '/../uploads/config/',
];

$total_uploads_size = 0;
$total_image_count = 0;

foreach ($upload_dirs as $name => $dir) {
    if (is_dir($dir)) {
        $files = glob($dir . '*');
        $size = 0;
        $count = count($files);

        foreach ($files as $file) {
            if (is_file($file)) {
                $size += filesize($file);
            }
        }

        $diagnostics['uploads'][$name] = [
            'size' => $size,
            'count' => $count,
            'size_formatted' => formatBytes($size)
        ];

        $total_uploads_size += $size;
        $total_image_count += $count;
    }
}

// 2. Verificar se GD está instalado
$diagnostics['gd_installed'] = extension_loaded('gd');

// 3. Verificar se WebP é suportado
$diagnostics['webp_supported'] = function_exists('imagewebp');

// 4. Memory limit
$diagnostics['memory_limit'] = ini_get('memory_limit');
$diagnostics['max_upload_size'] = ini_get('upload_max_filesize');

// 5. Contar imagens potencialmente grandes
$diagnostics['database'] = [];
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_products,
        COUNT(CASE WHEN imagem_path != '' THEN 1 END) as with_images
    FROM produtos
");
$db_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$diagnostics['database']['produtos'] = $db_stats;

$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_categories,
        COUNT(CASE WHEN imagem != '' THEN 1 END) as with_images
    FROM categorias
");
$db_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$diagnostics['database']['categorias'] = $db_stats;

// Processar ações
$action_result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    if (!validar_csrf()) {
        $action_result = ['success' => false, 'message' => 'Token de segurança inválido.'];
    }
    elseif ($_POST['action'] === 'optimize_images') {
        // Otimizar imagens
        $count = 0;
        $freed = 0;

        foreach ($upload_dirs as $dir) {
            if (!is_dir($dir))
                continue;

            $files = glob($dir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
            foreach ($files as $file) {
                // Verificar se já foi otimizado
                $base = pathinfo($file, PATHINFO_DIRNAME) . '/' . pathinfo($file, PATHINFO_FILENAME);
                if (file_exists($base . '.webp')) {
                    continue; // Já otimizado
                }

                $result = optimizeImage($file, $base, 75, 1200);
                if ($result['success']) {
                    $count++;
                    $freed += $result['saved_bytes'] ?? 0;
                }
            }
        }

        $action_result = [
            'success' => true,
            'message' => "Otimizadas $count imagens. Espaço liberado: " . formatBytes($freed)
        ];
    }
    elseif ($_POST['action'] === 'clean_old') {
        // Limpar imagens antigas
        $result = cleanOldImages(__DIR__ . '/uploads/produtos/', 60);

        if (!isset($result['error'])) {
            $action_result = [
                'success' => true,
                'message' => "Removidos {$result['removed']} arquivos antigos. Espaço liberado: " . formatBytes($result['freed_bytes'])
            ];
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Performance</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .status-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }
        
        .status-card.warning {
            border-left-color: #f59e0b;
        }
        
        .status-card.danger {
            border-left-color: #ef4444;
        }
        
        .status-card.success {
            border-left-color: #10b981;
        }
        
        .status-label {
            color: #999;
            font-size: 0.85rem;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        
        .status-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-icon {
            font-size: 1.2rem;
        }
        
        .icon-ok {
            color: #10b981;
        }
        
        .icon-warn {
            color: #f59e0b;
        }
        
        .icon-error {
            color: #ef4444;
        }
        
        .section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
        }
        
        .table-data {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table-data th {
            background: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #666;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .table-data td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #666;
        }
        
        .table-data tr:hover {
            background: #f9fafb;
        }
        
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        .btn-warning:hover {
            background: #d97706;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #0284c7;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            color: #0c2340;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .back-link:hover {
            color: #5568d3;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Voltar ao Painel
    </a>
    
    <div class="header">
        <h1>
            <i class="fas fa-chart-line"></i>
            Diagnóstico de Performance
        </h1>
        <p style="color: #999;">Análise detalhada do sistema e recomendações de otimização</p>
    </div>
    
    <?php if ($action_result): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <div>
            <strong>Sucesso!</strong><br>
            <?php echo $action_result['message']; ?>
        </div>
    </div>
    <?php
endif; ?>
    
    <!-- Status Overview -->
    <div class="status-grid">
        <div class="status-card success">
            <div class="status-label">Total de Imagens</div>
            <div class="status-value">
                <span class="status-icon icon-ok"><i class="fas fa-images"></i></span>
                <?php echo $total_image_count; ?>
            </div>
        </div>
        
        <div class="status-card <?php echo $total_uploads_size > 100 * 1024 * 1024 ? 'warning' : 'success'; ?>">
            <div class="status-label">Espaço em Uploads</div>
            <div class="status-value">
                <span class="status-icon <?php echo $total_uploads_size > 100 * 1024 * 1024 ? 'icon-warn' : 'icon-ok'; ?>">
                    <i class="fas fa-database"></i>
                </span>
                <?php echo formatBytes($total_uploads_size); ?>
            </div>
        </div>
        
        <div class="status-card <?php echo $diagnostics['gd_installed'] ? 'success' : 'danger'; ?>">
            <div class="status-label">Suporte GD</div>
            <div class="status-value">
                <span class="status-icon <?php echo $diagnostics['gd_installed'] ? 'icon-ok' : 'icon-error'; ?>">
                    <i class="fas fa-<?php echo $diagnostics['gd_installed'] ? 'check' : 'times'; ?>-circle"></i>
                </span>
                <?php echo $diagnostics['gd_installed'] ? 'Disponível' : 'Indisponível'; ?>
            </div>
        </div>
        
        <div class="status-card <?php echo $diagnostics['webp_supported'] ? 'success' : 'warning'; ?>">
            <div class="status-label">WebP Support</div>
            <div class="status-value">
                <span class="status-icon <?php echo $diagnostics['webp_supported'] ? 'icon-ok' : 'icon-warn'; ?>">
                    <i class="fas fa-<?php echo $diagnostics['webp_supported'] ? 'check' : 'exclamation'; ?>-circle"></i>
                </span>
                <?php echo $diagnostics['webp_supported'] ? 'Sim' : 'Não'; ?>
            </div>
        </div>
    </div>
    
    <!-- Server Config -->
    <div class="section">
        <div class="section-title">
            <i class="fas fa-server"></i>
            Configuração do Servidor
        </div>
        
        <table class="table-data">
            <tr>
                <th>Parâmetro</th>
                <th>Valor</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>Memory Limit</td>
                <td><?php echo $diagnostics['memory_limit']; ?></td>
                <td>
                    <?php
$mem_val = (int)str_replace('M', '', $diagnostics['memory_limit']);
if ($mem_val >= 128) {
    echo '<span style="color: #10b981;">✓ OK</span>';
}
else {
    echo '<span style="color: #f59e0b;">⚠ Baixo</span>';
}
?>
                </td>
            </tr>
            <tr>
                <td>Max Upload Size</td>
                <td><?php echo $diagnostics['max_upload_size']; ?></td>
                <td>
                    <?php
$upload_val = (int)str_replace('M', '', $diagnostics['max_upload_size']);
if ($upload_val >= 50) {
    echo '<span style="color: #10b981;">✓ OK</span>';
}
else {
    echo '<span style="color: #f59e0b;">⚠ Limitado</span>';
}
?>
                </td>
            </tr>
            <tr>
                <td>GD Library</td>
                <td><?php echo $diagnostics['gd_installed'] ? 'Instalado' : 'Não instalado'; ?></td>
                <td><span style="color: <?php echo $diagnostics['gd_installed'] ? '#10b981' : '#ef4444'; ?>">
                    <?php echo $diagnostics['gd_installed'] ? '✓ OK' : '✗ Crítico'; ?>
                </span></td>
            </tr>
            <tr>
                <td>WebP Support</td>
                <td><?php echo $diagnostics['webp_supported'] ? 'Suportado' : 'Não suportado'; ?></td>
                <td><span style="color: <?php echo $diagnostics['webp_supported'] ? '#10b981' : '#f59e0b'; ?>">
                    <?php echo $diagnostics['webp_supported'] ? '✓ OK' : '⚠ Limitado'; ?>
                </span></td>
            </tr>
        </table>
    </div>
    
    <!-- Database Stats -->
    <div class="section">
        <div class="section-title">
            <i class="fas fa-database"></i>
            Estatísticas do Banco de Dados
        </div>
        
        <table class="table-data">
            <tr>
                <th>Tabela</th>
                <th>Total</th>
                <th>Com Imagens</th>
                <th>Taxa</th>
            </tr>
            <?php foreach ($diagnostics['database'] as $table => $stats): ?>
            <tr>
                <td><strong><?php echo ucfirst($table); ?></strong></td>
                <td><?php echo $stats['total_products'] ?? $stats['total_categories']; ?></td>
                <td><?php echo $stats['with_images']; ?></td>
                <td>
                    <?php
    $total = $stats['total_products'] ?? $stats['total_categories'];
    $percent = $total > 0 ? round(($stats['with_images'] / $total) * 100) : 0;
    echo $percent . '%';
?>
                </td>
            </tr>
            <?php
endforeach; ?>
        </table>
    </div>
    
    <!-- Upload Directories -->
    <div class="section">
        <div class="section-title">
            <i class="fas fa-folder"></i>
            Espaço em Disco - Uploads
        </div>
        
        <table class="table-data">
            <tr>
                <th>Diretório</th>
                <th>Tamanho</th>
                <th>Quantidade</th>
                <th>Tamanho Médio</th>
            </tr>
            <?php foreach ($diagnostics['uploads'] as $name => $data): ?>
            <tr>
                <td><strong><?php echo $name; ?></strong></td>
                <td><?php echo $data['size_formatted']; ?></td>
                <td><?php echo $data['count']; ?> arquivos</td>
                <td><?php echo $data['count'] > 0 ? formatBytes($data['size'] / $data['count']) : '-'; ?></td>
            </tr>
            <?php
endforeach; ?>
            <tr style="font-weight: 700; background: #f0f0f0;">
                <td>TOTAL</td>
                <td><?php echo formatBytes($total_uploads_size); ?></td>
                <td><?php echo $total_image_count; ?> arquivos</td>
                <td><?php echo $total_image_count > 0 ? formatBytes($total_uploads_size / $total_image_count) : '-'; ?></td>
            </tr>
        </table>
        
        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <strong>Recomendação:</strong> Imagens idealmente devem ter entre 50KB e 500KB. Se a maioria está acima disso, aplicar compressão pode economizar espaço significativo.
        </div>
    </div>
    
    <!-- Recommendations -->
    <div class="section">
        <div class="section-title">
            <i class="fas fa-lightbulb"></i>
            Recomendações de Otimização
        </div>
        
        <ul style="line-height: 2; color: #666;">
            <li>
                <strong>✓ Lazy Loading:</strong> Já implementado no index.php! Imagens carregam sob demanda.
            </li>
            <li>
                <strong><?php echo $diagnostics['gd_installed'] ? '✓' : '✗'; ?> Compressão de Imagens:</strong> 
                <?php if ($diagnostics['gd_installed']): ?>
                    Disponível. Use o botão "Otimizar Imagens" para aplicar.
                <?php
else: ?>
                    Requer GD library no servidor. Contate seu provedor.
                <?php
endif; ?>
            </li>
            <li>
                <strong><?php echo $total_uploads_size > 100 * 1024 * 1024 ? '⚠' : '✓'; ?> Tamanho Total:</strong>
                <?php if ($total_uploads_size > 100 * 1024 * 1024): ?>
                    Acima de 100MB. Considere limpar imagens antigas.
                <?php
else: ?>
                    Dentro do esperado.
                <?php
endif; ?>
            </li>
        </ul>
    </div>
    
    <!-- Actions -->
    <?php if ($diagnostics['gd_installed']): ?>
    <div class="section">
        <div class="section-title">
            <i class="fas fa-tools"></i>
            Ferramentas de Otimização
        </div>
        
        <div class="actions">
            <form method="POST" onsubmit="return confirm('Isso pode levar alguns minutos. Continuar?');">
                <?php echo campo_csrf(); ?>
                <input type="hidden" name="action" value="optimize_images">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-compress"></i>
                    Otimizar Todas as Imagens
                </button>
            </form>
            
            <form method="POST" onsubmit="return confirm('Remover imagens com mais de 60 dias?');">
                <?php echo campo_csrf(); ?>
                <input type="hidden" name="action" value="clean_old">
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-trash"></i>
                    Limpar Imagens Antigas
                </button>
            </form>
        </div>
        
        <div class="info-box">
            <strong>⚠ Aviso:</strong> Essas operações modificam arquivos. Recomenda-se fazer backup primeiro. O processo pode levar alguns minutos dependendo da quantidade de imagens.
        </div>
    </div>
    <?php
endif; ?>

</div>

</body>
</html>

<?php require_once 'includes/footer.php'; ?>
