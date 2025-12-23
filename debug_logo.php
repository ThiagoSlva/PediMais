<?php
require 'includes/config.php';

echo "<h2>Dados da tabela configuracoes:</h2>";
$stmt = $pdo->query('SELECT id, nome_site, logo, favicon, capa FROM configuracoes WHERE id = 1');
$config = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($config);
echo "</pre>";

echo "<h2>Arquivos em admin/uploads/config/:</h2>";
$files = scandir('admin/uploads/config/');
echo "<pre>";
print_r($files);
echo "</pre>";

echo "<h2>Teste de imagem logo:</h2>";
$logo_path = 'admin/uploads/config/' . ($config['logo'] ?? 'logo.png');
echo "Caminho tentado: " . $logo_path . "<br>";
echo "Arquivo existe? " . (file_exists($logo_path) ? 'SIM' : 'NÃO') . "<br>";
echo "<img src='" . $logo_path . "' style='max-width:200px;'>";
