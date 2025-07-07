<?php

/**
 * Exemplo prático - Sistema de Extensões e Plugins
 *
 * Demonstra como criar e usar extensões/plugins no PivotPHP v2.1.0
 * com auto-discovery, hooks e sistema robusto de extensibilidade.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Providers\ServiceProvider;
use PivotPHP\Core\Support\HookManager;
use PivotPHP\Core\Events\Hook;

// =====================================
// EXEMPLO 1: EXTENSÃO PERSONALIZADA
// =====================================

/**
 * Exemplo de extensão personalizada
 */
class CustomAnalyticsProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('analytics', function () {
            return new class {
                private array $events = [];

                public function track(string $event, array $data = []): void
                {
                    $this->events[] = [
                        'event' => $event,
                        'data' => $data,
                        'timestamp' => time()
                    ];
                    echo "📊 Analytics: Tracked event '{$event}'\n";
                }

                public function getEvents(): array
                {
                    return $this->events;
                }
            };
        });
    }

    public function boot(): void
    {
        // Registrar hooks para tracking automático
        $analytics = $this->app->make('analytics');

        $this->app->addAction('request.received', function ($context) use ($analytics) {
            $analytics->track('page_view', [
                'url' => $context['request']->path ?? '/',
                'method' => $context['request']->method ?? 'GET'
            ]);
        });

        $this->app->addAction('user.login', function ($context) use ($analytics) {
            $analytics->track('user_login', [
                'user_id' => $context['user_id'] ?? null
            ]);
        });
    }
}

/**
 * Exemplo de extensão de segurança
 */
class SecurityEnhancementProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('security_scanner', function () {
            return new class {
                public function scanRequest($request): array
                {
                    $threats = [];

                    // Simulação de scan de segurança
                    if (isset($request->body) && is_string($request->body)) {
                        if (preg_match('/<script|javascript:|eval\(|on\w+=/i', $request->body)) {
                            $threats[] = 'Potential XSS detected';
                        }
                    }

                    return $threats;
                }
            };
        });
    }

    public function boot(): void
    {
        $scanner = $this->app->make('security_scanner');

        // Adicionar filtro de segurança antes do processamento da requisição
        $this->app->addFilter('request.middleware', function ($middlewares, $context) use ($scanner) {
            // Adicionar middleware de segurança personalizado
            array_unshift($middlewares, function ($request, $response, $next) use ($scanner) {
                $threats = $scanner->scanRequest($request);

                if (!empty($threats)) {
                    echo "🛡️ Security: Threats detected - " . implode(', ', $threats) . "\n";
                    $response->status(403)->json(['error' => 'Security threat detected']);
                    return;
                }

                return $next($request, $response);
            });

            return $middlewares;
        });
    }
}

// =====================================
// CONFIGURAÇÃO DA APLICAÇÃO
// =====================================

$app = new Application(__DIR__);

echo "🚀 PivotPHP Extension System Demo\n";
echo "=====================================\n\n";

// =====================================
// REGISTRO MANUAL DE EXTENSÕES
// =====================================

echo "📦 Registrando extensões manualmente...\n";

// Registrar extensão de analytics
$app->registerExtension('analytics', CustomAnalyticsProvider::class, [
    'enabled' => true,
    'auto_track' => true
]);

// Registrar extensão de segurança
$app->registerExtension('security_enhancement', SecurityEnhancementProvider::class, [
    'strict_mode' => true,
    'real_time_scan' => true
]);

// =====================================
// HOOKS E FILTROS PERSONALIZADOS
// =====================================

echo "🎣 Configurando hooks personalizados...\n";

// Hook de ação - executado quando algo acontece
$app->addAction('app.request_processed', function ($context) {
    echo "🔄 Hook Action: Request processed for {$context['url']}\n";
});

// Hook de filtro - modifica dados
$app->addFilter('response.headers', function ($headers, $context) {
    echo "🔧 Hook Filter: Adding custom headers\n";
    $headers['X-Powered-By'] = 'PivotPHP-Extended';
    $headers['X-Extension-System'] = 'Active';
    return $headers;
}, 5);

// Hook com prioridade alta
$app->addFilter('response.data', function ($data, $context) {
    if (is_array($data)) {
        $data['_meta'] = [
            'framework' => 'PivotPHP',
            'version' => Application::VERSION,
            'extensions_active' => true,
            'timestamp' => date('c')
        ];
    }
    return $data;
}, 1);

// =====================================
// ROTAS DE DEMONSTRAÇÃO
// =====================================

// Inicializar aplicação
$app->boot();

echo "✅ Aplicação inicializada com sistema de extensões\n\n";

// Rota principal - demonstra hooks em ação
$app->get('/', function ($req, $res) use ($app) {
    echo "📍 Processando rota principal...\n";

    // Disparar hooks personalizados
    $app->doAction('app.request_processed', [
        'url' => '/',
        'timestamp' => time()
    ]);

    // Aplicar filtros
    $responseData = [
        'message' => 'PivotPHP Extension System',
        'status' => 'active',
        'features' => [
            'auto_discovery' => true,
            'hook_system' => true,
            'extension_management' => true
        ]
    ];

    // Aplicar filtro aos dados
    $responseData = $app->applyFilter('response.data', $responseData, [
        'route' => '/',
        'user' => null
    ]);

    // Aplicar filtro aos headers
    $headers = $app->applyFilter('response.headers', [], [
        'route' => '/',
        'response_type' => 'json'
    ]);

    // Aplicar headers
    foreach ($headers as $name => $value) {
        $res->header($name, $value);
    }

    $res->json($responseData);
});

// Rota para estatísticas de extensões
$app->get('/extensions/stats', function ($req, $res) use ($app) {
    echo "📊 Gerando estatísticas de extensões...\n";

    $stats = $app->getExtensionStats();

    $res->json([
        'extension_stats' => $stats,
        'analytics_events' => $app->make('analytics')->getEvents() ?? [],
        'available_hooks' => $app->hooks()->getRegisteredHooks()
    ]);
});

// Rota protegida - demonstra middleware de segurança
$app->post('/secure-endpoint', function ($req, $res) use ($app) {
    echo "🔒 Processando endpoint seguro...\n";

    // Simular login para analytics
    $app->doAction('user.login', [
        'user_id' => 123,
        'timestamp' => time()
    ]);

    $res->json([
        'message' => 'Secure endpoint accessed successfully',
        'user_id' => 123,
        'security_checks' => 'passed'
    ]);
});

// =====================================
// DEMONSTRAÇÃO DE USO
// =====================================

echo "🎯 Demonstrando funcionalidades...\n\n";

// Simular requisição para rota principal
echo "1. Simulando GET /\n";
$mockRequest = (object)[
    'method' => 'GET',
    'path' => '/',
    'headers' => (object)[],
    'query' => (object)[],
    'body' => null
];

$mockResponse = new class {
    private array $headers = [];
    private int $statusCode = 200;
    private mixed $body = null;

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function json(array $data): self
    {
        $this->body = json_encode($data, JSON_PRETTY_PRINT);
        echo "📤 Response: {$this->body}\n";
        return $this;
    }
};

// Processar rota
$handler = $app->get('/', $mockRequest) ?? null;
if ($handler && is_callable($handler)) {
    $handler($mockRequest, $mockResponse);
}

echo "\n2. Simulando POST /secure-endpoint com possível XSS\n";
$xssRequest = (object)[
    'method' => 'POST',
    'path' => '/secure-endpoint',
    'headers' => (object)[],
    'query' => (object)[],
    'body' => '<script>alert("xss")</script>'
];

// Esta requisição seria bloqueada pelo middleware de segurança

echo "\n📈 Estatísticas finais:\n";
$finalStats = $app->getExtensionStats();
echo "- Extensões registradas: {$finalStats['extensions']['total']}\n";
echo "- Extensões ativas: {$finalStats['extensions']['enabled']}\n";
echo "- Hooks registrados: {$finalStats['hooks']['hooks']}\n";
echo "- Listeners totais: {$finalStats['hooks']['listeners']}\n";

echo "\n🎉 Demo concluída! O sistema de extensões está funcionando corretamente.\n";
echo "\n📚 Como usar em projetos reais:\n";
echo "1. Crie um ServiceProvider para sua extensão\n";
echo "2. Registre via config/app.php ou manualmente\n";
echo "3. Use hooks para pontos de extensão\n";
echo "4. Publique no Packagist com 'extra.express-php.providers'\n";
echo "5. Auto-discovery detectará automaticamente\n\n";

// =====================================
// EXEMPLO DE CONFIGURAÇÃO NO COMPOSER.JSON
// =====================================

echo "📋 Exemplo de composer.json para extensão:\n";
echo <<<JSON
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
JSON;

echo "\n\n✅ Sistema de extensões PivotPHP demonstrado com sucesso!\n";
