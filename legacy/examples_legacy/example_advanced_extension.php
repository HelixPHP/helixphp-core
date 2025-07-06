<?php

/**
 * Exemplo Avançado - Criando uma Extensão Completa para Helix-PHP
 *
 * Este exemplo demonstra como criar uma extensão completa com:
 * - Service Provider personalizado
 * - Sistema de configuração
 * - Hooks e filtros avançados
 * - Integração com PSR-14
 * - Middleware personalizado
 * - Auto-discovery via Composer
 * - Documentação e versionamento
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Helix\Core\Application;
use Helix\Providers\ServiceProvider;
use Psr\EventDispatcher\EventDispatcherInterface;

// ============================================
// EXTENSÃO: RATE LIMITING AVANÇADO
// ============================================

/**
 * Rate Limiter Service
 */
class RateLimiter
{
    private array $requests = [];
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'max_requests' => 100,
            'window_seconds' => 3600,
            'block_duration' => 300,
            'whitelist' => [],
            'blacklist' => []
        ], $config);
    }

    public function isAllowed(string $identifier): bool
    {
        // Verificar whitelist
        if (in_array($identifier, $this->config['whitelist'])) {
            return true;
        }

        // Verificar blacklist
        if (in_array($identifier, $this->config['blacklist'])) {
            return false;
        }

        $now = time();
        $windowStart = $now - $this->config['window_seconds'];

        // Limpar requisições antigas
        $this->cleanOldRequests($identifier, $windowStart);

        // Contar requisições na janela atual
        $requestCount = count($this->requests[$identifier] ?? []);

        if ($requestCount >= $this->config['max_requests']) {
            return false;
        }

        // Registrar nova requisição
        $this->requests[$identifier][] = $now;
        return true;
    }

    public function getRemainingRequests(string $identifier): int
    {
        $now = time();
        $windowStart = $now - $this->config['window_seconds'];
        $this->cleanOldRequests($identifier, $windowStart);

        $currentRequests = count($this->requests[$identifier] ?? []);
        return max(0, $this->config['max_requests'] - $currentRequests);
    }

    public function getWindowReset(string $identifier): int
    {
        if (empty($this->requests[$identifier])) {
            return time();
        }

        $oldestRequest = min($this->requests[$identifier]);
        return $oldestRequest + $this->config['window_seconds'];
    }

    private function cleanOldRequests(string $identifier, int $windowStart): void
    {
        if (!isset($this->requests[$identifier])) {
            return;
        }

        $this->requests[$identifier] = array_filter(
            $this->requests[$identifier],
            fn($timestamp) => $timestamp > $windowStart
        );
    }
}

/**
 * Rate Limiting Extension Provider
 */
class RateLimitingProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registrar o serviço de rate limiting
        $this->app->singleton('rate_limiter', function ($app) {
            $config = $app->make('config')['rate_limiting'] ?? [];
            return new RateLimiter($config);
        });

        // Registrar middleware factory
        $this->app->singleton('rate_limit_middleware', function ($app) {
            return function (array $options = []) use ($app) {
                $rateLimiter = $app->make('rate_limiter');
                $logger = $app->has('logger') ? $app->make('logger') : null;

                return function ($request, $response, $next) use ($rateLimiter, $logger, $options) {
                    $identifier = $this->getClientIdentifier($request, $options);

                    if (!$rateLimiter->isAllowed($identifier)) {
                        if ($logger) {
                            $logger->warning("Rate limit exceeded for client: {$identifier}");
                        }

                        return $response
                            ->status(429)
                            ->header('X-RateLimit-Limit', (string)$rateLimiter->config['max_requests'])
                            ->header('X-RateLimit-Remaining', '0')
                            ->header('X-RateLimit-Reset', (string)$rateLimiter->getWindowReset($identifier))
                            ->json(['error' => 'Rate limit exceeded']);
                    }

                    // Adicionar headers informativos
                    $response
                        ->header('X-RateLimit-Limit', (string)$rateLimiter->config['max_requests'])
                        ->header('X-RateLimit-Remaining', (string)$rateLimiter->getRemainingRequests($identifier))
                        ->header('X-RateLimit-Reset', (string)$rateLimiter->getWindowReset($identifier));

                    return $next($request, $response);
                };
            };
        });
    }

    public function boot(): void
    {
        $rateLimiter = $this->app->make('rate_limiter');
        $logger = $this->app->has('logger') ? $this->app->make('logger') : null;

        // Hook para estatísticas de rate limiting
        $this->app->addFilter('app.stats', function ($stats, $context) use ($rateLimiter) {
            $stats['rate_limiting'] = [
                'enabled' => true,
                'max_requests_per_hour' => $rateLimiter->config['max_requests'],
                'window_seconds' => $rateLimiter->config['window_seconds']
            ];
            return $stats;
        });

        // Hook para logging avançado
        $this->app->addAction('request.completed', function ($context) use ($logger) {
            if ($logger && isset($context['rate_limit_info'])) {
                $logger->info('Request completed', [
                    'client' => $context['client_id'],
                    'remaining_requests' => $context['rate_limit_info']['remaining'],
                    'path' => $context['request']->path ?? 'unknown'
                ]);
            }
        });

        // Integração com sistema de eventos PSR-14
        if ($this->app->has(EventDispatcherInterface::class)) {
            $dispatcher = $this->app->make(EventDispatcherInterface::class);

            // Event listener para rate limit violations
            $this->app->addAction('rate_limit.exceeded', function ($context) use ($dispatcher) {
                // Poderia disparar evento personalizado aqui
                echo "🚨 Rate limit exceeded for: {$context['client_id']}\n";
            });
        }

        if ($logger) {
            $logger->info('Rate Limiting Extension initialized');
        }
    }

    private function getClientIdentifier($request, array $options): string
    {
        // Estratégias de identificação do cliente
        $strategy = $options['identifier_strategy'] ?? 'ip';

        switch ($strategy) {
            case 'user_id':
                return $request->user_id ?? $this->getIpAddress($request);

            case 'api_key':
                return $request->headers->get('X-API-Key') ?? $this->getIpAddress($request);

            case 'session':
                return session_id() ?: $this->getIpAddress($request);

            default: // 'ip'
                return $this->getIpAddress($request);
        }
    }

    private function getIpAddress($request): string
    {
        // Tentar diferentes headers para obter o IP real
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = trim(explode(',', $_SERVER[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

// ============================================
// EXTENSÃO: SISTEMA DE CACHE AVANÇADO
// ============================================

/**
 * Cache Manager com múltiplos drivers
 */
class CacheManager
{
    private array $stores = [];
    private string $defaultStore;

    public function __construct(array $config = [])
    {
        $this->defaultStore = $config['default'] ?? 'array';

        // Inicializar stores configurados
        foreach ($config['stores'] ?? [] as $name => $storeConfig) {
            $this->stores[$name] = $this->createStore($storeConfig);
        }

        // Store padrão array se nenhum configurado
        if (empty($this->stores)) {
            $this->stores['array'] = new ArrayCacheStore();
        }
    }

    public function store(?string $name): CacheStoreInterface
    {
        $name = $name ?? $this->defaultStore;

        if (!isset($this->stores[$name])) {
            throw new \InvalidArgumentException("Cache store '{$name}' not configured");
        }

        return $this->stores[$name];
    }

    public function remember(string $key, $value, int $ttl = 3600, ?string $store)
    {
        $cache = $this->store($store);

        if ($cache->has($key)) {
            return $cache->get($key);
        }

        $computed = is_callable($value) ? $value() : $value;
        $cache->put($key, $computed, $ttl);

        return $computed;
    }

    private function createStore(array $config): CacheStoreInterface
    {
        $driver = $config['driver'] ?? 'array';

        switch ($driver) {
            case 'file':
                return new FileCacheStore($config['path'] ?? sys_get_temp_dir());

            case 'array':
            default:
                return new ArrayCacheStore();
        }
    }
}

/**
 * Interface para stores de cache
 */
interface CacheStoreInterface
{
    public function get(string $key, $default = null);
    public function put(string $key, $value, int $ttl = 3600): bool;
    public function has(string $key): bool;
    public function forget(string $key): bool;
    public function flush(): bool;
}

/**
 * Array cache store (em memória)
 */
class ArrayCacheStore implements CacheStoreInterface
{
    private array $storage = [];

    public function get(string $key, $default = null)
    {
        if (!$this->has($key)) {
            return $default;
        }

        $item = $this->storage[$key];

        if ($item['expires_at'] && time() > $item['expires_at']) {
            unset($this->storage[$key]);
            return $default;
        }

        return $item['value'];
    }

    public function put(string $key, $value, int $ttl = 3600): bool
    {
        $this->storage[$key] = [
            'value' => $value,
            'expires_at' => $ttl > 0 ? time() + $ttl : null
        ];

        return true;
    }

    public function has(string $key): bool
    {
        return isset($this->storage[$key]);
    }

    public function forget(string $key): bool
    {
        unset($this->storage[$key]);
        return true;
    }

    public function flush(): bool
    {
        $this->storage = [];
        return true;
    }
}

/**
 * File cache store
 */
class FileCacheStore implements CacheStoreInterface
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = rtrim($path, '/') . '/express-cache';
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    public function get(string $key, $default = null)
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $data = unserialize(file_get_contents($file));

        if ($data['expires_at'] && time() > $data['expires_at']) {
            unlink($file);
            return $default;
        }

        return $data['value'];
    }

    public function put(string $key, $value, int $ttl = 3600): bool
    {
        $file = $this->getFilePath($key);
        $data = [
            'value' => $value,
            'expires_at' => $ttl > 0 ? time() + $ttl : null
        ];

        return file_put_contents($file, serialize($data)) !== false;
    }

    public function has(string $key): bool
    {
        return file_exists($this->getFilePath($key));
    }

    public function forget(string $key): bool
    {
        $file = $this->getFilePath($key);
        return !file_exists($file) || unlink($file);
    }

    public function flush(): bool
    {
        $files = glob($this->path . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }

    private function getFilePath(string $key): string
    {
        return $this->path . '/' . md5($key) . '.cache';
    }
}

/**
 * Cache Extension Provider
 */
class CacheProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('cache', function ($app) {
            $config = $app->make('config')['cache'] ?? [];
            return new CacheManager($config);
        });

        // Helper para cache de responses
        $this->app->singleton('response_cache_middleware', function ($app) {
            return function (array $options = []) use ($app) {
                $cache = $app->make('cache');

                return function ($request, $response, $next) use ($cache, $options) {
                    $cacheKey = $this->generateCacheKey($request, $options);
                    $ttl = $options['ttl'] ?? 300; // 5 minutos padrão

                    // Verificar cache
                    if ($cache->store()->has($cacheKey)) {
                        $cachedResponse = $cache->store()->get($cacheKey);
                        return $response
                            ->header('X-Cache', 'HIT')
                            ->header('Content-Type', $cachedResponse['content_type'])
                            ->send($cachedResponse['body']);
                    }

                    // Processar request
                    $result = $next($request, $response);

                    // Cachear resposta se aplicável
                    if ($this->shouldCache($response, $options)) {
                        $cache->store()->put($cacheKey, [
                            'body' => $response->getBody(),
                            'content_type' => $response->getHeader('Content-Type') ?? 'text/html'
                        ], $ttl);

                        $response->header('X-Cache', 'MISS');
                    }

                    return $result;
                };
            };
        });
    }

    public function boot(): void
    {
        // Hook para estatísticas de cache
        $this->app->addFilter('app.stats', function ($stats, $context) {
            $stats['cache'] = [
                'enabled' => true,
                'default_store' => 'array',
                'stores' => ['array', 'file']
            ];
            return $stats;
        });

        // Hook para limpeza de cache
        $this->app->addAction('cache.clear', function ($context) {
            $cache = $this->app->make('cache');
            if ($cache) {
                $cache->store()->flush();
                echo "🗑️ Cache cleared successfully\n";
            }
        });
    }

    private function generateCacheKey($request, array $options): string
    {
        $parts = [
            $request->method ?? 'GET',
            $request->path ?? '/',
            http_build_query($request->query ?? [])
        ];

        if (!empty($options['vary_headers'])) {
            foreach ($options['vary_headers'] as $header) {
                $parts[] = $request->headers->get($header) ?? '';
            }
        }

        return 'response:' . md5(implode('|', $parts));
    }

    private function shouldCache($response, array $options): bool
    {
        $status = $response->getStatusCode();
        $cacheable = $options['cacheable_status'] ?? [200, 301, 302];

        return in_array($status, $cacheable);
    }
}

// ============================================
// DEMONSTRAÇÃO DO USO DAS EXTENSÕES
// ============================================

echo "🚀 Helix-PHP Advanced Extensions Demo\n";
echo "=======================================\n\n";

// Inicializar aplicação
$app = new Application(__DIR__);

// Configuração avançada
$app->configure([
    'rate_limiting' => [
        'max_requests' => 10,
        'window_seconds' => 60,
        'whitelist' => ['127.0.0.1'],
        'blacklist' => []
    ],
    'cache' => [
        'default' => 'array',
        'stores' => [
            'array' => ['driver' => 'array'],
            'file' => ['driver' => 'file', 'path' => __DIR__ . '/../cache']
        ]
    ]
]);

// Registrar extensões avançadas
echo "📦 Registrando extensões avançadas...\n";

$app->registerExtension('rate_limiting', RateLimitingProvider::class, [
    'enabled' => true,
    'strict_mode' => false
]);

$app->registerExtension('cache_system', CacheProvider::class, [
    'enabled' => true,
    'default_ttl' => 300
]);

// Inicializar aplicação
$app->boot();

echo "✅ Extensões avançadas inicializadas\n\n";

// ============================================
// ROTAS DE DEMONSTRAÇÃO
// ============================================

// Rota com rate limiting
$app->get('/api/data', [
    $app->make('rate_limit_middleware')(['identifier_strategy' => 'ip']),
    function ($req, $res) use ($app) {
        echo "📊 Processing API request...\n";

        // Usar cache para dados pesados
        $data = $app->make('cache')->remember('expensive_data', function () {
            echo "💾 Computing expensive data...\n";
            return [
                'timestamp' => time(),
                'data' => range(1, 1000),
                'computed_at' => date('Y-m-d H:i:s')
            ];
        }, 60);

        $res->json($data);
    }
]);

// Rota com cache de response
$app->get('/cached-content', [
    $app->make('response_cache_middleware')(['ttl' => 120]),
    function ($req, $res) {
        echo "🔄 Generating content...\n";

        $content = [
            'message' => 'This content is cached',
            'generated_at' => date('Y-m-d H:i:s'),
            'random' => rand(1, 1000)
        ];

        $res->json($content);
    }
]);

// Rota para estatísticas das extensões
$app->get('/extensions/advanced-stats', function ($req, $res) use ($app) {
    $stats = $app->applyFilter('app.stats', [], []);

    $advancedStats = [
        'framework' => 'Helix-PHP',
        'version' => Application::VERSION,
        'extensions' => $app->getExtensionStats(),
        'features' => $stats,
        'runtime' => [
            'php_version' => PHP_VERSION,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ]
    ];

    $res->json($advancedStats);
});

// Rota para limpar cache
$app->post('/admin/clear-cache', function ($req, $res) use ($app) {
    $app->doAction('cache.clear', []);

    $res->json([
        'message' => 'Cache cleared successfully',
        'timestamp' => date('c')
    ]);
});

// ============================================
// SIMULAÇÃO DE REQUESTS
// ============================================

echo "🎯 Simulando requests para demonstrar funcionalidades...\n\n";

// Simulação de múltiplas requests para rate limiting
echo "1. Testando Rate Limiting (10 requests em 60s):\n";
for ($i = 1; $i <= 12; $i++) {
    echo "   Request #{$i}: ";

    $rateLimiter = $app->make('rate_limiter');
    $allowed = $rateLimiter->isAllowed('test_client');

    echo $allowed ? "✅ Allowed" : "❌ Rate Limited";
    echo " (Remaining: {$rateLimiter->getRemainingRequests('test_client')})\n";

    if ($i === 10) {
        echo "   → Rate limit reached!\n";
    }
}

echo "\n2. Testando Cache System:\n";
$cache = $app->make('cache');

// Teste cache array
echo "   Array Cache: ";
$start = microtime(true);
$data1 = $cache->remember('test_key', function () {
    usleep(1000); // Simular processamento
    return ['computed' => true, 'value' => rand()];
}, 60);
$time1 = microtime(true) - $start;

$start = microtime(true);
$data2 = $cache->remember('test_key', function () {
    usleep(1000);
    return ['computed' => true, 'value' => rand()];
}, 60);
$time2 = microtime(true) - $start;

echo "First: {$time1}ms, Second: {$time2}ms ";
echo ($data1['value'] === $data2['value']) ? "✅ Cache Hit\n" : "❌ Cache Miss\n";

// ============================================
// RELATÓRIO FINAL
// ============================================

echo "\n📈 Relatório Final das Extensões Avançadas:\n";
echo "===========================================\n";

$extensionStats = $app->getExtensionStats();
$appStats = $app->applyFilter('app.stats', [], []);

echo "🔧 Extensões Ativas:\n";
foreach ($extensionStats['extensions']['enabled_list'] as $name) {
    echo "   - {$name}\n";
}

echo "\n⚡ Funcionalidades Disponíveis:\n";
foreach ($appStats as $feature => $config) {
    if (is_array($config) && isset($config['enabled']) && $config['enabled']) {
        echo "   - " . ucwords(str_replace('_', ' ', $feature)) . "\n";
    }
}

echo "\n🎯 Como Distribuir Suas Extensões:\n";
echo "1. Crie um repositório Git para sua extensão\n";
echo "2. Configure composer.json com auto-discovery:\n\n";

echo <<<COMPOSER
{
    "name": "vendor/express-php-rate-limiter",
    "description": "Advanced rate limiting for Helix-PHP",
    "type": "express-php-extension",
    "require": {
        "cafernandes/express-php": "^2.1"
    },
    "extra": {
        "express-php": {
            "providers": [
                "Vendor\\RateLimit\\RateLimitingProvider"
            ]
        }
    },
    "autoload": {
        "psr-4": {
            "Vendor\\RateLimit\\": "src/"
        }
    }
}

COMPOSER;

echo "\n3. Publique no Packagist\n";
echo "4. Usuários instalam com: composer require vendor/express-php-rate-limiter\n";
echo "5. Auto-discovery registra automaticamente!\n\n";

echo "✨ Sistema de extensões avançado funcionando perfeitamente!\n";
echo "📚 Consulte docs/EXTENSION_SYSTEM.md para documentação completa.\n";
