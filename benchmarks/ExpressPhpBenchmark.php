<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Routing\Router;

/**
 * PivotPHP Core - Performance Benchmark
 *
 * Testa performance do framework com as novas otimizações:
 * - Lazy Loading PSR-7
 * - Object Pooling
 * - Hybrid Request/Response
 */
class ExpressPhpBenchmark
{
    private int $iterations;
    private array $results = [];
    private bool $usePooling = true;

    public function __construct(int $iterations = 1000)
    {
        $this->iterations = $iterations;
        
        // Inicializar factory otimizada
        OptimizedHttpFactory::initialize([
            'enable_pooling' => $this->usePooling,
            'warm_up_pools' => true,
            'enable_metrics' => true,
        ]);
    }

    public function run(): void
    {
        echo "🚀 PivotPHP Core - Performance Benchmark\n";
        echo "=========================================\n\n";

        $this->warmup();
        
        echo "📊 Running benchmarks with {$this->iterations} iterations...\n\n";

        // Benchmark 1: Request Creation (Express.js style)
        $this->benchmarkRequestCreation();
        
        // Benchmark 2: Response Creation (Express.js style)
        $this->benchmarkResponseCreation();
        
        // Benchmark 3: PSR-7 Compatibility
        $this->benchmarkPsr7Compatibility();
        
        // Benchmark 4: Hybrid Operations
        $this->benchmarkHybridOperations();
        
        // Benchmark 5: Object Pooling Performance
        $this->benchmarkObjectPooling();
        
        // Benchmark 6: Route Processing
        $this->benchmarkRouteProcessing();

        $this->displayResults();
        $this->displayPoolingMetrics();
    }

    private function warmup(): void
    {
        echo "🔥 Warming up...\n";
        
        // Warm up pools
        OptimizedHttpFactory::warmUpPools();
        
        // Warm up JIT
        for ($i = 0; $i < 100; $i++) {
            $request = new Request('GET', '/test', '/test');
            $response = new Response();
            $response->json(['test' => true]);
            unset($request, $response);
        }
        
        echo "✅ Warmup complete\n\n";
    }

    private function benchmarkRequestCreation(): void
    {
        echo "📋 Benchmarking Request Creation...\n";
        
        $start = microtime(true);
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $request = new Request('GET', '/api/users/' . $i, '/api/users/' . $i);
            
            // Simular uso típico
            $request->param('id', $i);
            $request->header('Authorization');
            $request->ip();
            
            unset($request);
        }
        
        $end = microtime(true);
        $time = $end - $start;
        
        $this->results['request_creation'] = [
            'time' => $time,
            'ops_per_sec' => $this->iterations / $time,
            'memory_peak' => memory_get_peak_usage(true),
        ];
        
        echo "  ✅ Completed in " . number_format($time, 4) . "s\n";
        echo "  📈 " . number_format($this->iterations / $time, 0) . " ops/sec\n\n";
    }

    private function benchmarkResponseCreation(): void
    {
        echo "📋 Benchmarking Response Creation...\n";
        
        $start = microtime(true);
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $response = new Response();
            $response->setTestMode(true); // Evitar output
            
            // Simular uso típico
            $response->status(200);
            $response->header('Content-Type', 'application/json');
            $response->json(['id' => $i, 'name' => "User {$i}"]);
            
            unset($response);
        }
        
        $end = microtime(true);
        $time = $end - $start;
        
        $this->results['response_creation'] = [
            'time' => $time,
            'ops_per_sec' => $this->iterations / $time,
            'memory_peak' => memory_get_peak_usage(true),
        ];
        
        echo "  ✅ Completed in " . number_format($time, 4) . "s\n";
        echo "  📈 " . number_format($this->iterations / $time, 0) . " ops/sec\n\n";
    }

    private function benchmarkPsr7Compatibility(): void
    {
        echo "📋 Benchmarking PSR-7 Compatibility...\n";
        
        $start = microtime(true);
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $request = new Request('POST', '/api/data', '/api/data');
            
            // Usar métodos PSR-7 (trigger lazy loading)
            $request->getMethod();
            $request->getUri();
            $request->getHeaders();
            $request->getBody();
            $request->getAttribute('test', 'default');
            
            // Testar imutabilidade
            $newRequest = $request->withAttribute('user_id', $i);
            $newRequest->getAttribute('user_id');
            
            unset($request, $newRequest);
        }
        
        $end = microtime(true);
        $time = $end - $start;
        
        $this->results['psr7_compatibility'] = [
            'time' => $time,
            'ops_per_sec' => $this->iterations / $time,
            'memory_peak' => memory_get_peak_usage(true),
        ];
        
        echo "  ✅ Completed in " . number_format($time, 4) . "s\n";
        echo "  📈 " . number_format($this->iterations / $time, 0) . " ops/sec\n\n";
    }

    private function benchmarkHybridOperations(): void
    {
        echo "📋 Benchmarking Hybrid Operations...\n";
        
        $start = microtime(true);
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $request = new Request('GET', '/api/users/:id', '/api/users/' . $i);
            $response = new Response();
            $response->setTestMode(true);
            
            // Mix Express.js e PSR-7
            $userId = $request->param('id'); // Express.js
            $headers = $request->getHeaders(); // PSR-7
            
            $response->status(200); // Express.js
            $newResponse = $response->withHeader('X-User-ID', (string)$userId); // PSR-7
            
            $newResponse->json(['user' => $userId]); // Express.js
            
            unset($request, $response, $newResponse);
        }
        
        $end = microtime(true);
        $time = $end - $start;
        
        $this->results['hybrid_operations'] = [
            'time' => $time,
            'ops_per_sec' => $this->iterations / $time,
            'memory_peak' => memory_get_peak_usage(true),
        ];
        
        echo "  ✅ Completed in " . number_format($time, 4) . "s\n";
        echo "  📈 " . number_format($this->iterations / $time, 0) . " ops/sec\n\n";
    }

    private function benchmarkObjectPooling(): void
    {
        echo "📋 Benchmarking Object Pooling...\n";
        
        // Pré-aquecer pools (não limpar - isso zera as estatísticas)
        OptimizedHttpFactory::warmUpPools();
        
        $start = microtime(true);
        
        for ($i = 0; $i < $this->iterations; $i++) {
            // Usar factory otimizada
            $request = OptimizedHttpFactory::createRequest('GET', '/pool/test', '/pool/test');
            $response = OptimizedHttpFactory::createResponse();
            
            // Usar objetos PSR-7 do pool
            $psr7Request = OptimizedHttpFactory::createServerRequest('POST', '/psr7/test');
            $psr7Response = OptimizedHttpFactory::createPsr7Response(200, [], '{"pooled": true}');
            
            // Retornar objetos ao pool para reutilização
            if (method_exists('PivotPHP\Core\Http\Pool\Psr7Pool', 'returnServerRequest')) {
                \PivotPHP\Core\Http\Pool\Psr7Pool::returnServerRequest($psr7Request);
                \PivotPHP\Core\Http\Pool\Psr7Pool::returnResponse($psr7Response);
            }
            
            unset($request, $response, $psr7Request, $psr7Response);
        }
        
        $end = microtime(true);
        $time = $end - $start;
        
        $this->results['object_pooling'] = [
            'time' => $time,
            'ops_per_sec' => $this->iterations / $time,
            'memory_peak' => memory_get_peak_usage(true),
        ];
        
        echo "  ✅ Completed in " . number_format($time, 4) . "s\n";
        echo "  📈 " . number_format($this->iterations / $time, 0) . " ops/sec\n\n";
    }

    private function benchmarkRouteProcessing(): void
    {
        echo "📋 Benchmarking Route Processing...\n";
        
        $start = microtime(true);
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $request = new Request('GET', '/api/users/:id/posts/:postId', '/api/users/' . $i . '/posts/' . ($i * 10));
            
            // Simular processamento de rota
            $userId = $request->param('id');
            $postId = $request->param('postId');
            
            $response = new Response();
            $response->setTestMode(true);
            $response->json([
                'user_id' => $userId,
                'post_id' => $postId,
                'data' => 'Sample data for user ' . $userId
            ]);
            
            unset($request, $response);
        }
        
        $end = microtime(true);
        $time = $end - $start;
        
        $this->results['route_processing'] = [
            'time' => $time,
            'ops_per_sec' => $this->iterations / $time,
            'memory_peak' => memory_get_peak_usage(true),
        ];
        
        echo "  ✅ Completed in " . number_format($time, 4) . "s\n";
        echo "  📈 " . number_format($this->iterations / $time, 0) . " ops/sec\n\n";
    }

    private function displayResults(): void
    {
        echo "📊 BENCHMARK RESULTS\n";
        echo "===================\n\n";
        
        $totalOps = 0;
        $totalTime = 0;
        
        foreach ($this->results as $name => $result) {
            $totalOps += $result['ops_per_sec'];
            $totalTime += $result['time'];
            
            echo sprintf("%-20s: %s ops/sec (%.4fs)\n", 
                ucwords(str_replace('_', ' ', $name)),
                number_format($result['ops_per_sec'], 0),
                $result['time']
            );
        }
        
        echo "\n";
        echo "📈 Average Performance: " . number_format($totalOps / count($this->results), 0) . " ops/sec\n";
        echo "⏱️  Total Time: " . number_format($totalTime, 4) . "s\n";
        echo "🧠 Peak Memory: " . $this->formatBytes(memory_get_peak_usage(true)) . "\n";
        echo "💾 Current Memory: " . $this->formatBytes(memory_get_usage(true)) . "\n\n";
    }

    private function displayPoolingMetrics(): void
    {
        echo "♻️  OBJECT POOLING METRICS\n";
        echo "=========================\n\n";
        
        $metrics = OptimizedHttpFactory::getPerformanceMetrics();
        
        if (isset($metrics['metrics_disabled'])) {
            echo "⚠️  Pooling metrics disabled\n\n";
            return;
        }
        
        echo "Pool Efficiency:\n";
        foreach ($metrics['pool_efficiency'] as $type => $rate) {
            $emoji = $rate > 80 ? '🟢' : ($rate > 50 ? '🟡' : '🔴');
            echo sprintf("  %s %-20s: %s %.1f%%\n", 
                $emoji,
                ucwords(str_replace('_', ' ', $type)),
                $emoji,
                $rate
            );
        }
        
        echo "\nMemory Usage:\n";
        echo sprintf("  Current: %s\n", $this->formatBytes($metrics['memory_usage']['current']));
        echo sprintf("  Peak: %s\n", $this->formatBytes($metrics['memory_usage']['peak']));
        
        echo "\nRecommendations:\n";
        foreach ($metrics['recommendations'] as $recommendation) {
            echo "  • {$recommendation}\n";
        }
        
        echo "\n";
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

// Executar benchmark
if (isset($argv[1])) {
    $iterations = (int)$argv[1];
} else {
    $iterations = 1000;
}

$benchmark = new ExpressPhpBenchmark($iterations);
$benchmark->run();