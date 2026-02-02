<?php
require_once 'includes/header.php';

// Verificar se tabela existe
try {
    $pdo->query("SELECT 1 FROM site_analytics LIMIT 1");
} catch (Exception $e) {
    // Criar tabela se não existir
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS site_analytics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            country VARCHAR(100) NULL,
            country_code VARCHAR(10) NULL,
            region VARCHAR(100) NULL,
            city VARCHAR(100) NULL,
            latitude DECIMAL(10, 8) NULL,
            longitude DECIMAL(11, 8) NULL,
            isp VARCHAR(200) NULL,
            page_url VARCHAR(500) NOT NULL,
            page_title VARCHAR(200) NULL,
            referrer VARCHAR(500) NULL,
            user_agent TEXT NULL,
            device_type VARCHAR(50) NULL,
            browser VARCHAR(100) NULL,
            os VARCHAR(100) NULL,
            session_id VARCHAR(100) NULL,
            visited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip (ip_address),
            INDEX idx_visited (visited_at),
            INDEX idx_page (page_url(100)),
            INDEX idx_country (country_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

// Período selecionado
$period = $_GET['period'] ?? 'week';
$valid_periods = ['today', 'yesterday', 'week', 'month', 'all'];
if (!in_array($period, $valid_periods)) {
    $period = 'week';
}

// Construir WHERE clause
$where = "";
switch ($period) {
    case 'today':
        $where = "WHERE DATE(visited_at) = CURDATE()";
        $period_label = "Hoje";
        break;
    case 'yesterday':
        $where = "WHERE DATE(visited_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        $period_label = "Ontem";
        break;
    case 'week':
        $where = "WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $period_label = "Últimos 7 dias";
        break;
    case 'month':
        $where = "WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $period_label = "Últimos 30 dias";
        break;
    default:
        $where = "";
        $period_label = "Todo o período";
}

// Estatísticas gerais
$stats = [
    'total' => 0,
    'unique' => 0,
    'sessions' => 0,
    'today' => 0
];

try {
    $stats['total'] = $pdo->query("SELECT COUNT(*) FROM site_analytics {$where}")->fetchColumn();
    $stats['unique'] = $pdo->query("SELECT COUNT(DISTINCT ip_address) FROM site_analytics {$where}")->fetchColumn();
    $stats['sessions'] = $pdo->query("SELECT COUNT(DISTINCT session_id) FROM site_analytics {$where}")->fetchColumn();
    $stats['today'] = $pdo->query("SELECT COUNT(*) FROM site_analytics WHERE DATE(visited_at) = CURDATE()")->fetchColumn();
} catch (Exception $e) {
    // Tabela pode estar vazia
}

// Dados para gráfico (últimos 7 dias)
$chart_data = [];
try {
    $stmt = $pdo->query("
        SELECT DATE(visited_at) as date, COUNT(*) as visits 
        FROM site_analytics 
        WHERE visited_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(visited_at) 
        ORDER BY date ASC
    ");
    $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Top países
$top_countries = [];
try {
    $stmt = $pdo->query("
        SELECT country, country_code, COUNT(*) as visits 
        FROM site_analytics 
        {$where}
        AND country IS NOT NULL
        GROUP BY country, country_code 
        ORDER BY visits DESC 
        LIMIT 10
    ");
    $top_countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Top cidades
$top_cities = [];
try {
    $stmt = $pdo->query("
        SELECT city, region, country_code, COUNT(*) as visits 
        FROM site_analytics 
        {$where}
        AND city IS NOT NULL
        GROUP BY city, region, country_code 
        ORDER BY visits DESC 
        LIMIT 10
    ");
    $top_cities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Top dispositivos
$devices = [];
try {
    $stmt = $pdo->query("
        SELECT device_type, COUNT(*) as visits 
        FROM site_analytics 
        {$where}
        GROUP BY device_type 
        ORDER BY visits DESC
    ");
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Top navegadores
$browsers = [];
try {
    $stmt = $pdo->query("
        SELECT browser, COUNT(*) as visits 
        FROM site_analytics 
        {$where}
        GROUP BY browser 
        ORDER BY visits DESC
        LIMIT 5
    ");
    $browsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Top páginas
$top_pages = [];
try {
    $stmt = $pdo->query("
        SELECT page_url, page_title, COUNT(*) as visits 
        FROM site_analytics 
        {$where}
        GROUP BY page_url, page_title 
        ORDER BY visits DESC 
        LIMIT 10
    ");
    $top_pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Últimas visitas
$recent_visits = [];
try {
    $stmt = $pdo->query("
        SELECT * FROM site_analytics 
        ORDER BY visited_at DESC 
        LIMIT 50
    ");
    $recent_visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Preparar dados do gráfico para JavaScript
$chart_labels = [];
$chart_values = [];
foreach ($chart_data as $row) {
    $chart_labels[] = date('d/m', strtotime($row['date']));
    $chart_values[] = (int)$row['visits'];
}
?>

<style>
.analytics-card {
    background: var(--card-bg, #1e293b);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.stat-box {
    background: linear-gradient(135deg, var(--stat-color, #4a66f9) 0%, var(--stat-color-dark, #3b52cc) 100%);
    border-radius: 16px;
    padding: 24px;
    color: white;
    position: relative;
    overflow: hidden;
}
.stat-box::before {
    content: '';
    position: absolute;
    top: -20px;
    right: -20px;
    width: 80px;
    height: 80px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}
.stat-box .icon {
    font-size: 2.5rem;
    opacity: 0.3;
    position: absolute;
    right: 20px;
    top: 20px;
}
.stat-box .value {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 5px;
}
.stat-box .label {
    font-size: 0.9rem;
    opacity: 0.9;
}
.stat-box.blue { --stat-color: #3b82f6; --stat-color-dark: #2563eb; }
.stat-box.green { --stat-color: #10b981; --stat-color-dark: #059669; }
.stat-box.purple { --stat-color: #8b5cf6; --stat-color-dark: #7c3aed; }
.stat-box.orange { --stat-color: #f97316; --stat-color-dark: #ea580c; }

.section-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-primary, #f8fafc);
}
.section-title iconify-icon {
    color: #4a66f9;
}

.period-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}
.period-tab {
    padding: 10px 20px;
    border-radius: 10px;
    background: rgba(74, 102, 249, 0.1);
    color: #94a3b8;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
}
.period-tab:hover {
    background: rgba(74, 102, 249, 0.2);
    color: #fff;
}
.period-tab.active {
    background: linear-gradient(135deg, #4a66f9, #3b52cc);
    color: white;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}
.data-table th,
.data-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}
.data-table th {
    background: rgba(74, 102, 249, 0.1);
    font-weight: 600;
    color: #94a3b8;
    font-size: 0.85rem;
    text-transform: uppercase;
}
.data-table tr:hover {
    background: rgba(74, 102, 249, 0.05);
}

.chart-container {
    height: 300px;
    position: relative;
}

.top-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.top-list li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}
.top-list li:last-child {
    border-bottom: none;
}
.top-list .name {
    display: flex;
    align-items: center;
    gap: 10px;
}
.top-list .flag {
    font-size: 1.2rem;
}
.top-list .count {
    background: rgba(74, 102, 249, 0.2);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #4a66f9;
}

.device-bar {
    display: flex;
    height: 30px;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 15px;
}
.device-bar .segment {
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.8rem;
    font-weight: 600;
    transition: all 0.3s;
}
.device-bar .segment:hover {
    filter: brightness(1.1);
}
.device-bar .mobile { background: #3b82f6; }
.device-bar .desktop { background: #10b981; }
.device-bar .tablet { background: #f97316; }

.device-legend {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}
.device-legend .item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}
.device-legend .dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}
.device-legend .dot.mobile { background: #3b82f6; }
.device-legend .dot.desktop { background: #10b981; }
.device-legend .dot.tablet { background: #f97316; }

.ip-badge {
    background: rgba(74, 102, 249, 0.1);
    padding: 4px 10px;
    border-radius: 6px;
    font-family: monospace;
    font-size: 0.85rem;
}
.location-text {
    color: #94a3b8;
    font-size: 0.85rem;
}
.device-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.8rem;
}
.device-badge.mobile { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
.device-badge.desktop { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.device-badge.tablet { background: rgba(249, 115, 22, 0.15); color: #f97316; }

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
}
.empty-state iconify-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.3;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .stat-box .value {
        font-size: 1.8rem;
    }
}
</style>

<!-- Breadcrumb -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
    <h6 class="fw-semibold mb-0">Analytics</h6>
    <ul class="d-flex align-items-center gap-2">
        <li class="fw-medium">
            <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                Dashboard
            </a>
        </li>
        <li>-</li>
        <li class="fw-medium">Analytics</li>
    </ul>
</div>

<!-- Period Tabs -->
<div class="period-tabs">
    <a href="?period=today" class="period-tab <?php echo $period === 'today' ? 'active' : ''; ?>">Hoje</a>
    <a href="?period=yesterday" class="period-tab <?php echo $period === 'yesterday' ? 'active' : ''; ?>">Ontem</a>
    <a href="?period=week" class="period-tab <?php echo $period === 'week' ? 'active' : ''; ?>">7 Dias</a>
    <a href="?period=month" class="period-tab <?php echo $period === 'month' ? 'active' : ''; ?>">30 Dias</a>
    <a href="?period=all" class="period-tab <?php echo $period === 'all' ? 'active' : ''; ?>">Tudo</a>
</div>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-box blue">
        <iconify-icon icon="solar:eye-bold-duotone" class="icon"></iconify-icon>
        <div class="value"><?php echo number_format($stats['total']); ?></div>
        <div class="label">Total de Visitas</div>
    </div>
    
    <div class="stat-box green">
        <iconify-icon icon="solar:users-group-rounded-bold-duotone" class="icon"></iconify-icon>
        <div class="value"><?php echo number_format($stats['unique']); ?></div>
        <div class="label">Visitantes Únicos</div>
    </div>
    
    <div class="stat-box purple">
        <iconify-icon icon="solar:monitor-smartphone-bold-duotone" class="icon"></iconify-icon>
        <div class="value"><?php echo number_format($stats['sessions']); ?></div>
        <div class="label">Sessões</div>
    </div>
    
    <div class="stat-box orange">
        <iconify-icon icon="solar:calendar-bold-duotone" class="icon"></iconify-icon>
        <div class="value"><?php echo number_format($stats['today']); ?></div>
        <div class="label">Visitas Hoje</div>
    </div>
</div>

<div class="row g-4">
    <!-- Gráfico de Visitas -->
    <div class="col-xl-8">
        <div class="analytics-card">
            <div class="section-title">
                <iconify-icon icon="solar:chart-2-bold-duotone"></iconify-icon>
                Visitas nos Últimos 7 Dias
            </div>
            <div class="chart-container">
                <canvas id="visitsChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Dispositivos -->
    <div class="col-xl-4">
        <div class="analytics-card">
            <div class="section-title">
                <iconify-icon icon="solar:devices-bold-duotone"></iconify-icon>
                Dispositivos
            </div>
            
            <?php
            $total_devices = array_sum(array_column($devices, 'visits'));
            $device_percentages = [];
            foreach ($devices as $d) {
                $device_percentages[$d['device_type']] = $total_devices > 0 ? round(($d['visits'] / $total_devices) * 100) : 0;
            }
            ?>
            
            <?php if ($total_devices > 0): ?>
            <div class="device-bar">
                <?php if (isset($device_percentages['Mobile']) && $device_percentages['Mobile'] > 0): ?>
                <div class="segment mobile" style="width: <?php echo $device_percentages['Mobile']; ?>%">
                    <?php echo $device_percentages['Mobile']; ?>%
                </div>
                <?php endif; ?>
                
                <?php if (isset($device_percentages['Desktop']) && $device_percentages['Desktop'] > 0): ?>
                <div class="segment desktop" style="width: <?php echo $device_percentages['Desktop']; ?>%">
                    <?php echo $device_percentages['Desktop']; ?>%
                </div>
                <?php endif; ?>
                
                <?php if (isset($device_percentages['Tablet']) && $device_percentages['Tablet'] > 0): ?>
                <div class="segment tablet" style="width: <?php echo $device_percentages['Tablet']; ?>%">
                    <?php echo $device_percentages['Tablet']; ?>%
                </div>
                <?php endif; ?>
            </div>
            
            <div class="device-legend">
                <div class="item">
                    <div class="dot mobile"></div>
                    <span>Mobile (<?php echo $device_percentages['Mobile'] ?? 0; ?>%)</span>
                </div>
                <div class="item">
                    <div class="dot desktop"></div>
                    <span>Desktop (<?php echo $device_percentages['Desktop'] ?? 0; ?>%)</span>
                </div>
                <div class="item">
                    <div class="dot tablet"></div>
                    <span>Tablet (<?php echo $device_percentages['Tablet'] ?? 0; ?>%)</span>
                </div>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <iconify-icon icon="solar:devices-bold-duotone"></iconify-icon>
                <p>Sem dados de dispositivos</p>
            </div>
            <?php endif; ?>
            
            <!-- Navegadores -->
            <div class="section-title mt-4">
                <iconify-icon icon="solar:global-bold-duotone"></iconify-icon>
                Navegadores
            </div>
            
            <?php if (!empty($browsers)): ?>
            <ul class="top-list">
                <?php foreach ($browsers as $b): ?>
                <li>
                    <span class="name"><?php echo htmlspecialchars($b['browser']); ?></span>
                    <span class="count"><?php echo number_format($b['visits']); ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p class="text-muted">Sem dados</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Top Países -->
    <div class="col-lg-6">
        <div class="analytics-card">
            <div class="section-title">
                <iconify-icon icon="solar:globe-bold-duotone"></iconify-icon>
                Top Países
            </div>
            
            <?php if (!empty($top_countries)): ?>
            <ul class="top-list">
                <?php foreach ($top_countries as $c): ?>
                <li>
                    <span class="name">
                        <span class="flag"><?php echo $c['country_code'] ? get_country_flag($c['country_code']) : '🌍'; ?></span>
                        <?php echo htmlspecialchars($c['country'] ?? 'Desconhecido'); ?>
                    </span>
                    <span class="count"><?php echo number_format($c['visits']); ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <div class="empty-state">
                <iconify-icon icon="solar:globe-bold-duotone"></iconify-icon>
                <p>Sem dados de localização</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Top Cidades -->
    <div class="col-lg-6">
        <div class="analytics-card">
            <div class="section-title">
                <iconify-icon icon="solar:city-bold-duotone"></iconify-icon>
                Top Cidades
            </div>
            
            <?php if (!empty($top_cities)): ?>
            <ul class="top-list">
                <?php foreach ($top_cities as $c): ?>
                <li>
                    <span class="name">
                        <span class="flag"><?php echo $c['country_code'] ? get_country_flag($c['country_code']) : '📍'; ?></span>
                        <?php echo htmlspecialchars($c['city'] ?? 'Desconhecida'); ?>
                        <small class="text-muted">(<?php echo htmlspecialchars($c['region'] ?? ''); ?>)</small>
                    </span>
                    <span class="count"><?php echo number_format($c['visits']); ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <div class="empty-state">
                <iconify-icon icon="solar:city-bold-duotone"></iconify-icon>
                <p>Sem dados de cidades</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Top Páginas -->
<div class="analytics-card">
    <div class="section-title">
        <iconify-icon icon="solar:document-text-bold-duotone"></iconify-icon>
        Páginas Mais Acessadas
    </div>
    
    <?php if (!empty($top_pages)): ?>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Página</th>
                    <th>URL</th>
                    <th>Visitas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_pages as $p): ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['page_title'] ?? 'Sem título'); ?></td>
                    <td><code><?php echo htmlspecialchars($p['page_url']); ?></code></td>
                    <td><span class="count"><?php echo number_format($p['visits']); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <iconify-icon icon="solar:document-text-bold-duotone"></iconify-icon>
        <p>Sem dados de páginas</p>
    </div>
    <?php endif; ?>
</div>

<!-- Últimas Visitas -->
<div class="analytics-card">
    <div class="section-title">
        <iconify-icon icon="solar:clock-circle-bold-duotone"></iconify-icon>
        Últimas Visitas
    </div>
    
    <?php if (!empty($recent_visits)): ?>
    <div class="table-responsive">
        <table class="data-table" id="recentVisitsTable">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>IP</th>
                    <th>Localização</th>
                    <th>Página</th>
                    <th>Dispositivo</th>
                    <th>Navegador</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_visits as $v): ?>
                <tr>
                    <td><?php echo date('d/m/Y H:i', strtotime($v['visited_at'])); ?></td>
                    <td><span class="ip-badge"><?php echo htmlspecialchars($v['ip_address']); ?></span></td>
                    <td class="location-text">
                        <?php 
                        $location = [];
                        if ($v['city']) $location[] = $v['city'];
                        if ($v['region']) $location[] = $v['region'];
                        if ($v['country']) $location[] = $v['country'];
                        echo htmlspecialchars(implode(', ', $location) ?: 'Desconhecida');
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($v['page_title'] ?? $v['page_url']); ?></td>
                    <td>
                        <?php 
                        $device_class = strtolower($v['device_type'] ?? 'desktop');
                        ?>
                        <span class="device-badge <?php echo $device_class; ?>">
                            <iconify-icon icon="<?php echo $device_class === 'mobile' ? 'solar:smartphone-bold' : ($device_class === 'tablet' ? 'solar:tablet-bold' : 'solar:monitor-bold'); ?>"></iconify-icon>
                            <?php echo htmlspecialchars($v['device_type'] ?? 'Desktop'); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($v['browser'] ?? 'Desconhecido'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <iconify-icon icon="solar:clock-circle-bold-duotone"></iconify-icon>
        <p>Nenhuma visita registrada ainda</p>
        <small>As visitas aparecerão aqui quando os usuários acessarem seu site</small>
    </div>
    <?php endif; ?>
</div>

<?php
// Função helper para obter emoji de bandeira do país
function get_country_flag($country_code) {
    if (strlen($country_code) !== 2) return '🌍';
    $country_code = strtoupper($country_code);
    $flag = '';
    for ($i = 0; $i < 2; $i++) {
        $flag .= mb_chr(ord($country_code[$i]) - ord('A') + 0x1F1E6);
    }
    return $flag;
}
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Gráfico de Visitas
const ctx = document.getElementById('visitsChart');
if (ctx) {
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Visitas',
                data: <?php echo json_encode($chart_values); ?>,
                borderColor: '#4a66f9',
                backgroundColor: 'rgba(74, 102, 249, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#4a66f9',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.05)'
                    },
                    ticks: {
                        color: '#94a3b8'
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#94a3b8'
                    }
                }
            }
        }
    });
}

// DataTable para últimas visitas
$(document).ready(function() {
    if ($('#recentVisitsTable').length && $.fn.DataTable) {
        $('#recentVisitsTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 10,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
