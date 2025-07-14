<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Json\Pool;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

/**
 * Test JsonBufferPool encodeWithPool method uses optimal capacity
 */
class JsonBufferPoolEncodeTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear pools and reset configuration before each test
        JsonBufferPool::clearPools();
        JsonBufferPool::resetConfiguration();
    }

    protected function tearDown(): void
    {
        // Clear pools and reset configuration after each test
        JsonBufferPool::clearPools();
        JsonBufferPool::resetConfiguration();
    }

    /**
     * Test that encodeWithPool uses standard size categories
     */
    public function testEncodeWithPoolUsesStandardCapacities(): void
    {
        // Test small data that should use 1KB buffer (use nested structure with 15 elements)
        $smallData = array_fill(0, 15, ['a' => 'x', 'b' => ['nested' => 'y']]); // Nested structure ensures pooling
        $json1 = JsonBufferPool::encodeWithPool($smallData);

        // Test medium data that should use 4KB buffer
        $mediumData = array_fill(0, 100, ['field' => 'value', 'num' => 123]);
        $json2 = JsonBufferPool::encodeWithPool($mediumData);

        // Test large data that should use 16KB buffer
        $largeData = array_fill(0, 500, ['item' => 'data', 'id' => rand(1, 1000)]);
        $json3 = JsonBufferPool::encodeWithPool($largeData);

        $stats = JsonBufferPool::getStatistics();
        $poolSizes = $stats['pool_sizes'];

        // Should have created standard sized pools, not arbitrary ones
        // Note: Based on data estimation, we should have at least some standard pools
        $this->assertGreaterThan(0, count($poolSizes), 'Should have created at least one standard pool');

        // Check that only standard sizes were used (not arbitrary ones)
        $validSizes = ['1.0KB (1024 bytes)', '4.0KB (4096 bytes)', '16.0KB (16384 bytes)', '64.0KB (65536 bytes)'];
        foreach (array_keys($poolSizes) as $poolKey) {
            $this->assertContains($poolKey, $validSizes, "Pool size '$poolKey' should be a standard size");
        }

        // Each pool should have exactly 1 buffer returned to it
        foreach ($poolSizes as $size => $count) {
            $this->assertEquals(1, $count, "Pool '$size' should have exactly 1 buffer");
        }

        // Verify JSON output is correct
        $this->assertIsString($json1);
        $this->assertIsString($json2);
        $this->assertIsString($json3);

        $this->assertStringContainsString('nested', $json1);
        $this->assertStringContainsString('value', $json2);
        $this->assertStringContainsString('data', $json3);
    }

    /**
     * Test that multiple calls with similar data reuse same pool
     */
    public function testEncodeWithPoolReusesBuffers(): void
    {
        // Encode similar sized data multiple times (use data that will use pooling)
        for ($i = 0; $i < 5; $i++) {
            $data = array_fill(0, 55, ['iteration' => $i, 'test' => 'data']); // Ensure pooling
            $json = JsonBufferPool::encodeWithPool($data);
            $this->assertStringContainsString((string)$i, $json);
        }

        $stats = JsonBufferPool::getStatistics();

        // Should have high reuse rate since all data uses same buffer size
        $this->assertEquals(5, $stats['total_operations']);
        $this->assertEquals(4, $stats['detailed_stats']['reuses']); // 4 reuses (first is allocation)
        $this->assertEquals(80.0, $stats['reuse_rate']); // 4/5 * 100 = 80%

        // Should only have one pool type
        $this->assertEquals(1, $stats['active_pool_count']);
        // Pool size will depend on data estimation, just verify there's one active
    }

    /**
     * Test edge case with very large data
     */
    public function testEncodeWithPoolLargeData(): void
    {
        // Create data that exceeds standard categories
        $veryLargeData = array_fill(0, 2000, ['id' => rand(1, 10000), 'data' => str_repeat('x', 50)]);
        $json = JsonBufferPool::encodeWithPool($veryLargeData);

        $this->assertIsString($json);
        $this->assertGreaterThan(100000, strlen($json)); // Should be large JSON

        $stats = JsonBufferPool::getStatistics();
        $poolsByCapacity = $stats['pools_by_capacity'];

        // Should have created a large custom capacity pool
        $this->assertNotEmpty($poolsByCapacity);
        $largestPool = end($poolsByCapacity);
        $this->assertGreaterThanOrEqual(65536, $largestPool['capacity_bytes']); // At least 64KB
    }

    /**
     * Test that different data types get appropriate buffer sizes
     */
    public function testEncodeWithPoolDataTypeOptimization(): void
    {
        // String data - should use small buffer
        $stringData = 'This is a simple string';
        JsonBufferPool::encodeWithPool($stringData);

        // Array data - should use appropriately sized buffer
        $arrayData = range(1, 100);
        JsonBufferPool::encodeWithPool($arrayData);

        // Object data - should use appropriately sized buffer
        $objectData = (object)array_fill_keys(range('a', 'z'), 'value');
        JsonBufferPool::encodeWithPool($objectData);

        $stats = JsonBufferPool::getStatistics();

        // Should have multiple pool sizes for different data types
        $this->assertGreaterThanOrEqual(2, $stats['active_pool_count']);
        $this->assertGreaterThanOrEqual(2, count($stats['pool_sizes']));
    }

    /**
     * Test consistency between encodeWithPool and manual buffer usage
     */
    public function testEncodeWithPoolConsistency(): void
    {
        $testData = ['message' => 'Hello World', 'count' => 42, 'active' => true];

        // Encode using pool
        $pooledResult = JsonBufferPool::encodeWithPool($testData);

        // Encode manually with same flags
        $manualResult = json_encode($testData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Results should be identical
        $this->assertEquals($manualResult, $pooledResult);
        $this->assertStringContainsString('Hello World', $pooledResult);
        $this->assertStringContainsString('42', $pooledResult);
        $this->assertStringContainsString('true', $pooledResult);
    }

    /**
     * Test that encodeWithPool handles encoding failures gracefully
     */
    public function testEncodeWithPoolErrorHandling(): void
    {
        // Create data that should encode fine and use pooling
        $validData = array_fill(0, 55, ['test' => 'data']);
        $result = JsonBufferPool::encodeWithPool($validData);

        $this->assertIsString($result);
        $this->assertStringContainsString('"test":"data"', $result);

        // Verify buffer was returned to pool even after successful encoding
        $stats = JsonBufferPool::getStatistics();
        $this->assertEquals(1, $stats['total_buffers_pooled']);
    }

    /**
     * Test memory efficiency with optimal capacity selection
     */
    public function testEncodeWithPoolMemoryEfficiency(): void
    {
        $memBefore = memory_get_usage();

        // Encode various sized data multiple times
        for ($i = 0; $i < 10; $i++) {
            // Small data (use nested structure to ensure pooling)
            JsonBufferPool::encodeWithPool(array_fill(0, 15, ['small' => $i, 'nested' => ['data' => 'x']]));

            // Medium data
            JsonBufferPool::encodeWithPool(array_fill(0, 80, ['med' => $i]));

            // Large data
            JsonBufferPool::encodeWithPool(array_fill(0, 200, ['large' => $i]));
        }

        $memAfter = memory_get_usage();
        $stats = JsonBufferPool::getStatistics();

        // Memory growth should be reasonable due to buffer reuse
        $memoryGrowth = $memAfter - $memBefore;
        $this->assertLessThan(1024 * 1024, $memoryGrowth); // Less than 1MB growth

        // Should have high reuse rate
        $this->assertGreaterThan(70, $stats['reuse_rate']); // At least 70% reuse

        // Should have created pool sizes (2 or 3 depending on data estimation)
        $this->assertGreaterThanOrEqual(2, $stats['active_pool_count']); // At least 2 different sizes
    }
}
