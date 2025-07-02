# 🛡️ SecurityMiddleware

Middleware essencial para aplicar headers de segurança e proteger aplicações contra ataques comuns como XSS, CSRF, clickjacking e outros vetores de ataque.

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Instalação e Uso Básico](#instalação-e-uso-básico)
- [Configurações Disponíveis](#configurações-disponíveis)
- [Headers de Segurança](#headers-de-segurança)
- [Proteções Implementadas](#proteções-implementadas)
- [Configurações por Ambiente](#configurações-por-ambiente)
- [Integração com Outros Middlewares](#integração-com-outros-middlewares)
- [Testing](#testing)
- [Exemplos Práticos](#exemplos-práticos)
- [Boas Práticas](#boas-práticas)

## 🔍 Visão Geral

O `SecurityMiddleware` é um middleware fundamental que aplica múltiplas camadas de proteção de segurança web, incluindo headers HTTP de segurança padrão da indústria e proteções contra vulnerabilidades OWASP Top 10.

### Proteções Principais

- **XSS Protection** - Prevenção de Cross-Site Scripting
- **Content Type Options** - Prevenção de MIME sniffing
- **Frame Options** - Proteção contra clickjacking
- **Referrer Policy** - Controle de informações de referência
- **Custom Security Headers** - Headers personalizados

## 🚀 Instalação e Uso Básico

### Uso Simples

```php
<?php

use Express\Core\Application;
use Express\Middleware\Security\SecurityMiddleware;

$app = new Application();

// Aplicação básica (recomendado para a maioria dos casos)
$app->use(new SecurityMiddleware());

// Suas rotas aqui
$app->get('/', function($req, $res) {
    return $res->json(['status' => 'secure']);
});

$app->run();
```

### Factory Methods

```php
// Método de criação padrão
$app->use(SecurityMiddleware::create());

// Configuração para desenvolvimento (mais permissiva)
$app->use(SecurityMiddleware::development());

// Configuração para produção (mais restritiva)
$app->use(SecurityMiddleware::production());

// Configuração estrita (máxima segurança)
$app->use(SecurityMiddleware::strict());
```

## ⚙️ Configurações Disponíveis

### Opções de Configuração

```php
$app->use(new SecurityMiddleware([
    // Headers básicos
    'xContentTypeOptions' => true,        // X-Content-Type-Options: nosniff
    'referrerPolicy' => 'strict-origin-when-cross-origin',

    // Headers customizados
    'customHeaders' => [
        'X-Custom-Security' => 'enabled',
        'X-API-Version' => '2.1',
        'X-Powered-By' => 'Express PHP'
    ],

    // Configurações avançadas
    'frameOptions' => 'DENY',             // X-Frame-Options
    'contentSecurityPolicy' => null,      // CSP (se configurado)
    'strictTransportSecurity' => null,    // HSTS (se configurado)

    // Configurações de debug
    'logSecurityEvents' => false,
    'debugMode' => false
]));
```

### Configurações Detalhadas

#### Headers de Segurança Básicos

```php
$config = [
    // X-Content-Type-Options
    'xContentTypeOptions' => true,  // Impede MIME sniffing

    // Referrer Policy
    'referrerPolicy' => 'strict-origin-when-cross-origin', // Controla referrer

    // X-XSS-Protection (sempre ativo)
    // Automaticamente definido como "1; mode=block"

    // Headers customizados
    'customHeaders' => [
        'X-Content-Security' => 'protected',
        'X-Request-ID' => function($request) {
            return $request->getAttribute('request_id') ?: uniqid();
        }
    ]
];
```

#### Configurações Avançadas

```php
$advancedConfig = [
    // Content Security Policy
    'contentSecurityPolicy' => [
        'default-src' => "'self'",
        'script-src' => "'self' 'unsafe-inline'",
        'style-src' => "'self' 'unsafe-inline'",
        'img-src' => "'self' data: https:",
        'font-src' => "'self'",
        'connect-src' => "'self'",
        'frame-ancestors' => "'none'"
    ],

    // HTTP Strict Transport Security
    'strictTransportSecurity' => [
        'max-age' => 31536000,  // 1 ano
        'includeSubDomains' => true,
        'preload' => false
    ],

    // X-Frame-Options
    'frameOptions' => 'DENY', // ou 'SAMEORIGIN' ou 'ALLOW-FROM uri'

    // Logging de eventos de segurança
    'logSecurityEvents' => true,
    'logLevel' => 'info'
];
```

## 🔒 Headers de Segurança

### Headers Aplicados Automaticamente

#### 1. X-XSS-Protection
```http
X-XSS-Protection: 1; mode=block
```
- **Propósito**: Ativa proteção XSS do navegador
- **Valor**: Sempre `1; mode=block`
- **Compatibilidade**: Navegadores legacy

#### 2. X-Content-Type-Options
```http
X-Content-Type-Options: nosniff
```
- **Propósito**: Impede MIME type sniffing
- **Configurável**: `xContentTypeOptions => true/false`
- **Recomendação**: Sempre ativado

#### 3. Referrer-Policy
```http
Referrer-Policy: strict-origin-when-cross-origin
```
- **Propósito**: Controla informações de referência
- **Configurável**: `referrerPolicy => 'valor'`
- **Opções**: `no-referrer`, `strict-origin`, `same-origin`, etc.

### Headers Opcionais Avançados

#### Content Security Policy (CSP)

```php
$app->use(new SecurityMiddleware([
    'contentSecurityPolicy' => [
        'default-src' => "'self'",
        'script-src' => "'self' https://cdnjs.cloudflare.com",
        'style-src' => "'self' 'unsafe-inline'",
        'img-src' => "'self' data: https:",
        'font-src' => "'self' https://fonts.gstatic.com",
        'connect-src' => "'self' https://api.exemplo.com",
        'frame-ancestors' => "'none'",
        'base-uri' => "'self'",
        'object-src' => "'none'"
    ]
]));
```

Resultado:
```http
Content-Security-Policy: default-src 'self'; script-src 'self' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com; connect-src 'self' https://api.exemplo.com; frame-ancestors 'none'; base-uri 'self'; object-src 'none'
```

#### Strict Transport Security (HSTS)

```php
$app->use(new SecurityMiddleware([
    'strictTransportSecurity' => [
        'max-age' => 31536000,      // 1 ano em segundos
        'includeSubDomains' => true,
        'preload' => false          // Para inclusion no HSTS preload list
    ]
]));
```

Resultado:
```http
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

## 🛡️ Proteções Implementadas

### 1. Proteção XSS (Cross-Site Scripting)

```php
// O middleware automaticamente adiciona:
// X-XSS-Protection: 1; mode=block

// Para CSP mais rigoroso contra XSS:
$app->use(new SecurityMiddleware([
    'contentSecurityPolicy' => [
        'default-src' => "'self'",
        'script-src' => "'self'",           // Sem 'unsafe-inline'
        'object-src' => "'none'",
        'base-uri' => "'self'"
    ]
]));
```

### 2. Proteção MIME Sniffing

```php
// Automaticamente adicionado:
// X-Content-Type-Options: nosniff

// Impede que navegadores "adivinhem" o tipo de conteúdo
// e executem scripts maliciosos
```

### 3. Proteção Clickjacking

```php
$app->use(new SecurityMiddleware([
    'frameOptions' => 'DENY'  // Impede embedding em frames/iframes
]));

// Ou permitir apenas same-origin
$app->use(new SecurityMiddleware([
    'frameOptions' => 'SAMEORIGIN'
]));

// Resultado:
// X-Frame-Options: DENY
```

### 4. Controle de Referrer

```php
$app->use(new SecurityMiddleware([
    'referrerPolicy' => 'strict-origin-when-cross-origin'
]));

// Opções disponíveis:
// - 'no-referrer'                     - Nunca envia referrer
// - 'no-referrer-when-downgrade'     - Padrão do navegador
// - 'origin'                          - Apenas origem
// - 'origin-when-cross-origin'       - Origem para cross-origin
// - 'same-origin'                     - Apenas same-origin
// - 'strict-origin'                   - Origem, mas não para HTTPS→HTTP
// - 'strict-origin-when-cross-origin' - Recomendado
// - 'unsafe-url'                      - URL completa sempre
```

## 🌍 Configurações por Ambiente

### Desenvolvimento

```php
class SecurityMiddleware
{
    public static function development(): self
    {
        return new self([
            'xContentTypeOptions' => true,
            'referrerPolicy' => 'origin-when-cross-origin',
            'frameOptions' => 'SAMEORIGIN',
            'logSecurityEvents' => true,
            'debugMode' => true,
            'customHeaders' => [
                'X-Environment' => 'development',
                'X-Debug-Mode' => 'enabled'
            ]
        ]);
    }
}

// Uso
if ($_ENV['APP_ENV'] === 'development') {
    $app->use(SecurityMiddleware::development());
}
```

### Produção

```php
class SecurityMiddleware
{
    public static function production(): self
    {
        return new self([
            'xContentTypeOptions' => true,
            'referrerPolicy' => 'strict-origin-when-cross-origin',
            'frameOptions' => 'DENY',
            'strictTransportSecurity' => [
                'max-age' => 31536000,
                'includeSubDomains' => true
            ],
            'contentSecurityPolicy' => [
                'default-src' => "'self'",
                'script-src' => "'self'",
                'style-src' => "'self' 'unsafe-inline'",
                'img-src' => "'self' data:",
                'font-src' => "'self'",
                'connect-src' => "'self'",
                'frame-ancestors' => "'none'",
                'base-uri' => "'self'",
                'object-src' => "'none'"
            ],
            'customHeaders' => [
                'X-Environment' => 'production',
                'Server' => 'Express-PHP'  // Ocultar versão do servidor
            ]
        ]);
    }
}
```

### Configuração Estrita

```php
class SecurityMiddleware
{
    public static function strict(): self
    {
        return new self([
            'xContentTypeOptions' => true,
            'referrerPolicy' => 'no-referrer',
            'frameOptions' => 'DENY',
            'strictTransportSecurity' => [
                'max-age' => 63072000,  // 2 anos
                'includeSubDomains' => true,
                'preload' => true
            ],
            'contentSecurityPolicy' => [
                'default-src' => "'none'",
                'script-src' => "'self'",
                'style-src' => "'self'",
                'img-src' => "'self'",
                'font-src' => "'self'",
                'connect-src' => "'self'",
                'media-src' => "'none'",
                'object-src' => "'none'",
                'child-src' => "'none'",
                'frame-ancestors' => "'none'",
                'base-uri' => "'none'",
                'form-action' => "'self'"
            ],
            'customHeaders' => [
                'X-Security-Level' => 'strict',
                'X-Permitted-Cross-Domain-Policies' => 'none'
            ]
        ]);
    }
}
```

## 🔗 Integração com Outros Middlewares

### Com CorsMiddleware

```php
// Ordem correta: Security primeiro, depois CORS
$app->use(new SecurityMiddleware());
$app->use(new CorsMiddleware([
    'origins' => ['https://app.exemplo.com'],
    'credentials' => true
]));
```

### Com AuthMiddleware

```php
// Headers de segurança antes da autenticação
$app->use(new SecurityMiddleware());
$app->use(new AuthMiddleware(['authMethods' => ['jwt']]));
```

### Com ErrorMiddleware

```php
// Error handling primeiro para capturar todos os erros
$app->use(new ErrorMiddleware());
$app->use(new SecurityMiddleware());
// Outros middlewares...
```

### Stack Recomendada

```php
$app->use(new ErrorMiddleware());           // 1. Captura de erros
$app->use(new SecurityMiddleware());        // 2. Headers de segurança
$app->use(new CorsMiddleware());           // 3. CORS
$app->use(new RateLimitMiddleware());      // 4. Rate limiting
$app->use(new AuthMiddleware());           // 5. Autenticação
$app->use(new ValidationMiddleware());     // 6. Validação
```

## 🧪 Testing

### Teste Básico

```php
<?php

namespace Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Express\Middleware\Security\SecurityMiddleware;

class SecurityMiddlewareTest extends TestCase
{
    public function testAddsSecurityHeaders(): void
    {
        $middleware = new SecurityMiddleware();
        $request = $this->createMockRequest();
        $response = $this->createMockResponse();

        $next = function($req, $res) { return $res; };

        $result = $middleware->handle($request, $response, $next);

        // Verificar headers obrigatórios
        $this->assertTrue($response->hasHeader('X-XSS-Protection'));
        $this->assertEquals('1; mode=block', $response->getHeader('X-XSS-Protection'));

        $this->assertTrue($response->hasHeader('X-Content-Type-Options'));
        $this->assertEquals('nosniff', $response->getHeader('X-Content-Type-Options'));
    }

    public function testCustomHeaders(): void
    {
        $middleware = new SecurityMiddleware([
            'customHeaders' => [
                'X-Custom-Security' => 'test-value'
            ]
        ]);

        $request = $this->createMockRequest();
        $response = $this->createMockResponse();
        $next = function($req, $res) { return $res; };

        $middleware->handle($request, $response, $next);

        $this->assertTrue($response->hasHeader('X-Custom-Security'));
        $this->assertEquals('test-value', $response->getHeader('X-Custom-Security'));
    }

    public function testContentSecurityPolicy(): void
    {
        $middleware = new SecurityMiddleware([
            'contentSecurityPolicy' => [
                'default-src' => "'self'",
                'script-src' => "'self' 'unsafe-inline'"
            ]
        ]);

        $request = $this->createMockRequest();
        $response = $this->createMockResponse();
        $next = function($req, $res) { return $res; };

        $middleware->handle($request, $response, $next);

        $this->assertTrue($response->hasHeader('Content-Security-Policy'));
        $csp = $response->getHeader('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline'", $csp);
    }
}
```

### Teste de Integração

```php
<?php

namespace Tests\Integration;

use Tests\TestCase;
use Express\Middleware\Security\SecurityMiddleware;

class SecurityMiddlewareIntegrationTest extends TestCase
{
    public function testSecurityHeadersInResponse(): void
    {
        $this->app->use(new SecurityMiddleware());

        $this->app->get('/test', function($req, $res) {
            return $res->json(['message' => 'test']);
        });

        $response = $this->get('/test');

        $response->assertStatus(200);
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeaderContains('Referrer-Policy', 'strict-origin');
    }

    public function testCspViolationReporting(): void
    {
        $this->app->use(new SecurityMiddleware([
            'contentSecurityPolicy' => [
                'default-src' => "'self'",
                'report-uri' => '/csp-report'
            ]
        ]));

        $response = $this->get('/');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('report-uri /csp-report', $csp);
    }
}
```

## 💡 Exemplos Práticos

### API REST Segura

```php
<?php

use Express\Core\Application;
use Express\Middleware\Security\SecurityMiddleware;
use Express\Http\Psr15\Middleware\CorsMiddleware;

$app = new Application();

// Configuração de segurança para API
$app->use(new SecurityMiddleware([
    'xContentTypeOptions' => true,
    'referrerPolicy' => 'strict-origin-when-cross-origin',
    'frameOptions' => 'DENY',
    'contentSecurityPolicy' => [
        'default-src' => "'none'",
        'connect-src' => "'self'"  // Apenas requisições AJAX para mesma origem
    ],
    'customHeaders' => [
        'X-API-Version' => '2.1',
        'X-Content-Security' => 'protected'
    ]
]));

$app->use(new CorsMiddleware([
    'origins' => ['https://app.exemplo.com'],
    'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'headers' => ['Content-Type', 'Authorization'],
    'credentials' => true
]));

$app->get('/api/status', function($req, $res) {
    return $res->json(['status' => 'secure', 'timestamp' => time()]);
});
```

### Aplicação Web com CSP

```php
<?php

$app = new Application();

// Configuração para aplicação web com assets externos
$app->use(new SecurityMiddleware([
    'contentSecurityPolicy' => [
        'default-src' => "'self'",
        'script-src' => [
            "'self'",
            "'unsafe-inline'",  // Para scripts inline (use com cuidado)
            'https://cdnjs.cloudflare.com',
            'https://code.jquery.com'
        ],
        'style-src' => [
            "'self'",
            "'unsafe-inline'",  // Para estilos inline
            'https://fonts.googleapis.com'
        ],
        'font-src' => [
            "'self'",
            'https://fonts.gstatic.com'
        ],
        'img-src' => [
            "'self'",
            'data:',  // Para imagens base64
            'https:'  // Qualquer imagem HTTPS
        ],
        'connect-src' => [
            "'self'",
            'https://api.exemplo.com'
        ]
    ],
    'strictTransportSecurity' => [
        'max-age' => 31536000,
        'includeSubDomains' => true
    ]
]));
```

### Aplicação com Logging de Segurança

```php
<?php

class SecurityLoggingMiddleware extends SecurityMiddleware
{
    protected function logSecurityEvent(string $event, array $context = []): void
    {
        $logger = $this->container->get('logger');

        $logger->info("Security Event: {$event}", array_merge([
            'timestamp' => date('c'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ], $context));
    }

    public function handle($request, $response, callable $next)
    {
        // Log da aplicação de headers de segurança
        $this->logSecurityEvent('security_headers_applied', [
            'headers' => $this->getAppliedHeaders()
        ]);

        return parent::handle($request, $response, $next);
    }
}

$app->use(new SecurityLoggingMiddleware([
    'logSecurityEvents' => true
]));
```

## 📋 Boas Práticas

### 1. Configuração por Ambiente

```php
// ✅ Bom - configuração específica por ambiente
$securityConfig = match($_ENV['APP_ENV']) {
    'development' => SecurityMiddleware::development(),
    'testing' => SecurityMiddleware::development(),
    'staging' => SecurityMiddleware::production(),
    'production' => SecurityMiddleware::strict(),
    default => SecurityMiddleware::create()
};

$app->use($securityConfig);
```

### 2. Headers Customizados Funcionais

```php
// ✅ Bom - headers que adicionam valor
$app->use(new SecurityMiddleware([
    'customHeaders' => [
        'X-API-Version' => '2.1',
        'X-Rate-Limit-Policy' => 'standard',
        'X-Security-Level' => 'high'
    ]
]));

// ❌ Evitar - headers que expõem informações desnecessárias
$app->use(new SecurityMiddleware([
    'customHeaders' => [
        'X-Powered-By' => 'PHP/8.1.0',  // Exposição de versão
        'X-Server-Name' => 'web-01'     // Informação interna
    ]
]));
```

### 3. CSP Progressivo

```php
// ✅ Comece restritivo e ajuste conforme necessário
$baseCsp = [
    'default-src' => "'self'",
    'script-src' => "'self'",
    'style-src' => "'self'",
    'img-src' => "'self' data:",
    'font-src' => "'self'",
    'connect-src' => "'self'",
    'frame-ancestors' => "'none'",
    'base-uri' => "'self'",
    'object-src' => "'none'"
];

// Adicione origens conforme necessário
if ($needsCdn) {
    $baseCsp['script-src'][] = 'https://cdnjs.cloudflare.com';
    $baseCsp['style-src'][] = 'https://fonts.googleapis.com';
}

$app->use(new SecurityMiddleware([
    'contentSecurityPolicy' => $baseCsp
]));
```

### 4. Monitoramento de Segurança

```php
// ✅ Implementar reporting para CSP
$app->use(new SecurityMiddleware([
    'contentSecurityPolicy' => [
        'default-src' => "'self'",
        'report-uri' => '/security/csp-report',
        'report-to' => 'security-reports'
    ]
]));

// Endpoint para receber relatórios
$app->post('/security/csp-report', function($req, $res) {
    $report = $req->json();

    // Log violation
    error_log("CSP Violation: " . json_encode($report));

    // Opcionalmente, enviar para serviço de monitoramento
    $this->securityMonitor->reportViolation('csp', $report);

    return $res->status(204)->send();
});
```

### 5. Teste Regular de Headers

```php
// Função para verificar headers de segurança
function checkSecurityHeaders(string $url): array
{
    $headers = get_headers($url, true);

    $checks = [
        'X-XSS-Protection' => isset($headers['X-XSS-Protection']),
        'X-Content-Type-Options' => isset($headers['X-Content-Type-Options']),
        'X-Frame-Options' => isset($headers['X-Frame-Options']),
        'Strict-Transport-Security' => isset($headers['Strict-Transport-Security']),
        'Content-Security-Policy' => isset($headers['Content-Security-Policy'])
    ];

    return $checks;
}

// Usar em testes automatizados
public function testSecurityHeadersPresent(): void
{
    $response = $this->get('/');
    $headers = $response->headers->all();

    $this->assertArrayHasKey('x-xss-protection', $headers);
    $this->assertArrayHasKey('x-content-type-options', $headers);
    $this->assertArrayHasKey('x-frame-options', $headers);
}
```

---

## 🔗 Links Relacionados

- [CorsMiddleware](CorsMiddleware.md) - Configuração CORS
- [AuthMiddleware](AuthMiddleware.md) - Sistema de autenticação
- [Middleware Overview](README.md) - Visão geral de middlewares
- [Security Best Practices](../../guides/security.md) - Práticas de segurança

## 📚 Recursos Adicionais

- **OWASP Security Headers**: Implementação baseada nas recomendações OWASP
- **CSP Generator**: Ferramentas online para gerar Content Security Policy
- **Security Testing**: Ferramentas como securityheaders.com para validação
- **Monitoring**: Integração com serviços de monitoramento de segurança

Para dúvidas ou contribuições, consulte o [guia de contribuição](../../contributing/README.md).
