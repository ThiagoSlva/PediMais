# 🔐 Plano de Melhorias de Segurança - CardapiX/PediMais

> **Data da Análise:** 23/12/2025  
> **Versão:** 1.0.0  
> **Status:** Em andamento

---

## 📊 Resumo Executivo

| Categoria | Total | Crítico | Médio | Baixo |
|-----------|-------|---------|-------|-------|
| Segurança | 7 | 2 | 3 | 2 |

---

## ✅ Pontos Positivos (Já Implementados)

- [x] **Senhas seguras** - Usa `password_hash()` e `password_verify()`
- [x] **Proteção SQL Injection** - Usa Prepared Statements em todo o código
- [x] **Proteção XSS** - Usa `htmlspecialchars()` na saída de dados
- [x] **Session Fixation** - Usa `session_regenerate_id(true)` no login
- [x] **Estrutura de BD** - Foreign Keys e índices bem definidos
- [x] **Sistema de Permissões** - Níveis de acesso (admin, gerente, cozinha, entregador)

---

## 🔴 CRÍTICO - Prioridade Alta

### 1. Credenciais do Banco de Dados Expostas no Código

**Arquivo:** `includes/config.php`

**Problema:**
```php
define('DB_HOST', '104.225.130.177');
define('DB_NAME', 'xfxpanel_cardapix');
define('DB_USER', 'xfxpanel_cardapix');
define('DB_PASS', '72734108Thi@go');
```

**Solução:** Criar arquivo `.env` e usar variáveis de ambiente

**Arquivos a modificar:**
- [ ] Criar `.env` na raiz do projeto
- [ ] Criar `.env.example` (template sem dados sensíveis)
- [ ] Atualizar `includes/config.php`
- [ ] Adicionar `.env` ao `.gitignore`

**Código sugerido para `.env`:**
```env
DB_HOST=104.225.130.177
DB_NAME=xfxpanel_cardapix
DB_USER=xfxpanel_cardapix
DB_PASS=sua_senha_aqui

SITE_URL=http://localhost:8000
APP_ENV=production
APP_DEBUG=false
```

**Status:** ⬜ Não iniciado

---

### 2. Falta Rate Limiting no Login

**Arquivo:** `admin/login.php`

**Problema:** Não há proteção contra ataques de força bruta. Um atacante pode tentar senhas ilimitadamente.

**Solução:** Implementar controle de tentativas de login

**Funcionalidades necessárias:**
- [ ] Criar tabela `login_attempts` no banco
- [ ] Bloquear IP após 5 tentativas falhas
- [ ] Tempo de bloqueio: 15 minutos
- [ ] Registrar tentativas de login
- [ ] Exibir mensagem de bloqueio

**SQL para criar tabela:**
```sql
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(255) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) DEFAULT 0,
    INDEX idx_ip (ip_address),
    INDEX idx_email (email),
    INDEX idx_attempted (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Status:** ⬜ Não iniciado

---

## 🟡 MÉDIO - Prioridade Média

### 3. CORS Muito Permissivo nas APIs

**Arquivos afetados:**
- `api/finalizar_pedido.php`
- `api/enviar_codigo_verificacao.php`
- `api/validar_codigo_verificacao.php`
- Outras APIs em `api/`

**Problema:**
```php
header('Access-Control-Allow-Origin: *');
```

**Solução:** Restringir para domínios específicos

**Código sugerido:**
```php
$allowed_origins = [
    'https://seudominio.com.br',
    'https://www.seudominio.com.br',
    'http://localhost:8000' // apenas em desenvolvimento
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}
```

**Status:** ⬜ Não iniciado

---

### 4. CSRF Não Implementado em Todas as Páginas Admin

**Páginas COM CSRF (OK):**
- [x] `admin/clientes.php`
- [x] `admin/usuarios.php`

**Páginas SEM CSRF (Precisam correção):**
- [ ] `admin/produtos.php` - permite delete via GET
- [ ] `admin/categorias.php`
- [ ] `admin/grupos_adicionais.php`
- [ ] `admin/itens_retirar.php`
- [ ] `admin/formas_pagamento.php`
- [ ] `admin/horarios.php`

**Problema atual em produtos.php:**
```php
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // Deleta sem verificar CSRF
```

**Solução:** Implementar verificação CSRF em todas as ações destrutivas

**Status:** ⬜ Não iniciado

---

### 5. Arquivos de Debug em Produção

**Arquivos que devem ser removidos ou protegidos:**

```
📁 Raiz do projeto
├── debug_all_config.php
├── debug_avaliacoes.php
├── debug_avaliacoes_clean.php
├── debug_avaliacoes_config.php
├── debug_bairros_entrega.php
├── debug_categorias.php
├── debug_check_order.php
├── debug_clientes.php
├── debug_clientes_cols.php
├── debug_clientes_table.php
├── debug_colors.php
├── debug_colors2.php
├── debug_config.php
├── debug_config_paths.php
├── debug_configuracoes.php
├── debug_enderecos.php
├── debug_find_order_for_review.php
├── debug_formas_pagamento.php
├── debug_horarios.php
├── debug_insert_code.php
├── debug_kanban.php
├── debug_kanban_api.php
├── debug_kanban_status.php
├── debug_lanes.php
├── debug_login_cliente.php
├── debug_logo.php
├── debug_logo_paths.php
├── debug_output.json
├── debug_output.txt
├── debug_pedidos.php
├── debug_pedidos_clean.php
├── debug_pedidos_columns.php
├── debug_pedidos_data.php
├── debug_produtos.php
├── debug_produtos_clean.php
├── debug_schema.php
├── debug_schema_orders.php
├── debug_status_output.txt
├── debug_status_test.php
├── debug_tables.php
├── debug_test_get_pedidos.php
├── debug_update_config.php
├── debug_verificacao_codigos.php
├── debug_whatsapp_columns.php
├── debug_whatsapp_config.php
├── debug_whatsapp_envio.php
├── debug_whatsapp_finalizacao.php
├── debug_whatsapp_templates.php
├── debug_whatsapp_test.php
├── fix_cliente_enderecos.php
├── fix_config_schema.php
├── fix_kanban_orders.php
├── fix_logo_paths.php
├── fix_pedido_status.php
├── fix_schema.php
├── fix_status_complete.php
├── fix_status_enum.php
├── fix_token_avaliacao.php
├── fix_verificacao_table.php
├── test_admin_api.php
├── test_api.php
├── test_concluir.php
├── test_horarios.php
├── test_includes.php
├── test_mercadopago.php
├── test_whatsapp.php
└── view_whatsapp_logs.php
```

**Soluções possíveis:**
1. **Opção A:** Remover todos os arquivos de debug/test/fix
2. **Opção B:** Mover para pasta `/_debug/` e proteger com .htaccess
3. **Opção C:** Adicionar verificação de autenticação admin em cada arquivo

**Código .htaccess para proteger pasta:**
```apache
# Bloquear acesso externo
Order Deny,Allow
Deny from all
Allow from 127.0.0.1
Allow from ::1
```

**Status:** ✅ Concluído (28/12/2025)

> **Implementação:** Todos os arquivos de debug, test e fix foram movidos para a pasta `/Debug/` com proteção via `.htaccess` que permite acesso apenas de localhost.

---

## 🟢 BAIXO - Prioridade Baixa

### 6. Logs Muito Verbosos em Produção

**Arquivo:** `api/mercadopago_webhook.php`

**Problema:**
```php
error_log("📦 Body recebido (" . strlen($input) . " bytes)");
error_log("📦 Body: " . $input);
```
Pode expor dados sensíveis nos logs do servidor.

**Solução:** Usar flag de ambiente para controlar logs

**Código sugerido:**
```php
$debug_mode = getenv('APP_DEBUG') === 'true';

if ($debug_mode) {
    error_log("📦 Body recebido (" . strlen($input) . " bytes)");
    error_log("📦 Body: " . $input);
}
```

**Status:** ⬜ Não iniciado

---

### 7. Validação de Upload de Arquivos

**Verificar implementação em:**
- [ ] `admin/produtos_add.php`
- [ ] `admin/produtos_edit.php`
- [ ] `admin/categorias_add.php`
- [ ] `admin/configuracoes.php`

**Checklist de segurança para uploads:**
- [ ] Validar extensão do arquivo
- [ ] Validar MIME type real (não apenas extensão)
- [ ] Limitar tamanho máximo
- [ ] Renomear arquivo com hash único
- [ ] Salvar fora da pasta pública ou com .htaccess
- [ ] Verificar se é realmente uma imagem (getimagesize)

**Status:** ⬜ Não iniciado

---

## 📝 Melhorias Adicionais (Futuras)

### Segurança
- [ ] Implementar 2FA (autenticação em dois fatores) para admin
- [ ] Adicionar logs de auditoria para ações críticas
- [ ] Implementar Content Security Policy (CSP)
- [ ] Adicionar headers de segurança HTTP

### Performance
- [ ] Implementar cache de queries frequentes
- [ ] Otimizar consultas N+1 no Kanban
- [ ] Minificar CSS/JS em produção

### UX/UI
- [ ] Adicionar notificações push para novos pedidos
- [ ] Melhorar feedback visual de ações
- [ ] Implementar modo offline (PWA)

---

## 🗓️ Cronograma Sugerido

| Semana | Tarefas | Prioridade |
|--------|---------|------------|
| 1 | Items 1 e 2 (Credenciais e Rate Limiting) | 🔴 Crítico |
| 2 | Items 3 e 4 (CORS e CSRF) | 🟡 Médio |
| 3 | Item 5 (Arquivos de Debug) | 🟡 Médio |
| 4 | Items 6 e 7 (Logs e Uploads) | 🟢 Baixo |

---

## 📌 Como Usar Este Documento

1. Marque com `[x]` as tarefas concluídas
2. Atualize o status de cada item (⬜ → 🔄 → ✅)
3. Adicione notas sobre implementação quando necessário
4. Mantenha o changelog atualizado

---

## 📋 Changelog

| Data | Versão | Alterações |
|------|--------|------------|
| 23/12/2025 | 1.0.0 | Documento inicial criado |

---

> **Nota:** Este documento deve ser mantido atualizado conforme as melhorias são implementadas.
