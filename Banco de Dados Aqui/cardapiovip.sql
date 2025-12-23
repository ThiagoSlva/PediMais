-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 04/12/2025 às 20:15
-- Versão do servidor: 5.7.40-log
-- Versão do PHP: 8.3.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `cardapiovip`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `avaliacoes`
--

CREATE TABLE `avaliacoes` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) DEFAULT NULL COMMENT 'ID do pedido relacionado (opcional)',
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome da pessoa que avaliou',
  `avaliacao` int(1) NOT NULL COMMENT 'Avaliação de 1 a 5 estrelas',
  `descricao` text COLLATE utf8mb4_unicode_ci COMMENT 'Descrição/comentário da avaliação',
  `ativo` tinyint(1) DEFAULT '1' COMMENT 'Se a avaliação está ativa (mostrar no site)',
  `data_avaliacao` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Data da avaliação'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Avaliações dos clientes';

--
-- Despejando dados para a tabela `avaliacoes`
--

INSERT INTO `avaliacoes` (`id`, `pedido_id`, `nome`, `avaliacao`, `descricao`, `ativo`, `data_avaliacao`) VALUES
(2, 5, 'Jéssica', 5, '', 1, '2025-12-02 18:52:04');

-- --------------------------------------------------------

--
-- Estrutura para tabela `bairros_entrega`
--

CREATE TABLE `bairros_entrega` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cidade_id` int(11) NOT NULL,
  `valor_entrega` decimal(10,2) NOT NULL DEFAULT '0.00',
  `entrega_disponivel` tinyint(1) NOT NULL DEFAULT '1',
  `tempo_estimado` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ex: 30-45 min',
  `gratis_acima_valor` decimal(10,2) DEFAULT NULL COMMENT 'Grátis se pedido > valor',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `ordem` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `bairros_entrega`
--

INSERT INTO `bairros_entrega` (`id`, `nome`, `cidade_id`, `valor_entrega`, `entrega_disponivel`, `tempo_estimado`, `gratis_acima_valor`, `ativo`, `ordem`) VALUES
(5, 'Olivença', 2, 5.00, 1, NULL, NULL, 1, 0),
(6, 'Estados', 3, 5.00, 1, NULL, NULL, 1, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `bairro_ceps`
--

CREATE TABLE `bairro_ceps` (
  `id` int(11) NOT NULL,
  `bairro_id` int(11) NOT NULL,
  `cep` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `cardapio_cores`
--

CREATE TABLE `cardapio_cores` (
  `id` int(11) NOT NULL,
  `cor_principal` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#4caf50' COMMENT 'Cor principal do tema',
  `cor_secundaria` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#45a049' COMMENT 'Cor secundária (para gradientes)',
  `nome_tema` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Verde' COMMENT 'Nome do tema selecionado',
  `ativo` tinyint(1) DEFAULT '1' COMMENT 'Se o tema está ativo',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configurações de cores do cardápio digital';

--
-- Despejando dados para a tabela `cardapio_cores`
--

INSERT INTO `cardapio_cores` (`id`, `cor_principal`, `cor_secundaria`, `nome_tema`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(1, '#9C27B0', '#7B1FA2', 'Roxo', 1, '2025-11-02 17:06:05', '2025-12-02 13:40:16');

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text,
  `ordem` int(11) NOT NULL DEFAULT '0',
  `imagem` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `nome`, `descricao`, `ordem`, `imagem`, `ativo`) VALUES
(12, 'Pizza', '', 2, 'admin/uploads/categorias/cat_1762601073_690f2871b13c0.jpg', 1),
(13, 'Hambúrguer', '', 1, 'admin/uploads/categorias/cat_1762601133_690f28ad67211.jpg', 1),
(14, 'Bebidas', '', 4, 'admin/uploads/categorias/cat_1762601175_690f28d7a4b05.jpg', 1),
(15, 'Batata Frita', '', 3, 'admin/uploads/categorias/cat_1762601222_690f2906bc086.jpg', 1),
(16, 'Hambúrguer Especiais', '', 0, 'admin/uploads/categorias/cat_1762647819_690fdf0b79273.jpg', 1),
(17, 'Pastéis', '', 6, 'admin/uploads/categorias/cat_1764595365_692d96a58e570.webp', 1),
(18, 'Porções', '', 7, 'admin/uploads/categorias/cat_1764595408_692d96d0f149c.jpg', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `cidades_entrega`
--

CREATE TABLE `cidades_entrega` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `ordem` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `cidades_entrega`
--

INSERT INTO `cidades_entrega` (`id`, `nome`, `estado`, `ativo`, `ordem`) VALUES
(2, 'Ilhéus', 'BA', 1, 0),
(3, 'Fazenda Rio Grande', 'PR', 1, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Hash da senha para área do cliente',
  `foto_perfil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Caminho da foto de perfil',
  `endereco_principal` text COLLATE utf8mb4_unicode_ci COMMENT 'Endereço completo formatado',
  `cep` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rua` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complemento` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bairro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_pedidos` int(11) DEFAULT '0' COMMENT 'Total de pedidos do cliente',
  `valor_total_gasto` decimal(10,2) DEFAULT '0.00' COMMENT 'Valor total gasto',
  `ultimo_pedido` datetime DEFAULT NULL COMMENT 'Data do último pedido',
  `ativo` tinyint(1) DEFAULT '1' COMMENT 'Cliente ativo/inativo',
  `observacoes` text COLLATE utf8mb4_unicode_ci COMMENT 'Observações internas sobre o cliente',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `primeiro_acesso` tinyint(1) DEFAULT '1' COMMENT 'Se 1, precisa trocar senha no primeiro login',
  `ultimo_login` datetime DEFAULT NULL COMMENT 'Data do último login',
  `token_sessao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Token de sessão único'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Clientes do sistema';

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes_verificados`
--

CREATE TABLE `clientes_verificados` (
  `id` int(11) NOT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Telefone do cliente (apenas números)',
  `cliente_id` int(11) DEFAULT NULL COMMENT 'ID do cliente na tabela clientes',
  `verificado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Data da primeira verificação',
  `primeiro_pedido_id` int(11) DEFAULT NULL COMMENT 'ID do primeiro pedido verificado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Clientes que já passaram pela verificação do primeiro pedido';

-- --------------------------------------------------------

--
-- Estrutura para tabela `cliente_enderecos`
--

CREATE TABLE `cliente_enderecos` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `titulo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ex: Casa, Trabalho, Apartamento',
  `cep` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rua` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `complemento` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bairro` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cidade` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `endereco_completo` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Endereço formatado',
  `principal` tinyint(1) DEFAULT '0' COMMENT 'Se é o endereço principal',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Endereços dos clientes';

-- --------------------------------------------------------

--
-- Estrutura para tabela `cliente_notificacoes`
--

CREATE TABLE `cliente_notificacoes` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `pedido_id` int(11) DEFAULT NULL,
  `tipo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensagem` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `lida` tinyint(1) DEFAULT '0',
  `enviada_push` tinyint(1) DEFAULT '0' COMMENT 'Se foi enviada notificação push',
  `criada_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notificações para clientes';

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracao_avaliacoes`
--

CREATE TABLE `configuracao_avaliacoes` (
  `id` int(11) NOT NULL,
  `ativo` tinyint(1) DEFAULT '1' COMMENT 'Se o sistema de avaliações está ativo',
  `mostrar_no_site` tinyint(1) DEFAULT '1' COMMENT 'Se deve mostrar avaliações no site',
  `mensagem_avaliacao` text COLLATE utf8mb4_unicode_ci COMMENT 'Mensagem personalizada para envio com link de avaliação'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuração do sistema de avaliações';

--
-- Despejando dados para a tabela `configuracao_avaliacoes`
--

INSERT INTO `configuracao_avaliacoes` (`id`, `ativo`, `mostrar_no_site`, `mensagem_avaliacao`) VALUES
(1, 1, 0, '⭐ *Avalie seu pedido!*\r\n\r\n🔗 Clique no link para avaliar:\r\n{link}');

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracao_entrega`
--

CREATE TABLE `configuracao_entrega` (
  `id` int(11) NOT NULL,
  `modo_gratis_valor_ativo` tinyint(1) DEFAULT '0',
  `modo_gratis_todos_ativo` tinyint(1) DEFAULT '0',
  `modo_valor_fixo_ativo` tinyint(1) DEFAULT '0',
  `modo_por_bairro_ativo` tinyint(1) DEFAULT '0',
  `valor_minimo_gratis` decimal(10,2) DEFAULT '0.00' COMMENT 'Entrega grátis acima deste valor',
  `valor_fixo_entrega` decimal(10,2) DEFAULT '0.00' COMMENT 'Valor fixo único',
  `km_gratis` decimal(10,2) DEFAULT '0.00' COMMENT 'Km gratuitos',
  `aceita_retirada` tinyint(1) NOT NULL DEFAULT '1',
  `taxa_retirada` decimal(10,2) DEFAULT '0.00' COMMENT 'Taxa para retirada no balcão',
  `endereco_referencia_cep` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_referencia_rua` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_referencia_numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_referencia_bairro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_referencia_cidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_referencia_estado` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `configuracao_entrega`
--

INSERT INTO `configuracao_entrega` (`id`, `modo_gratis_valor_ativo`, `modo_gratis_todos_ativo`, `modo_valor_fixo_ativo`, `modo_por_bairro_ativo`, `valor_minimo_gratis`, `valor_fixo_entrega`, `km_gratis`, `aceita_retirada`, `taxa_retirada`, `endereco_referencia_cep`, `endereco_referencia_rua`, `endereco_referencia_numero`, `endereco_referencia_bairro`, `endereco_referencia_cidade`, `endereco_referencia_estado`, `atualizado_em`) VALUES
(1, 0, 0, 0, 1, 15.00, 5.00, 0.00, 1, 0.00, '', '', '', '', '', '', '2025-11-10 00:22:30');

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracao_horarios`
--

CREATE TABLE `configuracao_horarios` (
  `id` int(11) NOT NULL,
  `sistema_ativo` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Sistema de horários ativado',
  `aberto_manual` tinyint(1) DEFAULT NULL COMMENT 'NULL=automático, 1=aberto forçado, 0=fechado forçado',
  `mensagem_fechado` text COLLATE utf8mb4_unicode_ci COMMENT 'Mensagem quando fechado',
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `configuracao_horarios`
--

INSERT INTO `configuracao_horarios` (`id`, `sistema_ativo`, `aberto_manual`, `mensagem_fechado`, `atualizado_em`) VALUES
(1, 0, 1, 'Estamos Fechados', '2025-12-03 13:34:56');

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `id` int(11) NOT NULL,
  `site_titulo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'Cardápio Digital',
  `site_descricao` text COLLATE utf8mb4_unicode_ci,
  `site_logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_favicon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `site_capa` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_cep` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_rua` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_complemento` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_bairro` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_cidade` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `endereco_estado` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contato_whatsapp` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contato_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `social_facebook` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `social_instagram` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `whatsapp_chave_pix` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Chave PIX para pagamentos',
  `whatsapp_tempo_preparo_padrao` int(11) DEFAULT '30' COMMENT 'Tempo de preparo padrão em minutos',
  `whatsapp_tempo_entrega_padrao` int(11) DEFAULT '40' COMMENT 'Tempo de entrega padrão em minutos',
  `whatsapp_mensagem_pronto_retirada` text COLLATE utf8mb4_unicode_ci COMMENT 'Mensagem quando pedido está pronto para retirada',
  `whatsapp_mensagem_pronto_delivery` text COLLATE utf8mb4_unicode_ci COMMENT 'Mensagem quando pedido está pronto para delivery',
  `recaptcha_v2_site_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Site Key do reCAPTCHA v2',
  `recaptcha_v2_secret_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Secret Key do reCAPTCHA v2',
  `recaptcha_v3_site_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Site Key do reCAPTCHA v3',
  `recaptcha_v3_secret_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Secret Key do reCAPTCHA v3',
  `recaptcha_version` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'v2' COMMENT 'Versão do reCAPTCHA (v2 ou v3)',
  `recaptcha_ativo` tinyint(1) DEFAULT '0' COMMENT 'Ativar reCAPTCHA (1=sim, 0=não)',
  `recaptcha_paginas` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON com lista de páginas onde usar reCAPTCHA',
  `push_vapid_public_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'VAPID Public Key para Push Notifications',
  `push_vapid_private_key` text COLLATE utf8mb4_unicode_ci COMMENT 'VAPID Private Key para Push Notifications',
  `push_notifications_ativo` tinyint(1) DEFAULT '1' COMMENT 'Ativar Push Notifications (1=sim, 0=não)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`id`, `site_titulo`, `site_descricao`, `site_logo`, `site_favicon`, `site_capa`, `endereco_cep`, `endereco_rua`, `endereco_numero`, `endereco_complemento`, `endereco_bairro`, `endereco_cidade`, `endereco_estado`, `contato_whatsapp`, `contato_email`, `social_facebook`, `social_instagram`, `atualizado_em`, `whatsapp_chave_pix`, `whatsapp_tempo_preparo_padrao`, `whatsapp_tempo_entrega_padrao`, `whatsapp_mensagem_pronto_retirada`, `whatsapp_mensagem_pronto_delivery`, `recaptcha_v2_site_key`, `recaptcha_v2_secret_key`, `recaptcha_v3_site_key`, `recaptcha_v3_secret_key`, `recaptcha_version`, `recaptcha_ativo`, `recaptcha_paginas`, `push_vapid_public_key`, `push_vapid_private_key`, `push_notifications_ativo`) VALUES
(1, 'CardapiX', 'O melhor sistema de pedidos Online!', 'uploads/config/logo.png', 'uploads/config/favicon.ico', 'uploads/config/capa.jpg', '83830-289', 'Avenida Rio Amazonas', '110', '3254', 'Estados', 'Fazenda Rio Grande', 'PR', '(41) 99860-8485', 'plw@gmail.com', '', 'plwdesign', '2025-12-02 13:40:12', '73981433240', 30, 40, 'dsfsdf', 'sfsdfsdfsdfsdf', '6LcfagssAAAAAFbUrL-yJAmqD1vFbTdW3PEcYqhN', '6LcfagssAAAAADKXyHkcAl86GDA3YJUdE1fyziJy', '6LcnagssAAAAACon_vm_dersse_buSf_uFDTqy0P', '6LcnagssAAAAAGuFQoTWsAy_vhnpCpT4UGsZEzJ9', 'v3', 0, '[\"admin\\/login.php\",\"cliente\\/login.php\"]', 'BJ0OHSU1jrYKynyxAfV5Fuu3tQUBV887udulpUS_GM1IUnnIA2voY31Q7B_77mwJiq_tgtgxNYC7qs_Y_Gsahig', 'hTd4UtJT0ChJN3Pg0ebIHmE6hIpaJihhHz-69L5tNSM', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `fidelidade_config`
--

CREATE TABLE `fidelidade_config` (
  `id` int(11) NOT NULL,
  `ativo` tinyint(1) DEFAULT '1' COMMENT 'Sistema de fidelidade ativo (1=Sim, 0=Não)',
  `quantidade_pedidos` int(11) DEFAULT '10' COMMENT 'Quantidade de pedidos necessários para resgate',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configurações do sistema de fidelidade';

--
-- Despejando dados para a tabela `fidelidade_config`
--

INSERT INTO `fidelidade_config` (`id`, `ativo`, `quantidade_pedidos`, `criado_em`, `atualizado_em`) VALUES
(1, 1, 10, '2025-11-30 11:56:21', '2025-12-01 17:22:33');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fidelidade_pontos`
--

CREATE TABLE `fidelidade_pontos` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL COMMENT 'ID do cliente',
  `pedido_id` int(11) NOT NULL COMMENT 'ID do pedido que gerou o ponto',
  `status` enum('ativo','cancelado','resgatado') COLLATE utf8mb4_unicode_ci DEFAULT 'ativo' COMMENT 'Status do ponto',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pontos de fidelidade dos clientes';

--
-- Despejando dados para a tabela `fidelidade_pontos`
--

INSERT INTO `fidelidade_pontos` (`id`, `cliente_id`, `pedido_id`, `status`, `criado_em`, `atualizado_em`) VALUES
(1, 8, 1, 'cancelado', '2025-11-30 12:15:23', '2025-11-30 12:18:58'),
(2, 8, 2, 'resgatado', '2025-11-30 12:15:23', '2025-11-30 12:41:26'),
(3, 8, 3, 'resgatado', '2025-11-30 12:15:23', '2025-11-30 12:41:26'),
(4, 8, 4, 'resgatado', '2025-11-30 12:20:18', '2025-11-30 12:41:26');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fidelidade_produtos`
--

CREATE TABLE `fidelidade_produtos` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL COMMENT 'ID do produto que pode ser resgatado',
  `quantidade` int(11) DEFAULT '1' COMMENT 'Quantidade do produto no resgate',
  `ativo` tinyint(1) DEFAULT '1' COMMENT 'Produto ativo para resgate (1=Sim, 0=Não)',
  `ordem` int(11) DEFAULT '0' COMMENT 'Ordem de exibição',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Produtos disponíveis para resgate no sistema de fidelidade';

--
-- Despejando dados para a tabela `fidelidade_produtos`
--

INSERT INTO `fidelidade_produtos` (`id`, `produto_id`, `quantidade`, `ativo`, `ordem`, `criado_em`, `atualizado_em`) VALUES
(1, 41, 1, 1, 1, '2025-11-30 12:05:44', '2025-11-30 12:05:44'),
(2, 37, 1, 1, 2, '2025-12-01 13:37:04', '2025-12-01 13:37:04'),
(3, 49, 1, 1, 3, '2025-12-01 17:22:24', '2025-12-01 17:22:24');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fidelidade_resgates`
--

CREATE TABLE `fidelidade_resgates` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL COMMENT 'ID do cliente que resgatou',
  `pedido_id` int(11) DEFAULT NULL COMMENT 'ID do pedido gerado pelo resgate',
  `pontos_usados` int(11) NOT NULL COMMENT 'Quantidade de pontos usados no resgate',
  `status` enum('pendente','resgatado','cancelado') COLLATE utf8mb4_unicode_ci DEFAULT 'pendente' COMMENT 'Status do resgate',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico de resgates de fidelidade';

--
-- Despejando dados para a tabela `fidelidade_resgates`
--

INSERT INTO `fidelidade_resgates` (`id`, `cliente_id`, `pedido_id`, `pontos_usados`, `status`, `criado_em`, `atualizado_em`) VALUES
(4, 8, 5, 3, 'resgatado', '2025-11-30 12:41:26', '2025-11-30 12:41:26');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fidelidade_resgate_itens`
--

CREATE TABLE `fidelidade_resgate_itens` (
  `id` int(11) NOT NULL,
  `resgate_id` int(11) NOT NULL COMMENT 'ID do resgate',
  `produto_id` int(11) NOT NULL COMMENT 'ID do produto resgatado',
  `quantidade` int(11) DEFAULT '1' COMMENT 'Quantidade do produto',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Itens resgatados em cada resgate de fidelidade';

--
-- Despejando dados para a tabela `fidelidade_resgate_itens`
--

INSERT INTO `fidelidade_resgate_itens` (`id`, `resgate_id`, `produto_id`, `quantidade`, `criado_em`) VALUES
(4, 4, 41, 1, '2025-11-30 12:41:26');

-- --------------------------------------------------------

--
-- Estrutura para tabela `formas_pagamento`
--

CREATE TABLE `formas_pagamento` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('dinheiro','credito','debito','pix','outro') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `aceita_troco` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Para dinheiro',
  `chave_pix` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Chave PIX se tipo=pix',
  `icone` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ordem` int(11) NOT NULL DEFAULT '0',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `formas_pagamento`
--

INSERT INTO `formas_pagamento` (`id`, `nome`, `tipo`, `ativo`, `aceita_troco`, `chave_pix`, `icone`, `ordem`, `criado_em`) VALUES
(1, 'Dinheiro', 'dinheiro', 1, 1, '', 'mdi:brazilian-real', 1, '2025-10-29 23:37:35'),
(2, 'Cartão de Crédito', 'credito', 1, 0, NULL, 'solar:card-outline', 2, '2025-10-29 23:37:35'),
(3, 'Cartão de Débito', 'debito', 1, 0, NULL, 'solar:card-outline', 3, '2025-10-29 23:37:35'),
(4, 'PIX', 'pix', 1, 0, 'plw@plw.com', 'solar:qr-code-outline', 4, '2025-10-29 23:37:35');

-- --------------------------------------------------------

--
-- Estrutura para tabela `horarios_funcionamento`
--

CREATE TABLE `horarios_funcionamento` (
  `id` int(11) NOT NULL,
  `dia_semana` int(1) NOT NULL COMMENT '0=Domingo, 1=Segunda, 2=Terça, 3=Quarta, 4=Quinta, 5=Sexta, 6=Sábado',
  `horario_abertura` time NOT NULL,
  `horario_fechamento` time NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Dia está funcionando',
  `ordem` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `horarios_funcionamento`
--

INSERT INTO `horarios_funcionamento` (`id`, `dia_semana`, `horario_abertura`, `horario_fechamento`, `ativo`, `ordem`) VALUES
(1, 0, '00:00:00', '00:00:00', 0, 0),
(2, 1, '09:00:00', '18:00:00', 0, 1),
(3, 2, '09:00:00', '18:00:00', 0, 2),
(4, 3, '09:00:00', '18:00:00', 0, 3),
(5, 4, '07:00:00', '18:00:00', 0, 4),
(6, 5, '09:00:00', '18:00:00', 0, 5),
(7, 6, '09:00:00', '14:00:00', 0, 6);

-- --------------------------------------------------------

--
-- Estrutura para tabela `itens_retirar`
--

CREATE TABLE `itens_retirar` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `ordem` int(11) NOT NULL DEFAULT '0',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `itens_retirar`
--

INSERT INTO `itens_retirar` (`id`, `nome`, `descricao`, `ativo`, `ordem`, `criado_em`) VALUES
(17, 'Cebola', 'Sem cebola', 1, 0, '2025-12-01 16:45:54'),
(18, 'Tomate', 'Sem tomate.', 1, 1, '2025-12-01 16:46:45'),
(19, 'Milho', 'Sem Milho.', 1, 2, '2025-12-01 16:47:12');

-- --------------------------------------------------------

--
-- Estrutura para tabela `kanban_lanes`
--

CREATE TABLE `kanban_lanes` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cor` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#6c757d',
  `icone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'solar:box-outline',
  `ordem` int(11) NOT NULL DEFAULT '0',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `kanban_lanes`
--

INSERT INTO `kanban_lanes` (`id`, `nome`, `cor`, `icone`, `ordem`, `ativo`, `criado_em`) VALUES
(2, 'Novos Pedidos', '#17a2b8', 'solar:bell-outline', 1, 1, '2025-10-29 19:25:32'),
(3, 'Em Preparo', '#ffc107', 'solar:chef-hat-outline', 2, 1, '2025-10-29 19:25:32'),
(4, 'Pronto', '#28a745', 'solar:check-circle-outline', 3, 1, '2025-10-29 19:25:32'),
(5, 'Saiu para Entrega', '#007bff', 'solar:delivery-outline', 4, 1, '2025-10-29 19:25:32'),
(6, 'Entregue', '#6c757d', 'solar:verified-check-outline', 5, 1, '2025-10-29 19:25:32'),
(8, 'Cancelado', '#ff0000', 'solar:box-outline', 7, 1, '2025-11-30 12:21:26');

-- --------------------------------------------------------

--
-- Estrutura para tabela `mercadopago_config`
--

CREATE TABLE `mercadopago_config` (
  `id` int(11) NOT NULL DEFAULT '1',
  `ativo` tinyint(1) DEFAULT '0' COMMENT 'Ativar/Desativar pagamento online',
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'PIX Online' COMMENT 'Nome exibido no checkout',
  `sandbox_mode` tinyint(1) DEFAULT '1' COMMENT '1=Sandbox, 0=Produção',
  `public_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Public Key do Mercado Pago',
  `access_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Access Token do Mercado Pago',
  `prazo_pagamento_minutos` int(11) DEFAULT '30' COMMENT 'Prazo em minutos para pagamento',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `mostrar_modal_pagamento` tinyint(1) DEFAULT '0' COMMENT 'Mostrar modal com QR Code após finalizar pedido (0=Não, 1=Sim)',
  `botao_whatsapp_modal` tinyint(1) DEFAULT '1' COMMENT 'Mostrar botão WhatsApp no modal de finalização PIX (0=Não, 1=Sim)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `mercadopago_config`
--

INSERT INTO `mercadopago_config` (`id`, `ativo`, `nome`, `sandbox_mode`, `public_key`, `access_token`, `prazo_pagamento_minutos`, `criado_em`, `atualizado_em`, `mostrar_modal_pagamento`, `botao_whatsapp_modal`) VALUES
(1, 1, 'Pix Online', 0, 'suachavepublica', 'seutokenprivado', 10, '2025-10-31 11:19:00', '2025-12-04 12:13:44', 1, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `mercadopago_mensagens`
--

CREATE TABLE `mercadopago_mensagens` (
  `id` int(11) NOT NULL,
  `tipo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensagem` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `mercadopago_mensagens`
--

INSERT INTO `mercadopago_mensagens` (`id`, `tipo`, `titulo`, `mensagem`, `ativo`, `criado_em`, `atualizado_em`) VALUES
(149, 'aguardando_pagamento', 'Aguardando Pagamento PIX', 'Olá, {nome}! Recebemos o seu pedido e estamos aguardando o pagamento 😃.\n\nVocê tem {minutos} minutos para pagar o valor de R$ {valor} usando o Pix Copia e Cola ou o QR Code abaixo. Após esse prazo, o pedido será cancelado automaticamente.', 1, '2025-11-08 01:56:28', '2025-11-08 01:56:28'),
(150, 'pagamento_recebido', 'Pagamento Recebido', '🎉 O pagamento do seu pedido foi confirmado! Em breve vamos iniciar a preparação e manter você atualizado(a).', 1, '2025-11-08 01:56:28', '2025-11-08 01:56:28');

-- --------------------------------------------------------

--
-- Estrutura para tabela `mercadopago_pagamentos`
--

CREATE TABLE `mercadopago_pagamentos` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `payment_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qr_code` text COLLATE utf8mb4_unicode_ci,
  `qr_code_base64` longtext COLLATE utf8mb4_unicode_ci,
  `ticket_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `valor` decimal(10,2) NOT NULL,
  `expiracao` datetime DEFAULT NULL,
  `pago_em` datetime DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `mesas`
--

CREATE TABLE `mesas` (
  `id` int(11) NOT NULL,
  `numero_mesa` varchar(20) NOT NULL,
  `nome_mesa` varchar(100) DEFAULT NULL,
  `qrcode_url` varchar(255) DEFAULT NULL,
  `qrcode_path` varchar(255) DEFAULT NULL,
  `qrcode_base64` longtext,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `acessos` int(11) NOT NULL DEFAULT '0',
  `ultimo_acesso` datetime DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura para tabela `opcoes`
--

CREATE TABLE `opcoes` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `preco_adicional` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tipo` enum('tamanho','adicional','opcional') NOT NULL DEFAULT 'adicional'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `entregador_id` int(11) DEFAULT NULL,
  `codigo_pedido` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_avaliacao` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Token único para link de avaliação',
  `cliente_nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_endereco` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `mesa_numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valor_total` decimal(10,2) NOT NULL,
  `valor_produtos` decimal(10,2) DEFAULT '0.00',
  `valor_entrega` decimal(10,2) DEFAULT '0.00',
  `tipo_entrega` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'balcao',
  `forma_pagamento_id` int(11) DEFAULT NULL,
  `qr_code_base64` longtext COLLATE utf8mb4_unicode_ci COMMENT 'QR Code PIX em base64 (imagem)',
  `troco_para` decimal(10,2) DEFAULT NULL,
  `status` enum('pendente','em_andamento','pronto','saiu_entrega','concluido','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendente',
  `lane_id` int(11) DEFAULT NULL,
  `posicao_kanban` int(11) DEFAULT '0',
  `pago` tinyint(1) DEFAULT '0',
  `entregue` tinyint(1) DEFAULT '0',
  `saiu_entrega` tinyint(1) DEFAULT '0',
  `em_preparo` tinyint(1) DEFAULT '0',
  `observacoes_kanban` text COLLATE utf8mb4_unicode_ci,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `data_pedido` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `comprovante_pagamento` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Caminho do comprovante de pagamento',
  `comprovante_enviado_em` datetime DEFAULT NULL COMMENT 'Data de envio do comprovante',
  `comprovante_pix` text COLLATE utf8mb4_unicode_ci COMMENT 'URL ou base64 do comprovante PIX',
  `comprovante_validado` tinyint(1) DEFAULT '0' COMMENT 'Se comprovante foi validado',
  `comprovante_validado_em` datetime DEFAULT NULL,
  `comprovante_validado_por` int(11) DEFAULT NULL,
  `pagamento_online` tinyint(1) DEFAULT '0' COMMENT 'Pagamento online via Mercado Pago'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedido_item_opcoes`
--

CREATE TABLE `pedido_item_opcoes` (
  `id` int(11) NOT NULL,
  `pedido_item_id` int(11) NOT NULL,
  `opcao_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL DEFAULT '1',
  `preco_adicional` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedido_itens`
--

CREATE TABLE `pedido_itens` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `produto_nome` varchar(255) DEFAULT NULL,
  `quantidade` int(11) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  `observacoes` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pizza_relacionamentos`
--

CREATE TABLE `pizza_relacionamentos` (
  `id` int(11) NOT NULL,
  `pizza_id` int(11) NOT NULL,
  `pizza_relacionada_id` int(11) NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Relaciona quais pizzas podem ser combinadas em meio a meio';

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text,
  `ordem` int(11) NOT NULL DEFAULT '0',
  `preco` decimal(10,2) NOT NULL,
  `preco_promocional` decimal(10,2) DEFAULT NULL,
  `imagem_path` varchar(255) DEFAULT NULL,
  `imagem_url` varchar(255) DEFAULT NULL,
  `disponivel` tinyint(1) NOT NULL DEFAULT '1',
  `limite_adicionais` int(11) NOT NULL DEFAULT '0' COMMENT '0 = ilimitado',
  `limite_retirar` int(11) NOT NULL DEFAULT '0' COMMENT '0 = ilimitado',
  `eh_pizza` tinyint(1) DEFAULT '0' COMMENT 'Ativa modelo pizza (0=Não, 1=Sim)',
  `permite_meio_meio` tinyint(1) DEFAULT '0' COMMENT 'Permite meio a meio 2 sabores (0=Não, 1=Sim)',
  `tipo_calculo_meio_meio` enum('valor_somado','valor_maior') DEFAULT NULL COMMENT 'Tipo de cálculo: valor_somado ou valor_maior',
  `tamanho_pizza` int(11) DEFAULT '8' COMMENT 'Tamanho da pizza em pedaços',
  `max_sabores_meio_meio` int(11) DEFAULT '2' COMMENT 'Quantidade máxima de sabores no meio a meio'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `categoria_id`, `nome`, `descricao`, `ordem`, `preco`, `preco_promocional`, `imagem_path`, `imagem_url`, `disponivel`, `limite_adicionais`, `limite_retirar`, `eh_pizza`, `permite_meio_meio`, `tipo_calculo_meio_meio`, `tamanho_pizza`, `max_sabores_meio_meio`) VALUES
(34, 13, 'Beach Burger', 'Hambúrguer, mussarela, cheddar fatiado e salada.', 0, 20.00, NULL, NULL, '', 1, 0, 0, 0, 0, NULL, 8, 2),
(35, 13, 'Beach Salada', 'Hambúrguer, mussarela, barbecue e salada.', 0, 18.00, NULL, NULL, '', 1, 0, 0, 0, 0, NULL, 8, 2),
(36, 13, 'Beach Bacon', 'Hambúrguer, mussarela, barbecue, bacon e salada.', 0, 25.00, NULL, NULL, '', 1, 0, 0, 0, 0, NULL, 8, 2),
(37, 13, 'Beach Point', 'Hambúrguer, mussarela, barbecue, cheddar, bacon, calabresa e salada.', 0, 28.00, NULL, NULL, '', 1, 0, 0, 0, 0, NULL, 8, 2),
(38, 13, 'Beach Egg Burger', 'Hambúrguer, mussarela, barbecue, ovo e salada.', 0, 23.00, NULL, NULL, '', 1, 0, 0, 0, 0, NULL, 8, 2),
(39, 13, 'Beach Egg Bacon', 'Hambúrguer, mussarela, barbecue, bacon, ovo e salada.', 0, 26.00, NULL, NULL, '', 1, 0, 0, 0, 0, NULL, 8, 2),
(40, 13, 'Beach Frango', 'Frango, mussarela, barbecue, cheddar e salada.', 0, 23.00, NULL, NULL, '', 1, 0, 0, 0, 0, NULL, 8, 2),
(41, 16, 'Beach Baiano', 'Carne seca desfiada, mussarela, banana e cebola.', 0, 28.00, NULL, NULL, '', 1, 0, 0, 0, 0, NULL, 8, 2),
(42, 16, 'Beach Especial', 'Hambúrguer, mussarela, cebola, cream cheese e geleia de pimenta.', 0, 30.00, NULL, NULL, '', 1, 0, 0, 0, 0, NULL, 8, 2),
(43, 16, 'Beach Duplo', 'hambúrgueres, mussarela e cheddar fatiado.', 0, 30.00, NULL, NULL, '', 1, 0, 0, 0, 0, NULL, 8, 2),
(44, 16, 'Beach Triplo', '3 hambúrgueres, mussarela e cheddar fatiado.', 0, 38.00, NULL, NULL, '', 1, 0, 0, 0, 0, NULL, 8, 2),
(45, 16, 'Beach Point Duplo', '2 hambúrgueres, mussarela, barbecue, cheddar, ovo, bacon, calabresa, salada e cebola.', 0, 35.00, NULL, NULL, '', 1, 0, 0, 0, 0, NULL, 8, 2),
(46, 16, 'Beach Frango Especial', 'Filé de frango, mussarela, barbecue, cheddar, bacon, cebola, salada e cream cheese.', 0, 28.00, NULL, NULL, '', 1, 0, 0, 0, 0, NULL, 8, 2),
(47, 15, 'Batata Bacon', 'Batata frita com cheddar, catupiry, parmesão e bacon.', 0, 35.00, NULL, NULL, '', 1, 0, 0, 0, 0, NULL, 8, 2),
(48, 15, 'Batata 4 Queijos', 'Batata frita com cheddar, catupiry e parmesão.', 0, 30.00, NULL, NULL, '', 1, 0, 0, 0, 0, NULL, 8, 2),
(49, 15, 'Batata Simples', 'Batata frita tradicional.', 0, 25.00, NULL, NULL, '', 1, 0, 0, 0, 0, NULL, 8, 2),
(50, 14, 'Água', '', 0, 5.00, NULL, NULL, '', 1, 0, 0, 0, 0, NULL, 8, 2);

-- --------------------------------------------------------

--
-- Estrutura para tabela `produto_adicionais`
--

CREATE TABLE `produto_adicionais` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL COMMENT 'Produto principal',
  `adicional_produto_id` int(11) NOT NULL COMMENT 'Produto que é adicional',
  `adicional_categoria_id` int(11) DEFAULT NULL COMMENT 'Categoria de adicionais',
  `preco_adicional` decimal(10,2) DEFAULT NULL COMMENT 'Preço extra (NULL = usar preço do produto)',
  `ordem` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `produto_adicionais`
--

INSERT INTO `produto_adicionais` (`id`, `produto_id`, `adicional_produto_id`, `adicional_categoria_id`, `preco_adicional`, `ordem`) VALUES
(94, 38, 50, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `produto_itens_retirar`
--

CREATE TABLE `produto_itens_retirar` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `item_retirar_id` int(11) NOT NULL,
  `ordem` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `push_subscriptions`
--

CREATE TABLE `push_subscriptions` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `endpoint` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `p256dh_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auth_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Subscriptions para notificações push';

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel_acesso` enum('admin','gerente','cozinha','entregador') NOT NULL DEFAULT 'admin',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `ultimo_acesso` timestamp NULL DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `nivel_acesso`, `ativo`, `ultimo_acesso`, `criado_em`) VALUES
(1, 'Administrador', 'admin@admin.com', '$2y$10$mhK7hQ7Ke9i2hX00CB/eP.f/vx9XX00fIcPDv3D6s6TqoO5481C6i', 'admin', 1, NULL, '2025-10-29 14:39:49'),
(7, 'Cozinha', 'cozinha@cozinha.com', '$2y$10$Gv7rRG73Sjrd8wE5GGOsCO3ncJUbyDA8LIBEELjsTlvp35hpCcUGC', 'cozinha', 1, NULL, '2025-11-10 00:17:50'),
(8, 'entregador', 'entregador@entregador.com', '$2y$10$7KhTBQN9La4q0TsAgLHCzOwYejyvzBguiKRQXbwPtypPVkiDXdHXm', 'entregador', 1, NULL, '2025-11-10 00:19:54');

-- --------------------------------------------------------

--
-- Estrutura para tabela `verificacao_codigos`
--

CREATE TABLE `verificacao_codigos` (
  `id` int(11) NOT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Telefone do cliente (apenas números)',
  `codigo` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Código de verificação de 6 dígitos',
  `usado` tinyint(1) DEFAULT '0' COMMENT 'Código foi usado (1=Sim, 0=Não)',
  `expirado` tinyint(1) DEFAULT '0' COMMENT 'Código expirado (1=Sim, 0=Não)',
  `tentativas` int(11) DEFAULT '0' COMMENT 'Número de tentativas de validação',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `usado_em` timestamp NULL DEFAULT NULL,
  `expira_em` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Códigos de verificação enviados via WhatsApp';

--
-- Despejando dados para a tabela `verificacao_codigos`
--

INSERT INTO `verificacao_codigos` (`id`, `telefone`, `codigo`, `usado`, `expirado`, `tentativas`, `criado_em`, `usado_em`, `expira_em`) VALUES
(3, '73998638624', '259678', 0, 1, 0, '2025-11-30 23:14:06', NULL, '2025-11-30 23:24:06'),
(4, '73998638624', '758747', 0, 1, 0, '2025-11-30 23:14:12', NULL, '2025-11-30 23:24:12'),
(5, '73998638624', '905896', 0, 1, 0, '2025-11-30 23:17:03', NULL, '2025-11-30 23:27:03'),
(6, '73998638624', '371228', 0, 1, 0, '2025-11-30 23:17:04', NULL, '2025-11-30 23:27:04'),
(7, '73998638624', '383137', 0, 1, 0, '2025-11-30 23:20:14', NULL, '2025-11-30 23:30:14'),
(8, '73998638624', '913721', 1, 0, 1, '2025-11-30 23:20:39', '2025-11-30 23:20:51', '2025-11-30 23:30:39'),
(9, '41988150812', '345082', 1, 0, 0, '2025-11-30 23:27:17', '2025-11-30 23:27:31', '2025-11-30 23:28:17'),
(10, '41988150812', '226998', 1, 0, 0, '2025-11-30 23:40:00', '2025-11-30 23:40:09', '2025-11-30 23:41:00'),
(11, '41988150812', '933787', 1, 0, 0, '2025-12-01 13:25:58', '2025-12-01 13:26:16', '2025-12-02 00:26:58'),
(12, '41988150812', '901488', 1, 0, 0, '2025-12-01 13:31:22', '2025-12-01 13:31:47', '2025-12-02 00:32:22'),
(13, '41984304401', '558063', 1, 0, 0, '2025-12-02 21:48:54', '2025-12-02 21:49:04', '2025-12-03 08:53:54'),
(14, '73991294235', '917399', 0, 1, 0, '2025-12-03 04:20:01', NULL, '2025-12-03 15:25:01'),
(15, '73991294235', '741968', 0, 1, 0, '2025-12-03 04:21:22', NULL, '2025-12-03 15:26:22'),
(16, '73991294235', '585851', 0, 0, 0, '2025-12-03 04:23:04', NULL, '2025-12-03 15:28:04');

-- --------------------------------------------------------

--
-- Estrutura para tabela `verificacao_config`
--

CREATE TABLE `verificacao_config` (
  `id` int(11) NOT NULL,
  `ativo` tinyint(1) DEFAULT '1' COMMENT 'Sistema de verificação ativo (1=Sim, 0=Não)',
  `tempo_expiracao` int(11) DEFAULT '10' COMMENT 'Tempo de expiração do código em minutos',
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `mensagem_codigo` text COLLATE utf8mb4_unicode_ci COMMENT 'Mensagem personalizada para envio do código (use {codigo} e {tempo} como variáveis)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configurações do sistema de verificação de primeiro pedido';

--
-- Despejando dados para a tabela `verificacao_config`
--

INSERT INTO `verificacao_config` (`id`, `ativo`, `tempo_expiracao`, `criado_em`, `atualizado_em`, `mensagem_codigo`) VALUES
(1, 1, 5, '2025-11-30 22:59:30', '2025-12-01 17:23:08', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `whatsapp_config`
--

CREATE TABLE `whatsapp_config` (
  `id` int(11) NOT NULL,
  `api_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL da Evolution API',
  `api_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Token Global da API',
  `instance_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nome da instância (ex: food_0917363)',
  `instance_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Chave da instância',
  `qrcode` text COLLATE utf8mb4_unicode_ci COMMENT 'QR Code base64',
  `status` enum('disconnected','connecting','connected','error') COLLATE utf8mb4_unicode_ci DEFAULT 'disconnected',
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Número conectado',
  `ativo` tinyint(1) DEFAULT '1' COMMENT 'Se está ativo',
  `enviar_comprovante` tinyint(1) DEFAULT '1' COMMENT 'Enviar comprovante ao finalizar pedido',
  `enviar_status` tinyint(1) DEFAULT '1' COMMENT 'Enviar mensagem ao mudar status',
  `enviar_link_acompanhamento` tinyint(1) DEFAULT '1' COMMENT 'Enviar link para acompanhar pedido',
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `sistema_entregadores_ativo` tinyint(1) DEFAULT '1' COMMENT 'Se sistema de atribuição está ativo',
  `popup_finalizacao_ativo` tinyint(1) DEFAULT '1',
  `whatsapp_estabelecimento` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usar_mercadopago` tinyint(1) DEFAULT '1' COMMENT 'Enviar mensagens do Mercado Pago via WhatsApp',
  `enviar_qrcode_whatsapp` tinyint(1) DEFAULT '1' COMMENT 'Enviar QR Code base64 junto com mensagem WhatsApp Mercado Pago',
  `webjs_api_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'http://127.0.0.1:4010',
  `webjs_admin_token` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `webjs_webhook_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usar_webjs_api` tinyint(1) NOT NULL DEFAULT '0',
  `webjs_enviar_comprovante` tinyint(1) NOT NULL DEFAULT '1',
  `webjs_enviar_status` tinyint(1) NOT NULL DEFAULT '1',
  `webjs_enviar_link_acompanhamento` tinyint(1) NOT NULL DEFAULT '1',
  `webjs_session_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `webjs_status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `webjs_last_qr` longtext COLLATE utf8mb4_unicode_ci,
  `webjs_atualizado_em` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `whatsapp_config`
--

INSERT INTO `whatsapp_config` (`id`, `api_url`, `api_token`, `instance_name`, `instance_key`, `qrcode`, `status`, `telefone`, `ativo`, `enviar_comprovante`, `enviar_status`, `enviar_link_acompanhamento`, `criado_em`, `atualizado_em`, `sistema_entregadores_ativo`, `popup_finalizacao_ativo`, `whatsapp_estabelecimento`, `usar_mercadopago`, `enviar_qrcode_whatsapp`, `webjs_api_url`, `webjs_admin_token`, `webjs_webhook_url`, `usar_webjs_api`, `webjs_enviar_comprovante`, `webjs_enviar_status`, `webjs_enviar_link_acompanhamento`, `webjs_session_uuid`, `webjs_status`, `webjs_last_qr`, `webjs_atualizado_em`) VALUES
(1, 'https://suaapi.com', 'seutokenaqui', 'instanciaapi', NULL, NULL, 'connected', NULL, 1, 0, 1, 1, '2025-10-30 12:10:01', '2025-12-04 09:13:20', 1, 1, 'NUMERO', 1, 0, NULL, NULL, NULL, 1, 1, 1, 1, NULL, 'connected', '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', '2025-11-13 08:23:38');

-- --------------------------------------------------------

--
-- Estrutura para tabela `whatsapp_logs`
--

CREATE TABLE `whatsapp_logs` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) DEFAULT NULL,
  `cliente_telefone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_mensagem` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensagem` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pendente','enviado','erro','lido') COLLATE utf8mb4_unicode_ci DEFAULT 'pendente',
  `resposta_api` text COLLATE utf8mb4_unicode_ci COMMENT 'Resposta da API Evolution',
  `erro` text COLLATE utf8mb4_unicode_ci,
  `enviado_em` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `whatsapp_logs`
--

INSERT INTO `whatsapp_logs` (`id`, `pedido_id`, `cliente_telefone`, `tipo_mensagem`, `mensagem`, `status`, `resposta_api`, `erro`, `enviado_em`) VALUES
(141, NULL, '5573988742045', 'comprovante_pedido', '🧾 *COMPROVANTE DO PEDIDO*\r\n\r\n📋 *Pedido:* #44072AD0\r\n📅 *Data:* 09/11/2025\r\n\r\n👤 *Cliente:* Amom\r\n📱 *Telefone:* (73) 98874-2045\r\n📍 *Endereço:* \r\n\r\n🛍️ *ITENS DO PEDIDO:*\r\n• 1x Beach Frango - R$ 33,00\n  └─ ➕ Água\n\r\n\r\n\n➕ *ADICIONAIS:*\n• Beach Frango: Água\n\r\n\r\n\r\n\r\n💰 *RESUMO DO PEDIDO*\r\n• Subtotal: R$ 33,00\r\n• Taxa de Entrega: R$ 0,00\r\n• *TOTAL: R$ 33,00*\r\n\r\n📦 *Tipo:* Retirada\r\n💳 *Pagamento:* PIX\r\n*Em caso de troco*: \r\n\r\nAcompanhe aqui: https://beachpoint.tupanara.com/api/cliente/pedidos.php?pedido=2&token=cafd0e13dae7dfd8fdb9860c24eca4f0\r\n\r\n✅ Pedido confirmado!\r\nObrigado por escolher nosso restaurante! 😊', 'enviado', NULL, NULL, '2025-11-09 00:48:04'),
(142, NULL, '5573988742045', 'pagamento_pix', '💳 *PAGAMENTO VIA PIX*\r\n\r\n🔑 *Chave PIX:* pixuhuu@gmail.com\r\n💰 *Valor:* R$ 33,00\r\n\r\n📋 *Pedido:* #44072AD0\r\n\r\n⚠️ *IMPORTANTE:*\r\nApós realizar o pagamento, envie o comprovante aqui mesmo no WhatsApp.\r\nSeu pedido só entrará em preparo após a confirmação do pagamento.\r\n\r\nObrigado! 🙏', 'enviado', NULL, NULL, '2025-11-09 00:48:06'),
(143, NULL, '5573988742045', 'confirmacao_pedido', '✅ *Pedido confirmado!*\n\nOlá, Amom! 👋\nRecebemos o seu pedido #05F5871E com sucesso.\n\n📦 *Itens*\n• 1x Beach Egg Burger - R$ 23,00\n\n💳 Forma de pagamento: Dinheiro\n💰 Total: 23,00\n\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-11-09 23:51:00'),
(144, NULL, '5573988742045', 'confirmacao_pedido', '✅ *Pedido confirmado!*\n\nOlá, Amom! 👋\nRecebemos o seu pedido #6E031427 com sucesso.\n\n📦 *Itens*\n• 1x Beach Egg Burger - R$ 33,00\n  └─ ➕ Água\n\n💳 Forma de pagamento: PIX\n💰 Total: 33,00\n\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-11-09 23:51:26'),
(145, NULL, '5573988742045', 'pagamento_pix', '💳 *PAGAMENTO VIA PIX*\r\n\r\n🔑 *Chave PIX:* pixuhuu@gmail.com\r\n💰 *Valor:* R$ 33,00\r\n\r\n📋 *Pedido:* #6E031427\r\n\r\n⚠️ *IMPORTANTE:*\r\nApós realizar o pagamento, envie o comprovante aqui mesmo no WhatsApp.\r\nSeu pedido só entrará em preparo após a confirmação do pagamento.\r\n\r\nObrigado! 🙏', 'enviado', NULL, NULL, '2025-11-09 23:51:27'),
(146, NULL, '5573988742045', 'pix_chave_manual', '🔑 *Chave PIX:* 73981433240', 'enviado', NULL, NULL, '2025-11-09 23:51:29'),
(147, NULL, '5573988742045', 'confirmacao_pedido', '✅ *Pedido confirmado!*\n\nOlá, Amom! 👋\nRecebemos o seu pedido #F4F9155E com sucesso.\n\n📦 *Itens*\n• 1x Beach Point - R$ 38,00\n  └─ ➕ Água\n\n💳 Forma de pagamento: PIX\n💰 Total: 38,00\n\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-11-09 23:56:15'),
(148, NULL, '5573988742045', 'pagamento_pix', '🔑 *Chave PIX:* pixuhuu@gmail.com\r\n💰 *Valor:* R$ 38,00\r\n', 'enviado', NULL, NULL, '2025-11-09 23:56:16'),
(149, NULL, '5573988742045', 'pix_chave_manual', '🔑 *Chave PIX:* 73981433240', 'enviado', NULL, NULL, '2025-11-09 23:56:18'),
(150, NULL, '5573988742045', 'confirmacao_pedido', '✅ *Pedido confirmado!*\n\nOlá, Amom! 👋\nRecebemos o seu pedido #DCFFF1A8 com sucesso.\n\n📦 *Itens*\n• 1x Beach Salada - R$ 28,00\n  └─ ➕ Água\n\n💳 Forma de pagamento: PIX\n💰 Total: 28,00\n\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-11-09 23:58:09'),
(151, NULL, '5573988742045', 'pagamento_pix', '🔑 *Chave PIX:* pixuhuu@gmail.com\r\n💰 *Valor:* R$ 28,00\r\n', 'enviado', NULL, NULL, '2025-11-09 23:58:11'),
(152, NULL, '5573988742045', 'confirmacao_pedido', '✅ *Pedido confirmado!*\r\n\r\nOlá, Amom! 👋\r\nRecebemos o seu pedido.\r\n\r\n💰 Total: 28,00\r\n\r\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-11-10 00:01:34'),
(153, NULL, '5573988742045', 'pagamento_pix', '🔑 *Chave PIX:* pixuhuu@gmail.com\r\n💰 *Valor:* R$ 28,00\r\n', 'enviado', NULL, NULL, '2025-11-10 00:01:35'),
(154, NULL, '5573988742045', 'status_saiu_entrega', '🛵 *Amom*, seu pedido acabou de sair para entrega!', 'enviado', NULL, NULL, '2025-11-10 00:04:23'),
(155, NULL, '5573988742045', 'status_saiu_entrega', '🛵 *Amom*, seu pedido acabou de sair para entrega!', 'enviado', NULL, NULL, '2025-11-10 00:07:06'),
(156, NULL, '5573988742045', 'confirmacao_pedido', '✅ *Pedido confirmado!*\r\n\r\nOlá, Amom! 👋\r\nRecebemos o seu pedido.\r\n\r\n💰 Total: 23,00\r\n\r\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-11-10 00:08:40'),
(157, NULL, '5573988742045', 'status_saiu_entrega', '🛵 *Amom*, seu pedido acabou de sair para entrega!', 'enviado', NULL, NULL, '2025-11-10 00:09:51'),
(158, NULL, '5573988742045', 'confirmacao_pedido', '✅ *Pedido confirmado!*\r\n\r\nOlá, Amom! 👋\r\nRecebemos o seu pedido.\r\n\r\n💰 Total: 28,00\r\n\r\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-11-10 00:12:04'),
(159, NULL, '5573988742045', 'confirmacao_pedido', '✅ *Pedido confirmado!*\r\n\r\nOlá, Amom! 👋\r\nRecebemos o seu pedido.\r\n\r\n💰 Total: 38,00\r\n\r\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-11-10 00:17:11'),
(160, NULL, '5573988742045', 'pagamento_pix', '🔑 *Chave PIX:* pixuhuu@gmail.com\r\n💰 *Valor:* R$ 38,00\r\n', 'enviado', NULL, NULL, '2025-11-10 00:17:13'),
(161, NULL, '5573988742045', 'status_saiu_entrega', '🛵 *Amom*, seu pedido acabou de sair para entrega!', 'enviado', NULL, NULL, '2025-11-10 00:19:26'),
(162, NULL, '5573988742045', 'confirmacao_pedido', '✅ *Pedido confirmado!*\r\n\r\nOlá, Amom! 👋\r\nRecebemos o seu pedido.\r\n\r\n💰 Total: 33,00\r\n\r\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-11-10 00:25:35'),
(163, NULL, '5573988742045', 'pagamento_pix', '🔑 *Chave PIX:* pixuhuu@gmail.com\r\n💰 *Valor:* R$ 33,00\r\n', 'enviado', NULL, NULL, '2025-11-10 00:25:37'),
(164, NULL, '5573988742045', 'confirmacao_pedido', '✅ *Pedido confirmado!*\r\n\r\nOlá, Amom! 👋\r\nRecebemos o seu pedido.\r\n\r\n💰 Total: 33,00\r\n\r\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-11-10 00:33:22'),
(165, NULL, '5573988742045', 'pagamento_pix', '🔑 *Chave PIX:* pixuhuu@gmail.com\r\n💰 *Valor:* R$ 33,00\r\n', 'enviado', NULL, NULL, '2025-11-10 00:33:23'),
(166, NULL, '5573988742045', 'status_saiu_entrega', '🛵 *Amom*, seu pedido acabou de sair para entrega!', 'enviado', NULL, NULL, '2025-11-10 00:36:38'),
(167, NULL, '5573988742045', 'status_saiu_entrega', '🛵 *Amom*, seu pedido acabou de sair para entrega!', 'enviado', NULL, NULL, '2025-11-10 00:37:33'),
(168, NULL, '5573988742045', 'status_saiu_entrega', '🛵 *Amom*, seu pedido acabou de sair para entrega!', 'enviado', NULL, NULL, '2025-11-10 00:37:49'),
(169, NULL, '5573988742045', 'status_saiu_entrega', '🛵 *Amom*, seu pedido acabou de sair para entrega!', 'enviado', NULL, NULL, '2025-11-10 00:38:07'),
(170, NULL, '5573988742045', 'confirmacao_pedido', '✅ *Pedido confirmado!*\r\n\r\nOlá, Amom! 👋\r\nRecebemos o seu pedido.\r\n\r\n💰 Total: 33,00\r\n\r\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-11-10 00:38:40'),
(171, NULL, '5573988742045', 'status_saiu_entrega', '🛵 *Amom*, seu pedido acabou de sair para entrega!', 'enviado', NULL, NULL, '2025-11-10 00:39:00'),
(172, NULL, '5573988742045', 'status_saiu_entrega', '🛵 *Amom*, seu pedido acabou de sair para entrega!', 'enviado', NULL, NULL, '2025-11-10 00:40:40'),
(173, NULL, '5541998608485', 'confirmacao_pedido', '✅ *Pedido confirmado!*\r\n\r\nOlá, Alexsandro Oliveira! 👋\r\nRecebemos o seu pedido.\r\n\r\n💰 Total: 46,00\r\n\r\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-11-11 18:33:05'),
(174, NULL, '5541998608485', 'pagamento_pix', '🔑 *Chave PIX:* pixuhuu@gmail.com\r\n💰 *Valor:* R$ 46,00\r\n', 'enviado', NULL, NULL, '2025-11-11 18:33:07'),
(175, NULL, '5541998608485', 'status_saiu_entrega', '🛵 *Alexsandro Oliveira*, seu pedido acabou de sair para entrega!', 'enviado', NULL, NULL, '2025-11-11 18:33:57'),
(176, NULL, '5573988742045', 'status_retirada_disponivel', 'Olá Amom! 😄\n\nSeu pedido 6B38C66A já está pronto e aguardando por você no balcão.\n\nMostre este código ao retirar e tenha uma ótima refeição!', 'enviado', NULL, NULL, '2025-11-13 09:26:40'),
(177, NULL, '5573988742045', 'aguardando_pagamento_mp', 'Olá, Amom! Recebemos o seu pedido e estamos aguardando o pagamento 😃.\n\nVocê tem 10 minutos para pagar o valor de R$ 28,00 usando o Pix Copia e Cola ou o QR Code abaixo. Após esse prazo, o pedido será cancelado automaticamente.', 'erro', NULL, NULL, '2025-11-13 09:29:58'),
(178, NULL, '5573988742045', 'pix_copia_cola_mp', 'PIX Copia e Cola', 'erro', NULL, NULL, '2025-11-13 09:30:00'),
(179, NULL, '5573998638624', 'confirmacao_pedido', '✅ *Pedido confirmado!*\r\n\r\nOlá, SANTOSPLW! 👋\r\nRecebemos o seu pedido.\r\n\r\n💰 Total: 56,00\r\n\r\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-11-30 20:20:56'),
(180, NULL, '5573998638624', 'pagamento_pix', '🔑 *Chave PIX:* pixuhuu@gmail.com\r\n💰 *Valor:* R$ 56,00\r\n', 'enviado', NULL, NULL, '2025-11-30 20:20:58'),
(181, NULL, '5541988150812', 'confirmacao_pedido', '✅ *Pedido confirmado!*\r\n\r\nOlá, Sandro Oliveira! 👋\r\nRecebemos o seu pedido.\r\n\r\n💰 Total: 28,00\r\n\r\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-11-30 20:40:16'),
(182, NULL, '5541988150812', 'pagamento_pix', '🔑 *Chave PIX:* pixuhuu@gmail.com\r\n💰 *Valor:* R$ 28,00\r\n', 'enviado', NULL, NULL, '2025-11-30 20:40:18'),
(183, NULL, '5541988150812', 'confirmacao_pedido', '✅ *Pedido confirmado!*\r\n\r\nOlá, ALEX! 👋\r\nRecebemos o seu pedido.\r\n\r\n💰 Total: 51,00\r\n\r\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-12-01 10:26:20'),
(184, NULL, '5541988150812', 'pagamento_pix', '🔑 *Chave PIX:* plw@plw.com\r\n💰 *Valor:* R$ 51,00\r\n', 'enviado', NULL, NULL, '2025-12-01 10:26:22'),
(185, NULL, '5541988150812', 'confirmacao_pedido', '✅ *Pedido confirmado!*\r\n\r\nOlá, ALEXSANDRO OLIVEIRA! 👋\r\nRecebemos o seu pedido.\r\n\r\n💰 Total: 63,00\r\n\r\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-12-01 10:31:59'),
(186, NULL, '5541988150812', 'pagamento_pix', '🔑 *Chave PIX:* plw@plw.com\r\n💰 *Valor:* R$ 63,00\r\n', 'enviado', NULL, NULL, '2025-12-01 10:32:01'),
(187, NULL, '5541988150812', 'status_retirada_disponivel', 'Olá ALEXSANDRO OLIVEIRA! 😄\n\nSeu pedido 829946D9 já está pronto e aguardando por você no balcão.\n\nMostre este código ao retirar e tenha uma ótima refeição!', 'enviado', NULL, NULL, '2025-12-01 13:53:39'),
(188, NULL, '5541988150812', 'confirmacao_pedido', '✅ *Pedido confirmado!*\r\n\r\nOlá, Sandro Oliveira! 👋\r\nRecebemos o seu pedido.\r\n\r\n💰 Total: 40,00\r\n\r\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-12-02 18:44:26'),
(189, NULL, '5541988150812', 'pagamento_pix', '🔑 *Chave PIX:* plw@plw.com\r\n💰 *Valor:* R$ 40,00\r\n', 'enviado', NULL, NULL, '2025-12-02 18:44:27'),
(190, NULL, '5541988150812', 'status_retirada_disponivel', 'Olá ALEX! 😄\n\nSeu pedido D5F6BB8F já está pronto e aguardando por você no balcão.\n\nMostre este código ao retirar e tenha uma ótima refeição!', 'enviado', NULL, NULL, '2025-12-02 18:45:56'),
(191, NULL, '5541988150812', 'status_saiu_entrega', '🛵 *Sandro Oliveira*, seu pedido acabou de sair para entrega!', 'enviado', NULL, NULL, '2025-12-02 18:46:14'),
(192, NULL, '5541984304401', 'confirmacao_pedido', '✅ *Pedido confirmado!*\r\n\r\nOlá, Jéssica Cristine De Lima Guranda De Oliveira! 👋\r\nRecebemos o seu pedido.\r\n\r\n💰 Total: 73,00\r\n\r\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-12-02 18:49:44'),
(193, NULL, '5541984304401', 'pagamento_pix', '🔑 *Chave PIX:* plw@plw.com\r\n💰 *Valor:* R$ 73,00\r\n', 'enviado', NULL, NULL, '2025-12-02 18:49:46'),
(194, NULL, '5541984304401', 'status_saiu_entrega', '🛵 *Jéssica Cristine De Lima Guranda De Oliveira*, seu pedido acabou de sair para entrega!', 'enviado', NULL, NULL, '2025-12-02 18:50:59'),
(195, NULL, '5541988150812', 'confirmacao_pedido', '✅ *Pedido confirmado!*\r\n\r\nOlá, Sandro Oliveira! 👋\r\nRecebemos o seu pedido.\r\n\r\n💰 Total: 28,00\r\n\r\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-12-03 10:35:15'),
(196, NULL, '5541988150812', 'pagamento_pix', '🔑 *Chave PIX:* plw@plw.com\r\n💰 *Valor:* R$ 28,00\r\n', 'enviado', NULL, NULL, '2025-12-03 10:35:17'),
(197, NULL, '5541988150812', 'confirmacao_pedido', '✅ *Pedido confirmado!*\r\n\r\nOlá, Sandro Oliveira! 👋\r\nRecebemos o seu pedido.\r\n\r\n💰 Total: 38,00\r\n\r\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-12-03 10:37:28'),
(198, NULL, '5541988150812', 'pagamento_pix', '🔑 *Chave PIX:* plw@plw.com\r\n💰 *Valor:* R$ 38,00\r\n', 'enviado', NULL, NULL, '2025-12-03 10:37:30'),
(199, NULL, '5541988150812', 'confirmacao_pedido', '✅ *Pedido confirmado!*\r\n\r\nOlá, ALEX! 👋\r\nRecebemos o seu pedido.\r\n\r\n💰 Total: 35,00\r\n\r\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-12-03 10:46:41'),
(200, NULL, '5541988150812', 'pagamento_pix', '🔑 *Chave PIX:* plw@plw.com\r\n💰 *Valor:* R$ 35,00\r\n', 'enviado', NULL, NULL, '2025-12-03 10:46:43'),
(201, NULL, '5541988150812', 'confirmacao_pedido', '✅ *Pedido confirmado!*\r\n\r\nOlá, ALEX! 👋\r\nRecebemos o seu pedido.\r\n\r\n💰 Total: 30,00\r\n\r\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-12-03 10:49:34'),
(202, NULL, '5541988150812', 'pagamento_pix', '🔑 *Chave PIX:* plw@plw.com\r\n💰 *Valor:* R$ 30,00\r\n', 'enviado', NULL, NULL, '2025-12-03 10:49:36'),
(203, NULL, '5541988150812', 'confirmacao_pedido', '✅ *Pedido confirmado!*\r\n\r\nOlá, ALEX! 👋\r\nRecebemos o seu pedido.\r\n\r\n💰 Total: 30,00\r\n\r\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 'enviado', NULL, NULL, '2025-12-03 10:51:16'),
(204, NULL, '5541988150812', 'pagamento_pix', '🔑 *Chave PIX:* plw@plw.com\r\n💰 *Valor:* R$ 30,00\r\n', 'enviado', NULL, NULL, '2025-12-03 10:51:18');

-- --------------------------------------------------------

--
-- Estrutura para tabela `whatsapp_mensagens`
--

CREATE TABLE `whatsapp_mensagens` (
  `id` int(11) NOT NULL,
  `tipo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'comprovante, pagamento_pix, pagamento_dinheiro, status_pendente, etc',
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensagem` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Template com variáveis: {nome}, {telefone}, {endereco}, etc',
  `ativo` tinyint(1) DEFAULT '1',
  `ordem` int(11) DEFAULT '0',
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `whatsapp_mensagens`
--

INSERT INTO `whatsapp_mensagens` (`id`, `tipo`, `titulo`, `mensagem`, `ativo`, `ordem`, `criado_em`, `atualizado_em`) VALUES
(1, 'comprovante_pedido', 'Comprovante do Pedido', '🧾 PEDIDO RECEBIDO!\r\n\r\n📋 Pedido: #{codigo_pedido}\r\n💰 Valor Total: R$ {total}\r\n\r\nAcompanhe seu pedido aqui: {link_acompanhamento}\r\n\r\nObrigado pela preferência! 😊', 1, 1, '2025-10-30 12:13:23', '2025-12-03 10:50:52'),
(2, 'pagamento_pix', 'Pagamento PIX', '🔑 *Chave PIX:* {chave_pix}\r\n💰 *Valor:* R$ {total}\r\n', 1, 2, '2025-10-30 12:13:23', '2025-11-09 23:55:55'),
(3, 'pagamento_dinheiro', 'Pagamento em Dinheiro', '💵 *PAGAMENTO EM DINHEIRO*\r\n\r\n💰 *Valor Total:* R$ {total}\r\n💸 *Troco para:* R$ {troco_para}\r\n🔄 *Troco:* R$ {troco}\r\n\r\n📋 *Pedido:* #{codigo_pedido}\r\n\r\nO entregador levará seu pedido e o troco! 🏍️', 0, 3, '2025-10-30 12:13:23', '2025-10-31 08:27:17'),
(4, 'pagamento_cartao', 'Pagamento no Cartão', '💳 *PAGAMENTO NO CARTÃO*\r\n\r\n💰 *Valor Total:* R$ {total}\r\n💳 *Forma:* {forma_pagamento}\r\n\r\n📋 *Pedido:* #{codigo_pedido}\r\n\r\nO entregador levará a maquininha para pagamento! 🏍️', 0, 4, '2025-10-30 12:13:23', '2025-10-30 15:10:02'),
(5, 'status_pendente', 'Status: Pendente', '⏳ *PEDIDO RECEBIDO*\r\n\r\n📋 *Pedido:* #{codigo_pedido}\r\n\r\nSeu pedido foi recebido e está aguardando confirmação.\r\n\r\nEm breve você receberá atualizações! ⏰', 0, 5, '2025-10-30 12:13:23', '2025-10-30 15:09:53'),
(6, 'status_em_andamento', 'Status: Em Preparo', '👨‍🍳 *PEDIDO EM PREPARO*\r\n\r\n📋 *Pedido:* #{codigo_pedido}\r\n\r\nSua refeição está sendo preparada com muito carinho!\r\n\r\n⏱️ Tempo estimado: {tempo_preparo} minutos\r\n\r\nAguarde mais um pouco! 😋', 0, 6, '2025-10-30 12:13:23', '2025-10-30 15:09:48'),
(7, 'status_pronto', 'Status: Pronto', '✅ *PEDIDO PRONTO*\r\n\r\n📋 *Pedido:* #{codigo_pedido}\r\n\r\n{mensagem_pronto}\r\n\r\nObrigado pela preferência! 🎉', 0, 7, '2025-10-30 12:13:23', '2025-10-30 15:09:42'),
(8, 'status_saiu_entrega', 'Status: Saiu para Entrega', '🛵 *{nome}*, seu pedido acabou de sair para entrega!', 1, 8, '2025-10-30 12:13:23', '2025-11-04 22:45:35'),
(9, 'status_concluido', 'Status: Entregue', '🎉 *PEDIDO ENTREGUE*\r\n\r\n📋 *Pedido:* #{codigo_pedido}\r\n\r\nEsperamos que tenha gostado!\r\n\r\nSua opinião é muito importante para nós.\r\nAvalie nosso atendimento! ⭐⭐⭐⭐⭐\r\n\r\nVolte sempre! 😊', 0, 9, '2025-10-30 12:13:23', '2025-11-04 22:45:47'),
(10, 'status_cancelado', 'Status: Cancelado', '❌ *PEDIDO CANCELADO*\n\n📋 *Pedido:* #{codigo_pedido}\n\nSeu pedido foi cancelado.\n{motivo_cancelamento}\n\nQualquer dúvida, entre em contato conosco.\n\nAté breve! 🙏', 1, 10, '2025-10-30 12:13:23', NULL),
(11, 'link_acompanhamento', 'Link Acompanhamento', '📱 *ACOMPANHE SEU PEDIDO*\r\n\r\n📋 *Pedido:* #{codigo_pedido}\r\n\r\n🔗 Clique no link abaixo para acompanhar em tempo real:\r\n{link_acompanhamento}\r\n\r\nVocê receberá notificações a cada mudança de status! 🔔', 0, 11, '2025-10-30 12:13:23', '2025-10-31 08:27:26'),
(45, 'status_cancelado_pix_expirado', 'Cancelado: PIX Expirado', '❌ *PEDIDO CANCELADO POR FALTA DE PAGAMENTO*\n\n📋 *Pedido:* #{codigo_pedido}\n\n{motivo_cancelamento}\n\nSe ainda desejar, faça um novo pedido quando estiver pronto. Estamos à disposição! 😊', 1, 15, '2025-10-31 12:44:49', NULL),
(46, 'confirmacao_pedido', 'Confirmação do Pedido', '✅ *Pedido confirmado!*\r\n\r\nOlá, {nome}! 👋\r\nRecebemos o seu pedido.\r\n\r\n💰 Total: {total}\r\n\r\nAssim que estiver em preparo avisaremos por aqui. Obrigado pela preferência! 🙏', 1, 5, '2025-11-09 23:49:48', '2025-11-10 00:01:10');

-- --------------------------------------------------------

--
-- Estrutura para tabela `whatsapp_qrcodes`
--

CREATE TABLE `whatsapp_qrcodes` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL COMMENT 'Nome/Descrição do link',
  `numero` varchar(20) NOT NULL COMMENT 'Número do WhatsApp (apenas dígitos)',
  `mensagem_padrao` text COMMENT 'Mensagem padrão pre-preenchida',
  `link_whatsapp` text NOT NULL COMMENT 'Link completo do WhatsApp',
  `qrcode_path` varchar(255) DEFAULT NULL,
  `qrcode_base64` longtext,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Links do WhatsApp com QR Code';

-- --------------------------------------------------------

--
-- Estrutura para tabela `whatsapp_web_events`
--

CREATE TABLE `whatsapp_web_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `session_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `whatsapp_web_events`
--

INSERT INTO `whatsapp_web_events` (`id`, `session_uuid`, `event_type`, `payload`, `created_at`) VALUES
(76, '22414dd3-82ff-4c6b-8736-4b247e498dc5', 'qrcode', '{\"qr\": \"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\"}', '2025-11-13 11:22:50'),
(81, '22414dd3-82ff-4c6b-8736-4b247e498dc5', 'qrcode', '{\"qr\": \"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\"}', '2025-11-13 11:23:13'),
(84, '22414dd3-82ff-4c6b-8736-4b247e498dc5', 'status', '{\"status\": \"connected\"}', '2025-11-13 11:23:38'),
(97, '4ba1da4d-69e8-4ac7-96a2-844a7d60d981', 'qrcode', '{\"qr\": \"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\"}', '2025-11-13 11:27:20'),
(98, '4ba1da4d-69e8-4ac7-96a2-844a7d60d981', 'qrcode', '{\"qr\": \"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\"}', '2025-11-13 11:27:40'),
(99, 'f2587875-e6e9-4ea6-8119-63745b333537', 'qrcode', '{\"qr\": \"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\"}', '2025-11-13 11:27:52'),
(100, '4ba1da4d-69e8-4ac7-96a2-844a7d60d981', 'qrcode', '{\"qr\": \"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\"}', '2025-11-13 11:28:00'),
(101, 'f2587875-e6e9-4ea6-8119-63745b333537', 'qrcode', '{\"qr\": \"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tda65GGttS55WGutS+wPLlKZKt5QmSpOVL6omFSmihOVk4pJ5aRiUpkqJpWpYlI5qThROak4UXmj4g2VqWJS+aLiDZWp4g2VqeINlTcqbnpYa61LHtZa65KHtda6xP7gL1L5TRWTyknFpDJVnKi8UTGpvFFxonJSMancVDGpTBWTyknFicobFZPKScWJylRxojJVnKhMFW+oTBWTylTxxcNaa13ysNZalzystdYlP1ymclIxqUwVN1WcqHxRMamcqLxRMam8UTGp/CaVqWJSmSpOVE4qTlTeqDhReUPlROUNlZOKqeJvelhrrUse1lrrkoe11rrE/uAXqdxUcaIyVbyh8kbFTSonFZPKVDGpvFFxojJVnKhMFScqU8WJyknFicpU8YbKVPGGyhsVk8pUMam8UfHFw1prXfKw1lqXPKy11iX2Bx+ovFExqUwVN6mcVEwqU8WJyhcVk8pUMalMFZPKScWJyknFpDJVvKFyUvGGyhcVk8pU8YXKVHGi8jdVfPGw1lqXPKy11iUPa611if3BBypfVJyoTBWTylRxojJV/Esqb1RMKlPFpDJVTCp/U8Wk8kbFicpUMamcVLyhclIxqfymikllqphUpoovHtZa65KHtda65GGttS754aOK31TxhspUcaIyVUwqU8WkMlVMKlPFVPE3qZxUvKFyU8WJylRxojJVnKj8popJZap4Q2VSmSomlanipoe11rrkYa21LnlYa61LfvhI5W+qOKmYVKaKSeWk4ouKE5U3KqaKSeWkYlI5UZkqTireqJhUpooTlaniRGWqOKmYVE4qTlTeUJkqTipOKiaVqeKLh7XWuuRhrbUueVhrrUt+uKziJpU3VE5UpopJ5aTiROWLiknljYoTlTcqvlA5qThROamYVL6oOKk4UZkqvqh4Q2WqOKm46WGttS55WGutSx7WWuuSH36ZyhsVX1RMKicqb6icVEwqU8UbFZPKVDGpTBVvqNxUMalMKm9UTConFV+ovFHxhcpvUjmp+OJhrbUueVhrrUse1lrrkh/+n1F5o2JSmSomlanipGJSmSomlZOKSeWNihOVL1T+S1SmiknljYpJZVI5qZhUTireUJlUpopJ5aaHtda65GGttS55WGutS374H1cxqUwVX6i8oXJScVIxqZxUnKjcVDGpnFRMKicVX1ScqHyhMlV8UfGGyknFpDKp/KaHtda65GGttS55WGutS374ZRV/U8WkMlW8UfFGxYnKVHFScaJyUnGi8ptUpoo3VE4qJpUvKiaVm1SmihOVqWJSmVT+pYe11rrkYa21LnlYa61LfrhM5W9SeUPlDZWp4kRlqpgq/iaVqeKk4jep3KRyUjGpnKhMFScqU8UbKn9TxaRy08Naa13ysNZalzystdYl9gdrrXXBw1prXfKw1lqXPKy11iUPa611ycNaa13ysNZalzystdYlD2utdcnDWmtd8rDWWpc8rLXWJQ9rrXXJw1prXfKw1lqXPKy11iX/BxUAYOejSId2AAAAAElFTkSuQmCC\"}', '2025-11-13 11:28:12');
INSERT INTO `whatsapp_web_events` (`id`, `session_uuid`, `event_type`, `payload`, `created_at`) VALUES
(102, 'f2587875-e6e9-4ea6-8119-63745b333537', 'qrcode', '{\"qr\": \"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\"}', '2025-11-13 11:28:32'),
(103, 'f2587875-e6e9-4ea6-8119-63745b333537', 'qrcode', '{\"qr\": \"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\"}', '2025-11-13 11:28:52'),
(104, '22414dd3-82ff-4c6b-8736-4b247e498dc5', 'message', '{\"id\": {\"id\": \"ACEE4CA631F885F04B1E7D1F0904063B\", \"fromMe\": false, \"remote\": \"status@broadcast\", \"_serialized\": \"false_status@broadcast_ACEE4CA631F885F04B1E7D1F0904063B_557186599490@c.us\", \"participant\": \"557186599490@c.us\"}, \"to\": \"557398638624@c.us\", \"ack\": 1, \"body\": \"Meu Deus uma saudade vó do interior da senhora minha negra virada\", \"from\": \"status@broadcast\", \"type\": \"video\", \"_data\": {\"t\": 1763033334, \"id\": {\"id\": \"ACEE4CA631F885F04B1E7D1F0904063B\", \"fromMe\": false, \"remote\": \"status@broadcast\", \"_serialized\": \"false_status@broadcast_ACEE4CA631F885F04B1E7D1F0904063B_557186599490@c.us\", \"participant\": \"557186599490@c.us\"}, \"to\": \"557398638624@c.us\", \"ack\": 1, \"body\": \"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\", \"from\": \"status@broadcast\", \"size\": 11377986, \"star\": false, \"type\": \"video\", \"invis\": false, \"links\": [], \"width\": 480, \"author\": \"557186599490@c.us\", \"height\": 864, \"viewed\": false, \"caption\": \"Meu Deus uma saudade vó do interior da senhora minha negra virada\", \"duration\": \"64\", \"filehash\": \"iJpFJVlMlg2DF3Q3Kc/4WM0Yw+urMXp+5T4yPpvyZDE=\", \"isAvatar\": false, \"isNewMsg\": true, \"mediaKey\": \"x353JWCy+8AoR2EVlOyNMN3T2VfGAyVJJ89gq89myWE=\", \"mimetype\": \"video/mp4\", \"viewMode\": \"VISIBLE\", \"recvFresh\": true, \"staticUrl\": \"\", \"bizBotType\": null, \"directPath\": \"/v/t62.7161-24/583236322_4349358651965071_3497663774691240144_n.enc?ccb=11-4&oh=01_Q5Aa3AEnRzNnTD56u6LvuUftGfYTXYXEdt6TcGYjnq66PbrcZA&oe=693D23F4&_nc_sid=5e03e0\", \"isAdsMedia\": false, \"isCallLink\": null, \"isViewOnce\": false, \"notifyName\": \"máquina Bolt\", \"callCreator\": null, \"encFilehash\": \"f0KhmuQCmqWckQuslopaG0801FoSkhL9JQNYRMMa0Gw=\", \"hasReaction\": false, \"isVideoCall\": false, \"kicNotified\": false, \"parentMsgId\": null, \"callDuration\": null, \"botPluginType\": null, \"callLinkToken\": null, \"groupMentions\": [], \"invokedBotWid\": null, \"messageSecret\": [101, 184, 145, 222, 34, 154, 184, 145, 162, 41, 90, 37, 254, 47, 88, 56, 238, 30, 231, 58, 159, 90, 224, 101, 107, 225, 195, 124, 64, 28, 98, 232], \"stickerSentTs\": 0, \"botMsgBodyType\": null, \"isCarouselCard\": false, \"isFromTemplate\": false, \"isMdHistoryMsg\": false, \"isEventCanceled\": false, \"pollInvalidated\": false, \"statusMentioned\": false, \"thumbnailSha256\": \"YILU7c9t1gkPUZyaqtKWbUHOVEz9L7b209G/mp/Cv9Y=\", \"callParticipants\": null, \"eventInvalidated\": false, \"latestEditMsgKey\": null, \"mentionedJidList\": [], \"streamingSidecar\": [], \"callSilenceReason\": null, \"deprecatedMms3Url\": \"https://mmg.whatsapp.net/v/t62.7161-24/583236322_4349358651965071_3497663774691240144_n.enc?ccb=11-4&oh=01_Q5Aa3AEnRzNnTD56u6LvuUftGfYTXYXEdt6TcGYjnq66PbrcZA&oe=693D23F4&_nc_sid=5e03e0&mms3=true\", \"mediaKeyTimestamp\": 1763033322, \"botPluginSearchUrl\": null, \"reportingTokenInfo\": {\"version\": 2, \"reportingTag\": [1, 12, 97, 175, 55, 85, 135, 144, 168, 246, 125, 234, 92, 44, 230, 234, 162, 227, 119, 143], \"reportingToken\": [231, 67, 174, 100, 157, 69, 119, 136, 33, 124, 38, 38, 192, 13, 41, 114]}, \"thumbnailEncSha256\": \"iO/KNoyTybEulbTRyRLiGqMquJUmvxqbprn3J5Wu3sY=\", \"botResponseTargetId\": null, \"thumbnailDirectPath\": \"/v/t62.36147-24/582478348_1397211795171565_3360324782111885677_n.enc?ccb=11-4&oh=01_Q5Aa3AEbkaM620KUwP68daPKVHR6R8lGLXaO_LoFzMcql1lf0g&oe=693D229B&_nc_sid=5e03e0\", \"botPluginMaybeParent\": false, \"botPluginSearchQuery\": null, \"lastPlaybackProgress\": 0, \"isSentCagPollCreation\": false, \"clientReceivedTsMillis\": 1763033333286, \"interactiveAnnotations\": [], \"isVcardOverMmsDocument\": false, \"lastUpdateFromServerTs\": 0, \"questionResponsesCount\": 0, \"botPluginReferenceIndex\": null, \"botPluginSearchProvider\": null, \"botMessageDisclaimerText\": null, \"isDynamicReplyButtonsMsg\": false, \"requiresDirectConnection\": null, \"bizContentPlaceholderType\": null, \"hostedBizEncStateMismatch\": false, \"groupHistoryBundleMetadata\": null, \"productHeaderImageRejected\": false, \"questionReplyQuotedMessage\": null, \"readQuestionResponsesCount\": 0, \"latestEditSenderTimestampMs\": null, \"botReelPluginThumbnailCdnUrl\": null, \"groupHistoryBundleMessageKey\": null, \"senderOrRecipientAccountTypeHosted\": false, \"placeholderCreatedWhenAccountIsHosted\": false}, \"isGif\": false, \"links\": [], \"author\": \"557186599490@c.us\", \"fromMe\": false, \"vCards\": [], \"duration\": \"64\", \"hasMedia\": true, \"isStatus\": true, \"mediaKey\": \"x353JWCy+8AoR2EVlOyNMN3T2VfGAyVJJ89gq89myWE=\", \"isStarred\": false, \"timestamp\": 1763033334, \"deviceType\": \"android\", \"hasReaction\": false, \"hasQuotedMsg\": false, \"mentionedIds\": [], \"groupMentions\": [], \"forwardingScore\": 0}', '2025-11-13 11:28:54'),
(105, '4ba1da4d-69e8-4ac7-96a2-844a7d60d981', 'qrcode', '{\"qr\": \"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\"}', '2025-11-13 11:29:11'),
(106, 'f2587875-e6e9-4ea6-8119-63745b333537', 'qrcode', '{\"qr\": \"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\"}', '2025-11-13 11:29:12'),
(107, '4ba1da4d-69e8-4ac7-96a2-844a7d60d981', 'qrcode', '{\"qr\": \"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Kl9UTCo3VUwqU8WkclLxhspvqphUpoovVKaKSWWqmFSmiknlpOILlZOKE5WpYlL5omJSOamYVE4qJpWTii8e1lrrkoe11rrkYa21LrE/+EBlqnhDZao4Ufmi4g2Vk4oTlZOKSeWkYlKZKiaVqWJS+aLiC5XfVDGp3FRxovJGxaRyUvGGylRx08Naa13ysNZalzystdYl9gcfqLxR8YbKVPGGym+qmFROKn6TylTxhcpUMan8popJZaqYVN6oOFGZKiaVqeJE5W+q+Jse1lrrkoe11rrkYa21LrE/+ItUTireUPmbKk5UflPFpDJVTCo3VUwqU8UbKicVb6h8UfGFylTxX6IyVdz0sNZalzystdYlD2utdYn9wS9SuaniC5WpYlKZKiaVqeJE5aTiC5UvKm5SeaPiROWLiknlpOImlaniROWNihOVNyq+eFhrrUse1lrrkoe11rrkh49U3qiYVKaKN1SmiknlpooTlaliUplUpopJ5aRiUpkqJpVJZaqYVKaKv6liUjmpOKk4UZkqblL5ouKNikllqrjpYa21LnlYa61LHtZa6xL7gw9Uvqg4UTmpmFROKiaVqeImlaniDZWTii9UbqqYVL6ouEllqphUvqg4UflNFZPKVPGbHtZa65KHtda65GGttS6xP/g/TGWqmFROKiaVk4pJZao4UfmiYlKZKiaVqWJSmSreUJkqvlCZKiaVqWJSOamYVE4qJpU3KiaVk4o3VE4q/qaHtda65GGttS55WGutS374SOVvqpgqJpWp4kTlDZUTlS8qJpWTikllqvhCZao4UTmpmFSmikllqripYlKZVKaKSWWqOKmYVE5Upoo3VN6o+OJhrbUueVhrrUse1lrrkh8uq7hJ5UTli4pJZaqYVKaKSeWNiknlROVvqvii4qTiC5Wp4kRlqpgqTlSmiknlpoo3Kk5UpoqbHtZa65KHtda65GGttS754ZepvFHxRcUbKm9UvFExqUwqU8WJylQxqZyonKh8UTGpvFFxojJVnKjcVDGpTBUnKicqX6i8oTJVfPGw1lqXPKy11iUPa611yQ//n6uYVE5U3lCZKiaVSeWkYlI5UTmpOFE5qTipmFTeqHhD5V9SmSqmiknlpOJE5aTiROWmh7XWuuRhrbUueVhrrUt++P+cyonKScWkcqLymyq+UJkq3lCZKt5QOamYVN6ouKliUjlRmSomlUnlpGJSmVSmit/0sNZalzystdYlD2utdckPv6ziN1X8SypTxRsqU8UbFZPKVDGpnFRMKicVJypfVEwqJxUnKl9UfFExqUwVk8qJylTxLz2stdYlD2utdcnDWmtd8sNlKn+TyknFpDJVnKhMFZPKicpJxaTyRcVJxYnKVDGpnFScqJxUnFScqJxUfKHyhspUcaIyVXyh8jc9rLXWJQ9rrXXJw1prXWJ/sNZaFzystdYlD2utdcnDWmtd8rDWWpc8rLXWJQ9rrXXJw1prXfKw1lqXPKy11iUPa611ycNaa13ysNZalzystdYlD2utdcnDWmtd8v8AUQOelF4yDRYAAAAASUVORK5CYII=\"}', '2025-11-13 11:30:11');
INSERT INTO `whatsapp_web_events` (`id`, `session_uuid`, `event_type`, `payload`, `created_at`) VALUES
(108, 'f2587875-e6e9-4ea6-8119-63745b333537', 'qrcode', '{\"qr\": \"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\"}', '2025-11-13 11:30:23'),
(109, '4ba1da4d-69e8-4ac7-96a2-844a7d60d981', 'qrcode', '{\"qr\": \"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\"}', '2025-11-13 11:30:31'),
(110, '4ba1da4d-69e8-4ac7-96a2-844a7d60d981', 'qrcode', '{\"qr\": \"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\"}', '2025-11-13 11:30:51'),
(111, '4ba1da4d-69e8-4ac7-96a2-844a7d60d981', 'qrcode', '{\"qr\": \"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\"}', '2025-11-13 11:31:11'),
(112, 'f2587875-e6e9-4ea6-8119-63745b333537', 'qrcode', '{\"qr\": \"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\"}', '2025-11-13 11:31:23'),
(113, '4ba1da4d-69e8-4ac7-96a2-844a7d60d981', 'qrcode', '{\"qr\": \"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\"}', '2025-11-13 11:31:31'),
(114, 'f2587875-e6e9-4ea6-8119-63745b333537', 'qrcode', '{\"qr\": \"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lROVNyomlZse1lrrkoe11rrkYa21LrE/+EUqU8WJylRxojJV3KQyVUwqJxWTylQxqZxUTConFScqU8UbKicVX6hMFZPKVHGiMlVMKlPFpDJVnKj8yypuelhrrUse1lrrkoe11rrkh8tUpoovVH6TylQxVZxUTCpfVHxRMalMFScqb1ScqPxNKicVk8pUMalMFZPKGxUnKlPFicpUcaIyqUwVXzystdYlD2utdcnDWmtdYn/wP0RlqjhRmSq+UDmpmFROKr5QmSomlaniDZU3Kk5UpooTlaniROWLijdUpopJ5aTiROU3Vdz0sNZalzystdYlD2utdckPf5nKVHGicpPKScWk8kXFicpJxaRyonKiMlVMKlPFicqkMlWcqEwVJyonFW+ofFExqXyhMlVMKicVk8pUMalMFV88rLXWJQ9rrXXJw1prXWJ/8IHKVDGpTBWTyknFTSp/U8WkclIxqUwVk8pUMancVHGiMlW8oTJVTCpTxaQyVUwqU8WJylTxhspJxaQyVUwqU8WJylTxmx7WWuuSh7XWuuRhrbUusT/4QOWkYlL5l1VMKm9UTConFZPK/ycVk8pJxaQyVbyh8kbFGypTxYnKf6liUpkqvnhYa61LHtZa65KHtda65IfLKiaVqWJSmSreUJkqvlB5o2JS+aLiDZWpYlJ5o+INlROVN1SmihOVqeKNiknljYqbKt5QmSreqLjpYa21LnlYa61LHtZa65IfPqqYVE5U3lCZKk5UvqiYVN6oOFE5UTmpOFGZKiaVN1SmipOKL1Qmlb+p4jdVTConKlPFicpJxaQyVXzxsNZalzystdYlD2utdckPl1VMKlPFpHJScVPFpHJSMamcqJxUnFRMKpPKGypfVPxLKiaVSeUNlZOKSWWqmFROKt6o+KLipOKmh7XWuuRhrbUueVhrrUvsDz5QmSomlaliUvkvVfyXVE4qJpWpYlKZKk5UflPFicpU8YbKVPGFyv+yiknljYovHtZa65KHtda65GGttS754aOKSWWqmFSmiptUTiomlZOKSWWqmFSmikllqnijYlJ5Q2WqmFTeqJhUJpWTihOVN1ROKr6omFTeqDhR+U0Vv+lhrbUueVhrrUse1lrrkh/+MSonFTdVvFFxUnFSMan8JpU3KiaVLypOVE4qJpWpYlKZKiaVk4pJ5TepTBWTylRxovKGylTxxcNa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\"}', '2025-11-13 11:31:43');

-- --------------------------------------------------------

--
-- Estrutura para tabela `whatsapp_web_instances`
--

CREATE TABLE `whatsapp_web_instances` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','qrcode','connected','disconnected','error') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `last_qr_code` longtext COLLATE utf8mb4_unicode_ci,
  `last_qr_at` datetime DEFAULT NULL,
  `webhook_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `connected_at` datetime DEFAULT NULL,
  `disconnected_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `whatsapp_web_instances`
--

INSERT INTO `whatsapp_web_instances` (`id`, `session_uuid`, `session_name`, `display_name`, `status`, `last_qr_code`, `last_qr_at`, `webhook_url`, `metadata`, `error_message`, `connected_at`, `disconnected_at`, `created_at`, `updated_at`) VALUES
(11, '22414dd3-82ff-4c6b-8736-4b247e498dc5', 'webjs_22414dd3', NULL, 'connected', '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', '2025-11-13 11:23:13', 'https://cardapio.agendifo.com/api/whatsapp-webhook.php', '{\"createdBy\": \"admin\"}', NULL, '2025-11-13 11:23:38', NULL, '2025-11-13 11:22:38', '2025-11-13 11:27:03'),
(14, '4ba1da4d-69e8-4ac7-96a2-844a7d60d981', 'webjs_4ba1da4d', NULL, 'qrcode', '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', '2025-11-13 11:31:31', NULL, NULL, NULL, NULL, NULL, '2025-11-13 11:27:20', '2025-11-13 11:31:31'),
(15, 'f2587875-e6e9-4ea6-8119-63745b333537', 'webjs_f2587875', NULL, 'qrcode', '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lROVNyomlZse1lrrkoe11rrkYa21LrE/+EUqU8WJylRxojJV3KQyVUwqJxWTylQxqZxUTConFScqU8UbKicVX6hMFZPKVHGiMlVMKlPFpDJVnKj8yypuelhrrUse1lrrkoe11rrkh8tUpoovVH6TylQxVZxUTCpfVHxRMalMFScqb1ScqPxNKicVk8pUMalMFZPKGxUnKlPFicpUcaIyqUwVXzystdYlD2utdcnDWmtdYn/wP0RlqjhRmSq+UDmpmFROKr5QmSomlaniDZU3Kk5UpooTlaniROWLijdUpopJ5aTiROU3Vdz0sNZalzystdYlD2utdckPf5nKVHGicpPKScWk8kXFicpJxaRyonKiMlVMKlPFicqkMlWcqEwVJyonFW+ofFExqXyhMlVMKicVk8pUMalMFV88rLXWJQ9rrXXJw1prXWJ/8IHKVDGpTBWTyknFTSp/U8WkclIxqUwVk8pUMancVHGiMlW8oTJVTCpTxaQyVUwqU8WJylTxhspJxaQyVUwqU8WJylTxmx7WWuuSh7XWuuRhrbUusT/4QOWkYlL5l1VMKm9UTConFZPK/ycVk8pJxaQyVbyh8kbFGypTxYnKf6liUpkqvnhYa61LHtZa65KHtda65IfLKiaVqWJSmSreUJkqvlB5o2JS+aLiDZWpYlJ5o+INlROVN1SmihOVqeKNiknljYqbKt5QmSreqLjpYa21LnlYa61LHtZa65IfPqqYVE5U3lCZKk5UvqiYVN6oOFE5UTmpOFGZKiaVN1SmipOKL1Qmlb+p4jdVTConKlPFicpJxaQyVXzxsNZalzystdYlD2utdckPl1VMKlPFpHJScVPFpHJSMamcqJxUnFRMKpPKGypfVPxLKiaVSeUNlZOKSWWqmFROKt6o+KLipOKmh7XWuuRhrbUueVhrrUvsDz5QmSomlaliUvkvVfyXVE4qJpWpYlKZKk5UflPFicpU8YbKVPGFyv+yiknljYovHtZa65KHtda65GGttS754aOKSWWqmFSmiptUTiomlZOKSWWqmFSmikllqnijYlJ5Q2WqmFTeqJhUJpWTihOVN1ROKr6omFTeqDhR+U0Vv+lhrbUueVhrrUse1lrrkh/+MSonFTdVvFFxUnFSMan8JpU3KiaVLypOVE4qJpWpYlKZKiaVk4pJ5TepTBWTylRxovKGylTxxcNaa13ysNZalzystdYlP3yk8psq3qj4QmWqmFSmihOVqWKqOFE5qZhUpopJZVKZKqaKSeWkYlKZKt5QmSpOKiaVk4pJ5Y2KSWWqmFTeqLhJ5Tc9rLXWJQ9rrXXJw1prXfLDf0zlROWkYlKZKiaVqeJEZaq4SeWNipOKSeWk4kRlqphUpoqp4o2KSWVSOak4qZhUpooTlZOKSeUNlaniROWk4kTlpoe11rrkYa21LnlYa61L7A/+ISpTxRsqJxVvqEwVk8pJxaTymypOVKaKE5Wp4kRlqphUpooTld9UMalMFScqU8WkMlVMKlPFpDJVTCpTxX/pYa21LnlYa61LHtZa65IfPlKZKm5SmSomlZOKE5Wp4o2KE5UvKiaVN1S+qJhUpooTlaliUpkqTiomlaniROUNlZOKk4pJZaqYVE5UpopJ5Y2Kmx7WWuuSh7XWuuRhrbUusT+4SGWqOFE5qZhUpopJ5aTiROWLihOVk4p/mcr/sopJZap4Q2WqmFROKt5QmSomlZOKSWWq+OJhrbUueVhrrUse1lrrkh8+UjlRmSpOKiaVf0nFpHKiMlVMKicqX1RMKicVk8pJxaRyUjGpTBU3qUwqb6hMFScqU8Wk8obKicpJxaTymx7WWuuSh7XWuuRhrbUu+eGXVUwqJypTxYnKTRU3VUwqU8WkclLxRcUXFZPKGypTxaQyVZyoTBUnFScqb1RMKicVk8oXFScqU8VvelhrrUse1lrrkoe11rrE/uAXqUwVk8oXFScqU8WJylTxhsoXFScqU8WJyhsVN6m8UTGpnFR8oTJVfKEyVZyoTBWTylTxhcpJxRcPa611ycNaa13ysNZal/xwmcpUMamcVJyofKEyVUwVN1WcqEwqX6hMFW+o/KaKSWVSeUNlqjhRmSpOVKaKSeUNlROVqeINlanipOKmh7XWuuRhrbUueVhrrUvsDz5QmSpOVP5lFZPKVHGiclIxqUwVJypTxRcqU8WkMlWcqHxR8YbKVHGTym+qmFROKk5UTip+08Naa13ysNZalzystdYl9gcXqZxUTCpTxYnKTRUnKv+liknljYrfpDJVTConFScqU8WJyr+s4g2VLyomlanipoe11rrkYa21LnlYa61LfvhlFV+onFS8oTKpTBVTxaRyUjGp3FQxqUwVX6i8UfFGxaRyUjGpvFFxojJVnKhMFW+oTBX/JZWp4ouHtda65GGttS55WGutS374ZSonFZPKVPGbKiaVk4pJ5V+i8kbFVDGpvKFyojJVTCqTyhsVk8obKl+oTBVTxYnKVPGGyqRyUnHTw1prXfKw1lqXPKy11iX2B3+Rym+qOFGZKk5UpopJ5aTiROWNihOVk4pJ5Y2KSeWNikllqnhDZaqYVKaKSWWqeENlqjhRmSpOVKaKN1SmikllqvjiYa21LnlYa61LHtZa6xL7g4tUTiomlaniC5Wp4kRlqvhCZaqYVE4qTlROKk5UvqiYVE4qvlA5qZhUbqr4QuVvqjhRmSpuelhrrUse1lrrkoe11rrE/uAXqdxUMamcVLyh8kbFTSonFZPKVDGpvFExqZxUnKi8UTGp/KaKSWWqmFROKt5QmSomlaliUpkqJpWpYlKZKr54WGutSx7WWuuSh7XWuuSHj1TeqJhUpooTlZOKSeWk4qTiROWLipOKSWWqmFROKk5UTiomlaniN1V8oXJSMamcVEwqb1RMKlPFpHKi8kbFTQ9rrXXJw1prXfKw1lqX2B98oPJFxYnKVPGFyknFGypTxRsqb1RMKlPFpDJVTCo3VUwqU8Wk8kbFicpUMamcVJyo/MsqJpWTipse1lrrkoe11rrkYa21Lvnho4rfVHGiclLxhcoXKicVf5PKScUbKpPKicpJxYnKVDFVTConFZPKScWkclJxojJVvKEyqUwVk8pvelhrrUse1lrrkoe11rrkh49U/qaKk4pJZap4o2JSeaNiUplUvqiYVE4qJpUTlanipGJSOal4o2JS+UJlqphU3qg4UXlDZao4qTipmFSmii8e1lrrkoe11rrkYa21LvnhsoqbVN5QOVGZKiaVk4oTlTcqTlROVKaKE5U3Kv4mlTcqJpU3Kk4qTiomlanii4o3VKaKSeU3Pay11iUPa611ycNaa13ywy9TeaPii4pJZaqYVL5QmSomlaliUpkqpooTlUllqnhD5aaKSWVSeaNiUjmp+ELljYovVH5TxaRy08Naa13ysNZalzystdYlP/w/o3JTxRcVk8qJym+qOFH5QuVfojJVTCpvVEwqk8pJxaQyVUwqU8WJyn/pYa21LnlYa61LHtZa65If/sdVTCpTxaQyVbyhcqJyUnGiclLxhsobFW+onFRMKicVX1ScqHyhMlV8UXFSMamcVEwqf9PDWmtd8rDWWpc8rLXWJT/8sor/kspUcaIyVdykclJxovJGxaQyVZyoTBVvqEwVb6icVEwqX1RMKjepTBUnKm+o/Jce1lrrkoe11rrkYa21LvnhMpW/SeWmiknlpGJSmSomlaliUpkqpopJ5URlqphU/iaVm1ROKiaVE5Wp4kRlqnhD5YuKSeWkYlK56WGttS55WGutSx7WWusS+4O11rrgYa21LnlYa61LHtZa65KHtda65GGttS55WGutSx7WWuuSh7XWuuRhrbUueVhrrUse1lrrkoe11rrkYa21LnlYa61LHtZa65L/A8KFZ8WOCKyOAAAAAElFTkSuQmCC', '2025-11-13 11:31:43', NULL, NULL, NULL, NULL, NULL, '2025-11-13 11:27:52', '2025-11-13 11:31:43');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `avaliacoes`
--
ALTER TABLE `avaliacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pedido_id` (`pedido_id`),
  ADD KEY `idx_ativo` (`ativo`),
  ADD KEY `idx_data_avaliacao` (`data_avaliacao`);

--
-- Índices de tabela `bairros_entrega`
--
ALTER TABLE `bairros_entrega`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cidade` (`cidade_id`);

--
-- Índices de tabela `bairro_ceps`
--
ALTER TABLE `bairro_ceps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bairro` (`bairro_id`),
  ADD KEY `idx_cep` (`cep`);

--
-- Índices de tabela `cardapio_cores`
--
ALTER TABLE `cardapio_cores`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `cidades_entrega`
--
ALTER TABLE `cidades_entrega`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cidade_estado` (`nome`,`estado`);

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `telefone_unique` (`telefone`),
  ADD KEY `idx_nome` (`nome`),
  ADD KEY `idx_telefone` (`telefone`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_ativo` (`ativo`),
  ADD KEY `idx_token_sessao` (`token_sessao`);

--
-- Índices de tabela `clientes_verificados`
--
ALTER TABLE `clientes_verificados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_telefone` (`telefone`),
  ADD KEY `idx_cliente_id` (`cliente_id`);

--
-- Índices de tabela `cliente_enderecos`
--
ALTER TABLE `cliente_enderecos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cliente_id` (`cliente_id`),
  ADD KEY `idx_principal` (`principal`);

--
-- Índices de tabela `cliente_notificacoes`
--
ALTER TABLE `cliente_notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cliente_id` (`cliente_id`),
  ADD KEY `idx_pedido_id` (`pedido_id`),
  ADD KEY `idx_lida` (`lida`);

--
-- Índices de tabela `configuracao_avaliacoes`
--
ALTER TABLE `configuracao_avaliacoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `configuracao_entrega`
--
ALTER TABLE `configuracao_entrega`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `configuracao_horarios`
--
ALTER TABLE `configuracao_horarios`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `fidelidade_config`
--
ALTER TABLE `fidelidade_config`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `fidelidade_pontos`
--
ALTER TABLE `fidelidade_pontos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cliente_id` (`cliente_id`),
  ADD KEY `idx_pedido_id` (`pedido_id`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `fidelidade_produtos`
--
ALTER TABLE `fidelidade_produtos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_produto_id` (`produto_id`),
  ADD KEY `idx_ativo` (`ativo`);

--
-- Índices de tabela `fidelidade_resgates`
--
ALTER TABLE `fidelidade_resgates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cliente_id` (`cliente_id`),
  ADD KEY `idx_pedido_id` (`pedido_id`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `fidelidade_resgate_itens`
--
ALTER TABLE `fidelidade_resgate_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_resgate_id` (`resgate_id`),
  ADD KEY `idx_produto_id` (`produto_id`);

--
-- Índices de tabela `formas_pagamento`
--
ALTER TABLE `formas_pagamento`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `horarios_funcionamento`
--
ALTER TABLE `horarios_funcionamento`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_dia` (`dia_semana`);

--
-- Índices de tabela `itens_retirar`
--
ALTER TABLE `itens_retirar`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `kanban_lanes`
--
ALTER TABLE `kanban_lanes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `mercadopago_config`
--
ALTER TABLE `mercadopago_config`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `mercadopago_mensagens`
--
ALTER TABLE `mercadopago_mensagens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tipo` (`tipo`);

--
-- Índices de tabela `mercadopago_pagamentos`
--
ALTER TABLE `mercadopago_pagamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_id` (`payment_id`),
  ADD KEY `idx_pedido_id` (`pedido_id`),
  ADD KEY `idx_status` (`status`);

--
-- Índices de tabela `mesas`
--
ALTER TABLE `mesas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_mesa` (`numero_mesa`);

--
-- Índices de tabela `opcoes`
--
ALTER TABLE `opcoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_pedido` (`codigo_pedido`),
  ADD UNIQUE KEY `codigo_pedido_2` (`codigo_pedido`),
  ADD KEY `idx_lane_id` (`lane_id`),
  ADD KEY `idx_posicao_kanban` (`posicao_kanban`),
  ADD KEY `idx_codigo_pedido` (`codigo_pedido`),
  ADD KEY `idx_tipo_entrega` (`tipo_entrega`),
  ADD KEY `idx_forma_pagamento` (`forma_pagamento_id`),
  ADD KEY `idx_cliente_id` (`cliente_id`),
  ADD KEY `idx_entregador` (`entregador_id`),
  ADD KEY `idx_token_avaliacao` (`token_avaliacao`);

--
-- Índices de tabela `pedido_item_opcoes`
--
ALTER TABLE `pedido_item_opcoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_item_id` (`pedido_item_id`),
  ADD KEY `opcao_id` (`opcao_id`);

--
-- Índices de tabela `pedido_itens`
--
ALTER TABLE `pedido_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `pizza_relacionamentos`
--
ALTER TABLE `pizza_relacionamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_relacao` (`pizza_id`,`pizza_relacionada_id`),
  ADD KEY `idx_pizza_id` (`pizza_id`),
  ADD KEY `idx_pizza_relacionada_id` (`pizza_relacionada_id`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Índices de tabela `produto_adicionais`
--
ALTER TABLE `produto_adicionais`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_produto_id` (`produto_id`),
  ADD KEY `idx_adicional_produto_id` (`adicional_produto_id`),
  ADD KEY `idx_adicional_categoria_id` (`adicional_categoria_id`);

--
-- Índices de tabela `produto_itens_retirar`
--
ALTER TABLE `produto_itens_retirar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_produto_id` (`produto_id`),
  ADD KEY `idx_item_retirar_id` (`item_retirar_id`);

--
-- Índices de tabela `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cliente_id` (`cliente_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_nivel_acesso` (`nivel_acesso`);

--
-- Índices de tabela `verificacao_codigos`
--
ALTER TABLE `verificacao_codigos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_telefone` (`telefone`),
  ADD KEY `idx_codigo` (`codigo`),
  ADD KEY `idx_usado` (`usado`),
  ADD KEY `idx_expirado` (`expirado`);

--
-- Índices de tabela `verificacao_config`
--
ALTER TABLE `verificacao_config`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `whatsapp_config`
--
ALTER TABLE `whatsapp_config`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `whatsapp_logs`
--
ALTER TABLE `whatsapp_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`);

--
-- Índices de tabela `whatsapp_mensagens`
--
ALTER TABLE `whatsapp_mensagens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tipo` (`tipo`);

--
-- Índices de tabela `whatsapp_qrcodes`
--
ALTER TABLE `whatsapp_qrcodes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ativo` (`ativo`);

--
-- Índices de tabela `whatsapp_web_events`
--
ALTER TABLE `whatsapp_web_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_uuid` (`session_uuid`);

--
-- Índices de tabela `whatsapp_web_instances`
--
ALTER TABLE `whatsapp_web_instances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_session_uuid` (`session_uuid`),
  ADD UNIQUE KEY `uniq_session_name` (`session_name`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `avaliacoes`
--
ALTER TABLE `avaliacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `bairros_entrega`
--
ALTER TABLE `bairros_entrega`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `bairro_ceps`
--
ALTER TABLE `bairro_ceps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cardapio_cores`
--
ALTER TABLE `cardapio_cores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `cidades_entrega`
--
ALTER TABLE `cidades_entrega`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `clientes_verificados`
--
ALTER TABLE `clientes_verificados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cliente_enderecos`
--
ALTER TABLE `cliente_enderecos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `cliente_notificacoes`
--
ALTER TABLE `cliente_notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `configuracao_avaliacoes`
--
ALTER TABLE `configuracao_avaliacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `configuracao_entrega`
--
ALTER TABLE `configuracao_entrega`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `configuracao_horarios`
--
ALTER TABLE `configuracao_horarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `fidelidade_config`
--
ALTER TABLE `fidelidade_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `fidelidade_pontos`
--
ALTER TABLE `fidelidade_pontos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `fidelidade_produtos`
--
ALTER TABLE `fidelidade_produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `fidelidade_resgates`
--
ALTER TABLE `fidelidade_resgates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `fidelidade_resgate_itens`
--
ALTER TABLE `fidelidade_resgate_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `formas_pagamento`
--
ALTER TABLE `formas_pagamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `horarios_funcionamento`
--
ALTER TABLE `horarios_funcionamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `itens_retirar`
--
ALTER TABLE `itens_retirar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `kanban_lanes`
--
ALTER TABLE `kanban_lanes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `mercadopago_mensagens`
--
ALTER TABLE `mercadopago_mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1359;

--
-- AUTO_INCREMENT de tabela `mercadopago_pagamentos`
--
ALTER TABLE `mercadopago_pagamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT de tabela `mesas`
--
ALTER TABLE `mesas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `opcoes`
--
ALTER TABLE `opcoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pedido_item_opcoes`
--
ALTER TABLE `pedido_item_opcoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pedido_itens`
--
ALTER TABLE `pedido_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pizza_relacionamentos`
--
ALTER TABLE `pizza_relacionamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de tabela `produto_adicionais`
--
ALTER TABLE `produto_adicionais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT de tabela `produto_itens_retirar`
--
ALTER TABLE `produto_itens_retirar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=197;

--
-- AUTO_INCREMENT de tabela `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `verificacao_codigos`
--
ALTER TABLE `verificacao_codigos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `verificacao_config`
--
ALTER TABLE `verificacao_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `whatsapp_config`
--
ALTER TABLE `whatsapp_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `whatsapp_logs`
--
ALTER TABLE `whatsapp_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=205;

--
-- AUTO_INCREMENT de tabela `whatsapp_mensagens`
--
ALTER TABLE `whatsapp_mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT de tabela `whatsapp_qrcodes`
--
ALTER TABLE `whatsapp_qrcodes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `whatsapp_web_events`
--
ALTER TABLE `whatsapp_web_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT de tabela `whatsapp_web_instances`
--
ALTER TABLE `whatsapp_web_instances`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `bairros_entrega`
--
ALTER TABLE `bairros_entrega`
  ADD CONSTRAINT `bairros_entrega_ibfk_1` FOREIGN KEY (`cidade_id`) REFERENCES `cidades_entrega` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `cliente_enderecos`
--
ALTER TABLE `cliente_enderecos`
  ADD CONSTRAINT `fk_endereco_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `cliente_notificacoes`
--
ALTER TABLE `cliente_notificacoes`
  ADD CONSTRAINT `fk_notif_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notif_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `mercadopago_pagamentos`
--
ALTER TABLE `mercadopago_pagamentos`
  ADD CONSTRAINT `fk_mercadopago_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `opcoes`
--
ALTER TABLE `opcoes`
  ADD CONSTRAINT `opcoes_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pedido_item_opcoes`
--
ALTER TABLE `pedido_item_opcoes`
  ADD CONSTRAINT `pedido_item_opcoes_ibfk_1` FOREIGN KEY (`pedido_item_id`) REFERENCES `pedido_itens` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedido_item_opcoes_ibfk_2` FOREIGN KEY (`opcao_id`) REFERENCES `opcoes` (`id`);

--
-- Restrições para tabelas `pedido_itens`
--
ALTER TABLE `pedido_itens`
  ADD CONSTRAINT `pedido_itens_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedido_itens_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`);

--
-- Restrições para tabelas `pizza_relacionamentos`
--
ALTER TABLE `pizza_relacionamentos`
  ADD CONSTRAINT `pizza_relacionamentos_ibfk_1` FOREIGN KEY (`pizza_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pizza_relacionamentos_ibfk_2` FOREIGN KEY (`pizza_relacionada_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `produtos`
--
ALTER TABLE `produtos`
  ADD CONSTRAINT `produtos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `produto_adicionais`
--
ALTER TABLE `produto_adicionais`
  ADD CONSTRAINT `produto_adicionais_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `produto_adicionais_ibfk_2` FOREIGN KEY (`adicional_produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `produto_adicionais_ibfk_3` FOREIGN KEY (`adicional_categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `produto_itens_retirar`
--
ALTER TABLE `produto_itens_retirar`
  ADD CONSTRAINT `produto_itens_retirar_ibfk_1` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `produto_itens_retirar_ibfk_2` FOREIGN KEY (`item_retirar_id`) REFERENCES `itens_retirar` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD CONSTRAINT `fk_push_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `whatsapp_logs`
--
ALTER TABLE `whatsapp_logs`
  ADD CONSTRAINT `whatsapp_logs_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `whatsapp_web_events`
--
ALTER TABLE `whatsapp_web_events`
  ADD CONSTRAINT `fk_web_events_instance` FOREIGN KEY (`session_uuid`) REFERENCES `whatsapp_web_instances` (`session_uuid`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
