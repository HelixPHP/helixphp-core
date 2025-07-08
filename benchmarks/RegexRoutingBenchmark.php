<?php

namespace PivotPHP\Benchmarks;

use PivotPHP\Core\Routing\Router;
use PivotPHP\Core\Routing\RouteCache;

class RegexRoutingBenchmark
{
    private array $results = [];
    private int $iterations = 10000;

    public function run(): void
    {
        echo "=== PivotPHP Regex Routing Benchmark ===\n\n";
        
        $this->benchmarkSimpleRoutes();
        $this->benchmarkConstrainedRoutes();
        $this->benchmarkMixedRoutes();
        $this->benchmarkComplexPatterns();
        
        $this->printResults();
    }

    private function benchmarkSimpleRoutes(): void
    {
        Router::reset();
        RouteCache::clear();
        
        // Setup rotas simples (sintaxe antiga)
        for ($i = 1; $i <= 100; $i++) {
            Router::get("/route{$i}/:id", function() {});
        }
        
        $start = microtime(true);
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $routeNum = rand(1, 100);
            Router::identify('GET', "/route{$routeNum}/123");
        }
        
        $duration = microtime(true) - $start;
        $this->results['simple_routes'] = [
            'duration' => $duration,
            'ops_per_second' => $this->iterations / $duration,
            'avg_ms' => ($duration / $this->iterations) * 1000
        ];
    }

    private function benchmarkConstrainedRoutes(): void
    {
        Router::reset();
        RouteCache::clear();
        
        // Setup rotas com constraints
        for ($i = 1; $i <= 100; $i++) {
            Router::get("/route{$i}/:id<\d+>", function() {});
        }
        
        $start = microtime(true);
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $routeNum = rand(1, 100);
            Router::identify('GET', "/route{$routeNum}/123");
        }
        
        $duration = microtime(true) - $start;
        $this->results['constrained_routes'] = [
            'duration' => $duration,
            'ops_per_second' => $this->iterations / $duration,
            'avg_ms' => ($duration / $this->iterations) * 1000
        ];
    }

    private function benchmarkMixedRoutes(): void
    {
        Router::reset();
        RouteCache::clear();
        
        // Setup mix de rotas
        for ($i = 1; $i <= 50; $i++) {
            Router::get("/simple{$i}/:id", function() {});
            Router::get("/constrained{$i}/:id<\d+>", function() {});
        }
        
        $start = microtime(true);
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $type = rand(0, 1) ? 'simple' : 'constrained';
            $routeNum = rand(1, 50);
            Router::identify('GET', "/{$type}{$routeNum}/123");
        }
        
        $duration = microtime(true) - $start;
        $this->results['mixed_routes'] = [
            'duration' => $duration,
            'ops_per_second' => $this->iterations / $duration,
            'avg_ms' => ($duration / $this->iterations) * 1000
        ];
    }

    private function benchmarkComplexPatterns(): void
    {
        Router::reset();
        RouteCache::clear();
        
        // Setup rotas com patterns complexos
        $complexPatterns = [
            '/api/:version<v\d+>/users/:id<\d+>',
            '/posts/:year<\d{4}>/:month<\d{2}>/:slug<[a-z0-9-]+>',
            '/files/:filename<[\w-]+>.:ext<jpg|png|gif|webp>',
            '/uuid/:id<uuid>',
            '/email/:address<[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+>',
        ];
        
        foreach ($complexPatterns as $i => $pattern) {
            for ($j = 1; $j <= 20; $j++) {
                Router::get(str_replace(':version', ":version{$j}", $pattern), function() {});
            }
        }
        
        $testCases = [
            '/api/v1/users/123',
            '/posts/2024/01/my-awesome-post',
            '/files/document.png',
            '/uuid/550e8400-e29b-41d4-a716-446655440000',
            '/email/user@example.com'
        ];
        
        $start = microtime(true);
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $testCase = $testCases[array_rand($testCases)];
            Router::identify('GET', $testCase);
        }
        
        $duration = microtime(true) - $start;
        $this->results['complex_patterns'] = [
            'duration' => $duration,
            'ops_per_second' => $this->iterations / $duration,
            'avg_ms' => ($duration / $this->iterations) * 1000
        ];
    }

    private function printResults(): void
    {
        echo "Results ({$this->iterations} iterations each):\n";
        echo str_repeat("=", 70) . "\n";
        printf("%-25s | %-15s | %-15s | %-10s\n", "Test Case", "Ops/Second", "Avg Time (ms)", "Total (s)");
        echo str_repeat("-", 70) . "\n";
        
        foreach ($this->results as $name => $result) {
            printf(
                "%-25s | %15s | %15.4f | %10.4f\n",
                str_replace('_', ' ', ucfirst($name)),
                number_format($result['ops_per_second'], 0),
                $result['avg_ms'],
                $result['duration']
            );
        }
        
        echo str_repeat("=", 70) . "\n";
        
        // Calcular overhead
        if (isset($this->results['simple_routes']) && isset($this->results['constrained_routes'])) {
            $overhead = (($this->results['constrained_routes']['avg_ms'] / $this->results['simple_routes']['avg_ms']) - 1) * 100;
            echo "\nOverhead Analysis:\n";
            echo "Constrained routes overhead: " . number_format($overhead, 2) . "%\n";
        }
        
        if (isset($this->results['simple_routes']) && isset($this->results['complex_patterns'])) {
            $overhead = (($this->results['complex_patterns']['avg_ms'] / $this->results['simple_routes']['avg_ms']) - 1) * 100;
            echo "Complex patterns overhead: " . number_format($overhead, 2) . "%\n";
        }
        
        // Cache stats
        echo "\nCache Statistics:\n";
        $stats = RouteCache::getStats();
        echo "Hit Rate: " . $stats['hit_rate_percentage'] . "%\n";
        echo "Total Compilations: " . $stats['compilations'] . "\n";
        echo "Cached Routes: " . $stats['cached_routes'] . "\n";
        echo "Memory Usage: " . $stats['memory_usage'] . "\n";
    }
}

// Run benchmark
if (php_sapi_name() === 'cli') {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $benchmark = new RegexRoutingBenchmark();
    $benchmark->run();
}