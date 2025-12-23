<?php
include 'includes/auth.php';
include '../includes/config.php';

// Nome do arquivo
$filename = "produtos_export_" . date('Y-m-d') . ".csv";

// Cabeçalhos para download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Criar arquivo na saída
$output = fopen('php://output', 'w');

// Adicionar BOM para Excel reconhecer UTF-8
fputs($output, "\xEF\xBB\xBF");

// Cabeçalho das colunas
fputcsv($output, array('ID', 'Nome', 'Descrição', 'Preço', 'Categoria ID', 'Ativo', 'Ordem'));

// Buscar produtos
$stmt = $pdo->query("SELECT id, nome, descricao, preco, categoria_id, ativo, ordem FROM produtos ORDER BY id ASC");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Formatar preço
    $row['preco'] = number_format($row['preco'], 2, ',', '.');
    // Converter status ativo
    $row['ativo'] = ($row['ativo'] == 1) ? 'Sim' : 'Não';
    
    fputcsv($output, $row);
}

fclose($output);
exit;