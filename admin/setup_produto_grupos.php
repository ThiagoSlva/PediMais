<?php
// Setup script for produto_grupos table
// Run once to create the association table

define('DB_HOST', '104.225.130.177');
define('DB_NAME', 'xfxpanel_cardapix');
define('DB_USER', 'xfxpanel_cardapix');
define('DB_PASS', '72734108Thi@go');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "CREATE TABLE IF NOT EXISTS `produto_grupos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produto_id` int(11) NOT NULL,
  `grupo_id` int(11) NOT NULL,
  `ordem` int(11) DEFAULT 0,
  `criado_em` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `produto_grupo` (`produto_id`, `grupo_id`),
  KEY `produto_id` (`produto_id`),
  KEY `grupo_id` (`grupo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

echo "<h2>Creating produto_grupos table...</h2>";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green'>Table 'produto_grupos' created or already exists.</p>";
} else {
    echo "<p style='color:red'>Error: " . $conn->error . "</p>";
}

$conn->close();
echo "<p><a href='produtos.php'>Back to Products</a></p>";
?>
