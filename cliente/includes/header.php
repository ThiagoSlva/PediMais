<?php
// cliente/includes/header.php - Premium Dashboard
if (!isset($_SESSION)) {
    session_start();
}

// Get client data if available
$cliente_foto = null;
$cliente_nome = 'Cliente';
if (isset($_SESSION['cliente_id'])) {
    global $pdo;
    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT nome, foto_perfil FROM clientes WHERE id = ?");
        $stmt->execute([$_SESSION['cliente_id']]);
        $cliente_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cliente_data) {
            $cliente_nome = $cliente_data['nome'];
            $cliente_foto = $cliente_data['foto_perfil'];
        }
    }
}

// Get user's theme preference
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - PedeMais' : 'Área do Cliente - PedeMais'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Premium Dashboard CSS -->
    <link href="assets/css/dashboard.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        /* Page-specific overrides */
        .container-dashboard {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .container-dashboard {
                padding: 1rem;
            }
        }
    </style>
</head>
<body class="<?php echo $theme === 'dark' ? 'dark-mode' : ''; ?>">

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<?php include __DIR__ . '/navbar.php'; ?>

<main class="container-dashboard">
