<?php
session_start();

function verificar_login() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}

function get_usuario_atual() {
    if (isset($_SESSION['usuario_id'])) {
        return [
            'id' => $_SESSION['usuario_id'],
            'nome' => $_SESSION['usuario_nome'],
            'email' => $_SESSION['usuario_email'],
            'nivel' => $_SESSION['usuario_nivel']
        ];
    }
    return null;
}

/**
 * Get permitted pages for each user level
 */
function get_paginas_permitidas($nivel) {
    // Pages everyone can access
    $comum = ['logout.php', 'minha_conta.php'];
    
    switch ($nivel) {
        case 'admin':
        case 'gerente':
            // Full access - return empty array means no restrictions
            return [];
            
        case 'cozinha':
            return array_merge($comum, [
                'index.php',
                'pedidos_kanban.php',
                'pedidos.php',
                'pedido_detalhe.php'
            ]);
            
        case 'entregador':
            return array_merge($comum, [
                'index.php',
                'entregas_painel.php',
                'pedidos.php',
                'pedido_detalhe.php'
            ]);
            
        default:
            return $comum;
    }
}

/**
 * Check if user has access to a specific page
 */
function verificar_permissao($pagina = null) {
    verificar_login();
    
    $nivel = $_SESSION['usuario_nivel'] ?? 'guest';
    
    // Admin and gerente have full access
    if ($nivel === 'admin' || $nivel === 'gerente') {
        return true;
    }
    
    // Get current page if not provided
    if ($pagina === null) {
        $pagina = basename($_SERVER['PHP_SELF']);
    }
    
    $permitidas = get_paginas_permitidas($nivel);
    
    // If empty array, user has full access
    if (empty($permitidas)) {
        return true;
    }
    
    if (!in_array($pagina, $permitidas)) {
        // Redirect to appropriate page based on role
        if ($nivel === 'cozinha') {
            header('Location: pedidos_kanban.php');
        } elseif ($nivel === 'entregador') {
            header('Location: entregas_painel.php');
        } else {
            header('Location: index.php');
        }
        exit;
    }
    
    return true;
}

/**
 * Check if user is admin or gerente
 */
function is_admin() {
    $nivel = $_SESSION['usuario_nivel'] ?? 'guest';
    return in_array($nivel, ['admin', 'gerente']);
}

/**
 * Check if user can edit menu (produtos, categorias, etc)
 */
function pode_editar_cardapio() {
    return is_admin();
}

/**
 * Check if user can manage settings
 */
function pode_gerenciar_configuracoes() {
    return is_admin();
}

/**
 * Get menu items based on user level
 */
function get_menu_items() {
    $nivel = $_SESSION['usuario_nivel'] ?? 'guest';
    
    $menu = [];
    
    if ($nivel === 'admin' || $nivel === 'gerente') {
        // Full menu for admin/gerente
        $menu['Principal'] = [
            ['href' => 'index.php', 'icon' => 'solar:home-2-bold-duotone', 'label' => 'Dashboard', 'color' => '#4a66f9'],
            ['href' => 'categorias.php', 'icon' => 'solar:widget-2-bold-duotone', 'label' => 'Categorias', 'color' => '#8b5cf6'],
            ['href' => 'produtos.php', 'icon' => 'solar:bag-5-bold-duotone', 'label' => 'Produtos', 'color' => '#10b981'],
            ['href' => 'grupos_adicionais.php', 'icon' => 'solar:layers-bold-duotone', 'label' => 'Adicionais', 'color' => '#f97316'],
            ['href' => 'itens_retirar.php', 'icon' => 'solar:minus-circle-bold-duotone', 'label' => 'Itens para Retirar', 'color' => '#ef4444'],
            ['href' => 'pedidos.php', 'icon' => 'solar:cart-large-2-bold-duotone', 'label' => 'Pedidos', 'color' => '#06b6d4'],
            ['href' => 'clientes.php', 'icon' => 'solar:users-group-rounded-bold-duotone', 'label' => 'Clientes', 'color' => '#ec4899'],
            ['href' => 'pedidos_kanban.php', 'icon' => 'solar:clipboard-list-bold-duotone', 'label' => 'Kanban', 'color' => '#14b8a6']
        ];
        
        $menu['Marketing'] = [
            ['href' => 'whatsapp_config.php', 'icon' => 'ic:baseline-whatsapp', 'label' => 'Configuração API', 'color' => '#25d366'],
            ['href' => 'whatsapp_mensagens.php', 'icon' => 'solar:chat-round-dots-bold-duotone', 'label' => 'Mensagens Editáveis', 'color' => '#3b82f6']
        ];
        
        $menu['Gateways de Pagamento'] = [
            ['href' => 'gateway_config.php', 'icon' => 'solar:card-bold-duotone', 'label' => 'Configuração', 'color' => '#00bcff'],
            ['href' => 'mercadopago_mensagens.php', 'icon' => 'solar:chat-line-bold-duotone', 'label' => 'Mensagens PIX', 'color' => '#32bcad']
        ];
        
        $menu['Sistema'] = [
            ['href' => 'usuarios.php', 'icon' => 'solar:user-id-bold-duotone', 'label' => 'Usuários', 'color' => '#8b5cf6'],
            ['href' => 'configuracoes.php', 'icon' => 'solar:settings-bold-duotone', 'label' => 'Configurações', 'color' => '#6b7280'],
            ['href' => 'impressora.php', 'icon' => 'solar:printer-bold-duotone', 'label' => 'Impressora', 'color' => '#f97316'],
            ['href' => 'recaptcha_config.php', 'icon' => 'solar:shield-check-bold-duotone', 'label' => 'reCAPTCHA', 'color' => '#4285f4'],
            ['href' => 'horarios.php', 'icon' => 'solar:clock-circle-bold-duotone', 'label' => 'Horários', 'color' => '#f59e0b'],
            ['href' => 'formas_pagamento.php', 'icon' => 'solar:wallet-bold-duotone', 'label' => 'Formas de Pagamento', 'color' => '#10b981'],
            ['href' => 'fidelidade_config.php', 'icon' => 'solar:gift-bold-duotone', 'label' => 'Fidelidade', 'color' => '#ec4899'],
            ['href' => 'verificacao_config.php', 'icon' => 'solar:verified-check-bold-duotone', 'label' => 'Verificação Primeiro Pedido', 'color' => '#22c55e'],
            ['href' => 'configuracao_entrega.php', 'icon' => 'solar:scooter-bold-duotone', 'label' => 'Configuração de Entrega', 'color' => '#f97316'],
            ['href' => 'avaliacoes.php', 'icon' => 'solar:star-bold-duotone', 'label' => 'Avaliações', 'color' => '#fbbf24']
        ];
        
        $menu['Inteligência Artificial'] = [
            ['href' => 'importar_cardapio.php', 'icon' => 'ri:gemini-fill', 'label' => 'Importar Cardápio IA', 'color' => '#8b5cf6'],
            ['href' => 'gemini_config.php', 'icon' => 'solar:settings-minimalistic-bold-duotone', 'label' => 'Configurar IA', 'color' => '#4285f4']
        ];
        
    } elseif ($nivel === 'cozinha') {
        // Menu for kitchen staff
        $menu['Principal'] = [
            ['href' => 'index.php', 'icon' => 'solar:home-2-bold-duotone', 'label' => 'Dashboard', 'color' => '#4a66f9'],
            ['href' => 'pedidos_kanban.php', 'icon' => 'solar:clipboard-list-bold-duotone', 'label' => 'Kanban', 'color' => '#14b8a6'],
            ['href' => 'pedidos.php', 'icon' => 'solar:cart-large-2-bold-duotone', 'label' => 'Pedidos', 'color' => '#06b6d4']
        ];
        
    } elseif ($nivel === 'entregador') {
        // Menu for delivery staff - ONLY their deliveries
        $menu['Principal'] = [
            ['href' => 'index.php', 'icon' => 'solar:home-2-bold-duotone', 'label' => 'Dashboard', 'color' => '#4a66f9'],
            ['href' => 'entregas_painel.php', 'icon' => 'solar:scooter-bold-duotone', 'label' => 'Minhas Entregas', 'color' => '#f97316']
        ];
    } else {
        // Guest/unknown - minimal menu
        $menu['Principal'] = [
            ['href' => 'index.php', 'icon' => 'solar:home-2-bold-duotone', 'label' => 'Dashboard', 'color' => '#4a66f9']
        ];
    }
    
    // Conta section - available to all
    $menu['Conta'] = [
        ['href' => 'logout.php', 'icon' => 'solar:logout-2-bold-duotone', 'label' => 'Sair', 'color' => '#ef4444']
    ];
    
    return $menu;
}
?>
