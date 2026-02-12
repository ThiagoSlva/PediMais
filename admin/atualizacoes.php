<?php
include 'includes/auth.php';
include '../includes/config.php';
include '../includes/functions.php';

verificar_login();

// Verifica permissão (apenas admin/gerente)
if (!is_admin()) {
    header('Location: index.php');
    exit;
}

include 'includes/header.php';

function parse_simple_markdown($text)
{
    // Sanitização básica
    $text = htmlspecialchars($text, ENT_NOQUOTES);

    // Headers
    $text = preg_replace('/^# (.*?)$/m', '<h1 class="text-primary-600 text-xl fw-bold mt-4 mb-3">$1</h1>', $text);
    $text = preg_replace('/^## (.*?)$/m', '<h2 class="text-lg fw-semibold mt-4 mb-2">$1</h2>', $text);
    $text = preg_replace('/^### (.*?)$/m', '<h3 class="text-md fw-semibold mt-3 mb-2">$1</h3>', $text);

    // Bold
    $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);

    // Links (básico)
    $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" target="_blank" class="text-primary-600 text-decoration-underline">$1</a>', $text);

    // Listas (tratar linhas que começam com * ou -)
    $lines = explode("\n", $text);
    $in_list = false;
    $output = [];

    foreach ($lines as $line) {
        $trim_line = trim($line);
        if (preg_match('/^[\*\-] (.*)$/', $trim_line, $matches)) {
            if (!$in_list) {
                $output[] = '<ul class="list-disc ps-4 mb-3">';
                $in_list = true;
            }
            $output[] = '<li class="mb-1">' . $matches[1] . '</li>';
        }
        else {
            if ($in_list) {
                $output[] = '</ul>';
                $in_list = false;
            }
            // Parágrafos para linhas não vazias que não são headers
            if (!empty($trim_line) && !preg_match('/^<h/', $trim_line)) {
                $output[] = '<p class="mb-2">' . $trim_line . '</p>';
            }
            else {
                $output[] = $line;
            }
        }
    }
    if ($in_list)
        $output[] = '</ul>';

    return implode("\n", $output);
}

// Ler arquivo CHANGELOG.md ou security_improvements.md
$changelog_path = __DIR__ . '/../CHANGELOG.md';
$content = '';

if (file_exists($changelog_path)) {
    $content = file_get_contents($changelog_path);

    // Detectar encoding e converter para UTF-8 se necessário
    $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'UTF-16LE', 'UTF-16BE'], true);
    if ($encoding !== 'UTF-8') {
        $content = mb_convert_encoding($content, 'UTF-8', $encoding ?: 'UTF-8');
    }
}
else {
    $content = "# Changelog\n\nArquivo de changelog não encontrado na raiz do sistema.";
}
?>

<div class="container-fluid">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-24">
        <h6 class="fw-semibold mb-0">Atualizações do Sistema</h6>
        <ul class="d-flex align-items-center gap-2">
            <li class="fw-medium">
                <a href="index.php" class="d-flex align-items-center gap-1 hover-text-primary">
                    <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
                    Dashboard
                </a>
            </li>
            <li>-</li>
            <li class="fw-medium">Atualizações</li>
        </ul>
    </div>

    <div class="card basic-data-table">
        <div class="card-header border-bottom bg-base-100">
            <h5 class="card-title mb-0">Histórico de Alterações (Changelog)</h5>
        </div>
        <div class="card-body">
            <div class="changelog-content p-3 border rounded">
                <?php echo parse_simple_markdown($content); ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
