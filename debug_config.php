<?php
include 'includes/config.php';

echo "<h2>Configurações:</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($config);
    echo "</pre>";
} catch (PDOException $e) {
    echo "Error configuracoes: " . $e->getMessage();
}

echo "<h2>Default Paths Check:</h2>";
echo "capa: admin/" . ($config['capa'] ?? 'assets/images/capa-padrao.jpg') . "<br>";
echo "logo: admin/" . ($config['logo'] ?? 'assets/images/logo-padrao.png') . "<br>";

// Check if files exist
$capa_path = 'admin/' . ($config['capa'] ?? 'assets/images/capa-padrao.jpg');
$logo_path = 'admin/' . ($config['logo'] ?? 'assets/images/logo-padrao.png');

echo "<h2>File Exists Check:</h2>";
echo "Capa exists: " . (file_exists($capa_path) ? "YES" : "NO - $capa_path") . "<br>";
echo "Logo exists: " . (file_exists($logo_path) ? "YES" : "NO - $logo_path") . "<br>";

echo "<h2>Categorias:</h2>";
$stmt = $pdo->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY ordem ASC");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($categorias);
echo "</pre>";
?>
