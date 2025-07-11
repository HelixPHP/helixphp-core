<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Performance\HighPerformanceMode;

/**
 * Quiet benchmark for Quality Gate
 * No JSON outputs, just clean performance metrics
 */
class QuietBenchmark
{
    private Application $app;
    private int $iterations;

    public function __construct(int $iterations = 1000)
    {
        $this->iterations = $iterations;
        $this->app = new Application();
        
        // Enable high performance mode quietly
        ob_start();
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
        ob_end_clean();
        
        // Setup basic routes without output
        $this->setupRoutes();
        $this->app->boot();
    }

    private function setupRoutes(): void
    {
        $this->app->get('/test', function ($req, $res) {
            return $res->json(['status' => 'ok']);
        });

        $this->app->post('/api/data', function ($req, $res) {
            return $res->json(['processed' => true]);
        });

        $this->app->get('/api/users/:id', function ($req, $res) {
            return $res->json(['id' => $req->param('id')]);
        });
    }

    public function run(): array
    {
        $results = [];
        
        // Test different endpoints
        $endpoints = [
            ['GET', '/test'],
            ['POST', '/api/data'],
            ['GET', '/api/users/123']
        ];

        foreach ($endpoints as [$method, $path]) {
            $results[$method . ' ' . $path] = $this->benchmarkEndpoint($method, $path);
        }

        return $results;
    }

    private function benchmarkEndpoint(string $method, string $path): array
    {
        $times = [];
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $start = microtime(true);
            
            $request = new Request($method, $path, $path);
            
            $response = $this->app->handle($request);
            // Ensure response is in test mode to prevent output
            $response->setTestMode(true);
            
            $end = microtime(true);
            $times[] = ($end - $start) * 1000; // Convert to milliseconds
        }

        $totalTime = array_sum($times);
        $avgTime = $totalTime / count($times);
        $opsPerSec = 1000 / $avgTime; // ops per second

        return [
            'iterations' => $this->iterations,
            'total_time_ms' => round($totalTime, 2),
            'avg_time_ms' => round($avgTime, 4),
            'ops_per_sec' => round($opsPerSec, 0)
        ];
    }

    public function getOverallPerformance(): int
    {
        $results = $this->run();
        
        // Calculate weighted average performance
        $totalOps = 0;
        $count = 0;
        
        foreach ($results as $result) {
            $totalOps += $result['ops_per_sec'];
            $count++;
        }
        
        return $count > 0 ? (int)round($totalOps / $count) : 0;
    }
}

// Run benchmark if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $benchmark = new QuietBenchmark(500); // Fewer iterations for speed
    $performance = $benchmark->getOverallPerformance();
    
    if ($performance > 0) {
        echo "📈 {$performance} ops/sec\n";
    } else {
        echo "📈 Performance test completed\n";
    }
}