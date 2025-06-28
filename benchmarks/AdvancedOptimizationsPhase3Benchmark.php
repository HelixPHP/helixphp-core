<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Express\Middleware\MiddlewarePipelineCompiler;
use Express\Routing\RouteMemoryManager;
use Express\Http\Psr7\Cache\ProbabilisticCache;
use Express\Http\Psr7\Cache\AdaptiveLearningCache;
use Express\Http\Psr7\Cache\PredictiveCacheWarmer;
use Express\Http\Optimization\ZeroCopyOptimizer;
use Express\Http\Optimization\MemoryMappingManager;

/**
 * Advanced Optimizations Phase 3 Benchmark
 *
 * Tests all new optimization features including:
 * - Improved Pipeline Cache Hit Rate
 * - Intelligent Garbage Collection
 * - Predictive Cache Warming with ML
 * - Zero-Copy Optimizations
 * - Memory Mapping for Large Files
 */
class AdvancedOptimizationsPhase3Benchmark
{
    private int $iterations = 1000;
    private array $results = [];
    private string $tempDir;

    public function __construct(int $iterations = 1000)
    {
        $this->iterations = $iterations;
        $this->tempDir = sys_get_temp_dir() . '/express_benchmark_' . uniqid();
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }
    }

    public function run(): void
    {
        echo "🚀 Running Advanced Optimizations Phase 3 Benchmark\n";
        echo "══════════════════════════════════════════════════════\n\n";

        $this->benchmarkImprovedPipelineCache();
        $this->benchmarkIntelligentGarbageCollection();
        $this->benchmarkPredictiveCacheWarming();
        $this->benchmarkZeroCopyOptimizations();
        $this->benchmarkMemoryMapping();
        $this->benchmarkIntegratedPerformance();
        $this->generateComprehensiveReport();
        $this->cleanup();
    }

    private function benchmarkImprovedPipelineCache(): void
    {
        echo "🧠 Testing Improved Pipeline Cache with Pattern Learning...\n";

        // Clear previous state
        MiddlewarePipelineCompiler::clearAll();
        MiddlewarePipelineCompiler::warmUp();

        // Test pattern learning with common patterns
        $commonPatterns = [
            ['cors', 'auth', 'json'],
            ['cors', 'session', 'csrf'],
            ['cors', 'auth', 'json', 'validation'],
            ['cors', 'security', 'auth', 'csrf'],
            ['cors', 'rate_limit', 'json'],
        ];

        // Phase 1: Training (should have low hit rate initially)
        $trainingStart = microtime(true);
        for ($i = 0; $i < $this->iterations; $i++) {
            $pattern = $commonPatterns[$i % count($commonPatterns)];
            MiddlewarePipelineCompiler::compilePipeline($pattern, "training_$i");
        }
        $trainingTime = microtime(true) - $trainingStart;
        $trainingStats = MiddlewarePipelineCompiler::getStats();

        // Phase 2: Usage (should have improved hit rate)
        $usageStart = microtime(true);
        for ($i = 0; $i < $this->iterations; $i++) {
            $pattern = $commonPatterns[$i % count($commonPatterns)];
            MiddlewarePipelineCompiler::compilePipeline($pattern, "usage_$i");
        }
        $usageTime = microtime(true) - $usageStart;
        $usageStats = MiddlewarePipelineCompiler::getStats();

        // Test intelligent GC
        $gcStats = MiddlewarePipelineCompiler::performIntelligentGC();

        echo sprintf("   Training Phase:\n");
        echo sprintf("     - Cache hit rate: %.1f%%\n", $trainingStats['cache_hit_rate']);
        echo sprintf("     - Performance: %.0f compilations/sec\n", $this->iterations / $trainingTime);
        echo sprintf("   Usage Phase:\n");
        echo sprintf("     - Cache hit rate: %.1f%%\n", $usageStats['cache_hit_rate']);
        echo sprintf("     - Performance: %.0f compilations/sec\n", $this->iterations / $usageTime);
        echo sprintf("     - Patterns learned: %d\n", $usageStats['patterns_learned']);
        echo sprintf("     - Intelligent matches: %d\n", $usageStats['intelligent_matches']);
        echo sprintf("   Garbage Collection:\n");
        echo sprintf("     - Pipelines removed: %d\n", $gcStats['pipelines_removed']);
        echo sprintf("     - Patterns optimized: %d\n", $gcStats['patterns_optimized']);

        $this->results['Improved Pipeline Cache'] = [
            'training_hit_rate' => $trainingStats['cache_hit_rate'],
            'usage_hit_rate' => $usageStats['cache_hit_rate'],
            'patterns_learned' => $usageStats['patterns_learned'],
            'gc_efficiency' => $gcStats['pipelines_removed'] + $gcStats['patterns_optimized']
        ];

        echo "\n";
    }

    private function benchmarkIntelligentGarbageCollection(): void
    {
        echo "🗑️ Testing Intelligent Garbage Collection...\n";

        // Create many pipelines to trigger memory pressure
        for ($i = 0; $i < 300; $i++) {
            $middlewares = $this->createRandomMiddlewareStack();
            MiddlewarePipelineCompiler::compilePipeline($middlewares, "gc_test_$i");
        }

        $beforeGC = MiddlewarePipelineCompiler::getStats();

        $gcStart = microtime(true);
        $gcResult = MiddlewarePipelineCompiler::performIntelligentGC();
        $gcTime = microtime(true) - $gcStart;

        $afterGC = MiddlewarePipelineCompiler::getStats();

        echo sprintf("   - Pipelines before GC: %d\n", $beforeGC['compiled_pipelines']);
        echo sprintf("   - Pipelines after GC: %d\n", $afterGC['compiled_pipelines']);
        echo sprintf("   - Pipelines removed: %d\n", $gcResult['pipelines_removed']);
        echo sprintf("   - Memory freed: %s\n", $this->formatBytes($gcResult['memory_freed']));
        echo sprintf("   - GC time: %.4f seconds\n", $gcTime);
        echo sprintf("   - GC efficiency: %.1f%%\n", $gcResult['pipelines_removed'] > 0 ?
            ($gcResult['pipelines_removed'] / $beforeGC['compiled_pipelines']) * 100 : 0);

        $this->results['Intelligent GC'] = [
            'pipelines_removed' => $gcResult['pipelines_removed'],
            'memory_freed' => $gcResult['memory_freed'],
            'gc_time' => $gcTime,
            'efficiency_percent' => $gcResult['pipelines_removed'] > 0 ?
                ($gcResult['pipelines_removed'] / $beforeGC['compiled_pipelines']) * 100 : 0
        ];

        echo "\n";
    }

    private function benchmarkPredictiveCacheWarming(): void
    {
        echo "🔮 Testing Predictive Cache Warming with ML...\n";

        PredictiveCacheWarmer::reset();

        // Simulate realistic access patterns
        $patterns = ['user:*:profile', 'api:*:data', 'session:*:info'];

        // Training phase - establish patterns
        for ($i = 0; $i < 200; $i++) {
            $pattern = $patterns[$i % count($patterns)];
            $key = str_replace('*', $i, $pattern);
            $context = [
                'user_id' => $i % 50,
                'session_id' => 'sess_' . ($i % 20),
                'timestamp' => time() - rand(0, 3600)
            ];
            PredictiveCacheWarmer::recordAccess($key, $context);
        }

        // Test prediction accuracy
        $predictionStart = microtime(true);
        $predictions = [];
        foreach ($patterns as $pattern) {
            $predictions[$pattern] = PredictiveCacheWarmer::predictNextAccesses($pattern);
        }
        $predictionTime = microtime(true) - $predictionStart;

        // Test cache warming
        $warmingStart = microtime(true);
        $warmedResults = PredictiveCacheWarmer::warmCache(function($key, $prediction) {
            // Simulate cache entry creation
            return ['data' => 'warmed_data', 'prediction' => $prediction];
        });
        $warmingTime = microtime(true) - $warmingStart;

        $stats = PredictiveCacheWarmer::getStats();

        echo sprintf("   - Models trained: %d\n", $stats['models_trained']);
        echo sprintf("   - Patterns learned: %d\n", $stats['patterns_learned']);
        echo sprintf("   - Predictions made: %d\n", $stats['predictions_made']);
        echo sprintf("   - Prediction accuracy: %.1f%%\n", $stats['prediction_accuracy']);
        echo sprintf("   - Cache entries warmed: %d\n", $stats['cache_warmed']);
        echo sprintf("   - Prediction time: %.4f seconds\n", $predictionTime);
        echo sprintf("   - Warming time: %.4f seconds\n", $warmingTime);

        $this->results['Predictive Cache Warming'] = [
            'models_trained' => $stats['models_trained'],
            'prediction_accuracy' => $stats['prediction_accuracy'],
            'cache_warmed' => $stats['cache_warmed'],
            'prediction_time' => $predictionTime,
            'warming_time' => $warmingTime
        ];

        echo "\n";
    }

    private function benchmarkZeroCopyOptimizations(): void
    {
        echo "⚡ Testing Zero-Copy Optimizations...\n";

        ZeroCopyOptimizer::reset();

        // Test string interning
        $internStart = microtime(true);
        $testStrings = [];
        for ($i = 0; $i < $this->iterations; $i++) {
            $commonString = "common_string_pattern_" . ($i % 100);
            $testStrings[] = ZeroCopyOptimizer::internString($commonString);
        }
        $internTime = microtime(true) - $internStart;

        // Test array references
        $refStart = microtime(true);
        $largeArray = range(1, 10000);
        $refIds = [];
        for ($i = 0; $i < 100; $i++) {
            $refIds[] = ZeroCopyOptimizer::createArrayReference($largeArray, "ref_$i");
        }
        $refTime = microtime(true) - $refStart;

        // Test copy-on-write
        $cowStart = microtime(true);
        $largeObject = (object) ['data' => range(1, 1000), 'metadata' => ['created' => time()]];
        $cowIds = [];
        for ($i = 0; $i < 50; $i++) {
            $cowIds[] = ZeroCopyOptimizer::createCOWWrapper($largeObject, "cow_$i");
        }
        $cowTime = microtime(true) - $cowStart;

        // Test efficient concatenation
        $concatStart = microtime(true);
        $strings = [];
        for ($i = 0; $i < 1000; $i++) {
            $strings[] = "part_$i";
        }
        $result = ZeroCopyOptimizer::efficientConcat($strings);
        $concatTime = microtime(true) - $concatStart;

        $stats = ZeroCopyOptimizer::getStats();

        echo sprintf("   - Copies avoided: %d\n", $stats['copies_avoided']);
        echo sprintf("   - Memory saved: %s\n", $stats['memory_saved']);
        echo sprintf("   - References active: %d\n", $stats['references_active']);
        echo sprintf("   - Interned strings: %d\n", $stats['interned_strings']);
        echo sprintf("   - Pool efficiency: %.1f%%\n", $stats['pool_efficiency']);
        echo sprintf("   - String interning: %.0f ops/sec\n", $this->iterations / $internTime);
        echo sprintf("   - Array reference: %.0f ops/sec\n", 100 / $refTime);
        echo sprintf("   - COW creation: %.0f ops/sec\n", 50 / $cowTime);
        echo sprintf("   - Efficient concat: %.4f seconds for 1000 strings\n", $concatTime);

        $this->results['Zero-Copy Optimizations'] = [
            'copies_avoided' => $stats['copies_avoided'],
            'memory_saved' => $stats['memory_saved'],
            'pool_efficiency' => $stats['pool_efficiency'],
            'concat_performance' => $concatTime
        ];

        echo "\n";
    }    private function benchmarkMemoryMapping(): void
    {
        echo "🗺️ Testing Memory Mapping for Large Files...\n";

        MemoryMappingManager::reset();

        // Create test files of different sizes (smaller for testing)
        $smallFile = $this->tempDir . '/small_file.txt';
        $largeFile = $this->tempDir . '/large_file.txt';

        file_put_contents($smallFile, str_repeat("Small file content.\n", 100)); // ~2KB
        file_put_contents($largeFile, str_repeat("Large file content with more data.\n", 10000)); // ~340KB

        // Test memory mapping creation
        $mappingStart = microtime(true);
        $mapping1 = MemoryMappingManager::createMapping($largeFile);
        $mapping2 = MemoryMappingManager::createMapping($largeFile); // Should hit cache
        $mappingTime = microtime(true) - $mappingStart;

        // Test file streaming (using memory stream to avoid file I/O)
        $streamStart = microtime(true);
        $output = fopen('php://memory', 'w+');
        $bytesStreamed = MemoryMappingManager::streamFile($largeFile, $output);
        fclose($output);
        $streamTime = microtime(true) - $streamStart;

        // Test file section reading (fewer iterations)
        $sectionStart = microtime(true);
        for ($i = 0; $i < 10; $i++) { // Reduced from 100 to 10
            $offset = $i * 100;
            $section = MemoryMappingManager::readFileSection($largeFile, $offset, 512);
        }
        $sectionTime = microtime(true) - $sectionStart;

        // Test search in file (smaller search)
        $searchStart = microtime(true);
        $matches = MemoryMappingManager::searchInFile($smallFile, 'content'); // Use smaller file
        $searchTime = microtime(true) - $searchStart;

        // Test line processing (with timeout protection)
        $processStart = microtime(true);
        $lineCount = 0;
        try {
            $lineCount = MemoryMappingManager::processFileLines($smallFile, function($line, $number) {
                // Simple processing with early exit for testing
                if ($number > 1000) return false; // Limit processing
                return strlen($line);
            });
        } catch (\Exception $e) {
            echo "   Warning: Line processing failed: " . $e->getMessage() . "\n";
        }
        $processTime = microtime(true) - $processStart;

        $stats = MemoryMappingManager::getStats();

        echo sprintf("   - Active mappings: %d\n", $stats['active_mappings']);
        echo sprintf("   - Total mapped size: %s\n", $stats['total_mapped_size']);
        echo sprintf("   - Cache hit rate: %.1f%%\n", $stats['cache_hit_rate']);
        echo sprintf("   - Mapping creation: %.4f seconds\n", $mappingTime);
        echo sprintf("   - File streaming: %.4f seconds (%s)\n", $streamTime, $this->formatBytes($bytesStreamed));
        echo sprintf("   - Section reading: %.0f ops/sec\n", 10 / max(0.0001, $sectionTime));
        echo sprintf("   - Search performance: %.4f seconds (%d matches)\n", $searchTime, count($matches));
        echo sprintf("   - Line processing: %.0f lines/sec\n", $lineCount / max(0.0001, $processTime));

        $this->results['Memory Mapping'] = [
            'cache_hit_rate' => $stats['cache_hit_rate'],
            'streaming_performance' => $bytesStreamed / max(0.0001, $streamTime),
            'search_matches' => count($matches),
            'line_processing_rate' => $lineCount / max(0.0001, $processTime)
        ];

        echo "\n";
    }

    private function benchmarkIntegratedPerformance(): void
    {
        echo "🔗 Testing Integrated Performance with All Optimizations...\n";

        $integrationStart = microtime(true);
        $operations = 0;

        for ($i = 0; $i < 200; $i++) {
            // Pipeline compilation with improved cache
            $middlewares = $this->createRandomMiddlewareStack();
            MiddlewarePipelineCompiler::compilePipeline($middlewares);
            $operations++;

            // Zero-copy string operations
            $str = ZeroCopyOptimizer::internString("common_pattern_$i");
            $operations++;

            // Memory mapping for data processing
            if ($i % 20 === 0) {
                $largeFile = $this->tempDir . '/large_file.txt';
                if (file_exists($largeFile)) {
                    $section = MemoryMappingManager::readFileSection($largeFile, $i * 100, 1024);
                    $operations++;
                }
            }

            // Predictive cache operations
            PredictiveCacheWarmer::recordAccess("integrated_$i", ['operation' => $i]);
            $operations++;

            // Route memory management
            RouteMemoryManager::trackRouteUsage("GET:/integrated/$i");
            $operations++;
        }

        $integrationTime = microtime(true) - $integrationStart;
        $opsPerSecond = $operations / $integrationTime;

        // Gather final statistics
        $pipelineStats = MiddlewarePipelineCompiler::getStats();
        $zeroCopyStats = ZeroCopyOptimizer::getStats();
        $mappingStats = MemoryMappingManager::getStats();
        $predictiveStats = PredictiveCacheWarmer::getStats();
        $routeStats = RouteMemoryManager::getStats();

        echo sprintf("   - Total operations: %d\n", $operations);
        echo sprintf("   - Operations per second: %.0f\n", $opsPerSecond);
        echo sprintf("   - Pipeline cache hit rate: %.1f%%\n", $pipelineStats['cache_hit_rate']);
        echo sprintf("   - Zero-copy efficiency: %s saved\n", $zeroCopyStats['memory_saved']);
        echo sprintf("   - Memory mapping cache: %.1f%%\n", $mappingStats['cache_hit_rate']);
        echo sprintf("   - Predictive accuracy: %.1f%%\n", $predictiveStats['prediction_accuracy']);
        echo sprintf("   - Route memory status: %s\n", $routeStats['memory_status'] ?? 'optimal');

        $this->results['Integrated Performance'] = [
            'operations_per_second' => $opsPerSecond,
            'pipeline_cache_hit_rate' => $pipelineStats['cache_hit_rate'],
            'overall_efficiency_score' => $this->calculateOverallEfficiency($pipelineStats, $zeroCopyStats, $mappingStats, $predictiveStats)
        ];

        echo "\n";
    }

    private function calculateOverallEfficiency(array $pipeline, array $zeroCopy, array $mapping, array $predictive): float
    {
        $score = 0;
        $score += min(100, $pipeline['cache_hit_rate']) * 0.3; // 30% weight
        $score += min(100, $zeroCopy['pool_efficiency']) * 0.25; // 25% weight
        $score += min(100, $mapping['cache_hit_rate']) * 0.25; // 25% weight
        $score += min(100, $predictive['prediction_accuracy']) * 0.2; // 20% weight

        return round($score, 1);
    }

    private function generateComprehensiveReport(): void
    {
        echo "📊 Advanced Optimizations Phase 3 - Performance Report\n";
        echo "════════════════════════════════════════════════════════\n";

        $totalMemory = memory_get_peak_usage(true);
        $currentMemory = memory_get_usage(true);

        echo sprintf("🎯 Overall Performance Metrics:\n");
        echo sprintf("   - Peak memory usage: %s\n", $this->formatBytes($totalMemory));
        echo sprintf("   - Current memory usage: %s\n", $this->formatBytes($currentMemory));

        foreach ($this->results as $category => $metrics) {
            echo sprintf("\n📈 %s:\n", $category);
            foreach ($metrics as $metric => $value) {
                if (is_numeric($value)) {
                    if (strpos($metric, 'rate') !== false || strpos($metric, 'percent') !== false || strpos($metric, 'accuracy') !== false) {
                        echo sprintf("   - %s: %.1f%%\n", ucwords(str_replace('_', ' ', $metric)), $value);
                    } elseif (strpos($metric, 'time') !== false) {
                        echo sprintf("   - %s: %.4f seconds\n", ucwords(str_replace('_', ' ', $metric)), $value);
                    } else {
                        echo sprintf("   - %s: %s\n", ucwords(str_replace('_', ' ', $metric)), is_int($value) ? number_format($value) : $value);
                    }
                } else {
                    echo sprintf("   - %s: %s\n", ucwords(str_replace('_', ' ', $metric)), $value);
                }
            }
        }

        echo "\n💡 Optimization Impact Analysis:\n";
        echo sprintf("   - Pipeline Cache Improvement: %.1f%% → %.1f%% hit rate\n",
            $this->results['Improved Pipeline Cache']['training_hit_rate'],
            $this->results['Improved Pipeline Cache']['usage_hit_rate']);
        echo sprintf("   - Memory Management: %d patterns learned, %d GC optimizations\n",
            $this->results['Improved Pipeline Cache']['patterns_learned'],
            $this->results['Intelligent GC']['pipelines_removed']);
        echo sprintf("   - Zero-Copy Efficiency: %s memory saved\n",
            $this->results['Zero-Copy Optimizations']['memory_saved']);
        echo sprintf("   - Memory Mapping: %.1f%% cache efficiency\n",
            $this->results['Memory Mapping']['cache_hit_rate']);

        echo "\n✅ Phase 3 benchmark completed successfully!\n";
    }

    private function createRandomMiddlewareStack(): array
    {
        $types = ['cors', 'auth', 'json', 'validation', 'logging', 'cache', 'security', 'rate_limit', 'session', 'csrf'];
        $stackSize = rand(3, 8);
        $stack = [];

        for ($i = 0; $i < $stackSize; $i++) {
            $stack[] = $types[array_rand($types)];
        }

        return $stack;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } elseif ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' MB';
        } else {
            return round($bytes / 1073741824, 2) . ' GB';
        }
    }

    private function cleanup(): void
    {
        // Clean up temporary files
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
    }

    public function __destruct()
    {
        $this->cleanup();
    }
}

// Run the benchmark
$benchmark = new AdvancedOptimizationsPhase3Benchmark(1000);
$benchmark->run();
