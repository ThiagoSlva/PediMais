<?php
// test_includes.php
// Verify that all recreated includes can be loaded and instantiated

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Testing Includes...\n";

// 1. site_config.php
require_once 'includes/config.php';
if (!$pdo) die("PDO is null in test script!\n");
require_once 'includes/site_config.php';
echo "[OK] site_config.php loaded. SITE_NAME: " . SITE_NAME . "\n";

// 2. horarios_helper.php
require_once 'includes/horarios_helper.php';
$horarios = new HorariosHelper($pdo);
echo "[OK] HorariosHelper instantiated. Loja aberta? " . ($horarios->isLojaAberta() ? 'Sim' : 'Não') . "\n";

// 3. entrega_helper.php
require_once 'includes/entrega_helper.php';
$entrega = new EntregaHelper($pdo);
echo "[OK] EntregaHelper instantiated. Frete R$ 100: " . $entrega->calcularFrete(100) . "\n";

// 4. whatsapp_helper.php
require_once 'includes/whatsapp_helper.php';
$whatsapp = new WhatsAppHelper($pdo);
echo "[OK] WhatsAppHelper instantiated. Configured? " . ($whatsapp->isConfigured() ? 'Sim' : 'Não') . "\n";

// 5. recaptcha_helper.php
require_once 'includes/recaptcha_helper.php';
$recaptcha = new ReCaptchaHelper($pdo);
echo "[OK] ReCaptchaHelper instantiated. Enabled? " . ($recaptcha->isEnabled() ? 'Sim' : 'Não') . "\n";

// 6. avaliacoes_helper.php
require_once 'includes/avaliacoes_helper.php';
$avaliacoes = new AvaliacoesHelper($pdo);
echo "[OK] AvaliacoesHelper instantiated.\n";

// 7. kanban_helper.php
require_once 'includes/kanban_helper.php';
$kanban = new KanbanHelper($pdo);
echo "[OK] KanbanHelper instantiated.\n";

// 8. qr_helper.php
require_once 'includes/qr_helper.php';
echo "[OK] QrHelper loaded. URL: " . QrHelper::gerarQrCode('test') . "\n";

// 9. log_helper.php
require_once 'includes/log_helper.php';
$log = new LogHelper($pdo);
$log->info('Test log entry');
echo "[OK] LogHelper instantiated and test log written.\n";

echo "All includes verified successfully!\n";
?>
