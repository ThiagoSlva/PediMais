<?php
// Teste de lógica de caminho de imagem
require_once 'includes/image_optimization.php';

// Simular caminhos
$path1 = 'f:/downthiagosilva/cardapix1.0.0-1/PediMais/admin/uploads/produtos/teste1';
$path2 = 'f:/downthiagosilva/cardapix1.0.0-1/PediMais/uploads/produtos/teste2';

echo "Teste 1 (admin/uploads):\n";
// Mocking file existence checks inside the function would be hard, 
// so we'll just copy the logic block here to verify it works as expected
$destination = $path1;
$relative = '';
if (strpos($destination, '/admin/uploads/') !== false) {
    $relative = 'admin/uploads/' . substr($destination, strpos($destination, '/admin/uploads/') + 15);
}
elseif (strpos($destination, '/uploads/') !== false) {
    $relative = 'uploads/' . substr($destination, strpos($destination, '/uploads/') + 9);
}
else {
    $relative = basename($destination);
}
echo "Resultado 1: " . $relative . "\n";

echo "Teste 2 (uploads raiz):\n";
$destination = $path2;
if (strpos($destination, '/admin/uploads/') !== false) {
    $relative = 'admin/uploads/' . substr($destination, strpos($destination, '/admin/uploads/') + 15);
}
elseif (strpos($destination, '/uploads/') !== false) {
    $relative = 'uploads/' . substr($destination, strpos($destination, '/uploads/') + 9);
}
else {
    $relative = basename($destination);
}
echo "Resultado 2: " . $relative . "\n";
?>
