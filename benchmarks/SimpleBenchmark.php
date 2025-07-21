<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Routing\Router;

/**
 * Simple Performance Benchmark for CI Environments
 *
 * Optimized for GitHub Actions and other CI environments:
 * - Lower iterations for faster execution
 * - Focus on core performance metrics
 * - CI-friendly output format
 */
class SimpleBenchmark
{
    private int $iterations;
    private array $results = [];
    private bool $usePooling = true;

    public function __construct(int $iterations = 500)  // Lower default for CI
    {
        $this->iterations = $iterations;
        
        // Initialize optimized factory with CI-friendly settings
        OptimizedHttpFactory::initialize([
            'enable_pooling' => $this->usePooling,
            'warm_up_pools' => true,
            'enable_metrics' => true,
            'initial_size' => 10,  // Smaller pool for CI
            'max_size' => 50,      // Smaller max pool for CI
        ]);
    }

    public function run(): void
    {
        echo "🚀 Express PHP Framework - Performance Benchmark\n";
        echo "================================================\n\n";

        // Skip extensive warmup in CI
        $this->quickWarmup();
        
        echo "📊 Running benchmarks with {$this->iterations} iterations...\n\n";

        // Core benchmarks - optimized for CI
        $this->benchmarkRequestCreation();
        $this->benchmarkResponseCreation();
        $this->benchmarkHybridOperations();
        $this->benchmarkObjectPooling();
        $this->benchmarkRouteProcessing();

        $this->displayResults();
        $this->displayPoolingMetrics();
    }

    private function quickWarmup(): void
    {
        // Minimal warmup for CI environments
        for ($i = 0; $i < 50; $i++) {  // Much smaller warmup
            $request = OptimizedHttpFactory::createServerRequest('GET', '/test');
            $response = OptimizedHttpFactory::createResponse();
            unset($request, $response);
        }
    }

    private function benchmarkRequestCreation(): void
    {
        echo "📋 Benchmarking Request Creation...\n";
        
        $start = hrtime(true);
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $request = OptimizedHttpFactory::createServerRequest('GET', '/api/test');
            unset($request);
        }
        
        $end = hrtime(true);
        $elapsed = ($end - $start) / 1_000_000_000; // Convert to seconds
        $opsPerSec = (int)($this->iterations / $elapsed);
        
        $this->results['request_creation'] = $opsPerSec;
        
        echo "  ✅ Completed in " . number_format($elapsed, 4) . "s\n";
        echo "  📈 " . number_format($opsPerSec) . " ops/sec\n\n";
    }

    private function benchmarkResponseCreation(): void
    {
        echo "📋 Benchmarking Response Creation...\n";
        
        $start = hrtime(true);
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $response = OptimizedHttpFactory::createResponse();
            unset($response);
        }
        
        $end = hrtime(true);
        $elapsed = ($end - $start) / 1_000_000_000;
        $opsPerSec = (int)($this->iterations / $elapsed);
        
        $this->results['response_creation'] = $opsPerSec;
        
        echo "  ✅ Completed in " . number_format($elapsed, 4) . "s\n";
        echo "  📈 " . number_format($opsPerSec) . " ops/sec\n\n";
    }

    private function benchmarkHybridOperations(): void
    {
        echo "📋 Benchmarking Hybrid Operations...\n";
        
        $start = hrtime(true);
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $request = OptimizedHttpFactory::createServerRequest('POST', '/api/users');
            $response = OptimizedHttpFactory::createResponse();
            
            // Simulate Express.js style operations
            $response->status(201);
            $response->header('Content-Type', 'application/json');
            
            unset($request, $response);
        }
        
        $end = hrtime(true);
        $elapsed = ($end - $start) / 1_000_000_000;
        $opsPerSec = (int)($this->iterations / $elapsed);
        
        $this->results['hybrid_operations'] = $opsPerSec;
        
        echo "  ✅ Completed in " . number_format($elapsed, 4) . "s\n";
        echo "  📈 " . number_format($opsPerSec) . " ops/sec\n\n";
    }

    private function benchmarkObjectPooling(): void
    {
        echo "📋 Benchmarking Object Pooling...\n";
        
        $start = hrtime(true);
        
        for ($i = 0; $i < $this->iterations; $i++) {
            $request = OptimizedHttpFactory::createServerRequest('GET', '/pool/test');
            $response = OptimizedHttpFactory::createResponse();
            
            // Pool operations
            unset($request, $response);
        }
        
        $end = hrtime(true);
        $elapsed = ($end - $start) / 1_000_000_000;
        $opsPerSec = (int)($this->iterations / $elapsed);
        
        $this->results['object_pooling'] = $opsPerSec;
        
        echo "  ✅ Completed in " . number_format($elapsed, 4) . "s\n";
        echo "  📈 " . number_format($opsPerSec) . " ops/sec\n\n";
    }

    private function benchmarkRouteProcessing(): void
    {
        echo "📋 Benchmarking Route Processing...\n";
        
        // Simple route processing simulation
        $routes = [
            'GET:/test' => function() { return 'test'; },
            'POST:/api/users' => function() { return 'create'; },
            'GET:/api/users/123' => function() { return 'show'; },
        ];
        
        $start = hrtime(true);
        
        for ($i = 0; $i < $this->iterations; $i++) {
            // Simulate route matching
            foreach ($routes as $pattern => $handler) {
                if (is_callable($handler)) {
                    $result = true; // Simulate successful match
                }
            }
        }
        
        $end = hrtime(true);
        $elapsed = ($end - $start) / 1_000_000_000;
        $opsPerSec = (int)(($this->iterations * count($routes)) / $elapsed);
        
        $this->results['route_processing'] = $opsPerSec;
        
        echo "  ✅ Completed in " . number_format($elapsed, 4) . "s\n";
        echo "  📈 " . number_format($opsPerSec) . " ops/sec\n\n";
    }

    private function displayResults(): void
    {
        echo "📊 BENCHMARK RESULTS\n";
        echo "===================\n\n";

        $totalTime = 0;
        foreach ($this->results as $test => $opsPerSec) {
            $testName = str_replace('_', ' ', ucwords($test, '_'));
            $time = $this->iterations / $opsPerSec;
            $totalTime += $time;
            
            echo sprintf("%-20s: %s ops/sec (%.4fs)\n", 
                $testName, 
                number_format($opsPerSec), 
                $time
            );
        }

        // Calculate average performance
        $avgPerformance = (int)(array_sum($this->results) / count($this->results));

        echo "\n📈 Average Performance: " . number_format($avgPerformance) . " ops/sec\n";
        echo "⏱️  Total Time: " . number_format($totalTime, 4) . "s\n";
        echo "🧠 Peak Memory: " . number_format(memory_get_peak_usage() / 1024 / 1024, 0) . " MB\n";
        echo "💾 Current Memory: " . number_format(memory_get_usage() / 1024 / 1024, 0) . " MB\n";
    }

    private function displayPoolingMetrics(): void
    {
        // Check if getPoolingMetrics method exists
        if (!method_exists(OptimizedHttpFactory::class, 'getPoolingMetrics')) {
            echo "\n♻️  OBJECT POOLING METRICS\n";
            echo "=========================\n\n";
            echo "Pool metrics not available in current version.\n";
            echo "Memory efficiency: " . number_format(memory_get_usage() / 1024 / 1024, 1) . " MB used\n";
            return;
        }
        
        $metrics = OptimizedHttpFactory::getPoolingMetrics();
        
        if (!empty($metrics)) {
            echo "\n♻️  OBJECT POOLING METRICS\n";
            echo "=========================\n\n";
            
            echo "Pool Efficiency:\n";
            foreach ($metrics as $type => $metric) {
                if (isset($metric['reuse_rate'])) {
                    $rate = number_format($metric['reuse_rate'], 1);
                    $status = $metric['reuse_rate'] >= 80 ? '🟢' : ($metric['reuse_rate'] >= 50 ? '🟡' : '🔴');
                    echo "  $status " . ucfirst($type) . " Reuse Rate  : $status {$rate}%\n";
                }
            }
            
            echo "\nMemory Usage:\n";
            echo "  Current: " . number_format(memory_get_usage() / 1024 / 1024, 0) . " MB\n";
            echo "  Peak: " . number_format(memory_get_peak_usage() / 1024 / 1024, 0) . " MB\n";
            
            // Simple recommendations
            echo "\nRecommendations:\n";
            $avgReuse = 0;
            $count = 0;
            foreach ($metrics as $metric) {
                if (isset($metric['reuse_rate'])) {
                    $avgReuse += $metric['reuse_rate'];
                    $count++;
                }
            }
            
            if ($count > 0) {
                $avgReuse = $avgReuse / $count;
                if ($avgReuse >= 90) {
                    echo "  • Excellent pool utilization across all components\n";
                } elseif ($avgReuse >= 70) {
                    echo "  • Good pool utilization with room for optimization\n";
                } else {
                    echo "  • Pool utilization could be improved\n";
                }
            }
        }
    }
}

// Run if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $iterations = isset($argv[1]) ? (int)$argv[1] : 500;
    $benchmark = new SimpleBenchmark($iterations);
    $benchmark->run();
    
    echo "\n✅ Benchmark completed successfully!\n";
}