<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID do produto não fornecido']);
    exit;
}

$id = intval($_GET['id']);
$produto = get_produto_detalhes($id);

if (!$produto) {
    echo json_encode(['error' => 'Produto não encontrado']);
    exit;
}

// Buscar se categoria permite meio a meio
$stmt_cat = $pdo->prepare("SELECT id, permite_meio_a_meio FROM categorias WHERE id = ?");
$stmt_cat->execute([$produto['categoria_id']]);
$categoria = $stmt_cat->fetch(PDO::FETCH_ASSOC);
$permite_meio_a_meio = $categoria && ($categoria['permite_meio_a_meio'] ?? 0);

// Formatar dados para o frontend
$response = [
    'id' => $produto['id'],
    'nome' => $produto['nome'],
    'descricao' => $produto['descricao'],
    'preco' => $produto['preco'],
    'preco_promocional' => $produto['preco_promocional'],
    'preco_formatado' => ($produto['preco_promocional'] > 0) ? formatar_moeda($produto['preco_promocional']) : formatar_moeda($produto['preco']),
    'imagem_path' => $produto['imagem_path'] ? $produto['imagem_path'] : 'admin/assets/images/sem-foto.jpg',
    'categoria_id' => $produto['categoria_id'],
    'permite_meio_a_meio' => $permite_meio_a_meio ? true : false,
    'opcoes' => []
];

if (isset($produto['opcoes'])) {
    foreach ($produto['opcoes'] as $opcao) {
        $response['opcoes'][] = [
            'id' => $opcao['id'],
            'nome' => $opcao['nome'],
            'preco' => $opcao['preco_adicional'],
            'preco_formatado' => formatar_moeda($opcao['preco_adicional']),
            'tipo' => $opcao['tipo']
        ];
    }
}

echo json_encode($response);
?>