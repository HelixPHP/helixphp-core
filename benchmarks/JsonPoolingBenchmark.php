<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Json\Pool\JsonBufferPool;
use PivotPHP\Core\Http\Response;

/**
 * JSON Pooling Performance Benchmark
 * 
 * Compares traditional JSON encoding vs pooled JSON encoding
 * across different data sizes and scenarios.
 */
class JsonPoolingBenchmark
{
    private const ITERATIONS = 10000;
    private const WARMUP_ITERATIONS = 1000;

    private array $testDataSets = [];

    public function __construct()
    {
        $this->prepareTestData();
    }

    /**
     * Run all benchmarks
     */
    public function run(): void
    {
        echo "🚀 JSON Pooling Performance Benchmark\n";
        echo "=====================================\n\n";

        echo "Warming up...\n";
        $this->warmUp();

        echo "\n📊 Benchmark Results:\n";
        echo "-------------------\n\n";

        foreach ($this->testDataSets as $name => $data) {
            echo "Testing: {$name}\n";
            $this->benchmarkDataSet($name, $data);
            echo "\n";
        }

        $this->showPoolStatistics();
        $this->runMemoryBenchmark();
    }

    /**
     * Prepare different test datasets
     */
    private function prepareTestData(): void
    {
        // Small JSON (< 1KB)
        $this->testDataSets['Small JSON'] = [
            'id' => 1,
            'name' => 'User Test',
            'email' => 'user@test.com'
        ];

        // Medium JSON (1-10KB)
        $this->testDataSets['Medium JSON'] = array_fill(0, 50, [
            'id' => random_int(1, 1000),
            'name' => 'User ' . uniqid(),
            'email' => uniqid() . '@test.com',
            'metadata' => [
                'created' => date('Y-m-d H:i:s'),
                'active' => true,
                'score' => random_int(1, 100)
            ]
        ]);

        // Large JSON (10-100KB)
        $this->testDataSets['Large JSON'] = array_fill(0, 500, [
            'id' => random_int(1, 10000),
            'name' => 'User ' . uniqid(),
            'email' => uniqid() . '@test.com',
            'profile' => [
                'bio' => str_repeat('Lorem ipsum dolor sit amet. ', 10),
                'preferences' => array_fill(0, 10, uniqid()),
                'settings' => [
                    'theme' => 'dark',
                    'language' => 'pt-BR',
                    'notifications' => true
                ]
            ],
            'activity' => array_fill(0, 20, [
                'timestamp' => date('Y-m-d H:i:s'),
                'action' => 'test_action_' . uniqid(),
                'data' => str_repeat('x', 50)
            ])
        ]);

        // Repeated structure (ideal for pooling)
        $template = [
            'user_id' => 0,
            'username' => '',
            'email' => '',
            'status' => 'active',
            'metadata' => [
                'created_at' => '',
                'last_login' => '',
                'preferences' => []
            ]
        ];

        $this->testDataSets['Repeated Structure'] = array_map(function($i) use ($template) {
            $template['user_id'] = $i;
            $template['username'] = "user{$i}";
            $template['email'] = "user{$i}@test.com";
            $template['metadata']['created_at'] = date('Y-m-d H:i:s');
            return $template;
        }, range(1, 100));
    }

    /**
     * Warm up both approaches
     */
    private function warmUp(): void
    {
        $warmupData = ['test' => 'warmup'];
        
        for ($i = 0; $i < self::WARMUP_ITERATIONS; $i++) {
            // Traditional encoding
            json_encode($warmupData);
            
            // Pooled encoding
            JsonBufferPool::encodeWithPool($warmupData);
        }
        
        // Clear pool statistics from warmup
        JsonBufferPool::clearPools();
    }

    /**
     * Benchmark a specific dataset
     */
    private function benchmarkDataSet(string $name, array $data): void
    {
        // Traditional JSON encoding benchmark
        $traditionalTime = $this->benchmarkTraditionalEncoding($data);
        
        // Pooled JSON encoding benchmark
        $pooledTime = $this->benchmarkPooledEncoding($data);
        
        // Response::json() with automatic pooling
        $responseTime = $this->benchmarkResponseJson($data);

        // Calculate improvements
        $pooledImprovement = $traditionalTime > 0 ? (($traditionalTime / $pooledTime) - 1) * 100 : 0;
        $responseImprovement = $traditionalTime > 0 ? (($traditionalTime / $responseTime) - 1) * 100 : 0;

        // Display results
        printf("  Traditional:  %.4f ms (%.2f ops/sec)\n", 
            $traditionalTime * 1000, 
            self::ITERATIONS / $traditionalTime
        );
        
        printf("  Pooled:       %.4f ms (%.2f ops/sec) [%+.1f%%]\n", 
            $pooledTime * 1000, 
            self::ITERATIONS / $pooledTime,
            $pooledImprovement
        );
        
        printf("  Response:     %.4f ms (%.2f ops/sec) [%+.1f%%]\n", 
            $responseTime * 1000, 
            self::ITERATIONS / $responseTime,
            $responseImprovement
        );

        // JSON size info
        $jsonSize = strlen(json_encode($data));
        printf("  JSON Size:    %s\n", $this->formatBytes($jsonSize));
    }

    /**
     * Benchmark traditional JSON encoding
     */
    private function benchmarkTraditionalEncoding(array $data): float
    {
        $start = microtime(true);
        
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            // Prevent optimization
            if ($json === false) {
                throw new \RuntimeException('JSON encoding failed');
            }
        }
        
        return microtime(true) - $start;
    }

    /**
     * Benchmark pooled JSON encoding
     */
    private function benchmarkPooledEncoding(array $data): float
    {
        $start = microtime(true);
        
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $json = JsonBufferPool::encodeWithPool($data);
            
            // Prevent optimization
            if (empty($json)) {
                throw new \RuntimeException('Pooled encoding failed');
            }
        }
        
        return microtime(true) - $start;
    }

    /**
     * Benchmark Response::json() method
     */
    private function benchmarkResponseJson(array $data): float
    {
        $start = microtime(true);
        
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $response = new Response();
            $response->setTestMode(true); // Prevent actual output
            $response->json($data);
            
            // Prevent optimization
            $body = $response->getBodyAsString();
            if (empty($body)) {
                throw new \RuntimeException('Response JSON failed');
            }
        }
        
        return microtime(true) - $start;
    }

    /**
     * Show pool statistics
     */
    private function showPoolStatistics(): void
    {
        $stats = JsonBufferPool::getStatistics();
        
        echo "📈 Pool Statistics:\n";
        echo "------------------\n";
        printf("  Reuse Rate:       %.1f%%\n", $stats['reuse_rate']);
        printf("  Total Operations: %d\n", $stats['total_operations']);
        printf("  Peak Usage:       %d buffers\n", $stats['peak_usage']);
        printf("  Current Usage:    %d buffers\n", $stats['current_usage']);
        
        if (!empty($stats['pool_sizes'])) {
            echo "  Pool Sizes:\n";
            foreach ($stats['pool_sizes'] as $pool => $size) {
                echo "    {$pool}: {$size} buffers\n";
            }
        }
        
        echo "\n";
    }

    /**
     * Run memory usage benchmark
     */
    private function runMemoryBenchmark(): void
    {
        echo "💾 Memory Usage Benchmark:\n";
        echo "-------------------------\n";

        $testData = $this->testDataSets['Large JSON'];
        
        // Test traditional encoding memory usage
        $memBefore = memory_get_usage(true);
        for ($i = 0; $i < 1000; $i++) {
            $json = json_encode($testData);
            unset($json);
        }
        $traditionalMemory = memory_get_usage(true) - $memBefore;

        // Reset memory
        gc_collect_cycles();
        
        // Test pooled encoding memory usage
        $memBefore = memory_get_usage(true);
        for ($i = 0; $i < 1000; $i++) {
            $json = JsonBufferPool::encodeWithPool($testData);
            unset($json);
        }
        $pooledMemory = memory_get_usage(true) - $memBefore;

        $memoryImprovement = $traditionalMemory > 0 ? 
            (($traditionalMemory - $pooledMemory) / $traditionalMemory) * 100 : 0;

        printf("  Traditional: %s\n", $this->formatBytes($traditionalMemory));
        printf("  Pooled:      %s [%+.1f%%]\n", 
            $this->formatBytes($pooledMemory), 
            -$memoryImprovement
        );
        
        echo "\n";
    }

    /**
     * Format bytes for display
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string)$bytes) - 1) / 3);
        
        return sprintf("%.2f %s", $bytes / (1024 ** $factor), $units[$factor]);
    }
}

// Run benchmark if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $benchmark = new JsonPoolingBenchmark();
    $benchmark->run();
}