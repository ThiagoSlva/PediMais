<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

// Fetch config
$stmt = $pdo->query("SELECT * FROM configuracoes WHERE id = 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

$nome_site = $config['nome_site'] ?? 'CardapiX';
$logo = $config['logo'] ?? '';
$theme_color = '#4caf50'; // Default or fetch from config if available

// If logo already contains full path (uploads/config/), use it directly
// Otherwise, prepend the path
if ($logo && strpos($logo, 'uploads/') !== 0) {
    $logo_path = '../uploads/config/' . $logo;
} elseif ($logo) {
    $logo_path = '../' . $logo;
} else {
    $logo_path = '../uploads/config/logo.png';
}

$manifest = [
    "name" => $nome_site,
    "short_name" => $nome_site,
    "start_url" => "index.php",
    "display" => "standalone",
    "background_color" => "#ffffff",
    "theme_color" => $theme_color,
    "icons" => [
        [
            "src" => $logo_path,
            "sizes" => "192x192",
            "type" => "image/png"
        ],
        [
            "src" => $logo_path,
            "sizes" => "512x512",
            "type" => "image/png"
        ]
    ]
];

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>