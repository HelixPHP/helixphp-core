<?php
/**
 * Exemplo de High Performance com Otimizações PSR-7/PSR-15
 *
 * Este exemplo demonstra como usar as classes padrão PSR-7/PSR-15
 * que agora incluem todas as otimizações de performance.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Express\Core\Application;
use Express\Http\Request;
use Express\Http\Response;
use Express\Http\Psr7\Factory\ResponseFactory;
use Express\Http\Psr15\Middleware\CorsMiddleware;

// Criar aplicação
$app = new Application();

// ================================
// MIDDLEWARE DE ALTA PERFORMANCE
// ================================

// CORS otimizado para alta performance (usando classe padrão otimizada)
$app->use(new CorsMiddleware([
    'origins' => ['http://localhost:3000', 'https://myapp.com'],
    'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'headers' => ['Content-Type', 'Authorization'],
    'credentials' => true,
    'max_age' => 86400 // 24 horas
]));

// Factory otimizada para respostas (classe padrão com otimizações)
$responseFactory = new ResponseFactory();

// ================================
// ROTAS DE ALTA PERFORMANCE
// ================================

// Rota básica com resposta JSON otimizada
$app->get('/', function (Request $req, Response $res) use ($responseFactory) {
    $data = [
        'message' => 'High Performance Express PHP!',
        'version' => '2.1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'performance' => 'optimized'
    ];

    // Usar factory otimizada para criar resposta JSON
    $psrResponse = $responseFactory->createJsonResponse($data);

    // Converter de PSR-7 para resposta tradicional
    $res->json($data);
    return $res;
});

// API de alta performance para dados
$app->get('/api/data/:id', function (Request $req, Response $res) use ($responseFactory) {
    $id = $req->params['id'];

    // Simular busca de dados otimizada
    $data = [
        'id' => (int) $id,
        'name' => "Item $id",
        'category' => 'performance',
        'cached' => true,
        'response_time_ms' => round(microtime(true) * 1000 - $_SERVER['REQUEST_TIME_FLOAT'] * 1000, 2)
    ];

    $res->json($data);
    return $res;
});

// Rota para teste de performance bulk
$app->get('/api/performance/bulk', function (Request $req, Response $res) use ($responseFactory) {
    $count = (int) ($req->query['count'] ?? 100);
    $count = min($count, 10000); // Limite de segurança

    $start = microtime(true);

    $items = [];
    for ($i = 1; $i <= $count; $i++) {
        $items[] = [
            'id' => $i,
            'name' => "Item $i",
            'value' => rand(1, 1000)
        ];
    }

    $processingTime = round((microtime(true) - $start) * 1000, 2);

    $response = [
        'items' => $items,
        'count' => $count,
        'processing_time_ms' => $processingTime,
        'items_per_second' => round($count / (microtime(true) - $start)),
        'memory_usage' => memory_get_usage(true),
        'peak_memory' => memory_get_peak_usage(true)
    ];

    $res->json($response);
    return $res;
});

// Endpoint para benchmarking interno
$app->get('/api/benchmark', function (Request $req, Response $res) use ($responseFactory) {
    $iterations = (int) ($req->query['iterations'] ?? 1000);
    $iterations = min($iterations, 50000); // Limite de segurança

    $start = microtime(true);
    $memory_start = memory_get_usage();

    // Teste de criação de objetos PSR-7 otimizados
    for ($i = 0; $i < $iterations; $i++) {
        $testResponse = $responseFactory->createJsonResponse(['test' => $i]);
        $testResponse->getBody()->getContents();
    }

    $end = microtime(true);
    $memory_end = memory_get_usage();

    $results = [
        'iterations' => $iterations,
        'total_time_ms' => round(($end - $start) * 1000, 2),
        'time_per_iteration_μs' => round((($end - $start) / $iterations) * 1000000, 2),
        'operations_per_second' => round($iterations / ($end - $start)),
        'memory_used_bytes' => $memory_end - $memory_start,
        'memory_used_kb' => round(($memory_end - $memory_start) / 1024, 2),
        'php_version' => PHP_VERSION,
        'optimization_level' => 'high_performance'
    ];

    $res->json($results);
    return $res;
});

// ================================
// MIDDLEWARE DE PERFORMANCE
// ================================

// Middleware para adicionar headers de performance
$app->use(function (Request $req, Response $res, $next) {
    $start = microtime(true);

    $next();

    $duration = round((microtime(true) - $start) * 1000, 2);
    $res->header('X-Response-Time', $duration . 'ms');
    $res->header('X-Memory-Usage', round(memory_get_usage() / 1024, 2) . 'KB');

    return $res;
});

// ================================
// INICIAR SERVIDOR
// ================================

echo "🚀 High Performance Express PHP Server\n";
echo "=====================================\n";
echo "Otimizações ativas:\n";
echo "✅ Optimized Response Factory (padrão)\n";
echo "✅ Optimized CORS Middleware (padrão)\n";
echo "✅ Memory-efficient operations\n";
echo "✅ Performance monitoring headers\n\n";

echo "Endpoints disponíveis:\n";
echo "GET  /                     - Status básico\n";
echo "GET  /api/data/:id         - Dados por ID\n";
echo "GET  /api/performance/bulk - Teste bulk (count=N)\n";
echo "GET  /api/benchmark        - Benchmark interno (iterations=N)\n\n";

echo "Server running at http://localhost:8000\n";
echo "Para testar performance: curl http://localhost:8000/api/benchmark?iterations=10000\n\n";

$app->listen(8000);
