<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Express\Core\Application;
use Express\Http\Request;
use Express\Http\Response;
use Express\Routing\Router;
use Express\Http\Psr15\Middleware\MiddlewareStack;
use Express\Http\Psr15\Middleware\SecurityMiddleware;
use Express\Http\Psr15\Middleware\CorsMiddleware;
use Express\Routing\RouteCache;
use Express\Routing\RouterInstance;

$app = new Application();

// =========================
// MIDDLEWARES OTIMIZADOS
// =========================

$corsMiddleware = function (Request $req, Response $resp, $next) {
    // Assuming CorsMiddleware::simple expects the origin as the first argument (string)
    $origin = $req->header->origin ?? '';
    return CorsMiddleware::simple(
        $origin,
        [
            'origins' => ['http://localhost:3000', 'http://localhost:8080'],
            'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
            'headers' => ['Content-Type', 'Authorization']
        ],
        null,
        $next
    );
};

$logMiddleware = function (Request $req, Response $resp, $next) {
    $start = microtime(true);
    $result = $next($req, $resp);
    $time = round((microtime(true) - $start) * 1000, 3);
    error_log("[OPTIMIZED] {$req->method} {$req->path} - {$time}ms");
    return $result;
};

$securityMiddleware = function (Request $req, Response $resp, $next) {
    $resp->header('X-Frame-Options', 'DENY');
    $resp->header('X-Content-Type-Options', 'nosniff');
    $resp->header('X-XSS-Protection', '1; mode=block');
    return $next($req, $resp);
};

// =========================
// ROTA PRINCIPAL COM ESTATÍSTICAS
// =========================

$app->get('/', function (Request $req, Response $resp) {
    return $resp->json([
        'message' => '🚀 Express PHP - Otimizações Implementadas',
        'version' => '2.0.1',
        'optimizations' => [
            'route_cache' => 'Cache de rotas pré-compiladas',
            'group_router' => 'Roteamento otimizado por grupos',
            'middleware_pipeline' => 'Pipeline otimizado de middlewares',
            'cors_optimization' => 'CORS com batch processing',
            'pattern_matching' => 'Pattern matching pré-compilado',
            'exact_match_cache' => 'Cache de exact matches',
            'prefix_ordering' => 'Prefixos ordenados por especificidade'
        ],
        'performance_metrics' => [
            'route_cache_stats' => RouteCache::getStats(),
            'group_stats' => Router::getGroupStats(),
            'middleware_stats' => MiddlewareStack::getStats(),
            'cors_stats' => CorsMiddleware::getStats()
        ]
    ]);
});

// =========================
// GRUPOS OTIMIZADOS DE API
// =========================

// API v1 - Sistema de usuários com middleware CORS otimizado
$app->use('/api/v1', function () use ($app) {

    // Subgrupo de usuários
    $app->use('/users', function () use ($app) {
        $app->get('/', function (Request $req, Response $resp) {
            return $resp->json([
                'users' => array_map(function($i) {
                    return [
                        'id' => $i,
                        'name' => "Usuário $i",
                        'email' => "user$i@example.com",
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                }, range(1, 10))
            ]);
        });

        $app->get('/:id', function (Request $req, Response $resp) {
            $id = $req->param->id;
            return $resp->json([
                'user' => [
                    'id' => (int)$id,
                    'name' => "Usuário $id",
                    'email' => "user$id@example.com",
                    'profile' => [
                        'bio' => "Biografia do usuário $id",
                        'location' => 'Brasil',
                        'joined' => date('Y-m-d', strtotime('-' . rand(1, 365) . ' days'))
                    ]
                ]
            ]);
        });

        $app->post('/', function (Request $req, Response $resp) {
            return $resp->status(201)->json([
                'message' => 'Usuário criado com sucesso',
                'user_id' => rand(1000, 9999),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        });

        $app->put('/:id', function (Request $req, Response $resp) {
            $id = $req->param->id;
            return $resp->json([
                'message' => "Usuário $id atualizado com sucesso",
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        });
    });

    // Subgrupo de produtos
    $app->use('/products', function () use ($app) {
        $app->get('/', function (Request $req, Response $resp) {
            return $resp->json([
                'products' => array_map(function($i) {
                    return [
                        'id' => $i,
                        'name' => "Produto $i",
                        'price' => rand(10, 500),
                        'category' => ['Eletrônicos', 'Roupas', 'Casa', 'Livros'][rand(0, 3)]
                    ];
                }, range(1, 20))
            ]);
        });

        $app->get('/:id', function (Request $req, Response $resp) {
            $id = $req->param->id;
            return $resp->json([
                'product' => [
                    'id' => (int)$id,
                    'name' => "Produto $id",
                    'price' => rand(10, 500),
                    'description' => "Descrição detalhada do produto $id",
                    'specifications' => [
                        'weight' => rand(100, 2000) . 'g',
                        'dimensions' => rand(10, 50) . 'x' . rand(10, 50) . 'x' . rand(5, 20) . 'cm',
                        'warranty' => rand(6, 24) . ' meses'
                    ]
                ]
            ]);
        });
    });

    // Subgrupo de pedidos
    $ordersRouter = new RouterInstance('/orders');
    $ordersRouter->get('/', function (Request $req, Response $resp) {
        return $resp->json([
            'orders' => array_map(function($i) {
                return [
                    'id' => $i,
                    'user_id' => rand(1, 100),
                    'total' => rand(50, 1000),
                    'status' => ['pending', 'processing', 'shipped', 'delivered'][rand(0, 3)],
                    'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'))
                ];
            }, range(1, 15))
        ]);
    });
    $ordersRouter->get('/:id', function (Request $req, Response $resp) {
        $id = $req->param->id;
        return $resp->json([
            'order' => [
                'id' => (int)$id,
                'user_id' => rand(1, 100),
                'items' => array_map(function($i) {
                    return [
                        'product_id' => rand(1, 50),
                        'quantity' => rand(1, 5),
                        'price' => rand(10, 200)
                    ];
                }, range(1, rand(1, 5))),
                'total' => rand(50, 1000),
                'status' => 'processing'
            ]
        ]);
    });
    $app->use($ordersRouter);

}, [$corsMiddleware, $logMiddleware]); // Middlewares aplicados a toda API v1

// API v2 - Sistema administrativo com segurança otimizada
$apiv2Router = new RouterInstance('/api/v2');
$apiv2Router->get('/status', function (Request $req, Response $resp) {
    return $resp->json([
        'api_version' => '2.0',
        'status' => 'operational',
        'uptime' => '99.9%',
        'optimizations_active' => true,
        'performance_metrics' => [
            'cache_hit_ratio' => '94.2%',
            'avg_response_time' => '12.3ms',
            'requests_per_second' => 1250
        ]
    ]);
});

$apiv2Router->get('/health', function (Request $req, Response $resp) {
    return $resp->json([
        'status' => 'healthy',
        'checks' => [
            'database' => 'ok',
            'cache' => 'ok',
            'storage' => 'ok',
            'external_apis' => 'ok'
        ],
        'timestamp' => date('c'),
        'response_time_ms' => rand(5, 15)
    ]);
});
$adminRouter = new RouterInstance('/admin');
$adminRouter->get('/dashboard', function (Request $req, Response $resp) {
    return $resp->json([
        'dashboard' => [
            'total_users' => rand(1000, 5000),
            'total_products' => rand(500, 2000),
            'total_orders' => rand(2000, 10000),
            'revenue_today' => rand(5000, 20000),
            'active_sessions' => rand(50, 200),
            'optimizations' => [
                'route_cache_size' => count(RouteCache::getStats()),
                'group_count' => count(Router::getGroupStats()),
                'middleware_pipelines' => count(MiddlewareStack::getStats())
            ]
        ]
    ]);
});
$adminRouter->get('/performance', function (Request $req, Response $resp) {
    return $resp->json([
        'performance_report' => [
            'route_cache' => RouteCache::getStats(),
            'group_router' => Router::getGroupStats(),
            'middleware_pipeline' => MiddlewareStack::getStats(),
            'cors_optimization' => CorsMiddleware::getStats()
        ]
    ]);
});
$apiv2Router->add($adminRouter, [$corsMiddleware, $logMiddleware]); // Middlewares aplicados ao subgrupo administrativo

$app->use($apiv2Router, [$securityMiddleware, $logMiddleware]); // Middlewares de segurança para API v2

// =========================
// ROTAS DE BENCHMARK DINÂMICO
// =========================
$benchmarkRouter = new RouterInstance('/benchmark');
$benchmarkRouter->get('/groups/:prefix', function (Request $req, Response $resp) {
    $prefix = '/' . $req->param->prefix;
    $iterations = $req->query->iterations ?? 1000;

    try {
        $results = Router::benchmarkGroupAccess($prefix, (int)$iterations);
        return $resp->json([
            'benchmark_results' => $results,
            'group_stats' => Router::getGroupStats()[$prefix] ?? null
        ]);
    } catch (Exception $e) {
        return $resp->status(404)->json([
            'error' => 'Group not found',
            'available_groups' => array_keys(Router::getGroupStats())
        ]);
    }
});
$benchmarkRouter->get('/middleware', function (Request $req, Response $resp) {
    $iterations = $req->query->iterations ?? 1000;

    // Cria middlewares de teste
    $testMiddlewares = [
        function($req, $resp, $next) { return $next($req, $resp); },
        function($req, $resp, $next) { return $next($req, $resp); },
        function($req, $resp, $next) { return $next($req, $resp); }
    ];

    $results = MiddlewareStack::benchmarkPipeline($testMiddlewares, (int)$iterations);

    return $resp->json([
        'benchmark_results' => $results,
        'pipeline_stats' => MiddlewareStack::getStats()
    ]);
});
$benchmarkRouter->get('/cors', function (Request $req, Response $resp) {
    $iterations = $req->query->iterations ?? 1000;

    $results = CorsMiddleware::benchmark((int)$iterations);

    return $resp->json([
        'benchmark_results' => $results,
        'cors_stats' => CorsMiddleware::getStats()
    ]);
});
$app->use($benchmarkRouter);

// =========================
// INFORMAÇÕES DO SISTEMA
// =========================

$app->get('/system/info', function (Request $req, Response $resp) {
    return $resp->json([
        'system_info' => [
            'php_version' => PHP_VERSION,
            'memory_usage' => memory_get_usage(true) / 1024 / 1024 . ' MB',
            'peak_memory' => memory_get_peak_usage(true) / 1024 / 1024 . ' MB',
            'uptime' => rand(1, 24) . ' hours',
            'request_count' => rand(1000, 50000)
        ],
        'optimization_status' => [
            'route_cache' => [
                'enabled' => true,
                'stats' => RouteCache::getStats()
            ],
            'group_router' => [
                'enabled' => true,
                'groups' => count(Router::getGroupStats()),
                'stats' => Router::getGroupStats()
            ],
            'middleware_pipeline' => [
                'enabled' => true,
                'pipelines' => count(MiddlewareStack::getStats()),
                'stats' => MiddlewareStack::getStats()
            ],
            'cors_optimization' => [
                'enabled' => true,
                'stats' => CorsMiddleware::getStats()
            ]
        ]
    ]);
});

// =========================
// FUNÇÃO DE EXIBIÇÃO DE INFORMAÇÕES
// =========================

function showOptimizationSummary() {
    echo "\n📊 RESUMO DAS OTIMIZAÇÕES ATIVAS:\n";
    echo str_repeat("-", 60) . "\n";

    echo "✅ Cache de Rotas:\n";
    echo "   • Rotas pré-compiladas para acesso O(1)\n";
    echo "   • Patterns regex compilados antecipadamente\n";
    echo "   • Parâmetros extraídos automaticamente\n\n";

    echo "✅ Router Otimizado por Grupos:\n";
    echo "   • Indexação por método HTTP\n";
    echo "   • Prefixos ordenados por especificidade\n";
    echo "   • Cache de matching de prefixos\n";
    echo "   • Middlewares pré-compilados por grupo\n\n";

    echo "✅ Pipeline Otimizado de Middlewares:\n";
    echo "   • Compilação de pipelines em funções únicas\n";
    echo "   • Detecção e remoção de middlewares redundantes\n";
    echo "   • Cache de pipelines compilados\n";
    echo "   • Batch processing para middlewares similares\n\n";

    echo "✅ CORS Otimizado:\n";
    echo "   • Batch processing de headers\n";
    echo "   • Cache de configurações\n";
    echo "   • Headers pré-compilados\n\n";

    echo "🔥 BENEFÍCIOS:\n";
    echo "   • Redução significativa na latência de rotas\n";
    echo "   • Menor overhead em grupos de rotas\n";
    echo "   • Cache inteligente reduz recomputação\n";
    echo "   • Pipeline de middlewares mais eficiente\n";
    echo "   • Melhor performance em alta carga\n\n";

    echo "🌐 URLs DE TESTE:\n";
    echo "   • http://localhost:8000/ (página principal)\n";
    echo "   • http://localhost:8000/api/v1/users (lista usuários)\n";
    echo "   • http://localhost:8000/api/v1/products/123 (produto específico)\n";
    echo "   • http://localhost:8000/api/v2/admin/dashboard (dashboard admin)\n";
    echo "   • http://localhost:8000/benchmark/groups/api%2Fv1?iterations=5000 (benchmark)\n";
    echo "   • http://localhost:8000/system/info (informações do sistema)\n\n";
}

// Exibe informações APENAS se executado via CLI e não via servidor web
if (php_sapi_name() === 'cli' && !isset($_SERVER['SERVER_NAME'])) {
    showOptimizationSummary();
    echo "🚀 Servidor iniciado com todas as otimizações ativas!\n";
    echo "📍 Acesse: http://localhost:8000\n\n";
}

$app->listen(8000);
