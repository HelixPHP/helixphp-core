# 🧩 Sistema de Extensões e Plugins

Guia completo do sistema de extensões do PivotPHP, incluindo criação, configuração, descoberta automática e padrões avançados.

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Arquitetura do Sistema](#arquitetura-do-sistema)
- [Criando Extensões](#criando-extensões)
- [Service Providers](#service-providers)
- [Sistema de Hooks](#sistema-de-hooks)
- [Auto-Discovery](#auto-discovery)
- [Eventos PSR-14](#eventos-psr-14)
- [Padrões e Boas Práticas](#padrões-e-boas-práticas)
- [Exemplos Práticos](#exemplos-práticos)
- [Testing](#testing)

## 🔍 Visão Geral

O PivotPHP possui um sistema de extensões robusto que permite modularizar funcionalidades, criar plugins reutilizáveis e estender o framework de forma limpa e padronizada.

### Componentes Principais

- **Service Providers** - Registram serviços no container
- **Hook System** - Sistema de ganchos para extensibilidade
- **Event System** - Eventos PSR-14 para comunicação
- **Auto-Discovery** - Descoberta automática de extensões
- **Extension Manager** - Gerenciamento centralizado

### Fluxo de Extensão

```
1. Auto-Discovery → 2. Service Provider → 3. Hooks/Events → 4. Funcionalidade
     ↓                      ↓                    ↓              ↓
   composer.json         register()           addFilter()    Middleware/Service
```

## 🏗️ Arquitetura do Sistema

### Container e Providers

```php
// Providers são registrados automaticamente
$app = new Application();

// Os providers padrão são carregados:
// - ContainerServiceProvider
// - EventServiceProvider
// - LoggingServiceProvider
// - HookServiceProvider
// - ExtensionServiceProvider
```

### Service Provider Base

```php
<?php

namespace PivotPHP\Core\Providers;

abstract class ServiceProvider
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Registra serviços no container
     */
    abstract public function register(): void;

    /**
     * Inicializa serviços após registro
     */
    public function boot(): void
    {
        // Implementação opcional
    }

    /**
     * Lista de serviços fornecidos
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Indica se o provider deve ser adiado
     */
    public function isDeferred(): bool
    {
        return false;
    }
}
```

## 🚀 Criando Extensões

### 1. Estrutura Básica de Extensão

```php
<?php

namespace MyVendor\MyExtension;

use PivotPHP\Core\Providers\ServiceProvider;

class MyExtensionProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registrar serviços no container
        $this->app->singleton('my_service', MyService::class);

        // Registrar configurações
        $this->app->instance('my_config', [
            'option1' => 'value1',
            'option2' => true
        ]);
    }

    public function boot(): void
    {
        // Inicialização após todos os providers
        $this->registerMiddlewares();
        $this->registerRoutes();
        $this->registerEventListeners();
    }

    private function registerMiddlewares(): void
    {
        // Adicionar middleware automaticamente
        $this->app->addFilter('middleware.stack', function($middlewares) {
            array_unshift($middlewares, new MyCustomMiddleware());
            return $middlewares;
        });
    }

    private function registerRoutes(): void
    {
        // Registrar rotas da extensão
        $this->app->group('/extension', function() {
            $this->app->get('/status', 'MyExtension\\StatusController@index');
        });
    }

    private function registerEventListeners(): void
    {
        // Registrar listeners de eventos
        $this->app->addListener('request.received', function($event) {
            // Lógica do listener
        });
    }
}
```

### 2. Extensão com Middleware Customizado

```php
<?php

namespace MyVendor\SecurityExtension;

use PivotPHP\Core\Providers\ServiceProvider;
use PivotPHP\Core\Middleware\Core\BaseMiddleware;

class SecurityExtensionProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('security_scanner', SecurityScanner::class);
        $this->app->singleton('threat_detector', ThreatDetector::class);
    }

    public function boot(): void
    {
        // Adicionar middleware de segurança automaticamente
        $this->app->addFilter('request.middleware', function($middlewares, $context) {
            array_unshift($middlewares, new SecurityScannerMiddleware(
                $this->app->make('security_scanner')
            ));
            return $middlewares;
        });

        // Hook para análise de ameaças
        $this->app->addFilter('response.before_send', function($response, $request) {
            $threatLevel = $this->app->make('threat_detector')->analyze($request);
            if ($threatLevel > 5) {
                $response->header('X-Threat-Level', $threatLevel);
            }
            return $response;
        });
    }
}

class SecurityScannerMiddleware extends BaseMiddleware
{
    private SecurityScanner $scanner;

    public function __construct(SecurityScanner $scanner)
    {
        $this->scanner = $scanner;
    }

    public function handle($request, $response, callable $next)
    {
        // Escanear requisição antes do processamento
        $scanResult = $this->scanner->scan($request);

        if ($scanResult->isThreat()) {
            return $response->status(403)->json([
                'error' => 'Security threat detected',
                'code' => $scanResult->getThreatCode()
            ]);
        }

        // Adicionar resultado do scan ao request
        $request->setAttribute('security_scan', $scanResult);

        return $next($request, $response);
    }
}
```

### 3. Extensão de API Externa

```php
<?php

namespace MyVendor\PaymentExtension;

use PivotPHP\Core\Providers\ServiceProvider;

class PaymentExtensionProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registrar serviços de pagamento
        $this->app->singleton('payment.gateway', PaymentGateway::class);
        $this->app->singleton('payment.processor', PaymentProcessor::class);

        // Registrar configurações
        $this->app->instance('payment.config', [
            'api_key' => $_ENV['PAYMENT_API_KEY'] ?? '',
            'webhook_secret' => $_ENV['PAYMENT_WEBHOOK_SECRET'] ?? '',
            'test_mode' => $_ENV['APP_ENV'] === 'testing'
        ]);
    }

    public function boot(): void
    {
        $this->registerPaymentRoutes();
        $this->registerPaymentEvents();
        $this->registerPaymentMiddleware();
    }

    private function registerPaymentRoutes(): void
    {
        $this->app->group('/payments', function() {
            // Rotas de API de pagamento
            $this->app->post('/process', 'PaymentController@process');
            $this->app->post('/webhook', 'PaymentController@webhook');
            $this->app->get('/status/{id}', 'PaymentController@status');
        });
    }

    private function registerPaymentEvents(): void
    {
        // Evento quando pagamento é processado
        $this->app->addListener('payment.processed', function($event) {
            // Enviar email de confirmação
            $this->app->make('mailer')->send('payment_confirmation', [
                'user' => $event->getUser(),
                'payment' => $event->getPayment()
            ]);
        });

        // Evento quando pagamento falha
        $this->app->addListener('payment.failed', function($event) {
            // Log do erro e notificação
            $this->app->make('logger')->error('Payment failed', [
                'payment_id' => $event->getPaymentId(),
                'error' => $event->getError()
            ]);
        });
    }

    private function registerPaymentMiddleware(): void
    {
        // Middleware específico para rotas de pagamento
        $this->app->addFilter('group.middleware./payments', function($middlewares) {
            $middlewares[] = new PaymentSecurityMiddleware();
            $middlewares[] = new PaymentRateLimitMiddleware();
            return $middlewares;
        });
    }
}
```

## 🔧 Service Providers

### Provider Avançado com Lazy Loading

```php
<?php

namespace MyVendor\DatabaseExtension;

use PivotPHP\Core\Providers\ServiceProvider;

class DatabaseExtensionProvider extends ServiceProvider
{
    /**
     * Indica que este provider deve ser carregado apenas quando necessário
     */
    public function isDeferred(): bool
    {
        return true;
    }

    /**
     * Lista de serviços que este provider fornece
     */
    public function provides(): array
    {
        return [
            'db',
            'db.connection',
            'db.query_builder',
            'db.migration_runner'
        ];
    }

    public function register(): void
    {
        // Connection factory
        $this->app->singleton('db.connection', function($app) {
            $config = $app->make('db.config');
            return new DatabaseConnection($config);
        });

        // Query builder
        $this->app->singleton('db.query_builder', function($app) {
            return new QueryBuilder($app->make('db.connection'));
        });

        // Alias principal
        $this->app->alias('db', 'db.query_builder');

        // Migration runner
        $this->app->singleton('db.migration_runner', function($app) {
            return new MigrationRunner(
                $app->make('db.connection'),
                $app->make('logger')
            );
        });
    }

    public function boot(): void
    {
        // Registrar comando de migração
        $this->app->addFilter('console.commands', function($commands) {
            $commands['db:migrate'] = new MigrateCommand(
                $this->app->make('db.migration_runner')
            );
            return $commands;
        });

        // Hook para logging de queries em desenvolvimento
        if ($this->app->make('config')->get('app.debug')) {
            $this->app->addFilter('db.query.executed', function($query, $bindings, $time) {
                $this->app->make('logger')->debug('Database Query', [
                    'query' => $query,
                    'bindings' => $bindings,
                    'time' => $time . 'ms'
                ]);
                return $query;
            });
        }
    }
}
```

## 🪝 Sistema de Hooks

### Hooks Disponíveis

```php
// Hooks de aplicação
$app->addFilter('app.starting', $callback);
$app->addFilter('app.started', $callback);

// Hooks de requisição
$app->addFilter('request.received', $callback);
$app->addFilter('request.before_routing', $callback);
$app->addFilter('request.after_routing', $callback);

// Hooks de middleware
$app->addFilter('middleware.stack', $callback);
$app->addFilter('middleware.before', $callback);
$app->addFilter('middleware.after', $callback);

// Hooks de resposta
$app->addFilter('response.before_send', $callback);
$app->addFilter('response.after_send', $callback);

// Hooks de erro
$app->addFilter('error.handling', $callback);
$app->addFilter('exception.thrown', $callback);
```

### Exemplo de Hook Customizado

```php
<?php

namespace MyVendor\LoggingExtension;

class RequestLoggingProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Log detalhado de todas as requisições
        $this->app->addFilter('request.received', function($request) {
            $this->app->make('logger')->info('Request received', [
                'method' => $request->method(),
                'uri' => $request->uri(),
                'ip' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'timestamp' => microtime(true)
            ]);

            return $request;
        });

        // Medir tempo de resposta
        $this->app->addFilter('response.before_send', function($response, $request) {
            $startTime = $request->getAttribute('start_time') ?? microtime(true);
            $duration = (microtime(true) - $startTime) * 1000;

            $response->header('X-Response-Time', round($duration, 2) . 'ms');

            $this->app->make('logger')->info('Response sent', [
                'status' => $response->getStatusCode(),
                'duration' => $duration,
                'memory_peak' => memory_get_peak_usage(true)
            ]);

            return $response;
        });
    }
}
```

## 🔍 Auto-Discovery

### Configuração no composer.json

```json
{
    "name": "meu-vendor/minha-extensao",
    "type": "express-extension",
    "require": {
        "cafernandes/pivotphp-core": "^2.1"
    },
    "extra": {
        "express": {
            "providers": [
                "MeuVendor\\MinhaExtensao\\ExtensionProvider"
            ],
            "aliases": {
                "MinhaExtensao": "MeuVendor\\MinhaExtensao\\Facade"
            },
            "config": {
                "publish": [
                    "config/minha-extensao.php"
                ]
            }
        }
    },
    "autoload": {
        "psr-4": {
            "MeuVendor\\MinhaExtensao\\": "src/"
        }
    }
}
```

### Discovery Provider

```php
<?php

namespace PivotPHP\Core\Providers;

class ExtensionDiscoveryProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('extension.discovery', ExtensionDiscovery::class);
    }

    public function boot(): void
    {
        // Descobrir e registrar extensões automaticamente
        $discovery = $this->app->make('extension.discovery');
        $extensions = $discovery->discover();

        foreach ($extensions as $extension) {
            $this->app->register($extension['provider']);

            // Registrar aliases se houver
            if (isset($extension['aliases'])) {
                foreach ($extension['aliases'] as $alias => $class) {
                    $this->app->alias($alias, $class);
                }
            }
        }
    }
}

class ExtensionDiscovery
{
    public function discover(): array
    {
        $extensions = [];
        $composerFiles = $this->findComposerFiles();

        foreach ($composerFiles as $file) {
            $composer = json_decode(file_get_contents($file), true);

            if (isset($composer['extra']['express'])) {
                $expressConfig = $composer['extra']['express'];

                if (isset($expressConfig['providers'])) {
                    foreach ($expressConfig['providers'] as $provider) {
                        $extensions[] = [
                            'provider' => $provider,
                            'aliases' => $expressConfig['aliases'] ?? [],
                            'config' => $expressConfig['config'] ?? []
                        ];
                    }
                }
            }
        }

        return $extensions;
    }

    private function findComposerFiles(): array
    {
        // Buscar arquivos composer.json no vendor
        $files = [];
        $vendorDir = __DIR__ . '/../../vendor';

        if (is_dir($vendorDir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($vendorDir)
            );

            foreach ($iterator as $file) {
                if ($file->getFilename() === 'composer.json') {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }
}
```

## 📡 Eventos PSR-14

### Criando Eventos Customizados

```php
<?php

namespace MyVendor\Events;

use PivotPHP\Core\Events\Event;

class UserRegisteredEvent extends Event
{
    private array $user;
    private string $registrationSource;

    public function __construct(array $user, string $source = 'web')
    {
        $this->user = $user;
        $this->registrationSource = $source;
    }

    public function getUser(): array
    {
        return $this->user;
    }

    public function getRegistrationSource(): string
    {
        return $this->registrationSource;
    }

    public function getUserId(): int
    {
        return $this->user['id'] ?? 0;
    }

    public function getUserEmail(): string
    {
        return $this->user['email'] ?? '';
    }
}

class PaymentProcessedEvent extends Event
{
    private array $payment;
    private bool $successful;

    public function __construct(array $payment, bool $successful = true)
    {
        $this->payment = $payment;
        $this->successful = $successful;
    }

    public function getPayment(): array
    {
        return $this->payment;
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function getAmount(): float
    {
        return (float)($this->payment['amount'] ?? 0);
    }
}
```

### Listeners de Eventos

```php
<?php

namespace MyVendor\Listeners;

class WelcomeEmailListener
{
    private $mailer;

    public function __construct($mailer)
    {
        $this->mailer = $mailer;
    }

    public function handle(UserRegisteredEvent $event): void
    {
        $user = $event->getUser();

        $this->mailer->send('welcome', [
            'to' => $user['email'],
            'name' => $user['name'],
            'registration_source' => $event->getRegistrationSource()
        ]);
    }
}

class UserAnalyticsListener
{
    private $analytics;

    public function __construct($analytics)
    {
        $this->analytics = $analytics;
    }

    public function handle(UserRegisteredEvent $event): void
    {
        $this->analytics->track('user_registered', [
            'user_id' => $event->getUserId(),
            'source' => $event->getRegistrationSource(),
            'timestamp' => time()
        ]);
    }
}
```

### Registrando Listeners

```php
<?php

namespace MyVendor\UserExtension;

class UserExtensionProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Registrar listeners usando o container
        $this->app->addListener(UserRegisteredEvent::class, function($event) {
            $this->app->make(WelcomeEmailListener::class)->handle($event);
        });

        $this->app->addListener(UserRegisteredEvent::class, function($event) {
            $this->app->make(UserAnalyticsListener::class)->handle($event);
        });

        // Ou registrar múltiplos listeners de uma vez
        $this->registerEventListeners([
            UserRegisteredEvent::class => [
                WelcomeEmailListener::class,
                UserAnalyticsListener::class,
                UserAuditListener::class
            ],
            PaymentProcessedEvent::class => [
                PaymentEmailListener::class,
                PaymentAnalyticsListener::class
            ]
        ]);
    }

    private function registerEventListeners(array $listeners): void
    {
        foreach ($listeners as $event => $eventListeners) {
            foreach ($eventListeners as $listener) {
                $this->app->addListener($event, function($event) use ($listener) {
                    $this->app->make($listener)->handle($event);
                });
            }
        }
    }
}
```

## 📋 Padrões e Boas Práticas

### 1. Estrutura de Diretórios

```
minha-extensao/
├── src/
│   ├── Providers/
│   │   └── ExtensionProvider.php
│   ├── Middleware/
│   │   └── CustomMiddleware.php
│   ├── Events/
│   │   └── CustomEvent.php
│   ├── Listeners/
│   │   └── CustomListener.php
│   ├── Services/
│   │   └── CustomService.php
│   └── Facades/
│       └── Extension.php
├── config/
│   └── extension.php
├── tests/
│   └── ExtensionTest.php
├── composer.json
└── README.md
```

### 2. Convenções de Nomenclatura

```php
// ✅ Bom
namespace MyVendor\PaymentExtension;
class PaymentExtensionProvider extends ServiceProvider {}

// ✅ Bom - serviços descritivos
$this->app->singleton('payment.gateway', PaymentGateway::class);
$this->app->singleton('payment.processor', PaymentProcessor::class);

// ❌ Evitar
namespace MyVendor\MyExtension;
class MyProvider extends ServiceProvider {}
$this->app->singleton('service', SomeClass::class);
```

### 3. Configuração Externa

```php
// config/payment.php
return [
    'default_gateway' => 'stripe',
    'gateways' => [
        'stripe' => [
            'api_key' => env('STRIPE_API_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET')
        ],
        'paypal' => [
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET')
        ]
    ],
    'features' => [
        'webhooks' => true,
        'recurring' => true,
        'refunds' => true
    ]
];

// No provider
public function register(): void
{
    $this->app->instance('payment.config', require __DIR__ . '/../config/payment.php');
}
```

### 4. Testes de Extensão

```php
<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use MyVendor\PaymentExtension\PaymentExtensionProvider;

class PaymentExtensionTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        $this->app = new Application();
        $this->app->register(PaymentExtensionProvider::class);
    }

    public function testExtensionRegistersServices(): void
    {
        $this->assertTrue($this->app->has('payment.gateway'));
        $this->assertTrue($this->app->has('payment.processor'));
    }

    public function testExtensionRegistersRoutes(): void
    {
        $routes = $this->app->getRouter()->getRoutes();
        $paymentRoutes = array_filter($routes, function($route) {
            return str_starts_with($route['path'], '/payments');
        });

        $this->assertNotEmpty($paymentRoutes);
    }

    public function testPaymentProcessing(): void
    {
        $gateway = $this->app->make('payment.gateway');
        $result = $gateway->process([
            'amount' => 100.00,
            'currency' => 'USD',
            'source' => 'test_token'
        ]);

        $this->assertTrue($result['success']);
    }
}
```

## 💡 Exemplos Práticos

### Extensão de Cache Redis

```php
<?php

namespace MyVendor\RedisExtension;

class RedisExtensionProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('redis', function($app) {
            $config = $app->make('redis.config');
            return new Redis($config);
        });

        $this->app->singleton('cache', function($app) {
            return new RedisCache($app->make('redis'));
        });
    }

    public function boot(): void
    {
        // Adicionar cache automático para GET requests
        $this->app->addFilter('response.before_send', function($response, $request) {
            if ($request->method() === 'GET' && $response->getStatusCode() === 200) {
                $cacheKey = 'route:' . md5($request->uri());
                $this->app->make('cache')->put($cacheKey, $response->getContent(), 300);
            }
            return $response;
        });
    }
}
```

### Extensão de Monitoramento

```php
<?php

namespace MyVendor\MonitoringExtension;

class MonitoringExtensionProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('metrics', MetricsCollector::class);
        $this->app->singleton('monitoring', MonitoringService::class);
    }

    public function boot(): void
    {
        // Coletar métricas de performance
        $this->app->addFilter('request.received', function($request) {
            $request->setAttribute('start_time', microtime(true));
            $this->app->make('metrics')->increment('requests.total');
            return $request;
        });

        $this->app->addFilter('response.before_send', function($response, $request) {
            $duration = microtime(true) - $request->getAttribute('start_time', microtime(true));

            $this->app->make('metrics')->timing('requests.duration', $duration * 1000);
            $this->app->make('metrics')->increment("responses.{$response->getStatusCode()}");

            return $response;
        });

        // Endpoint de métricas
        $this->app->get('/metrics', function($req, $res) {
            $metrics = $this->app->make('metrics')->getAll();
            return $res->json($metrics);
        });
    }
}
```

## 🧪 Testing

### Teste de Service Provider

```php
<?php

namespace Tests\Extensions;

use Tests\TestCase;
use MyVendor\MyExtension\MyExtensionProvider;

class MyExtensionProviderTest extends TestCase
{
    public function testProviderRegistersServices(): void
    {
        $provider = new MyExtensionProvider($this->app);
        $provider->register();

        $this->assertTrue($this->app->has('my_service'));
        $this->assertInstanceOf(MyService::class, $this->app->make('my_service'));
    }

    public function testProviderBootsCorrectly(): void
    {
        $provider = new MyExtensionProvider($this->app);
        $provider->register();
        $provider->boot();

        // Verificar se hooks foram registrados
        $hooks = $this->app->make('hook_manager')->getHooks();
        $this->assertArrayHasKey('my_hook', $hooks);
    }
}
```

### Teste de Eventos

```php
<?php

namespace Tests\Events;

use Tests\TestCase;
use MyVendor\Events\UserRegisteredEvent;

class UserRegisteredEventTest extends TestCase
{
    public function testEventDispatchesCorrectly(): void
    {
        $user = ['id' => 1, 'email' => 'test@example.com', 'name' => 'Test User'];

        $listenerCalled = false;
        $this->app->addListener(UserRegisteredEvent::class, function($event) use (&$listenerCalled) {
            $listenerCalled = true;
            $this->assertEquals(1, $event->getUserId());
        });

        $event = new UserRegisteredEvent($user);
        $this->app->make('event_dispatcher')->dispatch($event);

        $this->assertTrue($listenerCalled);
    }
}
```

---

## 🔗 Links Relacionados

- [Service Providers](../providers/usage.md) - Guia detalhado de Service Providers
- [Extension System](../providers/extension.md) - Criação de extensões avançadas
- [Event System](../events/README.md) - Sistema de eventos PSR-14
- [Hook System](../hooks/README.md) - Sistema de ganchos

## 📚 Recursos Adicionais

- **Auto-Discovery**: Descoberta automática de extensões via composer.json
- **PSR Compliance**: Suporte completo a PSR-14 para eventos
- **Performance**: Sistema otimizado com lazy loading de providers
- **Testing**: Suporte completo para testing de extensões

Para dúvidas ou contribuições, consulte o [guia de contribuição](../../contributing/README.md).
