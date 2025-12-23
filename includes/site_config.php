<?php
// site_config.php - Recreated
// Fetches configuration from the 'config' table

function get_site_config($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

$site_config = get_site_config($pdo);

// Define constants if they don't exist (fallback)
if (!defined('SITE_NAME')) define('SITE_NAME', $site_config['nome_site'] ?? 'PedeMais');
if (!defined('SITE_URL')) define('SITE_URL', $site_config['site_url'] ?? 'http://localhost');

?>
