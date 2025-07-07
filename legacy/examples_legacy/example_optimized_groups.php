<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Routing\Router;

$app = new Application();

// Middleware de autenticação para demonstração
$authMiddleware = function (Request $req, Response $resp, $next) {
    $authHeader = $req->header('Authorization');
    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        return $resp->status(401)->json(['error' => 'Unauthorized']);
    }
    return $next($req, $resp);
};

// Middleware de logging para demonstração
$logMiddleware = function (Request $req, Response $resp, $next) {
    error_log("[LOG] {$req->getMethod()} {$req->getPath()} - " . date('Y-m-d H:i:s'));
    return $next($req, $resp);
};

// =========================
// ROTAS NATIVAS (sem grupo)
// =========================

$app->get('/', function (Request $req, Response $resp) {
    return $resp->json([
        'message' => 'PivotPHP - Rotas Otimizadas',
        'version' => '2.0.1',
        'optimizations' => [
            'route_cache' => true,
            'group_router' => true,
            'pattern_matching' => true
        ]
    ]);
});

$app->get('/health', function (Request $req, Response $resp) {
    return $resp->json(['status' => 'OK', 'timestamp' => time()]);
});

// =========================
// GRUPO API v1 (com middlewares)
// =========================

$app->use('/api/v1', function () use ($app, $authMiddleware) {

    // Rotas de usuários
    $app->use('/users', function () use ($app) {
        $app->get('/', function (Request $req, Response $resp) {
            return $resp->json([
                'users' => [
                    ['id' => 1, 'name' => 'João', 'email' => 'joao@email.com'],
                    ['id' => 2, 'name' => 'Maria', 'email' => 'maria@email.com']
                ]
            ]);
        });

        $app->get('/:id', function (Request $req, Response $resp) {
            $id = $req->getParam('id');
            return $resp->json([
                'user' => ['id' => (int)$id, 'name' => 'Usuário ' . $id, 'email' => "user{$id}@email.com"]
            ]);
        });

        $app->post('/', function (Request $req, Response $resp) {
            return $resp->status(201)->json([
                'message' => 'Usuário criado com sucesso',
                'user_id' => rand(100, 999)
            ]);
        });
    });

    // Rotas de produtos
    $app->use('/products', function () use ($app) {
        $app->get('/', function (Request $req, Response $resp) {
            return $resp->json([
                'products' => [
                    ['id' => 1, 'name' => 'Notebook', 'price' => 2500.00],
                    ['id' => 2, 'name' => 'Mouse', 'price' => 50.00]
                ]
            ]);
        });

        $app->get('/:id', function (Request $req, Response $resp) {
            $id = $req->getParam('id');
            return $resp->json([
                'product' => ['id' => (int)$id, 'name' => 'Produto ' . $id, 'price' => rand(10, 1000)]
            ]);
        });
    });

    // Rotas administrativas (com autenticação)
    $app->use('/admin', function () use ($app, $authMiddleware) {
        $app->get('/dashboard', function (Request $req, Response $resp) {
            return $resp->json([
                'dashboard' => [
                    'total_users' => 150,
                    'total_products' => 89,
                    'total_orders' => 320
                ]
            ]);
        });

        $app->get('/stats', function (Request $req, Response $resp) {
            return $resp->json([
                'stats' => Router::getGroupStats()
            ]);
        });
    }, [$authMiddleware]); // Middleware de autenticação apenas para admin

}, [$logMiddleware]); // Middleware de logging para toda API v1

// =========================
// GRUPO API v2 (versão mais nova)
// =========================

$app->use('/api/v2', function () use ($app) {
    $app->get('/status', function (Request $req, Response $resp) {
        return $resp->json([
            'version' => '2.0',
            'status' => 'active',
            'features' => ['optimized_routing', 'group_cache', 'pattern_matching']
        ]);
    });

    $app->get('/benchmark/:group', function (Request $req, Response $resp) {
        $group = $req->getParam('group');

        try {
            $results = Router::benchmarkGroupAccess($group, 1000);
            return $resp->json([
                'benchmark_results' => $results
            ]);
        } catch (Exception $e) {
            return $resp->status(404)->json([
                'error' => 'Group not found',
                'available_groups' => array_keys(Router::getGroupStats())
            ]);
        }
    });
});

// =========================
// ROTA DE ESTATÍSTICAS
// =========================

$app->get('/stats/groups', function (Request $req, Response $resp) {
    return $resp->json([
        'group_stats' => Router::getGroupStats(),
        'total_groups' => count(Router::getGroupStats())
    ]);
});

// Função para exibir informações sobre as otimizações
function showOptimizationInfo() {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🚀 EXPRESS PHP - ROTAS OTIMIZADAS POR GRUPO\n";
    echo str_repeat("=", 60) . "\n";

    echo "\n📊 Grupos registrados:\n";
    $stats = Router::getGroupStats();
    foreach ($stats as $prefix => $data) {
        echo "  • {$prefix}: {$data['routes_count']} rotas\n";
        echo "    - Tempo de registro: {$data['registration_time_ms']}ms\n";
        echo "    - Middlewares: " . ($data['has_middlewares'] ? 'Sim' : 'Não') . "\n";
        if ($data['access_count'] > 0) {
            echo "    - Acessos: {$data['access_count']} (avg: {$data['avg_access_time_ms']}ms)\n";
        }
        echo "\n";
    }

    echo "🔧 Otimizações ativas:\n";
    echo "  ✅ Cache de rotas por grupo\n";
    echo "  ✅ Indexação por método HTTP\n";
    echo "  ✅ Exact match cache\n";
    echo "  ✅ Pattern matching otimizado\n";
    echo "  ✅ Prefixos ordenados por especificidade\n";
    echo "  ✅ Middlewares pré-compilados\n";

    echo "\n💡 Teste as rotas:\n";
    echo "  curl http://localhost:8000/\n";
    echo "  curl http://localhost:8000/api/v1/users\n";
    echo "  curl http://localhost:8000/api/v1/products/123\n";
    echo "  curl http://localhost:8000/api/v2/status\n";
    echo "  curl http://localhost:8000/stats/groups\n";
    echo "\n";
}

// Exibe informações se executado via CLI
if (php_sapi_name() === 'cli') {
    showOptimizationInfo();
    echo "🌐 Servidor iniciado em: http://localhost:8000\n";
    echo "⏰ Para parar: Ctrl+C\n\n";
}

$app->listen(8000);
