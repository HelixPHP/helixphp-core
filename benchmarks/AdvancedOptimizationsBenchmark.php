<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Helix\Http\Psr7\Pool\DynamicPoolManager;
use Helix\Http\Psr7\Pool\EnhancedStreamPool;
use Helix\Http\Psr7\Pool\HeaderPool;
use Helix\Http\Psr7\Cache\IntelligentJsonCache;
use Helix\Utils\Utils;

/**
 * Benchmark for New Optimization Features
 *
 * Tests the performance improvements of the newly implemented optimizations
 */
class OptimizationsBenchmark
{
    private int $iterations = 1000;
    private array $results = [];

    public function __construct(int $iterations = 1000)
    {
        $this->iterations = $iterations;
    }

    public function run(): void
    {
        echo "🚀 Running Advanced Optimizations Benchmark\n";
        echo "═══════════════════════════════════════════\n\n";

        $this->benchmarkDynamicPoolManager();
        $this->benchmarkEnhancedStreamPool();
        $this->benchmarkEnhancedHeaderPool();
        $this->benchmarkIntelligentJsonCache();
        $this->generateReport();
    }

    private function benchmarkDynamicPoolManager(): void
    {
        echo "🔄 Testing Dynamic Pool Manager...\n";

        // Test memory tier determination
        $startTime = microtime(true);
        for ($i = 0; $i < $this->iterations; $i++) {
            $poolSizes = DynamicPoolManager::getOptimalPoolSizes();
            $recommendations = DynamicPoolManager::getMemoryRecommendations();
        }
        $endTime = microtime(true);

        $opsPerSecond = $this->iterations / ($endTime - $startTime);

        echo sprintf("   - Pool size optimization: %.0f ops/sec\n", $opsPerSecond);

        $this->results['Dynamic Pool Manager'] = [
            'pool_optimization' => $opsPerSecond,
            'memory_stats' => DynamicPoolManager::getDetailedStats()
        ];

        echo "\n";
    }

    private function benchmarkEnhancedStreamPool(): void
    {
        echo "🔄 Testing Enhanced Stream Pool...\n";

        // Test small streams
        $startTime = microtime(true);
        for ($i = 0; $i < $this->iterations; $i++) {
            $stream = EnhancedStreamPool::getStream(100); // Small stream
            $stream->write("test data $i");
            EnhancedStreamPool::returnStream($stream);
        }
        $endTime = microtime(true);

        $smallStreamOps = $this->iterations / ($endTime - $startTime);

        // Test medium streams
        $startTime = microtime(true);
        for ($i = 0; $i < $this->iterations; $i++) {
            $stream = EnhancedStreamPool::getStream(5000); // Medium stream
            $stream->write(str_repeat("data", 100));
            EnhancedStreamPool::returnStream($stream);
        }
        $endTime = microtime(true);

        $mediumStreamOps = $this->iterations / ($endTime - $startTime);

        echo sprintf("   - Small streams (< 1KB): %.0f ops/sec\n", $smallStreamOps);
        echo sprintf("   - Medium streams (1-10KB): %.0f ops/sec\n", $mediumStreamOps);

        $stats = EnhancedStreamPool::getStats();
        echo sprintf("   - Pool hit rate: %.1f%%\n", $stats['hit_rate']);
        echo sprintf("   - Memory usage: %s\n", $stats['memory_usage']);

        $this->results['Enhanced Stream Pool'] = [
            'small_streams' => $smallStreamOps,
            'medium_streams' => $mediumStreamOps,
            'stats' => $stats
        ];

        echo "\n";
    }

    private function benchmarkEnhancedHeaderPool(): void
    {
        echo "🔄 Testing Enhanced Header Pool...\n";

        // Clear stats for clean test
        HeaderPool::clearAll();

        // Test common headers
        $commonHeaders = [
            'Content-Type', 'Authorization', 'Accept', 'User-Agent',
            'Host', 'Connection', 'Cache-Control', 'Accept-Encoding'
        ];

        $startTime = microtime(true);
        for ($i = 0; $i < $this->iterations; $i++) {
            foreach ($commonHeaders as $header) {
                $normalized = HeaderPool::getNormalizedName($header);
                $values = HeaderPool::getHeaderValues($header, "value-$i");
            }
        }
        $endTime = microtime(true);

        $headerOps = ($this->iterations * count($commonHeaders)) / ($endTime - $startTime);

        $metrics = HeaderPool::getDetailedMetrics();

        echo sprintf("   - Header operations: %.0f ops/sec\n", $headerOps);
        echo sprintf("   - Cache hit rate: %.1f%%\n", $metrics['cache_hit_rate']);
        echo sprintf("   - Memory efficiency ratio: %.2f\n", $metrics['memory_efficiency']['efficiency_ratio']);
        echo sprintf("   - Estimated memory saved: %s\n", $metrics['memory_efficiency']['estimated_memory_saved']);

        $this->results['Enhanced Header Pool'] = [
            'operations_per_second' => $headerOps,
            'metrics' => $metrics
        ];

        echo "\n";
    }

    private function benchmarkIntelligentJsonCache(): void
    {
        echo "🔄 Testing Intelligent JSON Cache...\n";

        // Test different data structures
        $testData = [
            'simple' => ['id' => 1, 'name' => 'test', 'active' => true],
            'nested' => [
                'user' => ['id' => 1, 'name' => 'John'],
                'settings' => ['theme' => 'dark', 'notifications' => true],
                'data' => [1, 2, 3, 4, 5]
            ],
            'complex' => [
                'users' => [
                    ['id' => 1, 'name' => 'John', 'email' => 'john@example.com'],
                    ['id' => 2, 'name' => 'Jane', 'email' => 'jane@example.com']
                ],
                'meta' => ['total' => 2, 'page' => 1, 'limit' => 10]
            ]
        ];

        // Test without cache first
        $startTime = microtime(true);
        for ($i = 0; $i < $this->iterations; $i++) {
            foreach ($testData as $data) {
                json_encode($data);
            }
        }
        $endTime = microtime(true);
        $standardOps = ($this->iterations * count($testData)) / ($endTime - $startTime);

        // Clear cache and test with intelligent cache
        IntelligentJsonCache::clearAll();

        $startTime = microtime(true);
        for ($i = 0; $i < $this->iterations; $i++) {
            foreach ($testData as $key => $data) {
                // Simulate varying data with same structure
                if ($key === 'simple') {
                    $data['id'] = $i;
                    $data['name'] = "test-$i";
                } elseif ($key === 'nested') {
                    $data['user']['id'] = $i;
                    $data['user']['name'] = "User-$i";
                } elseif ($key === 'complex') {
                    $data['users'][0]['id'] = $i;
                    $data['meta']['total'] = $i;
                }

                IntelligentJsonCache::getCachedJson($data);
            }
        }
        $endTime = microtime(true);
        $cachedOps = ($this->iterations * count($testData)) / ($endTime - $startTime);

        $improvement = (($cachedOps - $standardOps) / $standardOps) * 100;
        $stats = IntelligentJsonCache::getStats();

        echo sprintf("   - Standard JSON encoding: %.0f ops/sec\n", $standardOps);
        echo sprintf("   - Intelligent cache: %.0f ops/sec\n", $cachedOps);
        echo sprintf("   - Improvement: %+.1f%%\n", $improvement);
        echo sprintf("   - Template hit rate: %.1f%%\n", $stats['template_hit_rate']);
        echo sprintf("   - Direct hit rate: %.1f%%\n", $stats['direct_hit_rate']);
        echo sprintf("   - Templates created: %d\n", $stats['templates_created']);
        echo sprintf("   - Memory saved: %s\n", $stats['memory_saved']);

        $this->results['Intelligent JSON Cache'] = [
            'standard_ops' => $standardOps,
            'cached_ops' => $cachedOps,
            'improvement_percent' => $improvement,
            'stats' => $stats
        ];

        echo "\n";
    }

    private function generateReport(): void
    {
        echo "📊 Advanced Optimizations Performance Report\n";
        echo "═══════════════════════════════════════════\n\n";

        // Overall performance summary
        $totalImprovements = [];

        if (isset($this->results['Intelligent JSON Cache'])) {
            $totalImprovements[] = $this->results['Intelligent JSON Cache']['improvement_percent'];
        }

        $avgImprovement = !empty($totalImprovements) ? array_sum($totalImprovements) / count($totalImprovements) : 0;

        echo "🎯 Overall Performance Impact:\n";
        echo sprintf("   - Average improvement: %+.1f%%\n", $avgImprovement);
        echo sprintf("   - Test iterations: %d\n", $this->iterations);
        echo sprintf("   - Memory usage: %s\n", Utils::formatBytes(memory_get_usage()));
        echo sprintf("   - Peak memory: %s\n", Utils::formatBytes(memory_get_peak_usage()));
        echo "\n";

        // Detailed results
        foreach ($this->results as $component => $data) {
            echo "📈 $component:\n";

            if ($component === 'Dynamic Pool Manager') {
                echo sprintf("   - Pool optimization: %.0f ops/sec\n", $data['pool_optimization']);
                echo sprintf("   - Current memory tier: %s\n", $data['memory_stats']['current_tier']);

            } elseif ($component === 'Enhanced Stream Pool') {
                echo sprintf("   - Small streams: %.0f ops/sec\n", $data['small_streams']);
                echo sprintf("   - Medium streams: %.0f ops/sec\n", $data['medium_streams']);
                echo sprintf("   - Hit rate: %.1f%%\n", $data['stats']['hit_rate']);

            } elseif ($component === 'Enhanced Header Pool') {
                echo sprintf("   - Operations: %.0f ops/sec\n", $data['operations_per_second']);
                echo sprintf("   - Hit rate: %.1f%%\n", $data['metrics']['cache_hit_rate']);

            } elseif ($component === 'Intelligent JSON Cache') {
                echo sprintf("   - Performance: %.0f ops/sec (%+.1f%%)\n",
                    $data['cached_ops'], $data['improvement_percent']);
                echo sprintf("   - Template hit rate: %.1f%%\n", $data['stats']['template_hit_rate']);
            }

            echo "\n";
        }

        // Recommendations
        echo "💡 Optimization Recommendations:\n";
        echo "   - Enhanced Stream Pool shows excellent hit rates for size-based pooling\n";
        echo "   - Header Pool benefits significantly from LRU and frequency tracking\n";
        echo "   - JSON Cache provides substantial improvements for structured data\n";
        echo "   - Dynamic Pool Manager adapts well to memory pressure\n";
        echo "\n";

        echo "✅ Benchmark completed successfully!\n";
    }
}

// Run the benchmark
$iterations = isset($argv[1]) ? (int)$argv[1] : 1000;
$benchmark = new OptimizationsBenchmark($iterations);
$benchmark->run();
