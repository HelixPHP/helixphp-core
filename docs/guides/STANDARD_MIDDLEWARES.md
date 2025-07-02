# 🛡️ Documentação dos Middlewares Padrão - Express PHP

Esta documentação apresenta todos os middlewares padrão disponíveis no Express PHP Framework, suas funcionalidades, configurações e exemplos práticos de uso.

## 🆕 Novos Middlewares PSR-15 (v2.1+)

A partir da versão 2.1, o Express PHP adota middlewares compatíveis com PSR-15 para máxima interoperabilidade e performance. Recomenda-se migrar para os middlewares abaixo:

- `ErrorMiddleware` — Tratamento global de erros/exceções
- `CsrfMiddleware` — Proteção automática contra CSRF
- `XssMiddleware` — Sanitização automática de dados de entrada
- `SecurityHeadersMiddleware` — Cabeçalhos de segurança HTTP
- `CacheMiddleware` — Cache de resposta HTTP

### Exemplo de uso correto (PSR-15):
```php
use Express\Http\Psr15\Middleware\ErrorMiddleware;
use Express\Http\Psr15\Middleware\CsrfMiddleware;
use Express\Http\Psr15\Middleware\XssMiddleware;
use Express\Http\Psr15\Middleware\SecurityHeadersMiddleware;
use Express\Http\Psr15\Middleware\CacheMiddleware;
use Express\Http\Psr15\Middleware\CorsMiddleware;
use Express\Http\Psr15\Middleware\AuthMiddleware;

$app->use(new ErrorMiddleware());
$app->use(new CsrfMiddleware());
$app->use(new XssMiddleware('<strong><em><p>'));
$app->use(new SecurityHeadersMiddleware());
$app->use(new CacheMiddleware(300));
$app->use(new CorsMiddleware());
$app->use(new AuthMiddleware(['jwtSecret' => 'sua_chave', 'authMethods' => ['jwt']]));
```

> **Nota:** Os middlewares antigos (ex: `SecurityMiddleware`, `CorsMiddleware`, `AuthMiddleware`, `CsrfMiddleware`, `XssMiddleware`) continuam disponíveis para retrocompatibilidade, mas recomenda-se o uso dos middlewares PSR-15, conforme exemplos acima.

---

## 📋 Índice

1. [Introdução aos Middlewares](#introdução-aos-middlewares)
2. [Middlewares de Segurança](#middlewares-de-segurança)
3. [Middlewares Core](#middlewares-core)
4. [Configuração e Uso](#configuração-e-uso)
5. [Exemplos Práticos](#exemplos-práticos)
6. [Referência Completa](#referência-completa)

---

## 🎯 Introdução aos Middlewares

Os middlewares no Express PHP são componentes modulares que processam requisições HTTP em uma pipeline organizada. Cada middleware pode:

- **Processar dados** antes de chegar às rotas
- **Modificar** objetos Request e Response
- **Bloquear** requisições maliciosas
- **Adicionar** funcionalidades transversais
- **Registrar** logs e métricas

### Interface Básica

Todos os middlewares implementam a `MiddlewareInterface`:

```php
<?php

namespace Express\Middleware\Core;

interface MiddlewareInterface
{
    public function handle($request, $response, callable $next);
}
```

---

## 🛡️ Middlewares de Segurança

### 1. SecurityMiddleware

**Propósito:** Aplica headers de segurança HTTP padrão para proteção contra ataques comuns.

#### Configuração Padrão
```php
use Express\Middleware\Security\SecurityMiddleware;

$app->use(new SecurityMiddleware([
    'contentSecurityPolicy' => true,           // CSP básico
    'xFrameOptions' => 'DENY',                // Proteção contra clickjacking
    'xContentTypeOptions' => true,            // Previne MIME sniffing
    'referrerPolicy' => 'strict-origin-when-cross-origin',
    'permissionsPolicy' => true              // Controle de APIs do browser
]));
```

#### Headers Aplicados
- `Content-Security-Policy: default-src 'self'`
- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `X-XSS-Protection: 1; mode=block`

#### Exemplo Avançado
```php
$app->use(new SecurityMiddleware([
    'contentSecurityPolicy' => [
        'default-src' => "'self'",
        'script-src' => "'self' 'unsafe-inline' https://cdn.jsdelivr.net",
        'style-src' => "'self' 'unsafe-inline'",
        'img-src' => "'self' data: https:"
    ],
    'xFrameOptions' => 'SAMEORIGIN',
    'referrerPolicy' => 'no-referrer'
]));
```

---

### 2. CorsMiddleware

**Propósito:** Gerencia Cross-Origin Resource Sharing com otimizações de performance.

#### Configuração Básica
```php
use Express\Middleware\Security\CorsMiddleware;

$app->use(new CorsMiddleware([
    'origins' => ['*'],                       // Origens permitidas
    'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    'credentials' => false,                   // Cookies em cross-origin
    'maxAge' => 86400,                       // Cache preflight (24h)
    'expose' => []                           // Headers expostos ao cliente
]));
```

#### Configuração para Produção
```php
$app->use(new CorsMiddleware([
    'origins' => [
        'https://meusite.com',
        'https://app.meusite.com',
        'https://admin.meusite.com'
    ],
    'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'headers' => ['Content-Type', 'Authorization'],
    'credentials' => true,
    'maxAge' => 7200
]));
```

#### Configuração para Desenvolvimento
```php
$app->use(new CorsMiddleware([
    'origins' => ['*'],
    'methods' => ['*'],
    'headers' => ['*'],
    'credentials' => false
]));
```

---

### 3. AuthMiddleware

**Propósito:** Gerencia autenticação multi-método (JWT, Basic Auth, API Key).

#### Configuração JWT
```php
use Express\Middleware\Security\AuthMiddleware;

$app->use(AuthMiddleware::jwt('sua_chave_secreta_super_forte'));
```

#### Configuração Multi-método
```php
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt', 'basic', 'apikey'],
    'jwtSecret' => 'sua_chave_jwt',
    'basicAuthCallback' => function($username, $password) {
        // Validar credenciais no banco/API
        return $username === 'admin' && $password === 'senha123';
    },
    'apiKeyCallback' => function($apiKey) {
        // Validar API key
        return in_array($apiKey, ['key1', 'key2', 'key3']);
    },
    'excludePaths' => ['/public', '/health', '/docs'],
    'optional' => false
]));
```

#### Acessando Dados do Usuário
```php
$app->get('/profile', function($req, $res) {
    $user = $req->user;                      // Dados do usuário autenticado
    $authMethod = $req->auth['method'];      // Método usado (jwt/basic/apikey)
    $token = $req->auth['token'];            // Token original

    $res->json([
        'user' => $user,
        'authenticated_via' => $authMethod
    ]);
});
```

---

### 4. CsrfMiddleware

**Propósito:** Proteção contra ataques Cross-Site Request Forgery.

```php
use Express\Middleware\Security\CsrfMiddleware;

$app->use(new CsrfMiddleware([
    'tokenName' => '_token',                 // Nome do campo do token
    'cookieName' => 'csrf_token',           // Nome do cookie
    'excludeMethods' => ['GET', 'HEAD', 'OPTIONS'],
    'excludePaths' => ['/api/webhook'],
    'tokenLength' => 32
]));
```

#### Uso no Frontend
```html
<!-- Em formulários -->
<form method="POST" action="/api/users">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <!-- outros campos -->
</form>

<!-- Via JavaScript -->
<script>
fetch('/api/users', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': document.cookie.match(/csrf_token=([^;]+)/)?.[1]
    },
    body: JSON.stringify({...})
});
</script>
```

---

### 5. XssMiddleware

**Propósito:** Proteção contra ataques Cross-Site Scripting (XSS).

```php
use Express\Middleware\Security\XssMiddleware;

$app->use(new XssMiddleware([
    'sanitizeInput' => true,                 // Sanitizar entrada
    'escapeOutput' => true,                  // Escapar saída
    'allowedTags' => ['b', 'i', 'em', 'strong'],
    'allowedAttributes' => ['class', 'id'],
    'strictMode' => false
]));
```

---

## ⚙️ Middlewares Core

### 1. RateLimitMiddleware

**Propósito:** Controla taxa de requisições para prevenir abuso.

#### Configuração Básica
```php
use Express\Middleware\Core\RateLimitMiddleware;

$app->use(new RateLimitMiddleware([
    'maxRequests' => 100,                    // Máximo de requisições
    'timeWindow' => 3600,                    // Janela de tempo (1 hora)
    'keyGenerator' => function($req) {        // Gerador de chave única
        return $req->getClientIP();
    },
    'skipSuccessful' => false,               // Contar apenas erros
    'skipFailedRequests' => false            // Contar apenas sucessos
]));
```

#### Rate Limiting por Usuário
```php
$app->use(new RateLimitMiddleware([
    'maxRequests' => 1000,
    'timeWindow' => 3600,
    'keyGenerator' => function($req) {
        return $req->user['id'] ?? $req->getClientIP();
    }
]));
```

#### Rate Limiting por Endpoint
```php
// Rate limit específico para API de upload
$app->post('/api/upload', [
    new RateLimitMiddleware([
        'maxRequests' => 10,
        'timeWindow' => 300  // 5 minutos
    ]),
    function($req, $res) {
        // Lógica de upload
    }
]);
```

---

### 2. MiddlewareStack

**Propósito:** Gerencia a pipeline de execução de middlewares.

```php
use Express\Middleware\MiddlewareStack;

$stack = new MiddlewareStack();
$stack->push(new SecurityMiddleware());
$stack->push(new CorsMiddleware());
$stack->push(new AuthMiddleware(['optional' => true]));

$app->use($stack);
```

---

## 🔧 Configuração e Uso

### Aplicação Global

```php
<?php
require_once 'vendor/autoload.php';

use Express\Core\Application;
use Express\Middleware\Security\{SecurityMiddleware, CorsMiddleware, AuthMiddleware};
use Express\Middleware\Core\RateLimitMiddleware;

$app = new Application();

// Aplicar middlewares globalmente
$app->use(new SecurityMiddleware());
$app->use(new CorsMiddleware());
$app->use(new RateLimitMiddleware(['maxRequests' => 1000, 'timeWindow' => 3600]));

// Auth opcional globalmente, obrigatório em rotas específicas
$app->use(new AuthMiddleware(['optional' => true]));

$app->run();
```

### Aplicação em Rotas Específicas

```php
// Middleware em rota específica
$app->get('/api/admin', [
    new AuthMiddleware(['role' => 'admin']),
    new RateLimitMiddleware(['maxRequests' => 50, 'timeWindow' => 3600]),
    function($req, $res) {
        $res->json(['message' => 'Admin area']);
    }
]);

// Múltiplos middlewares
$app->post('/api/upload', [
    new AuthMiddleware(),
    new CsrfMiddleware(),
    new RateLimitMiddleware(['maxRequests' => 5, 'timeWindow' => 300]),
    function($req, $res) {
        // Lógica de upload
    }
]);
```

### Grupos de Rotas com Middleware

```php
// Grupo de rotas da API com autenticação
$app->group('/api', [
    'middleware' => [
        new AuthMiddleware(),
        new RateLimitMiddleware(['maxRequests' => 500, 'timeWindow' => 3600])
    ]
], function($api) {
    $api->get('/users', 'UserController@index');
    $api->post('/users', 'UserController@store');
    $api->get('/users/:id', 'UserController@show');
});

// Grupo público sem autenticação
$app->group('/public', [
    'middleware' => [
        new CorsMiddleware(['origins' => ['*']]),
        new RateLimitMiddleware(['maxRequests' => 100, 'timeWindow' => 3600])
    ]
], function($public) {
    $public->get('/status', function($req, $res) {
        $res->json(['status' => 'ok']);
    });
});
```

---

## 📝 Exemplos Práticos

### 1. API REST Completa com Segurança

```php
<?php
require_once 'vendor/autoload.php';

use Express\Core\Application;
use Express\Middleware\Security\{SecurityMiddleware, CorsMiddleware, AuthMiddleware, CsrfMiddleware};
use Express\Middleware\Core\RateLimitMiddleware;

$app = new Application();

// Configuração de segurança para produção
$app->use(new SecurityMiddleware([
    'contentSecurityPolicy' => "default-src 'self'; script-src 'self' 'unsafe-inline'",
    'xFrameOptions' => 'DENY'
]));

// CORS restritivo para produção
$app->use(new CorsMiddleware([
    'origins' => ['https://meuapp.com', 'https://app.meuapp.com'],
    'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'credentials' => true
]));

// Rate limiting global
$app->use(new RateLimitMiddleware([
    'maxRequests' => 1000,
    'timeWindow' => 3600
]));

// Rotas públicas
$app->get('/health', function($req, $res) {
    $res->json(['status' => 'healthy', 'timestamp' => time()]);
});

// API protegida
$app->group('/api', [
    'middleware' => [new AuthMiddleware()]
], function($api) {

    // Rate limit mais restritivo para operações sensíveis
    $api->group('/admin', [
        'middleware' => [
            new AuthMiddleware(['role' => 'admin']),
            new RateLimitMiddleware(['maxRequests' => 100, 'timeWindow' => 3600])
        ]
    ], function($admin) {
        $admin->get('/users', function($req, $res) {
            // Listar usuários (admin only)
        });

        $admin->delete('/users/:id', function($req, $res) {
            // Deletar usuário (admin only)
        });
    });

    // Operações de usuário normal
    $api->get('/profile', function($req, $res) {
        $res->json(['user' => $req->user]);
    });

    $api->put('/profile', [
        new CsrfMiddleware(),
        function($req, $res) {
            // Atualizar perfil
        }
    ]);
});

$app->run();
```

### 2. API Pública com Rate Limiting Diferenciado

```php
<?php
$app = new Application();

// CORS permissivo para API pública
$app->use(new CorsMiddleware(['origins' => ['*']]));

// Rate limiting diferenciado por endpoint
$app->get('/api/public/status', [
    new RateLimitMiddleware(['maxRequests' => 1000, 'timeWindow' => 3600]),
    function($req, $res) {
        $res->json(['status' => 'ok']);
    }
]);

$app->get('/api/public/data', [
    new RateLimitMiddleware(['maxRequests' => 100, 'timeWindow' => 3600]),
    function($req, $res) {
        // Dados que exigem mais processamento
    }
]);

$app->post('/api/public/contact', [
    new RateLimitMiddleware(['maxRequests' => 10, 'timeWindow' => 3600]),
    new CsrfMiddleware(),
    function($req, $res) {
        // Formulário de contato
    }
]);
```

### 3. Middleware Personalizado de Log

```php
<?php
use Express\Middleware\Core\BaseMiddleware;

class LoggingMiddleware extends BaseMiddleware
{
    public function handle($request, $response, callable $next)
    {
        $startTime = microtime(true);
        $method = $request->getMethod();
        $uri = $request->getUri();
        $ip = $request->getClientIP();

        // Log da requisição
        error_log("[$ip] $method $uri - Started");

        // Executar próximo middleware/rota
        $result = $next();

        // Log da resposta
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $status = $response->getStatusCode();
        error_log("[$ip] $method $uri - $status ({$duration}ms)");

        return $result;
    }
}

// Usar middleware personalizado
$app->use(new LoggingMiddleware());
```

---

## 📚 Referência Completa

### Métodos de Configuração

#### SecurityMiddleware
| Opção | Tipo | Padrão | Descrição |
|-------|------|--------|-----------|
| `contentSecurityPolicy` | `bool\|string\|array` | `true` | Política de segurança de conteúdo |
| `xFrameOptions` | `string` | `'DENY'` | Proteção contra clickjacking |
| `xContentTypeOptions` | `bool` | `true` | Previne MIME sniffing |
| `referrerPolicy` | `string` | `'strict-origin-when-cross-origin'` | Política de referrer |
| `permissionsPolicy` | `bool` | `true` | Controle de APIs do browser |

#### CorsMiddleware
| Opção | Tipo | Padrão | Descrição |
|-------|------|--------|-----------|
| `origins` | `array` | `['*']` | Origens permitidas |
| `methods` | `array` | `['GET', 'POST', ...]` | Métodos HTTP permitidos |
| `headers` | `array` | `['Content-Type', ...]` | Headers permitidos |
| `credentials` | `bool` | `false` | Permitir cookies cross-origin |
| `maxAge` | `int` | `86400` | Cache preflight em segundos |
| `expose` | `array` | `[]` | Headers expostos ao cliente |

#### AuthMiddleware
| Opção | Tipo | Padrão | Descrição |
|-------|------|--------|-----------|
| `authMethods` | `array` | `['jwt']` | Métodos de autenticação |
| `jwtSecret` | `string` | - | Chave secreta JWT |
| `basicAuthCallback` | `callable` | - | Validador Basic Auth |
| `apiKeyCallback` | `callable` | - | Validador API Key |
| `excludePaths` | `array` | `[]` | Caminhos excluídos |
| `optional` | `bool` | `false` | Autenticação opcional |

#### RateLimitMiddleware
| Opção | Tipo | Padrão | Descrição |
|-------|------|--------|-----------|
| `maxRequests` | `int` | `100` | Máximo de requisições |
| `timeWindow` | `int` | `3600` | Janela de tempo (segundos) |
| `keyGenerator` | `callable` | IP-based | Gerador de chave única |
| `skipSuccessful` | `bool` | `false` | Pular requisições bem-sucedidas |
| `skipFailedRequests` | `bool` | `false` | Pular requisições com erro |

### Headers HTTP Aplicados

#### Por SecurityMiddleware
- `Content-Security-Policy`
- `X-Frame-Options`
- `X-Content-Type-Options`
- `Referrer-Policy`
- `X-XSS-Protection`

#### Por CorsMiddleware
- `Access-Control-Allow-Origin`
- `Access-Control-Allow-Methods`
- `Access-Control-Allow-Headers`
- `Access-Control-Allow-Credentials`
- `Access-Control-Max-Age`
- `Access-Control-Expose-Headers`

#### Por RateLimitMiddleware
- `X-RateLimit-Limit`
- `X-RateLimit-Remaining`
- `X-RateLimit-Reset`
- `Retry-After` (quando limite excedido)

### Códigos de Status HTTP

| Middleware | Status | Descrição |
|------------|--------|-----------|
| AuthMiddleware | `401` | Não autenticado |
| AuthMiddleware | `403` | Token inválido/expirado |
| CsrfMiddleware | `403` | Token CSRF inválido |
| RateLimitMiddleware | `429` | Limite de taxa excedido |

---

## 🚀 Próximos Passos

1. **Implementar:** Copie os exemplos e adapte para sua aplicação
2. **Personalizar:** Crie middlewares específicos para suas necessidades
3. **Monitorar:** Use logs para acompanhar performance e segurança
4. **Otimizar:** Ajuste configurações baseado no uso real

**📚 Mais informações:** [Guia de Middleware Personalizado](CUSTOM_MIDDLEWARE_GUIDE.md) | [Guia de Segurança](SECURITY_IMPLEMENTATION.md)

---

*Documentação gerada em 27 de Junho de 2025 para Express PHP Framework v2.0*
