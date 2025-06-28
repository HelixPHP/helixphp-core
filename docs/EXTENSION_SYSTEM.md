# Sistema de Extensões e Plugins - Express-PHP v2.1.0

O Express-PHP v2.1.0 possui um sistema robusto de extensões e plugins que permite adicionar funcionalidades de forma modular e desacoplada. Este documento descreve como criar, registrar e usar extensões.

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Arquitetura](#arquitetura)
3. [Criando Extensões](#criando-extensões)
4. [Sistema de Hooks](#sistema-de-hooks)
5. [Auto-Discovery](#auto-discovery)
6. [Configuração](#configuração)
7. [Exemplos Práticos](#exemplos-práticos)
8. [Melhores Práticas](#melhores-práticas)

## Visão Geral

O sistema de extensões do Express-PHP fornece:

- **Service Providers**: Sistema PSR-11 para registro de serviços
- **Hook System**: Sistema de hooks WordPress-style para extensibilidade
- **Auto-Discovery**: Descoberta automática de extensões via Composer
- **Extension Manager**: Gerenciamento centralizado de extensões
- **Event System**: Sistema de eventos PSR-14 para comunicação entre componentes

## Arquitetura

### Componentes Principais

#### 1. ExtensionManager
```php
namespace Express\Providers;

class ExtensionManager
{
    public function discoverProviders(): array
    public function registerExtension(string $name, string $provider, array $config = []): void
    public function enableExtension(string $name): bool
    public function disableExtension(string $name): bool
    public function getExtensions(): array
}
```

#### 2. HookManager
```php
namespace Express\Support;

class HookManager
{
    public function addAction(string $hook, callable $callback, int $priority = 10): void
    public function addFilter(string $hook, callable $callback, int $priority = 10): void
    public function doAction(string $hook, array $context = []): void
    public function applyFilter(string $hook, mixed $data, array $context = []): mixed
}
```

#### 3. ServiceProvider Base
```php
namespace Express\Providers;

abstract class ServiceProvider
{
    abstract public function register(): void;
    public function boot(): void;
    public function provides(): array;
}
```

## Criando Extensões

### 1. Service Provider Básico

```php
<?php

namespace MyVendor\MyExtension;

use Express\Providers\ServiceProvider;

class MyExtensionProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registrar serviços no container
        $this->app->singleton('my_service', function ($app) {
            return new MyService($app->make('config'));
        });
    }

    public function boot(): void
    {
        // Configurar hooks, eventos, middlewares
        $this->registerHooks();
        $this->registerMiddleware();
    }

    private function registerHooks(): void
    {
        $this->app->addAction('request.received', [$this, 'onRequestReceived']);
        $this->app->addFilter('response.data', [$this, 'filterResponseData']);
    }

    public function onRequestReceived(array $context): void
    {
        // Lógica executada a cada requisição
        $this->app->make('my_service')->trackRequest($context);
    }

    public function filterResponseData(array $data, array $context): array
    {
        // Modificar dados de resposta
        $data['_extension'] = 'MyExtension';
        return $data;
    }
}
```

### 2. Extensão com Middleware

```php
<?php

namespace MyVendor\MyExtension;

use Express\Providers\ServiceProvider;
use Express\Middleware\Core\BaseMiddleware;

class SecurityExtensionProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('security_scanner', SecurityScanner::class);
    }

    public function boot(): void
    {
        // Adicionar middleware de segurança automaticamente
        $this->app->addFilter('request.middleware', function ($middlewares, $context) {
            array_unshift($middlewares, new SecurityMiddleware(
                $this->app->make('security_scanner')
            ));
            return $middlewares;
        });
    }
}

class SecurityMiddleware extends BaseMiddleware
{
    private SecurityScanner $scanner;

    public function __construct(SecurityScanner $scanner)
    {
        $this->scanner = $scanner;
    }

    public function handle($request, $response, callable $next)
    {
        if (!$this->scanner->isSafe($request)) {
            return $response->status(403)->json(['error' => 'Security threat detected']);
        }

        return $next($request, $response);
    }
}
```

## Sistema de Hooks

### Hooks de Ação (Actions)
Executam código sem modificar dados:

```php
// Registrar listener
$app->addAction('user.login', function ($context) {
    $logger = app('logger');
    $logger->info('User logged in', [
        'user_id' => $context['user_id'],
        'ip' => $context['ip']
    ]);
}, 10);

// Disparar hook
$app->doAction('user.login', [
    'user_id' => 123,
    'ip' => '192.168.1.1'
]);
```

### Hooks de Filtro (Filters)
Modificam e retornam dados:

```php
// Registrar filtro
$app->addFilter('user.profile_data', function ($data, $context) {
    // Adicionar dados calculados
    $data['age'] = date('Y') - date('Y', strtotime($data['birth_date']));
    return $data;
}, 10);

// Aplicar filtro
$profileData = $app->applyFilter('user.profile_data', $originalData, [
    'user_id' => 123
]);
```

### Hooks Core Disponíveis

#### Hooks de Aplicação
- `app.booting` - Aplicação iniciando
- `app.booted` - Aplicação iniciada
- `request.received` - Requisição recebida
- `response.sending` - Resposta sendo enviada

#### Hooks de Middleware
- `middleware.before` - Antes do middleware
- `middleware.after` - Depois do middleware
- `request.middleware` - Lista de middlewares (filtro)

#### Hooks de Rota
- `route.matched` - Rota encontrada
- `route.executed` - Rota executada

## Auto-Discovery

### Configuração no composer.json

Para que sua extensão seja descoberta automaticamente:

```json
{
    "name": "vendor/express-php-analytics",
    "type": "express-php-extension",
    "require": {
        "cafernandes/express-php": "^2.1"
    },
    "extra": {
        "express-php": {
            "providers": [
                "Vendor\\Analytics\\ExpressServiceProvider"
            ]
        }
    },
    "autoload": {
        "psr-4": {
            "Vendor\\Analytics\\": "src/"
        }
    }
}
```

### Convenções de Nomenclatura

O sistema também tenta descobrir automaticamente providers seguindo convenções:

- `Vendor\Package\ExpressServiceProvider`
- `Vendor\Package\ServiceProvider`
- `Vendor\Package\Providers\ExpressServiceProvider`
- `Vendor\Package\Providers\ServiceProvider`

## Configuração

### config/app.php

```php
return [
    // ... outras configurações

    // Configuração de extensões
    'extensions' => [
        // Auto-discovery ativo
        'auto_discover_providers' => true,

        // Extensões manuais
        'analytics' => [
            'provider' => 'MyVendor\\Analytics\\ExpressServiceProvider',
            'config' => [
                'api_key' => env('ANALYTICS_API_KEY'),
                'enabled' => env('ANALYTICS_ENABLED', true)
            ]
        ],

        'security_scanner' => [
            'provider' => 'MyVendor\\Security\\SecurityProvider',
            'config' => [
                'strict_mode' => env('SECURITY_STRICT', false)
            ]
        ]
    ],

    // Configuração de hooks
    'hooks' => [
        'enabled' => true,
        'core_hooks' => [
            'app.booting',
            'app.booted',
            'request.received',
            'response.sending'
        ]
    ],

    'auto_discover_providers' => true,
];
```

## Exemplos Práticos

### 1. Extensão de Analytics

```php
<?php

namespace MyVendor\Analytics;

use Express\Providers\ServiceProvider;

class AnalyticsProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('analytics', function ($app) {
            $config = $app->make('config')->get('extensions.analytics.config', []);
            return new AnalyticsService($config);
        });
    }

    public function boot(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $analytics = $this->app->make('analytics');

        // Track todas as requisições
        $this->app->addAction('request.received', function ($context) use ($analytics) {
            $analytics->track('page_view', [
                'url' => $context['request']->path,
                'method' => $context['request']->method,
                'user_agent' => $context['request']->getHeader('User-Agent'),
                'ip' => $context['request']->getClientIP()
            ]);
        });

        // Track erros
        $this->app->addAction('error.occurred', function ($context) use ($analytics) {
            $analytics->track('error', [
                'message' => $context['error']->getMessage(),
                'code' => $context['error']->getCode(),
                'file' => $context['error']->getFile(),
                'line' => $context['error']->getLine()
            ]);
        });
    }

    private function isEnabled(): bool
    {
        return $this->app->make('config')->get('extensions.analytics.config.enabled', true);
    }
}

class AnalyticsService
{
    private array $config;
    private array $events = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function track(string $event, array $data = []): void
    {
        $this->events[] = [
            'event' => $event,
            'data' => $data,
            'timestamp' => microtime(true),
            'session_id' => session_id()
        ];

        // Enviar para serviço externo se configurado
        if (!empty($this->config['api_key'])) {
            $this->sendToExternalService($event, $data);
        }
    }

    public function getEvents(): array
    {
        return $this->events;
    }

    private function sendToExternalService(string $event, array $data): void
    {
        // Implementar envio para Google Analytics, Mixpanel, etc.
    }
}
```

### 2. Extensão de Cache

```php
<?php

namespace MyVendor\Cache;

use Express\Providers\ServiceProvider;

class CacheProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('cache', function ($app) {
            $config = $app->make('config')->get('extensions.cache.config', []);
            return new CacheService($config);
        });
    }

    public function boot(): void
    {
        $cache = $this->app->make('cache');

        // Cache de rotas
        $this->app->addFilter('route.response', function ($response, $context) use ($cache) {
            $key = $this->generateCacheKey($context['request']);

            if ($cached = $cache->get($key)) {
                return $cached;
            }

            // Cache para próxima requisição
            $cache->set($key, $response, 300); // 5 minutos

            return $response;
        });

        // Invalidar cache em mudanças
        $this->app->addAction('data.updated', function ($context) use ($cache) {
            $cache->invalidatePattern($context['pattern'] ?? '*');
        });
    }

    private function generateCacheKey($request): string
    {
        return md5($request->method . ':' . $request->path . ':' . serialize($request->query));
    }
}
```

### 3. Extensão de Rate Limiting Avançado

```php
<?php

namespace MyVendor\RateLimit;

use Express\Providers\ServiceProvider;
use Express\Middleware\Core\BaseMiddleware;

class RateLimitProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('rate_limiter', function ($app) {
            return new AdvancedRateLimiter($app->make('cache'));
        });
    }

    public function boot(): void
    {
        // Adicionar middleware automaticamente
        $this->app->addFilter('request.middleware', function ($middlewares, $context) {
            $rateLimiter = $this->app->make('rate_limiter');
            array_unshift($middlewares, new RateLimitMiddleware($rateLimiter));
            return $middlewares;
        });
    }
}

class AdvancedRateLimiter
{
    public function __construct(private CacheService $cache) {}

    public function isAllowed(string $key, int $maxRequests, int $windowSeconds): bool
    {
        $current = $this->cache->get($key, 0);

        if ($current >= $maxRequests) {
            return false;
        }

        $this->cache->increment($key, 1, $windowSeconds);
        return true;
    }

    public function getRemainingAttempts(string $key, int $maxRequests): int
    {
        $current = $this->cache->get($key, 0);
        return max(0, $maxRequests - $current);
    }
}
```

## Melhores Práticas

### 1. Nomenclatura e Estrutura

```
vendor/express-php-extension/
├── src/
│   ├── ExpressServiceProvider.php
│   ├── Services/
│   ├── Middleware/
│   └── Events/
├── config/
│   └── extension.php
├── tests/
└── composer.json
```

### 2. Configuração Flexível

```php
class MyExtensionProvider extends ServiceProvider
{
    public function register(): void
    {
        // Usar configuração da aplicação ou padrões
        $config = array_merge(
            $this->getDefaultConfig(),
            $this->app->make('config')->get('extensions.my_extension.config', [])
        );

        $this->app->singleton('my_service', function () use ($config) {
            return new MyService($config);
        });
    }

    private function getDefaultConfig(): array
    {
        return [
            'enabled' => true,
            'debug' => false,
            'timeout' => 30
        ];
    }
}
```

### 3. Hooks Bem Documentados

```php
class MyExtensionProvider extends ServiceProvider
{
    public function boot(): void
    {
        /**
         * Hook: my_extension.before_process
         *
         * Executado antes do processamento principal
         *
         * @param array $context {
         *     @type mixed $data Dados sendo processados
         *     @type string $type Tipo de processamento
         *     @type int $user_id ID do usuário (se autenticado)
         * }
         */
        $this->app->doAction('my_extension.before_process', $context);

        /**
         * Filter: my_extension.process_data
         *
         * Permite modificar dados antes do processamento
         *
         * @param mixed $data Dados originais
         * @param array $context Contexto do processamento
         * @return mixed Dados modificados
         */
        $data = $this->app->applyFilter('my_extension.process_data', $data, $context);
    }
}
```

### 4. Testes Abrangentes

```php
class MyExtensionTest extends TestCase
{
    public function testExtensionRegistration(): void
    {
        $app = new Application();
        $app->register(MyExtensionProvider::class);
        $app->boot();

        $this->assertTrue($app->has('my_service'));
        $this->assertInstanceOf(MyService::class, $app->make('my_service'));
    }

    public function testHooksAreRegistered(): void
    {
        $app = new Application();
        $app->register(MyExtensionProvider::class);
        $app->boot();

        $hooks = $app->hooks();
        $this->assertTrue($hooks->hasListeners('my_extension.before_process'));
    }
}
```

### 5. Documentação Completa

```php
/**
 * My Extension for Express-PHP
 *
 * Provides advanced analytics and monitoring capabilities.
 *
 * ## Installation
 *
 * ```bash
 * composer require vendor/express-php-analytics
 * ```
 *
 * ## Configuration
 *
 * Add to config/app.php:
 *
 * ```php
 * 'extensions' => [
 *     'analytics' => [
 *         'provider' => 'Vendor\\Analytics\\ExpressServiceProvider',
 *         'config' => [
 *             'api_key' => env('ANALYTICS_API_KEY'),
 *             'enabled' => env('ANALYTICS_ENABLED', true)
 *         ]
 *     ]
 * ]
 * ```
 *
 * ## Usage
 *
 * ```php
 * $analytics = app('analytics');
 * $analytics->track('custom_event', ['key' => 'value']);
 * ```
 *
 * ## Hooks
 *
 * - `analytics.before_track` - Before tracking an event
 * - `analytics.after_track` - After tracking an event
 * - `analytics.event_data` - Filter event data before tracking
 */
class AnalyticsProvider extends ServiceProvider
{
    // ...
}
```

## Conclusão

O sistema de extensões do Express-PHP v2.1.0 fornece uma base sólida e flexível para criar aplicações modulares e extensíveis. Com suporte para auto-discovery, hooks, events e service providers, é possível criar extensões robustas que se integram perfeitamente com o framework.

As extensões podem ser distribuídas via Composer/Packagist e descobertas automaticamente, facilitando a criação de um ecossistema de plugins robusto para o Express-PHP.
