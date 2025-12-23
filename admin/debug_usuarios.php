<?php
require '../includes/config.php';

echo "<h2>Verificando usuários e seus níveis de acesso:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Nivel Acesso (DB)</th><th>Ativo</th></tr>";

$stmt = $pdo->query("SELECT id, nome, email, nivel_acesso, ativo FROM usuarios ORDER BY id");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($usuarios as $u) {
    echo "<tr>";
    echo "<td>" . $u['id'] . "</td>";
    echo "<td>" . htmlspecialchars($u['nome']) . "</td>";
    echo "<td>" . htmlspecialchars($u['email']) . "</td>";
    echo "<td><strong>" . htmlspecialchars($u['nivel_acesso']) . "</strong></td>";
    echo "<td>" . ($u['ativo'] ? 'Sim' : 'Não') . "</td>";
    echo "</tr>";
}

echo "</table>";

session_start();
echo "<h2>Sessão atual (usuário logado):</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Campo</th><th>Valor</th></tr>";
echo "<tr><td>usuario_id</td><td>" . ($_SESSION['usuario_id'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>usuario_nome</td><td>" . ($_SESSION['usuario_nome'] ?? 'N/A') . "</td></tr>";
echo "<tr><td>usuario_email</td><td>" . ($_SESSION['usuario_email'] ?? 'N/A') . "</td></tr>";
echo "<tr><td><strong>usuario_nivel</strong></td><td><strong>" . ($_SESSION['usuario_nivel'] ?? 'N/A') . "</strong></td></tr>";
echo "</table>";

echo "<br><br>";
echo "<a href='logout.php' style='padding:10px 20px; background:red; color:white; text-decoration:none; border-radius:5px;'>Fazer Logout para Testar</a>";
?>
