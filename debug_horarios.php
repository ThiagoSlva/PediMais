<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG HORÁRIOS ===\n\n";

echo "Hora atual do PHP: " . date('H:i:s') . "\n";
echo "Dia da semana: " . date('w') . " (" . ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'][date('w')] . ")\n\n";

// Configuração de horários
$stmt = $pdo->query("SELECT * FROM configuracao_horarios LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

echo "=== CONFIGURAÇÃO ===\n";
echo "sistema_ativo: " . var_export($config['sistema_ativo'], true) . "\n";
echo "aberto_manual: " . var_export($config['aberto_manual'], true) . "\n";
echo "aberto_manual is null: " . ($config['aberto_manual'] === null ? 'SIM' : 'NÃO') . "\n";
echo "mensagem_fechado: " . ($config['mensagem_fechado'] ?? 'N/A') . "\n\n";

// Horário do dia atual
$dia_semana = date('w');
$stmt = $pdo->prepare("SELECT * FROM horarios_funcionamento WHERE dia_semana = ?");
$stmt->execute([$dia_semana]);
$horario = $stmt->fetch(PDO::FETCH_ASSOC);

echo "=== HORÁRIO DE HOJE (Dia $dia_semana) ===\n";
if ($horario) {
    echo "ativo: " . var_export($horario['ativo'], true) . "\n";
    echo "horario_abertura: " . $horario['horario_abertura'] . "\n";
    echo "horario_fechamento: " . $horario['horario_fechamento'] . "\n";
    
    $hora_atual = date('H:i:s');
    echo "\nHora atual: $hora_atual\n";
    echo "Dentro do horário: " . ($hora_atual >= $horario['horario_abertura'] && $hora_atual <= $horario['horario_fechamento'] ? 'SIM' : 'NÃO') . "\n";
} else {
    echo "Nenhum horário cadastrado para este dia!\n";
}

echo "\n=== RESULTADO loja_aberta() ===\n";
$resultado = loja_aberta();
echo "Retorno: " . ($resultado ? 'ABERTA' : 'FECHADA') . "\n";

echo "\n=== EXPLICAÇÃO ===\n";
if ($config['aberto_manual'] !== null) {
    if (intval($config['aberto_manual']) === 1) {
        echo "A loja está ABERTA MANUALMENTE (override ativo)\n";
    } else {
        echo "A loja está FECHADA MANUALMENTE (override ativo)\n";
        echo "Para usar horários automáticos, clique em 'Modo Automático' no admin!\n";
    }
} else {
    echo "Modo automático ativo - usando horários cadastrados\n";
}
