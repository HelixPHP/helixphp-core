<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Json\Pool\JsonBuffer;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

/**
 * Benchmark JsonBuffer hybrid string/stream implementation
 */
class JsonBufferStreamBenchmark
{
    public function run(): void
    {
        echo "🧪 JsonBuffer Hybrid String/Stream Performance Test\n";
        echo "=================================================\n\n";

        $this->testSmallBuffers();
        $this->testLargeBuffers();
        $this->testMigrationScenarios();
        $this->testMemoryEfficiency();
    }

    private function testSmallBuffers(): void
    {
        echo "📊 Small Buffer Performance (< 8KB)\n";
        echo "-----------------------------------\n";

        $iterations = 10000;
        $smallData = ['message' => 'Hello World', 'id' => 123, 'active' => true];

        // Traditional json_encode
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $result = json_encode($smallData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $traditionalTime = microtime(true) - $start;

        // JsonBuffer (should use string mode)
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $buffer = new JsonBuffer(1024);
            $buffer->appendJson($smallData);
            $result = $buffer->finalize();
        }
        $bufferTime = microtime(true) - $start;

        $traditionalOps = $iterations / $traditionalTime;
        $bufferOps = $iterations / $bufferTime;

        echo "Traditional encode: " . number_format($traditionalOps, 0) . " ops/sec\n";
        echo "JsonBuffer (string): " . number_format($bufferOps, 0) . " ops/sec\n";
        echo "Ratio: " . number_format($bufferOps / $traditionalOps, 2) . "x\n\n";
    }

    private function testLargeBuffers(): void
    {
        echo "📊 Large Buffer Performance (> 8KB)\n";
        echo "-----------------------------------\n";

        $iterations = 1000;
        $largeData = array_fill(0, 100, [
            'id' => rand(1000, 9999),
            'name' => 'User ' . rand(1, 1000),
            'email' => 'user' . rand(1, 1000) . '@example.com',
            'data' => str_repeat('x', 50),
            'metadata' => ['created' => time(), 'active' => true]
        ]);

        // Traditional json_encode
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $result = json_encode($largeData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $traditionalTime = microtime(true) - $start;

        // JsonBuffer (should use stream mode)
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $buffer = new JsonBuffer(16384);
            $buffer->appendJson($largeData);
            $result = $buffer->finalize();
        }
        $bufferTime = microtime(true) - $start;

        $traditionalOps = $iterations / $traditionalTime;
        $bufferOps = $iterations / $bufferTime;

        echo "Traditional encode: " . number_format($traditionalOps, 0) . " ops/sec\n";
        echo "JsonBuffer (stream): " . number_format($bufferOps, 0) . " ops/sec\n";
        echo "Ratio: " . number_format($bufferOps / $traditionalOps, 2) . "x\n\n";
    }

    private function testMigrationScenarios(): void
    {
        echo "📊 String to Stream Migration Performance\n";
        echo "----------------------------------------\n";

        $iterations = 1000;

        // Test building large JSON incrementally (triggers migration)
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $buffer = new JsonBuffer(1024); // Start small
            
            $buffer->append('{"data": [');
            for ($j = 0; $j < 50; $j++) {
                if ($j > 0) $buffer->append(',');
                $buffer->appendJson(['item' => $j, 'value' => 'test' . $j]);
            }
            $buffer->append(']}');
            
            $result = $buffer->finalize();
        }
        $migrationTime = microtime(true) - $start;

        // Compare with traditional approach
        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $data = ['data' => []];
            for ($j = 0; $j < 50; $j++) {
                $data['data'][] = ['item' => $j, 'value' => 'test' . $j];
            }
            $result = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $traditionalTime = microtime(true) - $start;

        $migrationOps = $iterations / $migrationTime;
        $traditionalOps = $iterations / $traditionalTime;

        echo "Traditional build: " . number_format($traditionalOps, 0) . " ops/sec\n";
        echo "Migration build: " . number_format($migrationOps, 0) . " ops/sec\n";
        echo "Ratio: " . number_format($migrationOps / $traditionalOps, 2) . "x\n\n";
    }

    private function testMemoryEfficiency(): void
    {
        echo "💾 Memory Efficiency Test\n";
        echo "------------------------\n";

        // Test memory usage for large buffer operations
        $memStart = memory_get_usage();
        
        // Create large JSON using stream buffer
        $buffer = new JsonBuffer(32768); // Force stream mode
        $buffer->append('{"users": [');
        
        for ($i = 0; $i < 1000; $i++) {
            if ($i > 0) $buffer->append(',');
            $userData = [
                'id' => $i,
                'name' => 'User ' . $i,
                'email' => "user{$i}@example.com",
                'profile' => str_repeat('data', 25) // 100 chars
            ];
            $buffer->appendJson($userData);
        }
        
        $buffer->append(']}');
        $result = $buffer->finalize();
        
        $memStream = memory_get_usage() - $memStart;

        // Compare with traditional array building
        $memStart = memory_get_usage();
        
        $data = ['users' => []];
        for ($i = 0; $i < 1000; $i++) {
            $data['users'][] = [
                'id' => $i,
                'name' => 'User ' . $i,
                'email' => "user{$i}@example.com",
                'profile' => str_repeat('data', 25)
            ];
        }
        $traditionalResult = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        $memTraditional = memory_get_usage() - $memStart;

        echo "Result size: " . number_format(strlen($result)) . " bytes\n";
        echo "Stream memory: " . number_format($memStream / 1024, 2) . " KB\n";
        echo "Traditional memory: " . number_format($memTraditional / 1024, 2) . " KB\n";
        echo "Memory efficiency: " . number_format((1 - $memStream / $memTraditional) * 100, 1) . "% less memory\n\n";

        // Pool reuse efficiency
        echo "🏊 Pool Reuse Efficiency\n";
        echo "-----------------------\n";
        
        JsonBufferPool::clearPools(); // Reset stats
        
        $testData = array_fill(0, 20, ['field' => 'value', 'number' => 123]);
        
        for ($i = 0; $i < 100; $i++) {
            JsonBufferPool::encodeWithPool($testData);
        }
        
        $stats = JsonBufferPool::getStatistics();
        echo "Reuse rate: " . $stats['reuse_rate'] . "%\n";
        echo "Total operations: " . $stats['total_operations'] . "\n";
        echo "Peak usage: " . $stats['peak_usage'] . " buffers\n\n";

        echo "✅ Hybrid String/Stream Performance Test Complete!\n";
    }
}

// Run the benchmark
$benchmark = new JsonBufferStreamBenchmark();
$benchmark->run();