# 🔧 Guia de Criação de Middleware Personalizado

## Express PHP Framework - Desenvolvimento de Middleware

*Última atualização: 27 de Junho de 2025*

---

## 📋 Índice

1. [Introdução ao Middleware](#introdução-ao-middleware)
2. [Estrutura Básica](#estrutura-básica)
3. [Middleware de Autenticação](#middleware-de-autenticação)
4. [Middleware de Validação](#middleware-de-validação)
5. [Middleware de Cache](#middleware-de-cache)
6. [Middleware de Rate Limiting](#middleware-de-rate-limiting)
7. [Middleware de Log](#middleware-de-log)
8. [Melhores Práticas](#melhores-práticas)

---

## 🎯 Introdução ao Middleware

Middleware no Express PHP são funções que têm acesso aos objetos de request, response e à próxima função middleware na pipeline de execução. Eles podem:

- Executar código antes/depois das rotas
- Modificar objetos request/response
- Terminar o ciclo request-response
- Chamar o próximo middleware na stack

### Anatomia de um Middleware

```php
<?php

use Express\Http\Request;
use Express\Http\Response;
use Express\Middleware\Core\MiddlewareInterface;

function meuMiddleware(Request $req, Response $res, callable $next): void
{
    // Código executado ANTES da rota

    $next(); // Chama o próximo middleware/rota

    // Código executado DEPOIS da rota
}
```

### Interface de Middleware

Para middleware mais complexos, recomenda-se implementar a `MiddlewareInterface`:

```php
<?php

namespace Express\Middleware\Core;

interface MiddlewareInterface
{
    public function __invoke(Request $req, Response $res, callable $next): void;
}
```

> **Nota:** Para middlewares customizados, recomenda-se implementar `Psr\Http\Server\MiddlewareInterface` para compatibilidade PSR-15.

---

## 🏗️ Estrutura Básica

### 1. Middleware Simples

```php
<?php

namespace App\Middleware;

use ExpressPhp\Http\Request;
use ExpressPhp\Http\Response;

class SimpleMiddleware
{
    public function handle(Request $req, Response $res, callable $next): void
    {
        // Adicionar header customizado
        $res->setHeader('X-Custom-Header', 'Express-PHP');

        // Continuar para próximo middleware
        $next();
    }
}
```

### 2. Middleware com Configuração

```php
<?php

namespace App\Middleware;

use ExpressPhp\Http\Request;
use ExpressPhp\Http\Response;

class ConfigurableMiddleware
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'prefix' => 'APP',
            'enabled' => true,
        ], $config);
    }

    public function handle(Request $req, Response $res, callable $next): void
    {
        if (!$this->config['enabled']) {
            $next();
            return;
        }

        $res->setHeader('X-App-Prefix', $this->config['prefix']);
        $next();
    }
}
```

---

## 🔐 Middleware de Autenticação

### JWT Authentication Middleware

```php
<?php

namespace App\Middleware;

use ExpressPhp\Http\Request;
use ExpressPhp\Http\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTAuthMiddleware
{
    private string $secretKey;
    private array $excludePaths;

    public function __construct(string $secretKey, array $excludePaths = [])
    {
        $this->secretKey = $secretKey;
        $this->excludePaths = $excludePaths;
    }

    public function handle(Request $req, Response $res, callable $next): void
    {
        $path = $req->getPath();

        // Verificar se a rota está excluída
        if (in_array($path, $this->excludePaths)) {
            $next();
            return;
        }

        $token = $this->extractToken($req);

        if (!$token) {
            $res->status(401)->json([
                'error' => 'Token não fornecido',
                'code' => 'MISSING_TOKEN'
            ]);
            return;
        }

        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));

            // Adicionar dados do usuário ao request
            $req->setAttribute('user', $decoded);
            $req->setAttribute('userId', $decoded->sub);

            $next();

        } catch (Exception $e) {
            $res->status(401)->json([
                'error' => 'Token inválido',
                'code' => 'INVALID_TOKEN'
            ]);
        }
    }

    private function extractToken(Request $req): ?string
    {
        $authHeader = $req->getHeader('Authorization');

        if (!$authHeader) {
            return null;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
```

### Basic Auth Middleware

```php
<?php

namespace App\Middleware;

use ExpressPhp\Http\Request;
use ExpressPhp\Http\Response;

class BasicAuthMiddleware
{
    private array $users;

    public function __construct(array $users)
    {
        $this->users = $users;
    }

    public function handle(Request $req, Response $res, callable $next): void
    {
        $authHeader = $req->getHeader('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Basic ')) {
            $this->requireAuth($res);
            return;
        }

        $credentials = base64_decode(substr($authHeader, 6));
        [$username, $password] = explode(':', $credentials, 2);

        if (!isset($this->users[$username]) ||
            !password_verify($password, $this->users[$username])) {
            $this->requireAuth($res);
            return;
        }

        $req->setAttribute('user', $username);
        $next();
    }

    private function requireAuth(Response $res): void
    {
        $res->setHeader('WWW-Authenticate', 'Basic realm="Express PHP"')
            ->status(401)
            ->json(['error' => 'Autenticação necessária']);
    }
}
```

---

## ✅ Middleware de Validação

### Request Validation Middleware

```php
<?php

namespace App\Middleware;

use ExpressPhp\Http\Request;
use ExpressPhp\Http\Response;

class ValidationMiddleware
{
    private array $rules;

    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    public function handle(Request $req, Response $res, callable $next): void
    {
        $data = $req->getBody();
        $errors = [];

        foreach ($this->rules as $field => $rule) {
            $value = $data[$field] ?? null;

            if (!$this->validateField($value, $rule)) {
                $errors[$field] = $this->getErrorMessage($field, $rule);
            }
        }

        if (!empty($errors)) {
            $res->status(400)->json([
                'error' => 'Dados inválidos',
                'validation_errors' => $errors
            ]);
            return;
        }

        // Adicionar dados validados ao request
        $req->setAttribute('validated_data', $data);
        $next();
    }

    private function validateField($value, array $rules): bool
    {
        foreach ($rules as $rule) {
            switch ($rule) {
                case 'required':
                    if (empty($value)) return false;
                    break;

                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) return false;
                    break;

                case 'numeric':
                    if (!is_numeric($value)) return false;
                    break;

                default:
                    if (preg_match('/^min:(\d+)$/', $rule, $matches)) {
                        if (strlen($value) < $matches[1]) return false;
                    }
                    if (preg_match('/^max:(\d+)$/', $rule, $matches)) {
                        if (strlen($value) > $matches[1]) return false;
                    }
            }
        }

        return true;
    }

    private function getErrorMessage(string $field, array $rules): string
    {
        if (in_array('required', $rules)) {
            return "O campo {$field} é obrigatório";
        }

        if (in_array('email', $rules)) {
            return "O campo {$field} deve ser um email válido";
        }

        return "O campo {$field} é inválido";
    }
}
```

---

## 🗄️ Middleware de Cache

### Response Cache Middleware

```php
<?php

namespace App\Middleware;

use ExpressPhp\Http\Request;
use ExpressPhp\Http\Response;

class CacheMiddleware
{
    private string $cacheDir;
    private int $ttl;

    public function __construct(string $cacheDir = '/tmp/express-cache', int $ttl = 3600)
    {
        $this->cacheDir = $cacheDir;
        $this->ttl = $ttl;

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function handle(Request $req, Response $res, callable $next): void
    {
        // Apenas cachear GET requests
        if ($req->getMethod() !== 'GET') {
            $next();
            return;
        }

        $cacheKey = $this->generateCacheKey($req);
        $cachedResponse = $this->getFromCache($cacheKey);

        if ($cachedResponse) {
            $res->setHeader('X-Cache', 'HIT')
                ->setHeader('Content-Type', $cachedResponse['content_type'])
                ->send($cachedResponse['content']);
            return;
        }

        // Capturar a resposta
        ob_start();
        $next();
        $content = ob_get_clean();

        // Salvar no cache se a resposta for 200
        if ($res->getStatusCode() === 200) {
            $this->saveToCache($cacheKey, [
                'content' => $content,
                'content_type' => $res->getHeader('Content-Type') ?: 'text/html'
            ]);
        }

        $res->setHeader('X-Cache', 'MISS')
            ->send($content);
    }

    private function generateCacheKey(Request $req): string
    {
        $uri = $req->getUri();
        $query = $req->getQueryParams();
        ksort($query);

        return md5($uri . serialize($query));
    }

    private function getFromCache(string $key): ?array
    {
        $file = $this->cacheDir . '/' . $key . '.cache';

        if (!file_exists($file)) {
            return null;
        }

        $data = json_decode(file_get_contents($file), true);

        if ($data['expires'] < time()) {
            unlink($file);
            return null;
        }

        return $data['content'];
    }

    private function saveToCache(string $key, array $content): void
    {
        $file = $this->cacheDir . '/' . $key . '.cache';

        $data = [
            'expires' => time() + $this->ttl,
            'content' => $content
        ];

        file_put_contents($file, json_encode($data));
    }
}
```

---

## 🚦 Middleware de Rate Limiting

```php
<?php

namespace App\Middleware;

use ExpressPhp\Http\Request;
use ExpressPhp\Http\Response;

class RateLimitMiddleware
{
    private int $maxRequests;
    private int $windowTime;
    private string $storageDir;

    public function __construct(int $maxRequests = 100, int $windowTime = 3600, string $storageDir = '/tmp/rate-limit')
    {
        $this->maxRequests = $maxRequests;
        $this->windowTime = $windowTime;
        $this->storageDir = $storageDir;

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    public function handle(Request $req, Response $res, callable $next): void
    {
        $clientIp = $this->getClientIp($req);
        $key = md5($clientIp);

        $requests = $this->getRequests($key);
        $now = time();

        // Limpar requests antigos
        $requests = array_filter($requests, fn($time) => $now - $time < $this->windowTime);

        if (count($requests) >= $this->maxRequests) {
            $resetTime = min($requests) + $this->windowTime;

            $res->setHeader('X-RateLimit-Limit', (string)$this->maxRequests)
                ->setHeader('X-RateLimit-Remaining', '0')
                ->setHeader('X-RateLimit-Reset', (string)$resetTime)
                ->status(429)
                ->json([
                    'error' => 'Rate limit exceeded',
                    'retry_after' => $resetTime - $now
                ]);
            return;
        }

        // Adicionar request atual
        $requests[] = $now;
        $this->saveRequests($key, $requests);

        $remaining = $this->maxRequests - count($requests);

        $res->setHeader('X-RateLimit-Limit', (string)$this->maxRequests)
            ->setHeader('X-RateLimit-Remaining', (string)$remaining);

        $next();
    }

    private function getClientIp(Request $req): string
    {
        $headers = ['X-Forwarded-For', 'X-Real-IP', 'Client-IP'];

        foreach ($headers as $header) {
            $ip = $req->getHeader($header);
            if ($ip) {
                return explode(',', $ip)[0];
            }
        }

        return $req->getServerParam('REMOTE_ADDR') ?: 'unknown';
    }

    private function getRequests(string $key): array
    {
        $file = $this->storageDir . '/' . $key . '.json';

        if (!file_exists($file)) {
            return [];
        }

        return json_decode(file_get_contents($file), true) ?: [];
    }

    private function saveRequests(string $key, array $requests): void
    {
        $file = $this->storageDir . '/' . $key . '.json';
        file_put_contents($file, json_encode($requests));
    }
}
```

---

## 📝 Middleware de Log

```php
<?php

namespace App\Middleware;

use ExpressPhp\Http\Request;
use ExpressPhp\Http\Response;

class LoggingMiddleware
{
    private string $logFile;
    private array $excludeHeaders;

    public function __construct(string $logFile = 'app.log', array $excludeHeaders = ['authorization', 'cookie'])
    {
        $this->logFile = $logFile;
        $this->excludeHeaders = array_map('strtolower', $excludeHeaders);
    }

    public function handle(Request $req, Response $res, callable $next): void
    {
        $startTime = microtime(true);

        // Log da requisição
        $this->logRequest($req);

        $next();

        // Log da resposta
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $this->logResponse($req, $res, $duration);
    }

    private function logRequest(Request $req): void
    {
        $headers = [];
        foreach ($req->getHeaders() as $name => $value) {
            if (!in_array(strtolower($name), $this->excludeHeaders)) {
                $headers[$name] = $value;
            }
        }

        $logData = [
            'type' => 'REQUEST',
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $req->getMethod(),
            'uri' => $req->getUri(),
            'ip' => $req->getServerParam('REMOTE_ADDR'),
            'user_agent' => $req->getHeader('User-Agent'),
            'headers' => $headers,
            'body_size' => strlen($req->getRawBody())
        ];

        $this->writeLog($logData);
    }

    private function logResponse(Request $req, Response $res, float $duration): void
    {
        $logData = [
            'type' => 'RESPONSE',
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $req->getMethod(),
            'uri' => $req->getUri(),
            'status' => $res->getStatusCode(),
            'duration_ms' => $duration,
            'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB'
        ];

        $this->writeLog($logData);
    }

    private function writeLog(array $data): void
    {
        $logLine = json_encode($data) . "\n";
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}
```

---

## 🎯 Melhores Práticas

### 1. **Performance**
- Use middleware apenas quando necessário
- Implemente cache quando apropriado
- Evite operações pesadas em middleware frequentemente executados

### 2. **Ordem de Execução**
```php
$app->use(new CorsMiddleware());           // 1º - CORS
$app->use(new LoggingMiddleware());        // 2º - Logging
$app->use(new RateLimitMiddleware());      // 3º - Rate Limiting
$app->use(new AuthMiddleware());           // 4º - Autenticação
$app->use(new ValidationMiddleware());     // 5º - Validação
```

### 3. **Tratamento de Erros**
```php
public function handle(Request $req, Response $res, callable $next): void
{
    try {
        // Lógica do middleware
        $next();
    } catch (Exception $e) {
        $res->status(500)->json([
            'error' => 'Erro interno do servidor',
            'code' => 'MIDDLEWARE_ERROR'
        ]);
    }
}
```

### 4. **Configuração Flexível**
```php
// Uso com configuração
$app->use(new RateLimitMiddleware(
    maxRequests: 1000,
    windowTime: 3600,
    storageDir: '/app/storage/rate-limit'
));
```

### 5. **Testing**
```php
// Teste unitário de middleware
public function testAuthMiddleware(): void
{
    $req = new Request('GET', '/protected');
    $res = new Response();
    $nextCalled = false;

    $middleware = new JWTAuthMiddleware('secret');
    $middleware->handle($req, $res, function() use (&$nextCalled) {
        $nextCalled = true;
    });

    $this->assertEquals(401, $res->getStatusCode());
    $this->assertFalse($nextCalled);
}
```

---

## 📚 Exemplos de Uso

### Registro de Middleware Global
```php
<?php

use App\Middleware\{
    CorsMiddleware,
    LoggingMiddleware,
    RateLimitMiddleware,
    JWTAuthMiddleware
};

$app = new Application();

// Middleware globais
$app->use(new CorsMiddleware());
$app->use(new LoggingMiddleware('logs/app.log'));
$app->use(new RateLimitMiddleware(100, 3600));

// Middleware específico para rotas protegidas
$app->group('/api', function($router) {
    $router->use(new JWTAuthMiddleware('your-secret-key'));

    $router->get('/users', 'UserController@index');
    $router->post('/users', 'UserController@store');
});
```

### Middleware Condicional
```php
$app->get('/admin/*', function($req, $res, $next) {
    if (!$req->getAttribute('user')->isAdmin()) {
        $res->status(403)->json(['error' => 'Acesso negado']);
        return;
    }
    $next();
});
```

---

**Express PHP Framework** oferece flexibilidade total para criar middleware personalizados que atendam às necessidades específicas da sua aplicação. 🚀
