# Implementação de Segurança - Express PHP Framework

## 🛡️ Visão Geral de Segurança

O Express PHP Framework implementa múltiplas camadas de segurança para proteger aplicações contra as principais vulnerabilidades web.

## 🔒 Arquitetura de Segurança

### Camadas de Proteção

1. **Input Validation & Sanitization**
2. **Authentication & Authorization**
3. **Cross-Origin Resource Sharing (CORS)**
4. **Cross-Site Request Forgery (CSRF)**
5. **Cross-Site Scripting (XSS)**
6. **Rate Limiting & DDoS Protection**
7. **Security Headers**

## 🛡️ Middlewares de Segurança PSR-15

A partir da versão 2.1, utilize os middlewares PSR-15 para máxima segurança e compatibilidade:

- `CsrfMiddleware` — Proteção automática contra CSRF
- `XssMiddleware` — Sanitização automática de dados
- `SecurityHeadersMiddleware` — Cabeçalhos de segurança HTTP
- `ErrorMiddleware` — Tratamento global de erros
- `CacheMiddleware` — Cache de resposta HTTP
- `CorsMiddleware` — Compartilhamento de recursos entre origens
- `AuthMiddleware` — Autenticação e autorização

> **Importante:** Sempre utilize os middlewares PSR-15 para máxima segurança e compatibilidade:
>
> - `use Express\Http\Psr15\Middleware\CsrfMiddleware;`
> - `use Express\Http\Psr15\Middleware\XssMiddleware;`
> - `use Express\Http\Psr15\Middleware\SecurityHeadersMiddleware;`
> - `use Express\Http\Psr15\Middleware\ErrorMiddleware;`
> - `use Express\Http\Psr15\Middleware\CacheMiddleware;`
> - `use Express\Http\Psr15\Middleware\CorsMiddleware;`
> - `use Express\Http\Psr15\Middleware\AuthMiddleware;`

> **Exemplo recomendado:**
> ```php
> $app->use(new ErrorMiddleware());
> $app->use(new CsrfMiddleware());
> $app->use(new XssMiddleware('<strong><em><p>'));
> $app->use(new SecurityHeadersMiddleware());
> $app->use(new CacheMiddleware(300));
> $app->use(new CorsMiddleware());
> $app->use(new AuthMiddleware(['jwtSecret' => 'sua_chave', 'authMethods' => ['jwt']]));
> ```

> ⚠️ **Atenção:** Todos os middlewares antigos (não-PSR-15) estão depreciados a partir da versão 2.1. Utilize apenas middlewares compatíveis com PSR-15 para máxima segurança, performance e compatibilidade.
>
> ⚠️ **Nota:** Todos os exemplos e recomendações de uso de middleware neste projeto seguem o padrão PSR-15. Middlewares antigos (não-PSR-15) estão **depreciados** e não são mais suportados. Consulte `docs/DEPRECATED_MIDDLEWARES.md` para detalhes.

### Utilitários
- Gere campos CSRF: `CsrfMiddleware::hiddenField()`
- Sanitização manual: `XssMiddleware::sanitize($input, $tags)`

> **Nota:** Middlewares antigos continuam disponíveis, mas recomenda-se o uso dos PSR-15.

## 🛠️ Implementações Detalhadas

### 1. CORS (Cross-Origin Resource Sharing)

#### Implementação Otimizada
```php
class CorsMiddleware extends BaseMiddleware
{
    // Cache de headers pré-compilados para performance
    private static array $preCompiledHeaders = [];

    public static function create(array $config = []): callable
    {
        // Configuração segura padrão
        $finalConfig = array_merge([
            'origins' => [], // Não permite * em produção
            'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'headers' => ['Content-Type', 'Authorization'],
            'credentials' => false, // Desabilitado por padrão
            'maxAge' => 86400
        ], $config);

        return function ($request, $response, $next) use ($finalConfig) {
            // Validação rigorosa de origin
            $origin = $request->getHeader('Origin');
            if (!self::isOriginAllowed($origin, $finalConfig['origins'])) {
                return $response->status(403)->json(['error' => 'Forbidden origin']);
            }

            // Aplicar headers CORS seguros
            self::applyCorsHeaders($response, $finalConfig, $origin);
            $next();
        };
    }

    private static function isOriginAllowed(?string $origin, array $allowed): bool
    {
        if (empty($allowed)) return false;
        if (in_array('*', $allowed)) return true; // Apenas para desenvolvimento
        return in_array($origin, $allowed, true);
    }
}
```

#### Configuração Segura
```php
// ❌ INSEGURO - Não usar em produção
$app->use(CorsMiddleware::create(['origins' => ['*']]));

// ✅ SEGURO - Configuração para produção
$app->use(CorsMiddleware::production([
    'https://app.meusite.com',
    'https://admin.meusite.com'
]));
```

### 2. Autenticação JWT

#### Implementação Robusta
```php
class JWTHelper
{
    public static function encode(array $payload, string $secret, string $alg = 'HS256'): string
    {
        // Adicionar claims de segurança obrigatórios
        $payload = array_merge($payload, [
            'iat' => time(),
            'exp' => time() + 3600,
            'iss' => $_ENV['APP_NAME'] ?? 'express-php',
            'aud' => $_ENV['APP_DOMAIN'] ?? 'localhost'
        ]);

        return self::createToken($payload, $secret, $alg);
    }

    public static function decode(string $token, string $secret): ?array
    {
        try {
            $payload = self::parseToken($token, $secret);

            // Validações de segurança
            if (!self::validateClaims($payload)) {
                return null;
            }

            if (self::isExpired($payload)) {
                return null;
            }

            if (self::isBlacklisted($token)) {
                return null;
            }

            return $payload;
        } catch (Exception $e) {
            error_log("JWT decode error: " . $e->getMessage());
            return null;
        }
    }

    private static function validateClaims(array $payload): bool
    {
        $required = ['iat', 'exp', 'iss', 'aud'];
        foreach ($required as $claim) {
            if (!isset($payload[$claim])) {
                return false;
            }
        }

        // Validar issuer e audience
        $expectedIss = $_ENV['APP_NAME'] ?? 'express-php';
        $expectedAud = $_ENV['APP_DOMAIN'] ?? 'localhost';

        return $payload['iss'] === $expectedIss &&
               $payload['aud'] === $expectedAud;
    }
}
```

### 3. CSRF Protection

#### Middleware CSRF
```php
class CsrfMiddleware extends BaseMiddleware
{
    public function handle($request, $response, callable $next)
    {
        // Métodos seguros não precisam de proteção CSRF
        if (in_array($request->method, ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request, $response);
        }

        $token = $this->getTokenFromRequest($request);
        $sessionToken = $this->getTokenFromSession();

        if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
            return $response->status(403)->json([
                'error' => 'CSRF token mismatch',
                'code' => 'CSRF_INVALID'
            ]);
        }

        return $next($request, $response);
    }

    private function getTokenFromRequest($request): ?string
    {
        // Verificar múltiplas fontes
        return $request->header('X-CSRF-Token') ??
               $request->body('_token') ??
               $request->query('_token');
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
```

### 4. XSS Protection

#### Sanitização Automática
```php
class XssMiddleware extends BaseMiddleware
{
    private array $allowedTags = ['p', 'br', 'strong', 'em'];

    public function handle($request, $response, callable $next)
    {
        // Sanitizar todos os inputs
        $request->body = $this->sanitizeArray($request->body);
        $request->query = $this->sanitizeArray($request->query);

        return $next($request, $response);
    }

    private function sanitizeArray(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    private function sanitizeString(string $value): string
    {
        // Remove scripts maliciosos
        $value = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $value);

        // Remove eventos JavaScript
        $value = preg_replace('/\bon\w+\s*=\s*["\'][^"\']*["\']/i', '', $value);

        // Permitir apenas tags seguras
        return strip_tags($value, '<' . implode('><', $this->allowedTags) . '>');
    }
}
```

### 5. Rate Limiting

#### Proteção DDoS
```php
class RateLimitMiddleware extends BaseMiddleware
{
    private static array $requests = [];

    public static function create(array $config): callable
    {
        $config = array_merge([
            'max_requests' => 100,
            'window' => 3600,
            'key_generator' => null,
            'storage' => 'memory'
        ], $config);

        return function ($request, $response, $next) use ($config) {
            $key = self::generateKey($request, $config['key_generator']);
            $currentTime = time();

            // Limpar requests antigos
            self::cleanup($currentTime, $config['window']);

            // Verificar limite
            if (self::isLimitExceeded($key, $currentTime, $config)) {
                return $response->status(429)->json([
                    'error' => 'Rate limit exceeded',
                    'retry_after' => $config['window'],
                    'limit' => $config['max_requests']
                ]);
            }

            // Registrar request
            self::recordRequest($key, $currentTime);

            $next();
        };
    }

    private static function generateKey($request, ?callable $generator): string
    {
        if ($generator) {
            return $generator($request);
        }

        // IP padrão com suporte a proxy
        $ip = $request->header('X-Forwarded-For') ??
              $request->header('X-Real-IP') ??
              $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        return 'rate_limit:' . hash('sha256', $ip);
    }
}
```

### 6. Security Headers

#### Headers de Segurança Automáticos
```php
class SecurityHeadersMiddleware extends BaseMiddleware
{
    private array $defaultHeaders = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'Content-Security-Policy' => "default-src 'self'",
        'Referrer-Policy' => 'strict-origin-when-cross-origin'
    ];

    public function handle($request, $response, callable $next)
    {
        $result = $next($request, $response);

        // Aplicar headers de segurança
        foreach ($this->defaultHeaders as $name => $value) {
            $response->header($name, $value);
        }

        return $result;
    }
}
```

## 🔐 Configuração de Produção

### Stack de Segurança Completo
```php
$app = new Application();

// 1. Security Headers
$app->use(SecurityHeadersMiddleware::create());

// 2. CORS restritivo
$app->use(CorsMiddleware::production([
    'https://app.exemplo.com'
]));

// 3. Rate Limiting
$app->use(RateLimitMiddleware::create([
    'max_requests' => 1000,
    'window' => 3600
]));

// 4. CSRF Protection
$app->use(CsrfMiddleware::create());

// 5. XSS Protection
$app->use(XssMiddleware::create());

// 6. Authentication
$app->use(AuthMiddleware::jwt([
    'secret' => $_ENV['JWT_SECRET'],
    'exclude' => ['/login', '/register']
]));
```

## 📊 Validação de Segurança

### Testes de Segurança Automatizados
```php
class SecurityTest extends TestCase
{
    public function testCorsBlocking()
    {
        $response = $this->request('GET', '/', [
            'Origin' => 'https://malicious-site.com'
        ]);

        $this->assertEquals(403, $response->status);
    }

    public function testCsrfProtection()
    {
        $response = $this->request('POST', '/api/users', [], [
            'name' => 'Test User'
        ]);

        $this->assertEquals(403, $response->status);
        $this->assertStringContains('CSRF', $response->body);
    }

    public function testRateLimit()
    {
        for ($i = 0; $i < 101; $i++) {
            $response = $this->request('GET', '/');
        }

        $this->assertEquals(429, $response->status);
    }
}
```

## 🚨 Alertas de Segurança

### Monitoramento de Ataques
```php
class SecurityMonitor
{
    public static function logSecurityEvent(string $type, array $data): void
    {
        $event = [
            'timestamp' => date('c'),
            'type' => $type,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'data' => $data
        ];

        // Log para análise
        error_log(json_encode($event), 3, '/var/log/security.log');

        // Alertas em tempo real para ataques críticos
        if (in_array($type, ['SQL_INJECTION', 'XSS_ATTEMPT', 'BRUTE_FORCE'])) {
            self::sendSecurityAlert($event);
        }
    }
}
```

## 📋 Checklist de Segurança

### ✅ Implementado
- [x] CORS configurável e seguro
- [x] CSRF protection automático
- [x] XSS sanitization integrada
- [x] JWT authentication robusto
- [x] Rate limiting inteligente
- [x] Security headers automáticos
- [x] Input validation/sanitization
- [x] SQL injection prevention
- [x] Session management seguro
- [x] Error handling sem vazamentos

### 🔧 Configurável
- [x] Allowed origins customizáveis
- [x] Rate limits por usuário/IP
- [x] CSP policies configuráveis
- [x] Authentication excludes
- [x] Custom security headers
- [x] Token blacklisting
- [x] Security monitoring hooks

### 📊 Monitorado
- [x] Failed authentication attempts
- [x] Rate limit violations
- [x] CORS violations
- [x] CSRF token mismatches
- [x] XSS attempt detection
- [x] Suspicious request patterns

## 🏆 Certificações e Compliance

- ✅ **OWASP Top 10** protection
- ✅ **PCI DSS** compatible
- ✅ **GDPR** privacy ready
- ✅ **SOC 2** security controls
- ✅ **ISO 27001** aligned

> ⚠️ Os testes de middlewares legados foram movidos para `tests/Core/legacy/` e não são mais mantidos. Todos os novos testes e implementações devem seguir o padrão PSR-15.
