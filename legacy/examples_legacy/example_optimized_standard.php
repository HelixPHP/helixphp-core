<?php
/**
 * Exemplo com Otimizações Padrão - HelixPHP
 *
 * Este exemplo demonstra o uso das otimizações de performance
 * implementadas nas classes padrão PSR-7/PSR-15.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Helix\Core\Application;
use Helix\Http\Psr7\Factory\ResponseFactory;
use Helix\Http\Psr15\Middleware\CorsMiddleware;
use Helix\Http\Request;
use Helix\Http\Response;

// Criar aplicação
$app = new Application();

// ================================
// MIDDLEWARE OTIMIZADO
// ================================

// CORS otimizado (usando versão otimizada internamente)
$app->use(new CorsMiddleware([
    'origins' => ['*'],
    'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'credentials' => false,
    'max_age' => 86400 // Cache por 24h
]));

// ================================
// ROTAS OTIMIZADAS
// ================================

// Factory otimizada para respostas
$responseFactory = new ResponseFactory();

// Rota com resposta JSON otimizada
$app->get('/api/fast-json', function (Request $req, Response $res) use ($responseFactory) {
    $data = [
        'message' => 'High performance response',
        'timestamp' => time(),
        'performance' => true
    ];

    // Usar método otimizado da factory
    $response = $responseFactory->createJsonResponse($data);

    // Converter para Response do framework (se necessário)
    $res->json($data);
});

// Rota com resposta de texto otimizada
$app->get('/api/fast-text', function (Request $req, Response $res) use ($responseFactory) {
    $text = "Ultra fast text response with optimized headers!";

    // Usar método otimizado da factory
    $response = $responseFactory->createTextResponse($text);

    // Converter para Response do framework (se necessário)
    $res->text($text);
});

// Rota que demonstra manipulação de headers otimizada
$app->get('/api/headers-test', function (Request $req, Response $res) {
    // Headers são processados com menos validação para melhor performance
    $res->header('X-Performance', 'optimized')
        ->header('X-Fast-Headers', 'true')
        ->header('Cache-Control', 'public, max-age=3600')
        ->json(['status' => 'headers set efficiently']);
});

// Benchmark de performance interna
$app->get('/api/benchmark', function (Request $req, Response $res) {
    $iterations = 1000;

    // Teste de criação de responses
    $start = microtime(true);
    $factory = new ResponseFactory();

    for ($i = 0; $i < $iterations; $i++) {
        $response = $factory->createJsonResponse(['test' => $i]);
    }

    $end = microtime(true);
    $duration = ($end - $start) * 1000; // em ms

    $res->json([
        'test' => 'Response Factory Performance',
        'iterations' => $iterations,
        'duration_ms' => round($duration, 2),
        'ops_per_second' => round($iterations / ($duration / 1000))
    ]);
});

// ================================
// INICIAR SERVIDOR
// ================================

echo "🚀 Servidor com Otimizações Padrão iniciado!\n";
echo "📊 Endpoints disponíveis:\n";
echo "   GET /api/fast-json - Resposta JSON otimizada\n";
echo "   GET /api/fast-text - Resposta texto otimizada\n";
echo "   GET /api/headers-test - Teste de headers otimizados\n";
echo "   GET /api/benchmark - Benchmark de performance\n";
echo "\n";
echo "💡 Otimizações aplicadas nas classes padrão:\n";
echo "   ✅ Headers processados com menos validação\n";
echo "   ✅ Streams com cache de tamanho otimizado\n";
echo "   ✅ Factories com métodos especializados\n";
echo "   ✅ CORS middleware com processamento otimizado\n";
echo "\n";

$app->listen(8080);
