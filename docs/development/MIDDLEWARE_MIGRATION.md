# Migração de Middleware - Express PHP Framework

## 🔄 Guia de Migração

Este documento orienta a migração de middlewares de outros frameworks para o Express PHP Framework e vice-versa.

## 🎯 Visão Geral

O Express PHP Framework segue o padrão de middleware similar ao Express.js e outros frameworks modernos, facilitando a migração de código existente.

## 📋 Estrutura de Middleware

### Formato Padrão
```php
interface MiddlewareInterface
{
    public function handle($request, $response, callable $next);
}
```

### Implementação Básica
```php
class ExampleMiddleware implements MiddlewareInterface
{
    public function handle($request, $response, callable $next)
    {
        // Pre-processing
        // ... sua lógica antes da execução ...

        // Chamar próximo middleware
        $result = $next($request, $response);

        // Post-processing
        // ... sua lógica após a execução ...

        return $result;
    }
}
```

## 🔄 Migrando de Outros Frameworks

### 1. Laravel Middleware → Express PHP

#### Laravel (Antes)
```php
<?php
namespace App\Http\Middleware;

use Closure;

class CustomMiddleware
{
    public function handle($request, Closure $next)
    {
        // Pre-processing
        $request->merge(['custom' => 'value']);

        $response = $next($request);

        // Post-processing
        $response->header('X-Custom', 'Laravel');

        return $response;
    }
}
```

#### Express PHP (Depois)
```php
<?php
namespace Express\Middleware\Custom;

use Express\Middleware\Core\BaseMiddleware;

class CustomMiddleware extends BaseMiddleware
{
    public function handle($request, $response, callable $next)
    {
        // Pre-processing (mesmo conceito)
        $request->custom = 'value';

        $result = $next($request, $response);

        // Post-processing (ajuste na sintaxe)
        $response->header('X-Custom', 'Express-PHP');

        return $result;
    }
}
```

### 2. Slim Framework → Express PHP

#### Slim (Antes)
```php
class AuthMiddleware
{
    public function __invoke($request, $handler): ResponseInterface
    {
        $token = $request->getHeaderLine('Authorization');

        if (!$this->validateToken($token)) {
            return new Response(401);
        }

        return $handler->handle($request);
    }
}
```

#### Express PHP (Depois)
```php
class AuthMiddleware extends BaseMiddleware
{
    public function handle($request, $response, callable $next)
    {
        $token = $request->header('Authorization');

        if (!$this->validateToken($token)) {
            return $response->status(401)->json(['error' => 'Unauthorized']);
        }

        return $next($request, $response);
    }
}
```

### 3. Symfony Middleware → Express PHP

#### Symfony (Antes)
```php
class SecurityMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isSecure($request)) {
            throw new AccessDeniedHttpException();
        }

        return $handler->handle($request);
    }
}
```

#### Express PHP (Depois)
```php
class SecurityMiddleware extends BaseMiddleware
{
    public function handle($request, $response, callable $next)
    {
        if (!$this->isSecure($request)) {
            return $response->status(403)->json(['error' => 'Access denied']);
        }

        return $next($request, $response);
    }
}
```

## 🚀 Migrando Para Express PHP

### Step-by-Step Migration

#### 1. Estrutura Base
```php
// Seu middleware existente
class OldMiddleware
{
    public function handle($request, $next)
    {
        // lógica existente
    }
}

// Migração para Express PHP
class NewMiddleware extends BaseMiddleware
{
    public function handle($request, $response, callable $next)
    {
        // adaptar lógica existente
        return $next($request, $response);
    }
}
```

#### 2. Adaptação de Request/Response

##### Request Mapping
```php
// Framework anterior → Express PHP
$request->getMethod()           → $request->method
$request->getUri()->getPath()   → $request->path
$request->getQueryParams()      → $request->query
$request->getParsedBody()       → $request->body
$request->getHeaderLine('X')    → $request->header('X')
```

##### Response Mapping
```php
// Framework anterior → Express PHP
$response->withStatus(200)      → $response->status(200)
$response->withHeader('X', 'Y') → $response->header('X', 'Y')
$response->write(json_encode()) → $response->json()
$response->getBody()->write()   → $response->text()
```

#### 3. Error Handling
```php
// Framework anterior
throw new HttpException(401, 'Unauthorized');

// Express PHP
return $response->status(401)->json(['error' => 'Unauthorized']);
```

## 🔧 Padrões de Migração

### 1. Authentication Middleware

#### Padrão Genérico
```php
class AuthMigrationPattern extends BaseMiddleware
{
    public function handle($request, $response, callable $next)
    {
        // 1. Extrair token (adaptar método de extração)
        $token = $this->extractToken($request);

        // 2. Validar (manter lógica de validação)
        if (!$this->validateToken($token)) {
            return $this->unauthorizedResponse($response);
        }

        // 3. Adicionar user ao request (adaptar sintaxe)
        $request->user = $this->getUserFromToken($token);

        // 4. Continue pipeline
        return $next($request, $response);
    }

    private function extractToken($request): ?string
    {
        // Adaptar conforme framework original
        return $request->header('Authorization') ??
               $request->query('token') ??
               $request->body('token');
    }

    private function unauthorizedResponse($response)
    {
        return $response->status(401)->json([
            'error' => 'Unauthorized',
            'message' => 'Valid token required'
        ]);
    }
}
```

### 2. CORS Middleware

#### Migração de CORS Customizado
```php
// Middleware CORS existente
class LegacyCorsMiddleware
{
    public function handle($request, $next)
    {
        // lógica CORS personalizada
    }
}

// Migração para Express PHP
class MigratedCorsMiddleware extends BaseMiddleware
{
    public function handle($request, $response, callable $next)
    {
        // Opção 1: Usar CORS built-in otimizado
        return CorsMiddleware::create([
            'origins' => $this->getAllowedOrigins(),
            'methods' => $this->getAllowedMethods(),
            'headers' => $this->getAllowedHeaders()
        ])($request, $response, $next);

        // Opção 2: Implementar lógica customizada
        // $this->addCorsHeaders($response);
        // return $next($request, $response);
    }
}
```

### 3. Rate Limiting

#### Pattern de Migração
```php
class RateLimitMigration extends BaseMiddleware
{
    public function handle($request, $response, callable $next)
    {
        $key = $this->generateKey($request);

        if ($this->isLimitExceeded($key)) {
            return $response->status(429)->json([
                'error' => 'Rate limit exceeded',
                'retry_after' => $this->getRetryAfter()
            ]);
        }

        $this->incrementCounter($key);

        return $next($request, $response);
    }

    private function generateKey($request): string
    {
        // Adaptar lógica de geração de chave
        return 'rate_limit:' . ($request->ip() ?? 'unknown');
    }
}
```

## 🛠️ Ferramentas de Migração

### 1. Migration Helper Script
```php
<?php
/**
 * Script para auxiliar migração automática
 */
class MiddlewareMigrationHelper
{
    public static function convertLaravelMiddleware(string $filePath): string
    {
        $content = file_get_contents($filePath);

        // Substituições automáticas
        $replacements = [
            'use Closure;' => 'use Express\Middleware\Core\BaseMiddleware;',
            'public function handle($request, Closure $next)' => 'public function handle($request, $response, callable $next)',
            '$response = $next($request);' => '$result = $next($request, $response);',
            'return $response;' => 'return $result;'
        ];

        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }

        return $content;
    }
}
```

### 2. Compatibility Layer
```php
<?php
/**
 * Layer de compatibilidade para facilitar migração
 */
class CompatibilityLayer
{
    public static function wrapLegacyMiddleware(callable $legacy): callable
    {
        return function($request, $response, callable $next) use ($legacy) {
            // Adaptar request para formato legado
            $legacyRequest = new LegacyRequestAdapter($request);

            // Criar handler que simula framework anterior
            $legacyHandler = function($req) use ($next, $request, $response) {
                return $next($request, $response);
            };

            // Executar middleware legado
            $result = $legacy($legacyRequest, $legacyHandler);

            // Adaptar resposta de volta
            return $this->adaptResponse($result, $response);
        };
    }
}
```

## 📊 Checklist de Migração

### ✅ Pré-Migração
- [ ] Identificar todos os middlewares existentes
- [ ] Documentar dependências e configurações
- [ ] Criar testes para comportamento atual
- [ ] Avaliar performance baseline

### ✅ Durante Migração
- [ ] Manter interface pública consistente
- [ ] Adaptar sintaxe de request/response
- [ ] Converter error handling
- [ ] Atualizar configurações
- [ ] Migrar testes

### ✅ Pós-Migração
- [ ] Executar testes completos
- [ ] Validar performance
- [ ] Verificar compatibilidade
- [ ] Atualizar documentação

## 🔍 Troubleshooting

### Problemas Comuns

#### 1. Response não retornado
```php
// ❌ Problema
public function handle($request, $response, callable $next)
{
    $next($request, $response); // Não retorna o resultado
}

// ✅ Solução
public function handle($request, $response, callable $next)
{
    return $next($request, $response); // Sempre retornar
}
```

#### 2. Request modificado incorretamente
```php
// ❌ Problema - tentar modificar objeto imutável
$request->setMethod('POST');

// ✅ Solução - usar propriedades públicas
$request->method = 'POST';
```

#### 3. Headers não aplicados
```php
// ❌ Problema - ordem incorreta
$result = $next($request, $response);
$response->header('X-Custom', 'value'); // Muito tarde

// ✅ Solução - aplicar antes ou durante
$response->header('X-Custom', 'value');
return $next($request, $response);
```

## 📚 Recursos Adicionais

### Exemplos Completos
- [examples/migration/](../../examples/migration/) - Exemplos de migração
- [tests/Migration/](../../tests/Migration/) - Testes de middlewares migrados

### Documentação Relacionada
- [Middleware Architecture](../pt-br/objetos.md#middleware)
- [Security Implementation](SECURITY_IMPLEMENTATION.md)
- [Development Guide](DEVELOPMENT.md)

### Suporte
- GitHub Issues para dúvidas específicas
- Discord community para discussões
- Email: migration-help@express-php.com
