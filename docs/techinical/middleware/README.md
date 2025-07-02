# 🛡️ Middlewares do Express PHP

Guia completo dos middlewares disponíveis no framework, suas configurações, uso prático e criação de middlewares customizados.

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Middlewares de Segurança](#middlewares-de-segurança)
- [Middlewares Core](#middlewares-core)
- [Uso Prático](#uso-prático)
- [Configuração Avançada](#configuração-avançada)
- [Criação de Middleware Customizado](#criação-de-middleware-customizado)
- [Performance e Otimização](#performance-e-otimização)
- [Padrões e Boas Práticas](#padrões-e-boas-práticas)

## 🔍 Visão Geral

O Express PHP oferece uma arquitetura de middleware robusta e otimizada, suportando tanto padrões legados quanto PSR-15. Todos os middlewares são altamente configuráveis e otimizados para performance.

### Arquitetura do Sistema

```php
// Stack de middleware é executado na ordem
$app->use(new SecurityMiddleware());      // 1º - Segurança geral
$app->use(new CorsMiddleware());          // 2º - CORS
$app->use(new AuthMiddleware());          // 3º - Autenticação
$app->use(new RateLimitMiddleware());     // 4º - Rate limiting
$app->use(new ValidationMiddleware());    // 5º - Validação específica

// Rota final
$app->get('/api/users', function($req, $res) {
    // Handler da rota
});
```

### Tipos de Middleware

1. **Middleware Global** - Aplicado a todas as rotas
2. **Middleware de Grupo** - Aplicado a um conjunto de rotas
3. **Middleware de Rota** - Aplicado a rotas específicas
4. **Middleware Condicional** - Aplicado baseado em condições

## 🛡️ Middlewares de Segurança

### 1. SecurityMiddleware
**Localização**: `src/Middleware/Security/SecurityMiddleware.php`

Middleware de segurança geral que adiciona headers de proteção básicos.

```php
// Configuração básica
$app->use(new SecurityMiddleware());

// Configuração customizada
$app->use(new SecurityMiddleware([
    'xContentTypeOptions' => true,
    'referrerPolicy' => 'strict-origin-when-cross-origin',
    'customHeaders' => [
        'X-Custom-Security' => 'enabled'
    ]
]));
```

**Headers incluídos**:
- `X-Content-Type-Options: nosniff`
- `X-XSS-Protection: 1; mode=block`
- `Referrer-Policy` (configurável)

### 2. CorsMiddleware
**Localização**: `src/Middleware/Security/CorsMiddleware.php`

Middleware CORS altamente otimizado com cache e configuração flexível.

```php
// Configuração para desenvolvimento
$app->use(CorsMiddleware::development());

// Configuração para produção
$app->use(CorsMiddleware::production([
    'https://meuapp.com',
    'https://app.exemplo.com'
]));

// Configuração avançada
$app->use(new CorsMiddleware([
    'origins' => ['https://exemplo.com'],
    'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    'credentials' => true,
    'maxAge' => 86400,
    'expose' => ['X-Total-Count']
]));
```

**Performance**: Até 52M operações/segundo com cache otimizado.

### 3. AuthMiddleware
**Localização**: `src/Http/Psr15/Middleware/AuthMiddleware.php`

Sistema de autenticação multi-método com suporte a JWT, Basic Auth, API Key e Bearer Token.

```php
// Autenticação JWT
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt'],
    'jwtSecret' => 'sua_chave_secreta_aqui',
    'excludePaths' => ['/public', '/health']
]));

// Multi-método (detecta automaticamente)
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt', 'basic', 'bearer'],
    'jwtSecret' => 'chave_jwt',
    'basicAuthCallback' => 'validateUser',
    'bearerAuthCallback' => 'validateApiKey'
]));

// Por rota específica
$app->get('/admin', new AuthMiddleware(['authMethods' => ['jwt']]), function($req, $res) {
    $user = $req->user; // Usuário autenticado disponível
    $res->json(['user' => $user]);
});
```

### 4. RateLimitMiddleware
**Localização**: `src/Http/Psr15/Middleware/RateLimitMiddleware.php`

Controle de taxa de requisições com algoritmos flexíveis.

```php
// Configuração básica
$app->use(new RateLimitMiddleware([
    'maxRequests' => 60,
    'timeWindow' => 60, // 1 minuto
    'keyGenerator' => function($req) {
        return $req->ip() ?? 'unknown';
    }
]));

// Configuração avançada
$app->use(new RateLimitMiddleware([
    'maxRequests' => 100,
    'timeWindow' => 3600, // 1 hora
    'keyGenerator' => function($req) {
        // Rate limit por usuário autenticado ou IP
        return $req->user['id'] ?? $req->ip() ?? 'anonymous';
    },
    'skipCondition' => function($req) {
        // Pular rate limit para admins
        return $req->user['role'] ?? '' === 'admin';
    },
    'onExceeded' => function($req, $res) {
        $res->status(429)->json([
            'error' => 'Rate limit exceeded',
            'retry_after' => 60
        ]);
    }
]));
```

### 5. ValidationMiddleware
**Localização**: `src/Http/Psr15/Middleware/ValidationMiddleware.php`

Validação de dados de entrada com regras customizáveis.

```php
// Validação de API
$app->post('/api/users', new ValidationMiddleware([
    'body' => [
        'name' => 'required|string|min:2|max:100',
        'email' => 'required|email',
        'age' => 'optional|integer|min:18|max:120'
    ],
    'query' => [
        'format' => 'optional|in:json,xml'
    ]
]), function($req, $res) {
    // Dados já validados em $req->body
});
```

## ⚙️ Middlewares Core

### 1. ErrorMiddleware
Tratamento centralizado de erros e exceções.

```php
$app->use(new ErrorMiddleware([
    'debug' => $_ENV['APP_DEBUG'] ?? false,
    'logErrors' => true,
    'customHandlers' => [
        'ValidationException' => function($exception, $req, $res) {
            $res->status(400)->json([
                'error' => 'Validation failed',
                'details' => $exception->getErrors()
            ]);
        }
    ]
]));
```

### 2. LoggingMiddleware
Sistema de logging de requisições.

```php
$app->use(new LoggingMiddleware([
    'format' => '{method} {uri} - {status} - {response_time}ms',
    'includeBody' => false,
    'excludePaths' => ['/health', '/metrics']
]));
```

## 🚀 Uso Prático

### Configuração Recomendada para API

```php
use Express\Core\Application;
use Express\Http\Psr15\Middleware\{
    SecurityMiddleware,
    CorsMiddleware,
    AuthMiddleware,
    RateLimitMiddleware,
    ValidationMiddleware
};

$app = new Application();

// 1. Segurança básica
$app->use(new SecurityMiddleware());

// 2. CORS para frontend
$app->use(CorsMiddleware::production(['https://meuapp.com']));

// 3. Rate limiting global
$app->use(new RateLimitMiddleware([
    'maxRequests' => 1000,
    'timeWindow' => 3600 // 1 hora
]));

// 4. Logging
$app->use(new LoggingMiddleware());

// 5. Autenticação para rotas protegidas
$app->group('/api', function() use ($app) {
    $app->use(new AuthMiddleware([
        'authMethods' => ['jwt'],
        'jwtSecret' => $_ENV['JWT_SECRET']
    ]));

    // Rotas protegidas aqui
    $app->get('/users', function($req, $res) {
        // Usuário autenticado em $req->user
    });
});

// Rotas públicas
$app->get('/health', function($req, $res) {
    $res->json(['status' => 'ok']);
});
```

### Middleware por Ambiente

```php
// Desenvolvimento
if ($_ENV['APP_ENV'] === 'development') {
    $app->use(CorsMiddleware::development());
    $app->use(new ErrorMiddleware(['debug' => true]));
}

// Produção
if ($_ENV['APP_ENV'] === 'production') {
    $app->use(CorsMiddleware::production($allowedOrigins));
    $app->use(new ErrorMiddleware(['debug' => false]));
    $app->use(new RateLimitMiddleware(['maxRequests' => 100]));
}

// Teste
if ($_ENV['APP_ENV'] === 'testing') {
    // Middleware específico para testes
    $app->use(new TestingMiddleware());
}
```

## ⚙️ Configuração Avançada

### Middleware Condicional

```php
// Aplicar middleware baseado em condição
$app->use(function($req, $res, $next) {
    if ($req->path('/admin')) {
        // Aplicar autenticação admin
        $authMiddleware = new AuthMiddleware([
            'authMethods' => ['jwt'],
            'requiredRole' => 'admin'
        ]);
        return $authMiddleware($req, $res, $next);
    }

    return $next($req, $res);
});
```

### Middleware com Configuração Dinâmica

```php
// Configuração baseada em headers
$app->use(function($req, $res, $next) use ($app) {
    $apiVersion = $req->header('API-Version') ?? '1.0';

    if (version_compare($apiVersion, '2.0', '>=')) {
        // Usar validação mais rigorosa para v2+
        $validationMiddleware = new ValidationMiddleware([
            'strict' => true,
            'validateTypes' => true
        ]);
        return $validationMiddleware($req, $res, $next);
    }

    return $next($req, $res);
});
```

### Grupos com Middleware Específico

```php
// Grupo de API com autenticação
$app->group('/api/v1', function() use ($app) {
    $app->use(new AuthMiddleware(['authMethods' => ['jwt']]));
    $app->use(new RateLimitMiddleware(['maxRequests' => 1000]));

    // Subgrupo admin com rate limit mais restritivo
    $app->group('/admin', function() use ($app) {
        $app->use(new RateLimitMiddleware(['maxRequests' => 100]));
        $app->use(new AuditMiddleware()); // Log de auditoria

        $app->get('/users', 'AdminController@getUsers');
    });
});
```

## 🔧 Criação de Middleware Customizado

### Estrutura Básica

```php
<?php

namespace App\Middleware;

use Express\Middleware\Core\BaseMiddleware;

class CustomMiddleware extends BaseMiddleware
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'defaultOption' => 'value',
            'enabled' => true
        ], $config);
    }

    public function handle($request, $response, callable $next)
    {
        // Lógica antes da execução da rota
        if (!$this->config['enabled']) {
            return $next($request, $response);
        }

        // Modificar request se necessário
        $request->setAttribute('custom_data', 'valor');

        // Chamar próximo middleware/rota
        $result = $next($request, $response);

        // Lógica após execução da rota
        if ($response instanceof \Express\Http\Response) {
            $response->header('X-Custom-Header', 'processed');
        }

        return $result;
    }

    /**
     * Factory method para facilitar uso
     */
    public static function create(array $config = []): callable
    {
        $instance = new self($config);
        return [$instance, 'handle'];
    }
}
```

### Middleware PSR-15 Customizado

```php
<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Psr15CustomMiddleware implements MiddlewareInterface
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Lógica antes
        $request = $request->withAttribute('processed_by', static::class);

        // Processar
        $response = $handler->handle($request);

        // Lógica após
        return $response->withHeader('X-Processed-By', 'CustomMiddleware');
    }
}
```

### Middleware com Cache

```php
<?php

namespace App\Middleware;

class CacheMiddleware extends BaseMiddleware
{
    private $cache;
    private int $ttl;

    public function __construct($cache, int $ttl = 3600)
    {
        $this->cache = $cache;
        $this->ttl = $ttl;
    }

    public function handle($request, $response, callable $next)
    {
        // Gerar chave de cache
        $cacheKey = $this->generateCacheKey($request);

        // Verificar cache
        if ($cachedResponse = $this->cache->get($cacheKey)) {
            $response->header('X-Cache', 'HIT');
            return $response->json($cachedResponse);
        }

        // Executar próximo middleware
        $result = $next($request, $response);

        // Cache da resposta se for GET e status 200
        if ($request->method() === 'GET' && $response->getStatusCode() === 200) {
            $this->cache->set($cacheKey, $result, $this->ttl);
            $response->header('X-Cache', 'MISS');
        }

        return $result;
    }

    private function generateCacheKey($request): string
    {
        return 'cache:' . md5($request->uri() . serialize($request->query()));
    }
}
```

### Middleware de Auditoria

```php
<?php

namespace App\Middleware;

class AuditMiddleware extends BaseMiddleware
{
    private $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function handle($request, $response, callable $next)
    {
        $startTime = microtime(true);

        // Capturar dados da requisição
        $auditData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $request->method(),
            'uri' => $request->uri(),
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id' => $request->user['id'] ?? null
        ];

        // Executar próximo middleware
        $result = $next($request, $response);

        // Completar auditoria
        $auditData['status_code'] = $response->getStatusCode();
        $auditData['response_time'] = round((microtime(true) - $startTime) * 1000, 2);

        // Log da auditoria
        $this->logger->info('API_AUDIT', $auditData);

        return $result;
    }
}
```

## ⚡ Performance e Otimização

### Stack de Middleware Otimizado

O Express PHP otimiza automaticamente a execução de middleware através de:

1. **Pipeline Compilation**: Middlewares são compilados em uma única função
2. **Cache de Stack**: Stacks frequentes são cacheados
3. **Detecção de Redundância**: Middlewares duplicados são automaticamente removidos

```php
// O framework automaticamente otimiza isso:
$app->use(new CorsMiddleware());
$app->use(new CorsMiddleware()); // Detectado como redundante e removido

// Para verificar otimizações
$stats = MiddlewareStack::getStats();
echo "Cache hit rate: {$stats['cache_hit_rate']}%\n";
echo "Pipelines compiled: {$stats['compiled_pipelines']}\n";
```

### Benchmark de Performance

```php
// Teste de performance de middleware
$results = CorsMiddleware::benchmark(10000);
echo "CORS Middleware: {$results['operations_per_second']} ops/sec\n";
```

### Dicas de Performance

1. **Use factories estáticos**: `CorsMiddleware::simple()` é mais rápido
2. **Configure cache**: Para middlewares com configuração estática
3. **Evite closures**: Prefira classes para middleware complexo
4. **Order matters**: Coloque middlewares mais rápidos primeiro

```php
// ✅ Otimizado
$app->use(CorsMiddleware::simple('*'));
$app->use(new SecurityMiddleware());
$app->use(new AuthMiddleware());

// ❌ Menos otimizado
$app->use(function($req, $res, $next) {
    // Lógica complexa em closure
    return $next($req, $res);
});
```

## 📋 Padrões e Boas Práticas

### 1. Ordem de Middleware

```php
// Ordem recomendada
$app->use(new ErrorMiddleware());        // 1º - Captura erros de todos
$app->use(new LoggingMiddleware());      // 2º - Log de todas requisições
$app->use(new SecurityMiddleware());     // 3º - Headers de segurança
$app->use(new CorsMiddleware());         // 4º - CORS antes de autenticação
$app->use(new RateLimitMiddleware());    // 5º - Rate limit antes de processamento pesado
$app->use(new AuthMiddleware());         // 6º - Autenticação
$app->use(new ValidationMiddleware());   // 7º - Validação dos dados
```

### 2. Configuração por Ambiente

```php
// config/middleware.php
return [
    'development' => [
        CorsMiddleware::development(),
        new ErrorMiddleware(['debug' => true]),
        new LoggingMiddleware(['level' => 'debug'])
    ],
    'production' => [
        CorsMiddleware::production($allowedOrigins),
        new ErrorMiddleware(['debug' => false]),
        new RateLimitMiddleware(['maxRequests' => 1000]),
        new SecurityMiddleware()
    ]
];
```

### 3. Testes de Middleware

```php
// tests/Middleware/CustomMiddlewareTest.php
class CustomMiddlewareTest extends TestCase
{
    public function testMiddlewareProcessesRequest()
    {
        $middleware = new CustomMiddleware(['enabled' => true]);
        $request = $this->createMockRequest();
        $response = $this->createMockResponse();

        $called = false;
        $next = function() use (&$called) {
            $called = true;
            return 'processed';
        };

        $result = $middleware->handle($request, $response, $next);

        $this->assertTrue($called);
        $this->assertEquals('processed', $result);
    }
}
```

### 4. Documentação de Middleware

```php
/**
 * Middleware de Cache HTTP
 *
 * Este middleware implementa cache de resposta HTTP para melhorar performance.
 *
 * @example
 * // Uso básico
 * $app->use(new CacheMiddleware($redis, 3600));
 *
 * // Configuração avançada
 * $app->use(new CacheMiddleware($redis, 3600, [
 *     'cacheable_methods' => ['GET', 'HEAD'],
 *     'cache_key_prefix' => 'api:',
 *     'exclude_paths' => ['/admin/*', '/api/realtime/*']
 * ]));
 *
 * @param CacheInterface $cache Instância do cache (Redis, Memcached, etc.)
 * @param int $ttl Tempo de vida do cache em segundos
 * @param array $options Opções adicionais de configuração
 */
class CacheMiddleware extends BaseMiddleware
{
    // Implementação...
}
```

## 🔗 Links Relacionados

- [SecurityMiddleware](SecurityMiddleware.md) - Middleware de segurança geral
- [CorsMiddleware](CorsMiddleware.md) - Configuração CORS avançada
- [AuthMiddleware](AuthMiddleware.md) - Sistema de autenticação
- [RateLimitMiddleware](RateLimitMiddleware.md) - Controle de taxa
- [ValidationMiddleware](ValidationMiddleware.md) - Validação de dados
- [CustomMiddleware](CustomMiddleware.md) - Criação de middleware customizado

---

## 📚 Recursos Adicionais

- **Performance**: Os middlewares do Express PHP são otimizados para alta performance
- **PSR Compliance**: Suporte completo a PSR-15 e PSR-7
- **Testing**: Todos os middlewares incluem testes unitários abrangentes
- **Documentation**: Cada middleware possui documentação detalhada e exemplos

Para dúvidas ou contribuições, consulte o [guia de contribuição](../../contributing/README.md).
