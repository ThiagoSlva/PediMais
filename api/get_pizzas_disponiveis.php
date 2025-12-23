<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/functions.php';

try {
    // Buscar categoria "Pizza" ou "Pizzas" dinamicamente
    $stmt = $pdo->prepare("SELECT id FROM categorias WHERE (nome LIKE ? OR nome LIKE ?) AND ativo = 1 LIMIT 1");
    $stmt->execute(['%Pizza%', '%Pizzas%']);
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$categoria) {
        // Fallback: Tentar buscar qualquer categoria que tenha produtos com "Pizza" no nome
        $stmt = $pdo->query("SELECT categoria_id FROM produtos WHERE nome LIKE '%Pizza%' LIMIT 1");
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($prod) {
            $categoria_pizza_id = $prod['categoria_id'];
        } else {
            echo json_encode([]);
            exit;
        }
    } else {
        $categoria_pizza_id = $categoria['id'];
    }

    $pizzas = get_produtos_por_categoria($categoria_pizza_id);

    $response = [];
    foreach ($pizzas as $pizza) {
        $response[] = [
            'id' => $pizza['id'],
            'nome' => $pizza['nome'],
            'descricao' => $pizza['descricao'],
            'preco' => $pizza['preco'],
            'preco_formatado' => formatar_moeda($pizza['preco']),
            'imagem_path' => $pizza['imagem_path'] ? $pizza['imagem_path'] : 'admin/assets/images/sem-foto.jpg'
        ];
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(['erro' => true, 'mensagem' => $e->getMessage()]);
}
?>