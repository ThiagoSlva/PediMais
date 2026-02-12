<?php
/**
 * API: Verificar Status da Loja (Aberta/Fechada)
 */

header('Content-Type: application/json; charset=utf-8');
$allowed_origin = defined('SITE_URL') ? SITE_URL : '*';
header("Access-Control-Allow-Origin: $allowed_origin");

require_once '../includes/config.php';
require_once '../includes/functions.php';

$aberta = loja_aberta();

// Buscar mensagem personalizada
$mensagem = 'Estamos fechados no momento.';
try {
    $stmt = $pdo->query("SELECT mensagem_fechado FROM configuracao_horarios LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($config && !empty($config['mensagem_fechado'])) {
        $mensagem = $config['mensagem_fechado'];
    }
}
catch (Exception $e) {
// Ignorar
}

// Buscar horário de funcionamento do dia atual
$horario_hoje = null;
try {
    $dia_semana = date('w');
    $stmt = $pdo->prepare("SELECT horario_abertura, horario_fechamento, ativo FROM horarios_funcionamento WHERE dia_semana = ?");
    $stmt->execute([$dia_semana]);
    $horario = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($horario && intval($horario['ativo']) === 1) {
        $horario_hoje = [
            'abertura' => substr($horario['horario_abertura'], 0, 5),
            'fechamento' => substr($horario['horario_fechamento'], 0, 5)
        ];
    }
}
catch (Exception $e) {
// Ignorar
}

echo json_encode([
    'aberta' => $aberta,
    'mensagem' => $mensagem,
    'horario_hoje' => $horario_hoje
], JSON_UNESCAPED_UNICODE);
