<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Helix\Core\Application;
use Helix\Http\Request;
use Helix\Http\Response;

/**
 * Simulated Database Benchmark for HelixPHP
 * 
 * Tests API response times with simulated database operations
 */
class SimulatedDatabaseBenchmark
{
    private Application $app;
    private array $results = [];
    private int $iterations = 1000;
    
    // Simulated database latencies (in microseconds)
    private array $dbLatencies = [
        'mysql' => [
            'select_simple' => 150,
            'select_join' => 450,
            'insert' => 200,
            'update' => 180,
            'delete' => 160,
            'aggregate' => 350,
            'connection_overhead' => 50
        ],
        'postgres' => [
            'select_simple' => 180,
            'select_join' => 400,
            'insert' => 220,
            'update' => 200,
            'delete' => 180,
            'aggregate' => 300,
            'connection_overhead' => 60
        ],
        'mariadb' => [
            'select_simple' => 140,
            'select_join' => 420,
            'insert' => 190,
            'update' => 170,
            'delete' => 150,
            'aggregate' => 320,
            'connection_overhead' => 45
        ],
        'sqlite' => [
            'select_simple' => 50,
            'select_join' => 150,
            'insert' => 80,
            'update' => 70,
            'delete' => 60,
            'aggregate' => 120,
            'connection_overhead' => 10
        ]
    ];
    
    public function __construct()
    {
        $this->app = new Application();
        $this->setupRoutes();
    }
    
    private function setupRoutes(): void
    {
        // Simple SELECT endpoint
        $this->app->get('/api/users/:id', function($req, $res) {
            $db = $req->query['db'] ?? 'mysql';
            $this->simulateDbOperation($db, 'select_simple');
            
            return $res->json([
                'id' => $req->params['id'],
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ]);
        });
        
        // JOIN query endpoint
        $this->app->get('/api/users/:id/posts', function($req, $res) {
            $db = $req->query['db'] ?? 'mysql';
            $this->simulateDbOperation($db, 'select_join');
            
            return $res->json([
                'user_id' => $req->params['id'],
                'posts' => array_map(fn($i) => [
                    'id' => $i,
                    'title' => "Post $i",
                    'content' => "Content for post $i"
                ], range(1, 10))
            ]);
        });
        
        // INSERT endpoint
        $this->app->post('/api/users', function($req, $res) {
            $db = $req->query['db'] ?? 'mysql';
            $this->simulateDbOperation($db, 'insert');
            
            return $res->status(201)->json([
                'id' => rand(1000, 9999),
                'name' => $req->body['name'] ?? 'New User',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        });
        
        // UPDATE endpoint
        $this->app->put('/api/users/:id', function($req, $res) {
            $db = $req->query['db'] ?? 'mysql';
            $this->simulateDbOperation($db, 'update');
            
            return $res->json([
                'id' => $req->params['id'],
                'updated' => true,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        });
        
        // Aggregation endpoint
        $this->app->get('/api/stats', function($req, $res) {
            $db = $req->query['db'] ?? 'mysql';
            $this->simulateDbOperation($db, 'aggregate');
            
            return $res->json([
                'total_users' => 1567,
                'active_users' => 1234,
                'total_posts' => 5678,
                'avg_posts_per_user' => 3.62
            ]);
        });
    }
    
    private function simulateDbOperation(string $db, string $operation): void
    {
        if (isset($this->dbLatencies[$db][$operation])) {
            // Simulate database operation latency
            usleep($this->dbLatencies[$db][$operation]);
            
            // Simulate connection overhead
            usleep($this->dbLatencies[$db]['connection_overhead']);
        }
    }
    
    public function run(): void
    {
        echo "\n🚀 HelixPHP Database Performance Benchmark\n";
        echo "============================================\n\n";
        echo "Testing API endpoints with simulated database operations...\n";
        echo "Iterations per test: {$this->iterations}\n\n";
        
        $operations = [
            'Simple SELECT' => ['GET', '/api/users/123'],
            'JOIN Query' => ['GET', '/api/users/123/posts'],
            'INSERT' => ['POST', '/api/users'],
            'UPDATE' => ['PUT', '/api/users/123'],
            'Aggregation' => ['GET', '/api/stats']
        ];
        
        foreach (array_keys($this->dbLatencies) as $db) {
            echo "\n📊 Testing with {$db}:\n";
            echo str_repeat('-', 50) . "\n";
            
            $this->results[$db] = [];
            
            foreach ($operations as $name => $config) {
                [$method, $path] = $config;
                $result = $this->benchmarkEndpoint($method, $path, $db);
                $this->results[$db][$name] = $result;
                
                printf("%-20s: %8.2f req/s | %6.2f ms avg | %6.2f ms p95\n",
                    $name,
                    $result['requests_per_second'],
                    $result['avg_time_ms'],
                    $result['p95_ms']
                );
            }
            
            $totalAvg = array_sum(array_column($this->results[$db], 'avg_time_ms'));
            printf("\n%-20s: %6.2f ms\n", "Total Average", $totalAvg);
        }
        
        $this->printComparison();
        $this->saveResults();
    }
    
    private function benchmarkEndpoint(string $method, string $path, string $db): array
    {
        $times = [];
        $body = $method === 'POST' ? ['name' => 'Test User'] : [];
        
        // Warmup
        for ($i = 0; $i < 10; $i++) {
            $request = new Request($method, "{$path}?db={$db}", $path);
            if (!empty($body)) {
                $request->body = $body;
            }
            $this->app->handle($request);
        }
        
        // Actual benchmark
        $start = microtime(true);
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $iterStart = microtime(true);
            
            $request = new Request($method, "{$path}?db={$db}", $path);
            if (!empty($body)) {
                $request->body = $body;
            }
            
            $response = $this->app->handle($request);
            
            $times[] = (microtime(true) - $iterStart) * 1000; // Convert to ms
        }
        
        $totalTime = microtime(true) - $start;
        
        sort($times);
        $p95Index = (int) ($this->iterations * 0.95);
        
        return [
            'total_time' => $totalTime,
            'avg_time_ms' => array_sum($times) / count($times),
            'p95_ms' => $times[$p95Index],
            'requests_per_second' => $this->iterations / $totalTime,
            'iterations' => $this->iterations
        ];
    }
    
    private function printComparison(): void
    {
        echo "\n\n📈 Performance Comparison Summary\n";
        echo "=================================\n\n";
        
        $operations = array_keys($this->results[array_key_first($this->results)]);
        
        foreach ($operations as $operation) {
            echo "\n{$operation}:\n";
            $baseline = null;
            
            foreach ($this->results as $db => $results) {
                $reqPerSec = $results[$operation]['requests_per_second'];
                $avgTime = $results[$operation]['avg_time_ms'];
                
                if ($baseline === null) {
                    $baseline = $reqPerSec;
                    $diff = '';
                } else {
                    $percentage = (($reqPerSec - $baseline) / $baseline) * 100;
                    $diff = sprintf(" (%+.1f%%)", $percentage);
                }
                
                printf("  %-10s: %8.2f req/s | %6.2f ms%s\n",
                    $db,
                    $reqPerSec,
                    $avgTime,
                    $diff
                );
            }
        }
        
        echo "\n\n🏆 Overall Performance Ranking:\n";
        echo "==============================\n\n";
        
        $rankings = [];
        foreach ($this->results as $db => $results) {
            $avgReqPerSec = array_sum(array_column($results, 'requests_per_second')) / count($results);
            $rankings[$db] = $avgReqPerSec;
        }
        
        arsort($rankings);
        $rank = 1;
        
        foreach ($rankings as $db => $avgReqPerSec) {
            $totalAvgTime = array_sum(array_column($this->results[$db], 'avg_time_ms'));
            printf("%d. %-10s: %8.2f avg req/s | %6.2f ms total latency\n",
                $rank++,
                $db,
                $avgReqPerSec,
                $totalAvgTime
            );
        }
    }
    
    private function saveResults(): void
    {
        $filename = sprintf('benchmarks/reports/database_benchmark_%s.json',
            date('Y-m-d_H-i-s')
        );
        
        $data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'framework' => 'HelixPHP v2.1.3',
            'php_version' => PHP_VERSION,
            'iterations' => $this->iterations,
            'results' => $this->results,
            'summary' => $this->generateSummary()
        ];
        
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
        echo "\n\n✅ Results saved to: {$filename}\n";
    }
    
    private function generateSummary(): array
    {
        $summary = [];
        
        foreach ($this->results as $db => $results) {
            $summary[$db] = [
                'avg_requests_per_second' => array_sum(array_column($results, 'requests_per_second')) / count($results),
                'total_avg_latency_ms' => array_sum(array_column($results, 'avg_time_ms')),
                'operations' => $results
            ];
        }
        
        return $summary;
    }
}

// Run the benchmark
$benchmark = new SimulatedDatabaseBenchmark();
$benchmark->run();