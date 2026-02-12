# 🔒 Relatório de Melhorias de Segurança - PediMais

Este documento detalha as implementações de segurança realizadas no sistema PediMais para garantir a proteção dos dados dos usuários e a integridade da aplicação.

## 1. Proteção Contra CSRF (Cross-Site Request Forgery)
**Arquivo Principal:** `includes/csrf.php`

Implementamos um sistema robusto de tokens anti-CSRF em **todos** os formulários POST do sistema (46 arquivos no total).

*   **Funcionamento:**
    *   Geração de token criptográfico único por sessão.
    *   Validação automática do token em requisições POST via helper `validar_csrf()`.
    *   Tokens expiram automaticamente após 1 hora para evitar reuso malicioso.
*   **Abrangência:**
    *   Painel Administrativo (Login, Configurações, Edições).
    *   Área do Cliente (Login, Cadastro, Perfil, Pedidos).
    *   Integrações (WhatsApp, Gateways de Pagamento).

## 2. Cabeçalhos de Segurança HTTP (Security Headers)
**Arquivo Principal:** `includes/security_headers.php`

Adicionamos headers HTTP em todas as respostas do servidor para blindar o navegador do usuário contra ataques comuns.

*   `X-Content-Type-Options: nosniff`: Previne que o navegador "adivinhe" tipos de arquivo, mitigando ataques de MIME sniffing.
*   `X-Frame-Options: SAMEORIGIN`: Impede que o site seja carregado em iframes de outros domínios, protegendo contra **Clickjacking**.
*   `X-XSS-Protection: 1; mode=block`: Ativa o filtro de Cross-Site Scripting (XSS) do navegador.
*   `Referrer-Policy: strict-origin-when-cross-origin`: Protege dados de navegação ao clicar em links externos.
*   **Cookies Seguros:**
    *   `HttpOnly`: Impede acesso aos cookies de sessão via JavaScript (proteção contra roubo de sessão via XSS).
    *   `SameSite=Lax`: Restringe o envio de cookies em requisições cross-site.
    *   `Secure`: Ativado automaticamente se a conexão for HTTPS.

## 3. Proteção Contra Ataques de Força Bruta (Rate Limiting)
**Arquivo Principal:** `includes/rate_limiter.php`

Implementamos um limitador de requisições para impedir tentativas massivas de adivinhação de senhas ou abuso de recursos.

*   **Tecnologia:** Armazenamento local rápido baseada em arquivos (sem necessidade de banco de dados ou Redis).
*   **Aplicações:**
    *   Proteção de Login (Admin e Cliente).
    *   Proteção de rotas sensíveis de API.
*   **Configuração:** Define limites de tentativas por janela de tempo (ex: 5 tentativas em 15 minutos).

## 4. ReCAPTCHA (Proteção contra Bots)
**Arquivo Principal:** `includes/recaptcha_helper.php`

Integração flexível com o Google reCAPTCHA para diferenciar humanos de robôs.

*   **Gerenciamento:** Configurável via banco de dados (`configuracao_recaptcha`).
*   **Contextual:** Pode ser ativado/desativado especificamente para Login de Admin, Login de Cliente, Cadastro, etc.
*   **Verificação no Backend:** Validação da resposta do token diretamente com a API do Google.

## 5. Validação de Senhas Fortes
**Arquivo Principal:** `includes/validar_senha.php`

Novas regras para garantir que os usuários criem credenciais seguras.

*   **Mínimo de 8 caracteres.**
*   **Letras maiúsculas e minúsculas.**
*   **Números obrigatórios.**
*   Feedback visual claro para o usuário sobre quais requisitos não foram atendidos.

## 6. Segurança de Banco de Dados e Dados Sensíveis
**Arquivos Principais:** `includes/db.php`, `includes/env_loader.php`

*   **Variáveis de Ambiente (.env):** Credenciais de banco de dados, chaves de API e tokens não ficam mais hardcoded no código fonte.
*   **PDO com Prepared Statements:** Todas as consultas ao banco utilizam PDO, prevenindo injeções de SQL (SQL Injection).
*   **Charset UTF8Mb4:** Garante tratamento correto de caracteres especiais e emojis, evitando vetores de ataque por encoding.

## 7. Controle de Acesso e Autenticação (ACL)
**Arquivo Principal:** `admin/includes/auth.php`

Sistema de permissões hierárquico para garantir que usuários acessem apenas o que é permitido.

*   **Níveis de Acesso:**
    *   `admin` / `gerente`: Acesso total.
    *   `cozinha`: Acesso restrito ao Kanban de Pedidos.
    *   `entregador`: Acesso restrito ao Painel de Entregas.
*   **Redirecionamento Inteligente:** Usuários que tentam acessar páginas não autorizadas são redirecionados para seus painéis específicos.
*   **Verificação de Sessão:** Checagem rigorosa de `usuario_id` em todas as páginas protegidas.

---

**Resumo da Cobertura de Segurança:**
O sistema PediMais agora conta com uma arquitetura de defesa em profundidade, protegendo desde a camada de rede (Headers) até a camada de dados (SQL Injection), passando pela autenticação (Rate Limit, Senhas Fortes) e autorização (ACL).
