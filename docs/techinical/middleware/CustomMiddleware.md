# 🔧 Criando Middlewares Customizados

Guia completo para criação, implementação e otimização de middlewares personalizados no Express PHP, incluindo padrões avançados, testing e boas práticas.

## 📋 Índice

- [Conceitos Fundamentais](#conceitos-fundamentais)
- [Implementação Básica](#implementação-básica)
- [Middleware PSR-15](#middleware-psr-15)
- [Padrões Avançados](#padrões-avançados)
- [Middleware com Estado](#middleware-com-estado)
- [Performance e Otimização](#performance-e-otimização)
- [Testing](#testing)
- [Exemplos Práticos](#exemplos-práticos)
- [Boas Práticas](#boas-práticas)

## 🔍 Conceitos Fundamentais

### O que é um Middleware?

Um middleware é uma função que tem acesso ao objeto de requisição (`$request`), ao objeto de resposta (`$response`) e à próxima função middleware na stack (`$next`).

### Fluxo de Execução

```
Request → Middleware 1 → Middleware 2 → Route Handler → Middleware 2 → Middleware 1 → Response
          ↓ before      ↓ before      ↓               ↑ after       ↑ after
```

### Anatomia de um Middleware

```php
class CustomMiddleware
{
    public function handle($request, $response, callable $next)
    {
        // 1. Lógica ANTES da execução da rota

        // 2. Chamar próximo middleware/rota
        $result = $next($request, $response);

        // 3. Lógica APÓS a execução da rota

        return $result;
    }
}
```

## 🏗️ Implementação Básica

### Implementação com Interface Legacy

```php
<?php

namespace App\Middleware;

use Express\Middleware\Core\BaseMiddleware;
use Express\Http\Request;
use Express\Http\Response;

class TimingMiddleware extends BaseMiddleware
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'header_name' => 'X-Response-Time',
            'precision' => 2,
            'log_slow_requests' => true,
            'slow_threshold' => 1000 // ms
        ], $config);
    }

    public function handle($request, $response, callable $next)
    {
        // Marcar início
        $startTime = microtime(true);

        // Adicionar timestamp ao request
        $request->setAttribute('timing.start', $startTime);

        // Executar próximo middleware/rota
        $result = $next($request, $response);

        // Calcular tempo decorrido
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Adicionar header de timing
        if ($response instanceof Response) {
            $response->header(
                $this->config['header_name'],
                round($duration, $this->config['precision']) . 'ms'
            );
        }

        // Log de requisições lentas
        if ($this->config['log_slow_requests'] && $duration > $this->config['slow_threshold']) {
            error_log("Slow request detected: {$request->method()} {$request->uri()} took {$duration}ms");
        }

        return $result;
    }

    /**
     * Factory method para fácil uso
     */
    public static function create(array $config = []): callable
    {
        $instance = new self($config);
        return [$instance, 'handle'];
    }
}
```

### Usando o Middleware

```php
// Uso global
$app->use(new TimingMiddleware());

// Uso com configuração
$app->use(new TimingMiddleware([
    'header_name' => 'X-Processing-Time',
    'slow_threshold' => 500
]));

// Uso em rota específica
$app->get('/api/heavy-operation', new TimingMiddleware(), function($req, $res) {
    // Operação pesada
    return $res->json(['result' => 'processed']);
});

// Uso com factory
$app->use(TimingMiddleware::create(['precision' => 3]));
```

## 🔧 Middleware PSR-15

### Implementação PSR-15 Completa

```php
<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestValidationMiddleware implements MiddlewareInterface
{
    private array $rules;
    private array $config;

    public function __construct(array $rules = [], array $config = [])
    {
        $this->rules = $rules;
        $this->config = array_merge([
            'strict_mode' => false,
            'allow_unknown_fields' => true,
            'error_format' => 'json'
        ], $config);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Validar apenas métodos que contêm dados
        if (!in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            return $handler->handle($request);
        }

        // Obter dados do corpo da requisição
        $data = $this->extractRequestData($request);

        // Executar validação
        $validationResult = $this->validate($data);

        if (!$validationResult['valid']) {
            return $this->createErrorResponse($validationResult['errors']);
        }

        // Adicionar dados validados ao request
        $request = $request->withAttribute('validated_data', $validationResult['data']);

        return $handler->handle($request);
    }

    private function extractRequestData(ServerRequestInterface $request): array
    {
        $contentType = $request->getHeaderLine('Content-Type');

        if (str_contains($contentType, 'application/json')) {
            $body = (string) $request->getBody();
            return json_decode($body, true) ?? [];
        }

        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            return $request->getParsedBody() ?? [];
        }

        return [];
    }

    private function validate(array $data): array
    {
        $errors = [];
        $validatedData = [];

        foreach ($this->rules as $field => $rule) {
            $fieldValue = $data[$field] ?? null;
            $fieldErrors = $this->validateField($field, $fieldValue, $rule);

            if (!empty($fieldErrors)) {
                $errors[$field] = $fieldErrors;
            } else {
                $validatedData[$field] = $fieldValue;
            }
        }

        // Verificar campos desconhecidos em modo estrito
        if ($this->config['strict_mode']) {
            $unknownFields = array_diff(array_keys($data), array_keys($this->rules));
            if (!empty($unknownFields)) {
                $errors['_unknown'] = "Unknown fields: " . implode(', ', $unknownFields);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $validatedData
        ];
    }

    private function validateField(string $field, $value, string $rule): array
    {
        $errors = [];
        $rules = explode('|', $rule);

        foreach ($rules as $singleRule) {
            $error = $this->applyRule($field, $value, $singleRule);
            if ($error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    private function applyRule(string $field, $value, string $rule): ?string
    {
        if ($rule === 'required' && ($value === null || $value === '')) {
            return "The {$field} field is required";
        }

        if ($rule === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "The {$field} field must be a valid email";
        }

        if (str_starts_with($rule, 'min:')) {
            $min = (int) substr($rule, 4);
            if ($value && strlen($value) < $min) {
                return "The {$field} field must be at least {$min} characters";
            }
        }

        if (str_starts_with($rule, 'max:')) {
            $max = (int) substr($rule, 4);
            if ($value && strlen($value) > $max) {
                return "The {$field} field must not exceed {$max} characters";
            }
        }

        return null;
    }

    private function createErrorResponse(array $errors): ResponseInterface
    {
        $factory = new \Express\Http\Psr7\Factory\ResponseFactory();
        $response = $factory->createResponse(400);

        $errorData = [
            'error' => 'Validation failed',
            'errors' => $errors,
            'timestamp' => date('c')
        ];

        $response->getBody()->write(json_encode($errorData));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
```

## 🎯 Padrões Avançados

### Middleware com Configuração Dinâmica

```php
<?php

namespace App\Middleware;

class DynamicAuthMiddleware extends BaseMiddleware
{
    private $configResolver;

    public function __construct(callable $configResolver)
    {
        $this->configResolver = $configResolver;
    }

    public function handle($request, $response, callable $next)
    {
        // Resolver configuração dinamicamente
        $config = call_user_func($this->configResolver, $request);

        // Aplicar lógica baseada na configuração
        if ($config['require_auth']) {
            $authResult = $this->authenticate($request, $config['auth_methods']);

            if (!$authResult['success']) {
                return $response->status(401)->json([
                    'error' => 'Authentication required',
                    'message' => $authResult['message']
                ]);
            }

            $request->setAttribute('user', $authResult['user']);
        }

        return $next($request, $response);
    }

    private function authenticate($request, array $methods): array
    {
        foreach ($methods as $method) {
            $result = $this->tryAuthMethod($request, $method);
            if ($result['success']) {
                return $result;
            }
        }

        return ['success' => false, 'message' => 'No valid authentication found'];
    }
}

// Uso
$app->use(new DynamicAuthMiddleware(function($request) {
    // Configuração baseada na rota
    if ($request->path('/admin')) {
        return [
            'require_auth' => true,
            'auth_methods' => ['jwt', 'session'],
            'required_role' => 'admin'
        ];
    }

    if ($request->path('/api')) {
        return [
            'require_auth' => true,
            'auth_methods' => ['jwt', 'api_key']
        ];
    }

    return ['require_auth' => false];
}));
```

### Middleware Condicional

```php
<?php

namespace App\Middleware;

class ConditionalMiddleware extends BaseMiddleware
{
    private $condition;
    private $trueMiddleware;
    private $falseMiddleware;

    public function __construct(callable $condition, $trueMiddleware, $falseMiddleware = null)
    {
        $this->condition = $condition;
        $this->trueMiddleware = $trueMiddleware;
        $this->falseMiddleware = $falseMiddleware;
    }

    public function handle($request, $response, callable $next)
    {
        $conditionResult = call_user_func($this->condition, $request, $response);

        if ($conditionResult && $this->trueMiddleware) {
            return call_user_func($this->trueMiddleware, $request, $response, $next);
        }

        if (!$conditionResult && $this->falseMiddleware) {
            return call_user_func($this->falseMiddleware, $request, $response, $next);
        }

        return $next($request, $response);
    }

    public static function when(callable $condition, $middleware): self
    {
        return new self($condition, $middleware);
    }

    public static function unless(callable $condition, $middleware): self
    {
        return new self(function(...$args) use ($condition) {
            return !call_user_func($condition, ...$args);
        }, $middleware);
    }
}

// Uso
$app->use(ConditionalMiddleware::when(
    function($request) {
        return $request->header('X-API-Version') === '2.0';
    },
    new AdvancedValidationMiddleware()
));

$app->use(ConditionalMiddleware::unless(
    function($request) {
        return $request->path('/public');
    },
    new AuthMiddleware()
));
```

## 🔄 Middleware com Estado

### Middleware com Cache

```php
<?php

namespace App\Middleware;

class CacheMiddleware extends BaseMiddleware
{
    private $cache;
    private array $config;

    public function __construct($cache, array $config = [])
    {
        $this->cache = $cache;
        $this->config = array_merge([
            'ttl' => 3600,
            'cache_methods' => ['GET', 'HEAD'],
            'cache_status_codes' => [200],
            'key_prefix' => 'http_cache:',
            'vary_headers' => ['Accept', 'Accept-Language']
        ], $config);
    }

    public function handle($request, $response, callable $next)
    {
        // Verificar se deve usar cache
        if (!$this->shouldCache($request)) {
            return $next($request, $response);
        }

        // Gerar chave do cache
        $cacheKey = $this->generateCacheKey($request);

        // Tentar obter do cache
        $cached = $this->cache->get($cacheKey);
        if ($cached) {
            return $this->createCachedResponse($response, $cached);
        }

        // Executar próximo middleware
        $result = $next($request, $response);

        // Cache da resposta se aplicável
        if ($this->shouldCacheResponse($response)) {
            $this->cacheResponse($cacheKey, $response);
        }

        return $result;
    }

    private function shouldCache($request): bool
    {
        return in_array($request->method(), $this->config['cache_methods']);
    }

    private function generateCacheKey($request): string
    {
        $keyParts = [
            $request->method(),
            $request->uri(),
            $request->query() ? http_build_query($request->query()) : ''
        ];

        // Adicionar headers que afetam o cache
        foreach ($this->config['vary_headers'] as $header) {
            $keyParts[] = $request->header($header, '');
        }

        return $this->config['key_prefix'] . md5(implode('|', $keyParts));
    }

    private function createCachedResponse($response, array $cached)
    {
        if ($response instanceof Response) {
            $response->header('X-Cache', 'HIT');
            $response->header('X-Cache-Created', $cached['created']);

            if (isset($cached['headers'])) {
                foreach ($cached['headers'] as $name => $value) {
                    $response->header($name, $value);
                }
            }

            return $response->status($cached['status'])->send($cached['body']);
        }

        return $cached['body'];
    }

    private function shouldCacheResponse($response): bool
    {
        $statusCode = $response instanceof Response
            ? $response->getStatusCode()
            : 200;

        return in_array($statusCode, $this->config['cache_status_codes']);
    }

    private function cacheResponse(string $key, $response): void
    {
        $cacheData = [
            'status' => $response instanceof Response ? $response->getStatusCode() : 200,
            'headers' => $response instanceof Response ? $response->getHeaders() : [],
            'body' => $response instanceof Response ? $response->getContent() : $response,
            'created' => date('c')
        ];

        $this->cache->set($key, $cacheData, $this->config['ttl']);
    }
}
```

### Middleware de Rate Limiting Avançado

```php
<?php

namespace App\Middleware;

class AdvancedRateLimitMiddleware extends BaseMiddleware
{
    private $store;
    private array $config;

    public function __construct($store, array $config = [])
    {
        $this->store = $store;
        $this->config = array_merge([
            'max_attempts' => 60,
            'decay_minutes' => 1,
            'key_generator' => null,
            'response_callback' => null,
            'skip_callback' => null,
            'algorithms' => ['fixed_window'], // fixed_window, sliding_window, token_bucket
            'burst_protection' => true,
            'burst_max' => 10,
            'burst_window' => 10 // seconds
        ], $config);
    }

    public function handle($request, $response, callable $next)
    {
        // Verificar se deve pular rate limiting
        if ($this->shouldSkip($request)) {
            return $next($request, $response);
        }

        $key = $this->resolveRequestSignature($request);

        // Verificar burst protection
        if ($this->config['burst_protection'] && $this->isBurstViolation($key)) {
            return $this->buildExceededResponse($request, $response, 'burst');
        }

        // Verificar rate limit principal
        if ($this->tooManyAttempts($key)) {
            return $this->buildExceededResponse($request, $response, 'rate_limit');
        }

        // Incrementar contador
        $this->incrementAttempts($key);

        // Adicionar headers informativos
        $response = $this->addRateLimitHeaders($response, $key);

        return $next($request, $response);
    }

    private function shouldSkip($request): bool
    {
        if (!$this->config['skip_callback']) {
            return false;
        }

        return call_user_func($this->config['skip_callback'], $request);
    }

    private function resolveRequestSignature($request): string
    {
        if ($this->config['key_generator']) {
            return call_user_func($this->config['key_generator'], $request);
        }

        // Estratégia padrão: IP + User Agent
        $ip = $request->ip() ?? 'unknown';
        $userAgent = hash('sha256', $request->header('User-Agent', ''));

        return "rate_limit:{$ip}:{$userAgent}";
    }

    private function isBurstViolation(string $key): bool
    {
        $burstKey = $key . ':burst';
        $attempts = $this->store->get($burstKey, 0);

        if ($attempts >= $this->config['burst_max']) {
            return true;
        }

        // Incrementar contador de burst
        $this->store->set($burstKey, $attempts + 1, $this->config['burst_window']);

        return false;
    }

    private function tooManyAttempts(string $key): bool
    {
        $attempts = $this->store->get($key, 0);
        return $attempts >= $this->config['max_attempts'];
    }

    private function incrementAttempts(string $key): void
    {
        $attempts = $this->store->get($key, 0);
        $ttl = $this->config['decay_minutes'] * 60;

        $this->store->set($key, $attempts + 1, $ttl);
    }

    private function addRateLimitHeaders($response, string $key)
    {
        $attempts = $this->store->get($key, 0);
        $remaining = max(0, $this->config['max_attempts'] - $attempts);
        $resetTime = time() + ($this->config['decay_minutes'] * 60);

        if ($response instanceof Response) {
            $response->header('X-RateLimit-Limit', $this->config['max_attempts']);
            $response->header('X-RateLimit-Remaining', $remaining);
            $response->header('X-RateLimit-Reset', $resetTime);
        }

        return $response;
    }

    private function buildExceededResponse($request, $response, string $type)
    {
        if ($this->config['response_callback']) {
            return call_user_func($this->config['response_callback'], $request, $response, $type);
        }

        $message = $type === 'burst'
            ? 'Too many requests in short time'
            : 'Rate limit exceeded';

        return $response->status(429)->json([
            'error' => $message,
            'type' => $type,
            'retry_after' => $this->config['decay_minutes'] * 60
        ]);
    }
}
```

## ⚡ Performance e Otimização

### Middleware com Cache de Resultado

```php
<?php

namespace App\Middleware;

class OptimizedMiddleware extends BaseMiddleware
{
    private static array $compiledRules = [];
    private static array $resultCache = [];

    public function handle($request, $response, callable $next)
    {
        // Cache de resultado para requisições idênticas
        $requestHash = $this->hashRequest($request);

        if (isset(self::$resultCache[$requestHash])) {
            $cached = self::$resultCache[$requestHash];

            if ($cached['expires'] > time()) {
                return $this->applyCachedResult($response, $cached['result']);
            }

            unset(self::$resultCache[$requestHash]);
        }

        // Executar middleware
        $result = $next($request, $response);

        // Cache do resultado se aplicável
        if ($this->shouldCacheResult($request, $response)) {
            self::$resultCache[$requestHash] = [
                'result' => $this->extractCacheableData($result),
                'expires' => time() + 60 // 1 minuto
            ];
        }

        return $result;
    }

    private function hashRequest($request): string
    {
        return md5($request->method() . $request->uri() . serialize($request->query()));
    }

    private function shouldCacheResult($request, $response): bool
    {
        return $request->method() === 'GET' &&
               ($response instanceof Response ? $response->getStatusCode() === 200 : true);
    }
}
```

### Middleware com Pool de Objetos

```php
<?php

namespace App\Middleware;

class PooledMiddleware extends BaseMiddleware
{
    private static array $objectPool = [];
    private static int $poolSize = 10;

    public function handle($request, $response, callable $next)
    {
        // Obter objeto do pool
        $processor = $this->getFromPool();

        try {
            // Usar objeto para processamento
            $result = $processor->process($request, $response, $next);

            return $result;
        } finally {
            // Retornar objeto ao pool
            $this->returnToPool($processor);
        }
    }

    private function getFromPool()
    {
        if (!empty(self::$objectPool)) {
            return array_pop(self::$objectPool);
        }

        return new RequestProcessor();
    }

    private function returnToPool($processor): void
    {
        if (count(self::$objectPool) < self::$poolSize) {
            $processor->reset(); // Limpar estado
            self::$objectPool[] = $processor;
        }
    }
}
```

## 🧪 Testing

### Teste Unitário de Middleware

```php
<?php

namespace Tests\Middleware;

use PHPUnit\Framework\TestCase;
use App\Middleware\TimingMiddleware;

class TimingMiddlewareTest extends TestCase
{
    private $middleware;
    private $request;
    private $response;

    protected function setUp(): void
    {
        $this->middleware = new TimingMiddleware();
        $this->request = $this->createMockRequest();
        $this->response = $this->createMockResponse();
    }

    public function testAddsTimingHeader(): void
    {
        $nextCalled = false;
        $next = function($req, $res) use (&$nextCalled) {
            $nextCalled = true;
            return $res;
        };

        $result = $this->middleware->handle($this->request, $this->response, $next);

        $this->assertTrue($nextCalled);
        $this->assertTrue($this->response->hasHeader('X-Response-Time'));
    }

    public function testLogsSlowRequests(): void
    {
        $middleware = new TimingMiddleware(['slow_threshold' => 0]); // Threshold muito baixo

        $logSpy = $this->createLogSpy();

        $next = function($req, $res) {
            usleep(1000); // 1ms delay
            return $res;
        };

        $middleware->handle($this->request, $this->response, $next);

        $this->assertStringContainsString('Slow request detected', $logSpy->getLastMessage());
    }

    private function createMockRequest()
    {
        return new class {
            private array $attributes = [];

            public function method(): string { return 'GET'; }
            public function uri(): string { return '/test'; }
            public function setAttribute(string $key, $value): void {
                $this->attributes[$key] = $value;
            }
            public function getAttribute(string $key, $default = null) {
                return $this->attributes[$key] ?? $default;
            }
        };
    }

    private function createMockResponse()
    {
        return new class {
            private array $headers = [];

            public function header(string $name, string $value): self {
                $this->headers[$name] = $value;
                return $this;
            }

            public function hasHeader(string $name): bool {
                return isset($this->headers[$name]);
            }

            public function getHeader(string $name): ?string {
                return $this->headers[$name] ?? null;
            }
        };
    }
}
```

### Teste de Integração

```php
<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Middleware\ValidationMiddleware;

class ValidationMiddlewareIntegrationTest extends TestCase
{
    public function testValidationInApiEndpoint(): void
    {
        // Registrar middleware na aplicação de teste
        $this->app->use(new ValidationMiddleware([
            'name' => 'required|min:2',
            'email' => 'required|email'
        ]));

        $this->app->post('/users', function($req, $res) {
            return $res->json(['success' => true]);
        });

        // Teste com dados válidos
        $response = $this->post('/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Teste com dados inválidos
        $response = $this->post('/users', [
            'name' => 'J',
            'email' => 'invalid-email'
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('error', 'Validation failed');
    }
}
```

## 💡 Exemplos Práticos

### Middleware de Auditoria Completa

```php
<?php

namespace App\Middleware;

class AuditMiddleware extends BaseMiddleware
{
    private $auditLogger;
    private array $config;

    public function __construct($auditLogger, array $config = [])
    {
        $this->auditLogger = $auditLogger;
        $this->config = array_merge([
            'log_requests' => true,
            'log_responses' => true,
            'log_body' => false,
            'sensitive_fields' => ['password', 'token', 'secret'],
            'excluded_paths' => ['/health', '/metrics'],
            'include_user_context' => true
        ], $config);
    }

    public function handle($request, $response, callable $next)
    {
        // Verificar se deve fazer auditoria
        if ($this->shouldSkipAudit($request)) {
            return $next($request, $response);
        }

        $auditId = $this->generateAuditId();
        $startTime = microtime(true);

        // Log da requisição
        if ($this->config['log_requests']) {
            $this->logRequest($auditId, $request);
        }

        try {
            // Executar próximo middleware
            $result = $next($request, $response);

            // Log da resposta bem-sucedida
            if ($this->config['log_responses']) {
                $this->logResponse($auditId, $request, $response, $startTime, 'success');
            }

            return $result;

        } catch (\Throwable $e) {
            // Log de erro
            $this->logResponse($auditId, $request, $response, $startTime, 'error', $e);

            throw $e;
        }
    }

    private function shouldSkipAudit($request): bool
    {
        $path = $request->uri();

        foreach ($this->config['excluded_paths'] as $excludedPath) {
            if (str_starts_with($path, $excludedPath)) {
                return true;
            }
        }

        return false;
    }

    private function generateAuditId(): string
    {
        return uniqid('audit_', true);
    }

    private function logRequest(string $auditId, $request): void
    {
        $logData = [
            'audit_id' => $auditId,
            'type' => 'request',
            'timestamp' => date('c'),
            'method' => $request->method(),
            'uri' => $request->uri(),
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'headers' => $this->filterSensitiveData($request->headers())
        ];

        if ($this->config['log_body'] && in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $logData['body'] = $this->filterSensitiveData($request->body());
        }

        if ($this->config['include_user_context']) {
            $logData['user'] = $request->user ?? null;
        }

        $this->auditLogger->info('API Request', $logData);
    }

    private function logResponse(string $auditId, $request, $response, float $startTime, string $status, ?\Throwable $exception = null): void
    {
        $duration = (microtime(true) - $startTime) * 1000;

        $logData = [
            'audit_id' => $auditId,
            'type' => 'response',
            'timestamp' => date('c'),
            'status' => $status,
            'status_code' => $response instanceof Response ? $response->getStatusCode() : 200,
            'duration_ms' => round($duration, 2),
            'memory_peak' => memory_get_peak_usage(true)
        ];

        if ($exception) {
            $logData['exception'] = [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ];
        }

        $this->auditLogger->info('API Response', $logData);
    }

    private function filterSensitiveData($data): array
    {
        if (!is_array($data)) {
            return $data;
        }

        $filtered = $data;

        foreach ($this->config['sensitive_fields'] as $field) {
            if (isset($filtered[$field])) {
                $filtered[$field] = '[REDACTED]';
            }
        }

        return $filtered;
    }
}
```

### Middleware de Circuit Breaker

```php
<?php

namespace App\Middleware;

class CircuitBreakerMiddleware extends BaseMiddleware
{
    private $store;
    private array $config;

    public function __construct($store, array $config = [])
    {
        $this->store = $store;
        $this->config = array_merge([
            'failure_threshold' => 5,
            'recovery_timeout' => 60, // seconds
            'test_timeout' => 30, // seconds
            'failure_exceptions' => [\Exception::class],
            'success_threshold' => 3, // consecutive successes to close circuit
        ], $config);
    }

    public function handle($request, $response, callable $next)
    {
        $circuitKey = $this->getCircuitKey($request);
        $circuitState = $this->getCircuitState($circuitKey);

        // Circuit is open - fail fast
        if ($circuitState['state'] === 'open') {
            if (!$this->shouldAttemptRecovery($circuitState)) {
                return $this->createFailFastResponse($response);
            }

            // Transition to half-open
            $this->setCircuitState($circuitKey, 'half-open');
        }

        try {
            $result = $next($request, $response);

            // Success - record it
            $this->recordSuccess($circuitKey);

            return $result;

        } catch (\Throwable $e) {
            // Check if this exception should trigger circuit breaker
            if ($this->shouldTriggerOnException($e)) {
                $this->recordFailure($circuitKey);
            }

            throw $e;
        }
    }

    private function getCircuitKey($request): string
    {
        // Circuit per endpoint
        return 'circuit:' . $request->method() . ':' . parse_url($request->uri(), PHP_URL_PATH);
    }

    private function getCircuitState(string $key): array
    {
        return $this->store->get($key, [
            'state' => 'closed',
            'failures' => 0,
            'successes' => 0,
            'last_failure' => null,
            'opened_at' => null
        ]);
    }

    private function setCircuitState(string $key, string $state, array $additional = []): void
    {
        $current = $this->getCircuitState($key);
        $updated = array_merge($current, ['state' => $state], $additional);

        $this->store->set($key, $updated, 3600); // 1 hour TTL
    }

    private function shouldAttemptRecovery(array $state): bool
    {
        if (!$state['opened_at']) {
            return true;
        }

        return (time() - $state['opened_at']) >= $this->config['recovery_timeout'];
    }

    private function recordSuccess(string $key): void
    {
        $state = $this->getCircuitState($key);

        if ($state['state'] === 'half-open') {
            $successes = $state['successes'] + 1;

            if ($successes >= $this->config['success_threshold']) {
                // Close the circuit
                $this->setCircuitState($key, 'closed', [
                    'failures' => 0,
                    'successes' => 0,
                    'last_failure' => null,
                    'opened_at' => null
                ]);
            } else {
                $this->setCircuitState($key, 'half-open', ['successes' => $successes]);
            }
        } else {
            // Reset failure count on success
            $this->setCircuitState($key, 'closed', ['failures' => 0]);
        }
    }

    private function recordFailure(string $key): void
    {
        $state = $this->getCircuitState($key);
        $failures = $state['failures'] + 1;

        if ($failures >= $this->config['failure_threshold']) {
            // Open the circuit
            $this->setCircuitState($key, 'open', [
                'failures' => $failures,
                'opened_at' => time(),
                'last_failure' => time()
            ]);
        } else {
            $this->setCircuitState($key, $state['state'], [
                'failures' => $failures,
                'last_failure' => time()
            ]);
        }
    }

    private function shouldTriggerOnException(\Throwable $e): bool
    {
        foreach ($this->config['failure_exceptions'] as $exceptionClass) {
            if ($e instanceof $exceptionClass) {
                return true;
            }
        }

        return false;
    }

    private function createFailFastResponse($response)
    {
        return $response->status(503)->json([
            'error' => 'Service temporarily unavailable',
            'message' => 'Circuit breaker is open',
            'retry_after' => $this->config['recovery_timeout']
        ]);
    }
}
```

## 📋 Boas Práticas

### 1. Princípios de Design

```php
// ✅ Single Responsibility - Cada middleware tem uma responsabilidade
class AuthMiddleware extends BaseMiddleware { /* apenas autenticação */ }
class CacheMiddleware extends BaseMiddleware { /* apenas cache */ }

// ✅ Configuração flexível
$middleware = new CacheMiddleware($redis, [
    'ttl' => 3600,
    'vary_headers' => ['Accept-Language'],
    'exclude_paths' => ['/admin/*']
]);

// ✅ Fail gracefully
public function handle($request, $response, callable $next)
{
    try {
        return $this->doSomething($request, $response, $next);
    } catch (\Exception $e) {
        // Log error mas não quebra o fluxo
        error_log("Middleware error: " . $e->getMessage());
        return $next($request, $response);
    }
}
```

### 2. Performance

```php
// ✅ Use factories para reutilização
class FastMiddleware extends BaseMiddleware
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public static function create(): callable
    {
        return [self::getInstance(), 'handle'];
    }
}

// ✅ Cache configuração pesada
class OptimizedMiddleware extends BaseMiddleware
{
    private static array $compiledConfig = [];

    public function __construct(array $config)
    {
        $configHash = md5(serialize($config));

        if (!isset(self::$compiledConfig[$configHash])) {
            self::$compiledConfig[$configHash] = $this->compileConfig($config);
        }

        $this->config = self::$compiledConfig[$configHash];
    }
}
```

### 3. Testing

```php
// ✅ Middleware testável
class TestableMiddleware extends BaseMiddleware
{
    private $logger;

    public function __construct($logger = null)
    {
        $this->logger = $logger ?? $this->createDefaultLogger();
    }

    // Permite injeção de dependência para testes
    protected function createDefaultLogger()
    {
        return new FileLogger('/tmp/middleware.log');
    }
}

// ✅ Testes com mocks
public function testMiddlewareLogsRequest(): void
{
    $mockLogger = $this->createMock(LoggerInterface::class);
    $mockLogger->expects($this->once())
               ->method('info')
               ->with($this->stringContains('Request received'));

    $middleware = new TestableMiddleware($mockLogger);
    $middleware->handle($request, $response, $next);
}
```

### 4. Documentação

```php
/**
 * Middleware de autenticação multi-método
 *
 * Suporta JWT, Basic Auth, API Key e Bearer Token com detecção automática
 * do método baseado nos headers da requisição.
 *
 * @example
 * // Uso básico
 * $app->use(new AuthMiddleware(['jwtSecret' => 'key']));
 *
 * // Multi-método
 * $app->use(new AuthMiddleware([
 *     'authMethods' => ['jwt', 'basic'],
 *     'jwtSecret' => 'jwt_key',
 *     'basicAuthCallback' => 'validateUser'
 * ]));
 *
 * @param array $config Configuração do middleware
 * @throws AuthenticationException Quando autenticação falha
 */
class AuthMiddleware extends BaseMiddleware
{
    // Implementação...
}
```

---

## 🔗 Links Relacionados

- [Middleware Overview](README.md) - Visão geral do sistema de middlewares
- [SecurityMiddleware](SecurityMiddleware.md) - Middleware de segurança
- [AuthMiddleware](AuthMiddleware.md) - Sistema de autenticação
- [Performance Guide](../../performance/README.md) - Otimização de performance

## 📚 Recursos Adicionais

- **PSR-15**: Padrão de middleware HTTP server request handlers
- **Testing**: Framework de testes integrado para middleware
- **Performance**: Otimizações automáticas e cache de pipelines
- **Debugging**: Headers informativos e logging detalhado

Para dúvidas ou contribuições, consulte o [guia de contribuição](../../contributing/README.md).
