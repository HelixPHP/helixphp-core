<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Express\Middleware\MiddlewarePipelineCompiler;
use Express\Routing\RouteMemoryManager;
use Express\Http\Psr7\Cache\ProbabilisticCache;
use Express\Http\Psr7\Cache\AdaptiveLearningCache;

/**
 * Comprehensive Benchmark for Advanced Optimizations Phase 2
 *
 * Tests Middleware Pipeline Compilation, Route Memory Management,
 * and Advanced Caching Strategies
 */
class AdvancedOptimizationsPhase2Benchmark
{
    private int $iterations = 1000;
    private array $results = [];

    public function __construct(int $iterations = 1000)
    {
        $this->iterations = $iterations;
    }

    public function run(): void
    {
        echo "🚀 Running Advanced Optimizations Phase 2 Benchmark\n";
        echo "═══════════════════════════════════════════════════\n\n";

        $this->benchmarkMiddlewarePipelineCompilation();
        $this->benchmarkRouteMemoryManagement();
        $this->benchmarkProbabilisticCache();
        $this->benchmarkAdaptiveLearningCache();
        $this->benchmarkIntegratedPerformance();
        $this->generateComprehensiveReport();
    }

    private function benchmarkMiddlewarePipelineCompilation(): void
    {
        echo "🔄 Testing Middleware Pipeline Compilation...\n";

        // Test different middleware stack sizes
        $smallStack = $this->createMiddlewareStack(3);
        $mediumStack = $this->createMiddlewareStack(7);
        $largeStack = $this->createMiddlewareStack(15);

        // Warm up compiler
        MiddlewarePipelineCompiler::warmUp();

        // Benchmark small stack
        $startTime = microtime(true);
        for ($i = 0; $i < $this->iterations; $i++) {
            $pipeline = MiddlewarePipelineCompiler::compilePipeline($smallStack, "small_$i");
        }
        $endTime = microtime(true);
        $smallStackOps = $this->iterations / ($endTime - $startTime);

        // Benchmark medium stack
        $startTime = microtime(true);
        for ($i = 0; $i < $this->iterations; $i++) {
            $pipeline = MiddlewarePipelineCompiler::compilePipeline($mediumStack, "medium_$i");
        }
        $endTime = microtime(true);
        $mediumStackOps = $this->iterations / ($endTime - $startTime);

        // Benchmark large stack
        $startTime = microtime(true);
        for ($i = 0; $i < $this->iterations; $i++) {
            $pipeline = MiddlewarePipelineCompiler::compilePipeline($largeStack, "large_$i");
        }
        $endTime = microtime(true);
        $largeStackOps = $this->iterations / ($endTime - $startTime);

        // Test pattern recognition performance
        $patternRecognitionOps = $this->benchmarkPatternRecognition();

        $stats = MiddlewarePipelineCompiler::getStats();

        echo sprintf("   - Small stacks (3 middleware): %.0f compilations/sec\n", $smallStackOps);
        echo sprintf("   - Medium stacks (7 middleware): %.0f compilations/sec\n", $mediumStackOps);
        echo sprintf("   - Large stacks (15 middleware): %.0f compilations/sec\n", $largeStackOps);
        echo sprintf("   - Pattern recognition: %.0f ops/sec\n", $patternRecognitionOps);
        echo sprintf("   - Cache hit rate: %.1f%%\n", $stats['cache_hit_rate']);
        echo sprintf("   - Optimizations applied: %d\n", $stats['optimizations_applied']);
        echo sprintf("   - Redundancies removed: %d\n", $stats['redundancies_removed']);

        $this->results['Middleware Pipeline Compilation'] = [
            'small_stack_ops' => $smallStackOps,
            'medium_stack_ops' => $mediumStackOps,
            'large_stack_ops' => $largeStackOps,
            'pattern_recognition_ops' => $patternRecognitionOps,
            'stats' => $stats
        ];

        echo "\n";
    }

    private function benchmarkRouteMemoryManagement(): void
    {
        echo "🔄 Testing Route Memory Management...\n";

        RouteMemoryManager::initialize();

        // Simulate route creation and memory pressure
        $routeCreationOps = $this->benchmarkRouteCreation();
        $memoryOptimizationOps = $this->benchmarkMemoryOptimization();
        $gcEfficiency = $this->benchmarkGarbageCollection();

        $memoryStats = RouteMemoryManager::getStats();

        echo sprintf("   - Route creation/tracking: %.0f routes/sec\n", $routeCreationOps);
        echo sprintf("   - Memory optimization: %.0f ops/sec\n", $memoryOptimizationOps);
        echo sprintf("   - GC efficiency: %.1f%%\n", $gcEfficiency);
        echo sprintf("   - Memory status: %s\n", $memoryStats['memory_status']);
        echo sprintf("   - Routes tracked: %d\n", $memoryStats['route_usage_tracked']);

        $this->results['Route Memory Management'] = [
            'route_creation_ops' => $routeCreationOps,
            'memory_optimization_ops' => $memoryOptimizationOps,
            'gc_efficiency' => $gcEfficiency,
            'stats' => $memoryStats
        ];

        echo "\n";
    }

    private function benchmarkProbabilisticCache(): void
    {
        echo "🔄 Testing Probabilistic Cache...\n";

        ProbabilisticCache::clear();

        // Test basic cache operations
        $getOps = $this->benchmarkProbabilisticGet();
        $setOps = $this->benchmarkProbabilisticSet();
        $warmingEfficiency = $this->benchmarkCacheWarming();

        $stats = ProbabilisticCache::getStats();

        echo sprintf("   - Get operations: %.0f ops/sec\n", $getOps);
        echo sprintf("   - Set operations: %.0f ops/sec\n", $setOps);
        echo sprintf("   - Warming efficiency: %.1f%%\n", $warmingEfficiency);
        echo sprintf("   - Hit rate: %.1f%%\n", $stats['hit_rate'] * 100);
        echo sprintf("   - Preemptive loads: %d\n", $stats['preemptive_loads']);

        $this->results['Probabilistic Cache'] = [
            'get_ops' => $getOps,
            'set_ops' => $setOps,
            'warming_efficiency' => $warmingEfficiency,
            'stats' => $stats
        ];

        echo "\n";
    }

    private function benchmarkAdaptiveLearningCache(): void
    {
        echo "🔄 Testing Adaptive Learning Cache...\n";

        AdaptiveLearningCache::initialize();
        AdaptiveLearningCache::clear();

        // Test learning cache operations
        $learningGetOps = $this->benchmarkLearningGet();
        $adaptationSpeed = $this->benchmarkAdaptationSpeed();
        $predictionAccuracy = $this->benchmarkPredictionAccuracy();

        $stats = AdaptiveLearningCache::getLearningStats();

        echo sprintf("   - Learning get operations: %.0f ops/sec\n", $learningGetOps);
        echo sprintf("   - Adaptation speed: %.0f adaptations/sec\n", $adaptationSpeed);
        echo sprintf("   - Prediction accuracy: %.1f%%\n", $predictionAccuracy * 100);
        echo sprintf("   - Models created: %d\n", $stats['models_count']);
        echo sprintf("   - Learning cycles: %d\n", $stats['global_stats']['learning_cycles']);

        $this->results['Adaptive Learning Cache'] = [
            'learning_get_ops' => $learningGetOps,
            'adaptation_speed' => $adaptationSpeed,
            'prediction_accuracy' => $predictionAccuracy,
            'stats' => $stats
        ];

        echo "\n";
    }

    private function benchmarkIntegratedPerformance(): void
    {
        echo "🔄 Testing Integrated Performance...\n";

        // Test all optimizations working together
        $integratedOps = $this->benchmarkFullPipeline();
        $memoryEfficiency = $this->benchmarkMemoryEfficiency();
        $scalabilityScore = $this->benchmarkScalability();

        echo sprintf("   - Full pipeline: %.0f requests/sec\n", $integratedOps);
        echo sprintf("   - Memory efficiency: %.1f KB per operation\n", $memoryEfficiency);
        echo sprintf("   - Scalability score: %.1f/10\n", $scalabilityScore);

        $this->results['Integrated Performance'] = [
            'full_pipeline_ops' => $integratedOps,
            'memory_efficiency' => $memoryEfficiency,
            'scalability_score' => $scalabilityScore
        ];

        echo "\n";
    }

    // Helper benchmark methods

    private function createMiddlewareStack(int $size): array
    {
        $stack = [];
        $middlewareTypes = ['cors', 'auth', 'json', 'validation', 'logging', 'cache', 'security'];

        for ($i = 0; $i < $size; $i++) {
            $type = $middlewareTypes[$i % count($middlewareTypes)];
            $stack[] = function($req, $res, $next) use ($type) {
                // Simulate middleware processing
                return $next($req, $res);
            };
        }

        return $stack;
    }

    private function benchmarkPatternRecognition(): float
    {
        $stacks = [
            ['cors', 'auth', 'json'],
            ['cors', 'session', 'csrf'],
            ['cors', 'rate_limit'],
            ['cors', 'auth', 'admin_check', 'csrf']
        ];

        $startTime = microtime(true);
        for ($i = 0; $i < $this->iterations; $i++) {
            $stack = $stacks[$i % count($stacks)];
            MiddlewarePipelineCompiler::compilePipeline($stack);
        }
        $endTime = microtime(true);

        return $this->iterations / ($endTime - $startTime);
    }

    private function benchmarkRouteCreation(): float
    {
        $startTime = microtime(true);
        for ($i = 0; $i < $this->iterations; $i++) {
            $routeKey = "GET:/api/test/$i";
            // Track route usage instead of non-existent trackRoute method
            RouteMemoryManager::trackRouteUsage($routeKey);
        }
        $endTime = microtime(true);

        return $this->iterations / ($endTime - $startTime);
    }

    private function benchmarkMemoryOptimization(): float
    {
        $startTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            RouteMemoryManager::checkMemoryUsage();
        }
        $endTime = microtime(true);

        return 100 / ($endTime - $startTime);
    }

    private function benchmarkGarbageCollection(): float
    {
        $initialMemory = memory_get_usage();

        // Create lots of routes to simulate memory pressure
        for ($i = 0; $i < 1000; $i++) {
            RouteMemoryManager::trackRouteUsage("GET:/temp/$i");
        }

        $beforeGC = memory_get_usage();
        // Use checkMemoryUsage instead of performCleanup which might not exist
        RouteMemoryManager::checkMemoryUsage();
        $afterGC = memory_get_usage();

        $memoryFreed = $beforeGC - $afterGC;
        $memoryCreated = $beforeGC - $initialMemory;

        return $memoryCreated > 0 ? ($memoryFreed / $memoryCreated) * 100 : 0;
    }

    private function benchmarkProbabilisticGet(): float
    {
        // Pre-populate cache
        for ($i = 0; $i < 100; $i++) {
            ProbabilisticCache::set("key_$i", "value_$i");
        }

        $startTime = microtime(true);
        for ($i = 0; $i < $this->iterations; $i++) {
            $key = "key_" . ($i % 100);
            ProbabilisticCache::get($key);
        }
        $endTime = microtime(true);

        return $this->iterations / ($endTime - $startTime);
    }

    private function benchmarkProbabilisticSet(): float
    {
        $startTime = microtime(true);
        for ($i = 0; $i < $this->iterations; $i++) {
            ProbabilisticCache::set("set_key_$i", "value_$i");
        }
        $endTime = microtime(true);

        return $this->iterations / ($endTime - $startTime);
    }

    private function benchmarkCacheWarming(): float
    {
        $warmed = ProbabilisticCache::warmCache(50);
        return $warmed > 0 ? ($warmed / 50) * 100 : 0;
    }

    private function benchmarkLearningGet(): float
    {
        // Pre-populate and train
        for ($i = 0; $i < 50; $i++) {
            AdaptiveLearningCache::set("learn_key_$i", "value_$i", null, ['user_id' => $i % 10]);
        }

        $startTime = microtime(true);
        for ($i = 0; $i < $this->iterations; $i++) {
            $key = "learn_key_" . ($i % 50);
            $context = ['user_id' => $i % 10, 'hour' => date('H')];
            AdaptiveLearningCache::get($key, null, $context);
        }
        $endTime = microtime(true);

        return $this->iterations / ($endTime - $startTime);
    }

    private function benchmarkAdaptationSpeed(): float
    {
        $initialAdaptations = AdaptiveLearningCache::getLearningStats()['global_stats']['adaptations_made'];

        $startTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $key = "adapt_key_$i";
            $context = ['feature_' . ($i % 5) => rand(1, 100)];
            AdaptiveLearningCache::set($key, "value_$i", null, $context);
        }
        $endTime = microtime(true);

        $finalAdaptations = AdaptiveLearningCache::getLearningStats()['global_stats']['adaptations_made'];
        $adaptationsMade = $finalAdaptations - $initialAdaptations;

        return $adaptationsMade / ($endTime - $startTime);
    }

    private function benchmarkPredictionAccuracy(): float
    {
        $stats = AdaptiveLearningCache::getLearningStats();
        return $stats['global_stats']['prediction_accuracy'];
    }

    private function benchmarkFullPipeline(): float
    {
        $startTime = microtime(true);

        for ($i = 0; $i < 200; $i++) {
            // Simulate full request pipeline
            $middlewares = $this->createMiddlewareStack(5);
            $pipeline = MiddlewarePipelineCompiler::compilePipeline($middlewares);

            $routeKey = "GET:/api/benchmark/$i";
            RouteMemoryManager::trackRouteUsage($routeKey);

            $cacheKey = "response_$i";
            $context = ['request_id' => $i];
            AdaptiveLearningCache::get($cacheKey, function() {
                return ['data' => 'response'];
            }, $context);
        }

        $endTime = microtime(true);

        return 200 / ($endTime - $startTime);
    }

    private function benchmarkMemoryEfficiency(): float
    {
        $initialMemory = memory_get_usage();

        // Perform operations
        for ($i = 0; $i < 100; $i++) {
            $middlewares = $this->createMiddlewareStack(3);
            MiddlewarePipelineCompiler::compilePipeline($middlewares);
            RouteMemoryManager::trackRouteUsage("GET:/test/$i");
            ProbabilisticCache::set("test_$i", "data");
        }

        $finalMemory = memory_get_usage();
        $memoryUsed = ($finalMemory - $initialMemory) / 1024; // KB

        return $memoryUsed / 100; // Memory per operation
    }

    private function benchmarkScalability(): float
    {
        $scores = [];

        // Test with different loads
        foreach ([100, 500, 1000] as $load) {
            $startTime = microtime(true);

            for ($i = 0; $i < $load; $i++) {
                $middlewares = $this->createMiddlewareStack(2);
                MiddlewarePipelineCompiler::compilePipeline($middlewares);
            }

            $endTime = microtime(true);
            $opsPerSecond = $load / ($endTime - $startTime);

            // Score based on ops/sec relative to load
            $scores[] = min(10, $opsPerSecond / ($load * 0.1));
        }

        return array_sum($scores) / count($scores);
    }

    private function generateComprehensiveReport(): void
    {
        echo "📊 Advanced Optimizations Phase 2 - Performance Report\n";
        echo "═════════════════════════════════════════════════════\n\n";

        // Calculate overall performance metrics
        $overallScore = $this->calculateOverallScore();
        $memoryEfficiency = $this->calculateMemoryEfficiency();
        $cacheEfficiency = $this->calculateCacheEfficiency();

        echo "🎯 Overall Performance Metrics:\n";
        echo sprintf("   - Overall optimization score: %.1f/10\n", $overallScore);
        echo sprintf("   - Memory efficiency: %.1f/10\n", $memoryEfficiency);
        echo sprintf("   - Cache efficiency: %.1f/10\n", $cacheEfficiency);
        echo sprintf("   - Total memory usage: %s\n", $this->formatBytes(memory_get_usage()));
        echo sprintf("   - Peak memory usage: %s\n", $this->formatBytes(memory_get_peak_usage()));
        echo "\n";

        // Component-specific results
        foreach ($this->results as $component => $data) {
            echo "📈 $component:\n";

            switch ($component) {
                case 'Middleware Pipeline Compilation':
                    echo sprintf("   - Performance scales well: %s\n",
                        $data['large_stack_ops'] > $data['small_stack_ops'] * 0.3 ? '✅ Yes' : '⚠️ Needs optimization');
                    echo sprintf("   - Pattern recognition efficiency: %.0f ops/sec\n", $data['pattern_recognition_ops']);
                    break;

                case 'Route Memory Management':
                    echo sprintf("   - Memory optimization: %.0f ops/sec\n", $data['memory_optimization_ops']);
                    echo sprintf("   - GC efficiency: %.1f%%\n", $data['gc_efficiency']);
                    break;

                case 'Probabilistic Cache':
                    echo sprintf("   - Cache operations: %.0f ops/sec\n", $data['get_ops']);
                    echo sprintf("   - Hit rate: %.1f%%\n", $data['stats']['hit_rate'] * 100);
                    break;

                case 'Adaptive Learning Cache':
                    echo sprintf("   - Learning performance: %.0f ops/sec\n", $data['learning_get_ops']);
                    echo sprintf("   - Prediction accuracy: %.1f%%\n", $data['prediction_accuracy'] * 100);
                    break;

                case 'Integrated Performance':
                    echo sprintf("   - Full pipeline: %.0f requests/sec\n", $data['full_pipeline_ops']);
                    echo sprintf("   - Scalability score: %.1f/10\n", $data['scalability_score']);
                    break;
            }
            echo "\n";
        }

        // Recommendations
        echo "💡 Optimization Recommendations:\n";
        echo $this->generateRecommendations();
        echo "\n";

        echo "✅ Phase 2 benchmark completed successfully!\n";
    }

    private function calculateOverallScore(): float
    {
        $scores = [];

        if (isset($this->results['Middleware Pipeline Compilation'])) {
            $mpc = $this->results['Middleware Pipeline Compilation'];
            $scores[] = min(10, $mpc['small_stack_ops'] / 100000); // 1M ops/sec = 10 points
        }

        if (isset($this->results['Route Memory Management'])) {
            $rmm = $this->results['Route Memory Management'];
            $scores[] = min(10, $rmm['gc_efficiency'] / 10); // 100% efficiency = 10 points
        }

        if (isset($this->results['Integrated Performance'])) {
            $ip = $this->results['Integrated Performance'];
            $scores[] = $ip['scalability_score'];
        }

        return count($scores) > 0 ? array_sum($scores) / count($scores) : 5.0;
    }

    private function calculateMemoryEfficiency(): float
    {
        if (!isset($this->results['Integrated Performance'])) {
            return 5.0;
        }

        $memoryPerOp = $this->results['Integrated Performance']['memory_efficiency'];

        // Lower memory usage = higher score
        return max(1, min(10, 10 - ($memoryPerOp / 10)));
    }

    private function calculateCacheEfficiency(): float
    {
        $scores = [];

        if (isset($this->results['Probabilistic Cache'])) {
            $hitRate = $this->results['Probabilistic Cache']['stats']['hit_rate'];
            $scores[] = $hitRate * 10;
        }

        if (isset($this->results['Adaptive Learning Cache'])) {
            $accuracy = $this->results['Adaptive Learning Cache']['prediction_accuracy'];
            $scores[] = $accuracy * 10;
        }

        return count($scores) > 0 ? array_sum($scores) / count($scores) : 5.0;
    }

    private function generateRecommendations(): string
    {
        $recommendations = [];

        // Analyze results and generate specific recommendations
        if (isset($this->results['Middleware Pipeline Compilation'])) {
            $stats = $this->results['Middleware Pipeline Compilation']['stats'];
            if ($stats['cache_hit_rate'] < 80) {
                $recommendations[] = "- Improve middleware pipeline cache hit rate through better pattern detection";
            }
        }

        if (isset($this->results['Route Memory Management'])) {
            $gcEfficiency = $this->results['Route Memory Management']['gc_efficiency'];
            if ($gcEfficiency < 70) {
                $recommendations[] = "- Optimize garbage collection strategy for route memory management";
            }
        }

        if (isset($this->results['Adaptive Learning Cache'])) {
            $accuracy = $this->results['Adaptive Learning Cache']['prediction_accuracy'];
            if ($accuracy < 0.7) {
                $recommendations[] = "- Enhance feature extraction for better cache prediction accuracy";
            }
        }

        if (empty($recommendations)) {
            $recommendations[] = "- All optimizations performing well! Consider stress testing with higher loads";
            $recommendations[] = "- Monitor performance in production environment";
        }

        return implode("\n", $recommendations);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return round($bytes / 1048576, 2) . ' MB';
        }
    }
}

// Run the benchmark
$iterations = isset($argv[1]) ? (int)$argv[1] : 1000;
$benchmark = new AdvancedOptimizationsPhase2Benchmark($iterations);
$benchmark->run();
