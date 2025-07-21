<?php

declare(strict_types=1);

/**
 * Enhanced Advanced Optimizations Benchmark
 *
 * Captures real performance data from implemented optimizations
 * with detailed metrics and statistical analysis.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Middleware\MiddlewarePipelineCompiler;
// use PivotPHP\Core\Http\Optimization\ZeroCopyOptimizer; // REMOVED - Advanced optimization
// use PivotPHP\Core\Http\Optimization\MemoryMappingManager; // REMOVED - Advanced optimization
// use PivotPHP\Core\Http\Psr7\Cache\PredictiveCacheWarmer; // REMOVED - ML complexity
use PivotPHP\Core\Routing\RouteMemoryManager;

class EnhancedAdvancedOptimizationsBenchmark
{
    private array $results = [];
    private int $iterations;
    private string $testFile;

    public function __construct(int $iterations = 1000)
    {
        $this->iterations = $iterations;
        $this->testFile = __DIR__ . '/test_data.txt';
        $this->createTestData();
    }

    public function run(): void
    {
        echo "🚀 Enhanced Advanced Optimizations Benchmark\n";
        echo "═══════════════════════════════════════════════\n\n";

        // Clear previous stats
        $this->clearOptimizationStats();

        // Run all optimization benchmarks
        $this->benchmarkMiddlewarePipelineCompiler();
        $this->benchmarkZeroCopyOptimizations();
        $this->benchmarkMemoryMapping();
        $this->benchmarkPredictiveCache();
        $this->benchmarkRouteMemoryManager();
        $this->benchmarkIntegratedPerformance();

        // Generate detailed report
        $this->generateDetailedReport();

        echo "✅ Enhanced benchmark completed successfully!\n";
    }

    private function createTestData(): void
    {
        if (!file_exists($this->testFile)) {
            $content = "";
            for ($i = 0; $i < 10000; $i++) {
                $content .= "Line {$i}: This is test data for memory mapping and file operations.\n";
            }
            file_put_contents($this->testFile, $content);
        }
    }

    private function clearOptimizationStats(): void
    {
        // Clear all optimization caches and stats
        MiddlewarePipelineCompiler::clearAll();
        // ZeroCopyOptimizer::reset(); // REMOVED - Advanced optimization

        if (class_exists('Helix\Routing\RouteMemoryManager')) {
            RouteMemoryManager::clearAll();
        }
    }

    private function benchmarkMiddlewarePipelineCompiler(): void
    {
        echo "🧠 Testing Enhanced Middleware Pipeline Compiler...\n";

        $patterns = [
            ['cors', 'auth', 'json'],
            ['cors', 'security', 'auth', 'csrf', 'session'],
            ['cors', 'rate_limit', 'cache', 'json'],
            ['cors', 'auth', 'json', 'validation', 'logging'],
            ['cors', 'logging', 'json'],
        ];

        // Training phase - establish patterns
        $trainingStart = microtime(true);
        $compilations = 0;

        for ($i = 0; $i < $this->iterations; $i++) {
            foreach ($patterns as $pattern) {
                $middlewares = array_map(function($type) {
                    return function($req, $res, $next) use ($type) {
                        // Simulate middleware work
                        return $next($req, $res);
                    };
                }, $pattern);

                MiddlewarePipelineCompiler::compilePipeline($middlewares);
                $compilations++;
            }
        }

        $trainingTime = microtime(true) - $trainingStart;
        $trainingStats = MiddlewarePipelineCompiler::getStats();

        // Usage phase - test cache efficiency
        $usageStart = microtime(true);
        $usageCompilations = 0;

        for ($i = 0; $i < $this->iterations; $i++) {
            foreach ($patterns as $pattern) {
                $middlewares = array_map(function($type) {
                    return function($req, $res, $next) use ($type) {
                        return $next($req, $res);
                    };
                }, $pattern);

                MiddlewarePipelineCompiler::compilePipeline($middlewares);
                $usageCompilations++;
            }
        }

        $usageTime = microtime(true) - $usageStart;
        $usageStats = MiddlewarePipelineCompiler::getStats();

        // Test garbage collection
        $gcStart = microtime(true);
        $gcStats = MiddlewarePipelineCompiler::performIntelligentGC();
        $gcTime = microtime(true) - $gcStart;

        $this->results['middleware_pipeline'] = [
            'training_phase' => [
                'compilations' => $compilations,
                'time_seconds' => $trainingTime,
                'ops_per_second' => $compilations / $trainingTime,
                'cache_hit_rate' => $trainingStats['cache_hit_rate'],
                'patterns_learned' => $trainingStats['patterns_learned']
            ],
            'usage_phase' => [
                'compilations' => $usageCompilations,
                'time_seconds' => $usageTime,
                'ops_per_second' => $usageCompilations / $usageTime,
                'cache_hit_rate' => $usageStats['cache_hit_rate'],
                'intelligent_matches' => $usageStats['intelligent_matches']
            ],
            'garbage_collection' => [
                'time_seconds' => $gcTime,
                'pipelines_removed' => $gcStats['pipelines_removed'],
                'memory_freed' => $gcStats['memory_freed'],
                'patterns_optimized' => $gcStats['patterns_optimized']
            ],
            'final_stats' => $usageStats
        ];

        echo sprintf("   Training: %d compilations/sec (%.1f%% hit rate)\n",
            $compilations / $trainingTime, $trainingStats['cache_hit_rate']);
        echo sprintf("   Usage: %d compilations/sec (%.1f%% hit rate)\n",
            $usageCompilations / $usageTime, $usageStats['cache_hit_rate']);
        echo sprintf("   GC: %d pipelines removed, %s freed\n",
            $gcStats['pipelines_removed'], $this->formatBytes($gcStats['memory_freed']));
    }

    private function benchmarkZeroCopyOptimizations(): void
    {
        echo "⚡ Testing Enhanced Zero-Copy Optimizations...\n";

        $testStrings = [];
        for ($i = 0; $i < 100; $i++) {
            $testStrings[] = "Test string {$i} for interning optimization performance analysis";
        }

        // String operations benchmark (simplified)
        $stringStart = microtime(true);
        for ($i = 0; $i < $this->iterations; $i++) {
            foreach ($testStrings as $str) {
                $result = $str; // Simple assignment instead of interning
            }
        }
        $stringTime = microtime(true) - $stringStart;
        $stringOps = ($this->iterations * count($testStrings)) / $stringTime;

        // Array operations benchmark (simplified)
        $testArrays = [];
        for ($i = 0; $i < 50; $i++) {
            $testArrays[] = array_fill(0, 100, "data_{$i}");
        }

        $arrayStart = microtime(true);
        $arrayRefs = [];
        for ($i = 0; $i < $this->iterations; $i++) {
            foreach ($testArrays as $key => $array) {
                $refId = "test_ref_{$i}_{$key}"; // Simple assignment instead of reference
                $arrayRefs[] = $refId;
            }
        }
        $arrayTime = microtime(true) - $arrayStart;
        $arrayOps = ($this->iterations * count($testArrays)) / $arrayTime;

        // Object operations benchmark (simplified)
        $cowStart = microtime(true);
        for ($i = 0; $i < $this->iterations; $i++) {
            $obj = (object)['data' => "test_{$i}", 'value' => $i];
            $result = $obj; // Simple assignment instead of COW wrapper
        }
        $cowTime = microtime(true) - $cowStart;
        $cowOps = $this->iterations / $cowTime;

        // String concatenation benchmark (simplified)
        $concatStrings = array_fill(0, 1000, "concat_test_string");
        $concatStart = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $result = implode('', $concatStrings); // Simple implode instead of efficient concat
        }
        $concatTime = microtime(true) - $concatStart;

        // Memory cleanup (simplified)
        $cleanupStart = microtime(true);
        $cleanupStats = ['references_cleaned' => 0, 'memory_freed' => 0]; // Simple stats instead of cleanup
        $cleanupTime = microtime(true) - $cleanupStart;

        $finalStats = ['optimization_removed' => true]; // Simple stats instead of complex optimization

        $this->results['zero_copy_optimizations'] = [
            'string_interning' => [
                'ops_per_second' => $stringOps,
                'time_seconds' => $stringTime,
                'operations' => $this->iterations * count($testStrings)
            ],
            'array_references' => [
                'ops_per_second' => $arrayOps,
                'time_seconds' => $arrayTime,
                'operations' => $this->iterations * count($testArrays)
            ],
            'copy_on_write' => [
                'ops_per_second' => $cowOps,
                'time_seconds' => $cowTime,
                'operations' => $this->iterations
            ],
            'efficient_concatenation' => [
                'time_seconds' => $concatTime,
                'strings_concatenated' => 1000 * 100
            ],
            'cleanup' => [
                'time_seconds' => $cleanupTime,
                'references_cleaned' => $cleanupStats['references_cleaned'],
                'memory_freed' => $cleanupStats['memory_freed']
            ],
            'final_stats' => $finalStats
        ];

        echo sprintf("   String Operations: %d ops/sec\n", $stringOps);
        echo sprintf("   Array Operations: %d ops/sec\n", $arrayOps);
        echo sprintf("   Object Operations: %d ops/sec\n", $cowOps);
        echo sprintf("   String Concat: %.4f seconds for %d strings\n", $concatTime, 100000);
        echo sprintf("   Zero-copy complexity: REMOVED\n");
    }

    private function benchmarkMemoryMapping(): void
    {
        echo "🗺️ Testing Enhanced Memory Mapping...\n";

        // File operations benchmark (simplified)
        $mappingStart = microtime(true);
        $mapping = null; // Memory mapping removed
        $mappingTime = microtime(true) - $mappingStart;

        echo "   Memory mapping REMOVED - Following simplification principle\n";
        $this->results['memory_mapping'] = ['status' => 'removed_for_simplicity'];
        return;

        // Section reading benchmark
        $sectionStart = microtime(true);
        for ($i = 0; $i < $this->iterations; $i++) {
            $section = $mapping->read(0, 1024);
        }
        $sectionTime = microtime(true) - $sectionStart;
        $sectionOps = $this->iterations / $sectionTime;

        // Search benchmark
        $searchStart = microtime(true);
        $matches = 0;
        $content = $mapping->read(0, min(50000, $mapping->getSize()));
        for ($i = 0; $i < 100; $i++) {
            $pattern = "Line {$i}:";
            if (strpos($content, $pattern) !== false) {
                $matches++;
            }
        }
        $searchTime = microtime(true) - $searchStart;

        // Line processing benchmark
        $lineStart = microtime(true);
        $lineCount = 0;
        $lines = explode("\n", $content);
        $lineCount = count($lines);
        $lineTime = microtime(true) - $lineStart;
        $lineOps = $lineCount / $lineTime;

        $this->results['memory_mapping'] = [
            'mapping_creation' => [
                'time_seconds' => $mappingTime,
                'file_size' => filesize($this->testFile),
                'mapped_size' => $mapping->getSize()
            ],
            'section_reading' => [
                'ops_per_second' => $sectionOps,
                'time_seconds' => $sectionTime,
                'operations' => $this->iterations
            ],
            'search_performance' => [
                'time_seconds' => $searchTime,
                'matches_found' => $matches,
                'searches_performed' => 100
            ],
            'line_processing' => [
                'lines_per_second' => $lineOps,
                'time_seconds' => $lineTime,
                'total_lines' => $lineCount
            ]
        ];

        echo sprintf("   Mapping Creation: %.4f seconds\n", $mappingTime);
        echo sprintf("   Section Reading: %d ops/sec\n", $sectionOps);
        echo sprintf("   Search: %.4f seconds (%d matches)\n", $searchTime, $matches);
        echo sprintf("   Line Processing: %.0f lines/sec\n", $lineOps);
        echo sprintf("   Search: %.4f seconds (%d matches)\n", $searchTime, $matches);
        echo sprintf("   Line Processing: %.0f lines/sec\n", $lineOps);
    }
    
    private function benchmarkPredictiveCache(): void
    {
        echo "🔮 Testing Enhanced Predictive Cache Warming...\n";

        // ML complexity removed for microframework simplicity
        echo "   ML-based cache warming eliminated\n";
        $this->results['predictive_cache'] = ['status' => 'removed_for_simplicity'];
        return;
    }

    private function benchmarkRouteMemoryManager(): void
    {
        echo "🛣️ Testing Enhanced Route Memory Manager...\n";

        if (!class_exists('Helix\Routing\RouteMemoryManager')) {
            echo "   Route Memory Manager not available\n";
            $this->results['route_memory'] = ['status' => 'not_available'];
            return;
        }

        RouteMemoryManager::initialize();

        // Route usage simulation
        $routes = [];
        for ($i = 0; $i < 1000; $i++) {
            $routes[] = "route_key_{$i}";
        }

        $trackingStart = microtime(true);
        for ($i = 0; $i < $this->iterations; $i++) {
            foreach ($routes as $route) {
                RouteMemoryManager::trackRouteUsage($route);
            }
        }
        $trackingTime = microtime(true) - $trackingStart;
        $trackingOps = ($this->iterations * count($routes)) / $trackingTime;

        // Memory usage check
        $memoryStart = microtime(true);
        $memoryStats = RouteMemoryManager::checkMemoryUsage();
        $memoryTime = microtime(true) - $memoryStart;

        $finalStats = RouteMemoryManager::getStats();

        $this->results['route_memory'] = [
            'tracking' => [
                'ops_per_second' => $trackingOps,
                'time_seconds' => $trackingTime,
                'routes_tracked' => $this->iterations * count($routes)
            ],
            'memory_check' => [
                'time_seconds' => $memoryTime,
                'memory_stats' => $memoryStats
            ],
            'final_stats' => $finalStats
        ];

        echo sprintf("   Route Tracking: %d ops/sec\n", $trackingOps);
        echo sprintf("   Memory Check: %.4f seconds\n", $memoryTime);
    }

    private function benchmarkIntegratedPerformance(): void
    {
        echo "🔗 Testing Integrated Performance...\n";

        $integratedStart = microtime(true);
        $operations = 0;

        // Simulate realistic workload combining all optimizations
        for ($i = 0; $i < 100; $i++) {
            // Pipeline compilation
            $middlewares = [
                function($req, $res, $next) { return $next($req, $res); },
                function($req, $res, $next) { return $next($req, $res); },
                function($req, $res, $next) { return $next($req, $res); }
            ];
            MiddlewarePipelineCompiler::compilePipeline($middlewares);
            $operations++;

            // String operations (simplified)
            for ($j = 0; $j < 10; $j++) {
                $result = "integrated_test_string_{$i}_{$j}"; // Simple assignment instead of interning
                $operations++;
            }

            // File operations (simplified)
            if (file_exists($this->testFile)) {
                $content = file_get_contents($this->testFile); // Simple file reading instead of mapping
                if ($content) {
                    $operations++;
                }
            }

            // Route tracking
            if (class_exists('Helix\Routing\RouteMemoryManager')) {
                RouteMemoryManager::trackRouteUsage("integrated_route_{$i}");
                $operations++;
            }
        }

        $integratedTime = microtime(true) - $integratedStart;
        $integratedOps = $operations / $integratedTime;

        // Get final stats from all components
        $pipelineStats = MiddlewarePipelineCompiler::getStats();
        $zeroCopyStats = ['optimization_removed' => true, 'memory_saved' => '0 B']; // Simplified stats
        $routeStats = class_exists('Helix\Routing\RouteMemoryManager') ?
            RouteMemoryManager::getStats() : ['status' => 'not_available'];

        $this->results['integrated_performance'] = [
            'total_operations' => $operations,
            'ops_per_second' => $integratedOps,
            'time_seconds' => $integratedTime,
            'pipeline_cache_hit_rate' => $pipelineStats['cache_hit_rate'],
            'zero_copy_efficiency' => $zeroCopyStats['memory_saved'],
            'component_stats' => [
                'pipeline' => $pipelineStats,
                'zero_copy' => $zeroCopyStats,
                'route_memory' => $routeStats
            ]
        ];

        echo sprintf("   Total Operations: %d ops/sec\n", $integratedOps);
        echo sprintf("   Pipeline Cache Hit Rate: %.1f%%\n", $pipelineStats['cache_hit_rate']);
        echo sprintf("   Zero-Copy Memory Saved: %s\n", $zeroCopyStats['memory_saved']);
        echo sprintf("   Advanced optimizations: REMOVED\n");
    }

    private function generateDetailedReport(): void
    {
        echo "\n📊 Enhanced Advanced Optimizations - Detailed Report\n";
        echo "════════════════════════════════════════════════════════\n";

        // Overall performance metrics
        $currentMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        echo "🎯 Overall Performance Metrics:\n";
        echo sprintf("   - Peak memory usage: %s\n", $this->formatBytes($peakMemory));
        echo sprintf("   - Current memory usage: %s\n", $this->formatBytes($currentMemory));
        echo sprintf("   - Test iterations: %d\n", $this->iterations);
        echo sprintf("   - Test file size: %s\n",
            file_exists($this->testFile) ? $this->formatBytes(filesize($this->testFile)) : 'N/A');

        // Component-specific reports
        foreach ($this->results as $component => $data) {
            echo "\n📈 " . ucfirst(str_replace('_', ' ', $component)) . ":\n";
            $this->printComponentStats($data);
        }

        // Save detailed JSON report
        $reportFile = __DIR__ . '/reports/enhanced_advanced_optimizations_' . date('Y-m-d_H-i-s') . '.json';
        $reportData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'iterations' => $this->iterations,
            'memory_usage' => [
                'current' => $currentMemory,
                'peak' => $peakMemory
            ],
            'results' => $this->results
        ];

        if (!is_dir(dirname($reportFile))) {
            mkdir(dirname($reportFile), 0755, true);
        }

        file_put_contents($reportFile, json_encode($reportData, JSON_PRETTY_PRINT));
        echo "\n📋 Detailed JSON report saved: {$reportFile}\n";
    }

    private function printComponentStats(array $data): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (isset($value['ops_per_second'])) {
                    echo sprintf("   - %s: %s ops/sec\n",
                        ucfirst(str_replace('_', ' ', $key)),
                        number_format($value['ops_per_second']));
                } elseif (isset($value['time_seconds'])) {
                    echo sprintf("   - %s: %.4f seconds\n",
                        ucfirst(str_replace('_', ' ', $key)),
                        $value['time_seconds']);
                } else {
                    echo sprintf("   - %s: [complex data]\n",
                        ucfirst(str_replace('_', ' ', $key)));
                }
            } else {
                echo sprintf("   - %s: %s\n",
                    ucfirst(str_replace('_', ' ', $key)),
                    is_numeric($value) ? number_format($value) : $value);
            }
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return number_format($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        } elseif ($bytes >= 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}

// Execute benchmark
$iterations = isset($argv[1]) ? (int)$argv[1] : 1000;
$benchmark = new EnhancedAdvancedOptimizationsBenchmark($iterations);
$benchmark->run();
