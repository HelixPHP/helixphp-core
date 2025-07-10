<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Json\Pool\JsonBuffer;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

/**
 * Benchmark to test JsonBuffer refactoring performance
 */
class JsonBufferRefactorBenchmark
{
    public function run(): void
    {
        echo "🧪 JsonBuffer Refactoring Performance Test\n";
        echo "==========================================\n\n";

        $iterations = 10000;
        $testData = [
            'message' => 'Hello, World!',
            'timestamp' => time(),
            'unicode' => '🚀 Performance Test',
            'url' => 'https://example.com/test',
            'nested' => [
                'level1' => ['level2' => ['level3' => 'deep value']],
                'array' => range(1, 50)
            ]
        ];

        // Test 1: Direct JsonBuffer usage
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $buffer = new JsonBuffer();
            $buffer->appendJson($testData);
            $result = $buffer->finalize();
        }
        $directTime = microtime(true) - $start;

        // Test 2: JsonBufferPool usage
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $result = JsonBufferPool::encodeWithPool($testData);
        }
        $poolTime = microtime(true) - $start;

        // Test 3: Traditional json_encode
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $result = json_encode($testData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $traditionalTime = microtime(true) - $start;

        // Calculate performance metrics
        $directOps = $iterations / $directTime;
        $poolOps = $iterations / $poolTime;
        $traditionalOps = $iterations / $traditionalTime;

        echo "📊 Results ({$iterations} iterations):\n";
        echo "Direct JsonBuffer:    " . number_format($directOps, 0) . " ops/sec\n";
        echo "JsonBufferPool:       " . number_format($poolOps, 0) . " ops/sec\n";
        echo "Traditional encode:   " . number_format($traditionalOps, 0) . " ops/sec\n\n";

        echo "⚡ Performance ratios:\n";
        echo "Buffer vs Traditional: " . number_format($directOps / $traditionalOps, 2) . "x\n";
        echo "Pool vs Traditional:   " . number_format($poolOps / $traditionalOps, 2) . "x\n";
        echo "Pool vs Direct:        " . number_format($poolOps / $directOps, 2) . "x\n\n";

        // Test memory efficiency
        $memStart = memory_get_usage();
        
        // Create and reuse buffers
        $buffer = new JsonBuffer();
        for ($i = 0; $i < 1000; $i++) {
            $buffer->appendJson($testData);
            $buffer->finalize();
            $buffer->reset();
        }
        
        $memAfterReuse = memory_get_usage();
        $reuseMemory = $memAfterReuse - $memStart;

        // Create new buffers each time
        $memStart = memory_get_usage();
        for ($i = 0; $i < 1000; $i++) {
            $buffer = new JsonBuffer();
            $buffer->appendJson($testData);
            $buffer->finalize();
        }
        $memAfterNew = memory_get_usage();
        $newMemory = $memAfterNew - $memStart;

        echo "💾 Memory efficiency (1000 operations):\n";
        echo "Buffer reuse:   " . number_format($reuseMemory / 1024, 2) . " KB\n";
        echo "New buffers:    " . number_format($newMemory / 1024, 2) . " KB\n";
        echo "Memory saved:   " . number_format(($newMemory - $reuseMemory) / 1024, 2) . " KB\n";
        echo "Efficiency:     " . number_format((1 - $reuseMemory / $newMemory) * 100, 1) . "% less memory\n\n";

        // Pool statistics
        echo "🏊 Pool Statistics:\n";
        $stats = JsonBufferPool::getStatistics();
        echo "Reuse rate:     " . $stats['reuse_rate'] . "%\n";
        echo "Total ops:      " . $stats['total_operations'] . "\n";
        echo "Current usage:  " . $stats['current_usage'] . "\n";
        echo "Peak usage:     " . $stats['peak_usage'] . "\n\n";

        echo "✅ Refactoring Performance Test Complete!\n";
    }
}

// Run the benchmark
$benchmark = new JsonBufferRefactorBenchmark();
$benchmark->run();