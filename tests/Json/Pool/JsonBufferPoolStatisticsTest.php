<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Json\Pool;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

/**
 * Test JsonBufferPool enhanced statistics functionality
 */
class JsonBufferPoolStatisticsTest extends TestCase
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
     * Test basic statistics structure
     */
    public function testBasicStatisticsStructure(): void
    {
        $stats = JsonBufferPool::getStatistics();

        // Verify all expected keys are present
        $expectedKeys = [
            'reuse_rate', 'total_operations', 'current_usage', 'peak_usage',
            'total_buffers_pooled', 'active_pool_count', 'pool_sizes', 
            'pools_by_capacity', 'detailed_stats'
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $stats, "Missing key: {$key}");
        }
    }

    /**
     * Test enhanced pool statistics with multiple buffer sizes
     */
    public function testEnhancedPoolStatistics(): void
    {
        // Create buffers of different sizes and return them to pools
        $buffer1KB = JsonBufferPool::getBuffer(1024);
        $buffer4KB = JsonBufferPool::getBuffer(4096);
        $buffer8KB = JsonBufferPool::getBuffer(8192);
        $buffer16KB = JsonBufferPool::getBuffer(16384);

        JsonBufferPool::returnBuffer($buffer1KB);
        JsonBufferPool::returnBuffer($buffer4KB);
        JsonBufferPool::returnBuffer($buffer8KB);
        JsonBufferPool::returnBuffer($buffer16KB);

        $stats = JsonBufferPool::getStatistics();

        // Check enhanced statistics
        $this->assertEquals(4, $stats['total_buffers_pooled']);
        $this->assertEquals(4, $stats['active_pool_count']);

        // Check pool_sizes has readable format
        $this->assertArrayHasKey('1.0KB (1024 bytes)', $stats['pool_sizes']);
        $this->assertArrayHasKey('4.0KB (4096 bytes)', $stats['pool_sizes']);
        $this->assertArrayHasKey('8.0KB (8192 bytes)', $stats['pool_sizes']);
        $this->assertArrayHasKey('16.0KB (16384 bytes)', $stats['pool_sizes']);

        // Check each pool has exactly 1 buffer
        $this->assertEquals(1, $stats['pool_sizes']['1.0KB (1024 bytes)']);
        $this->assertEquals(1, $stats['pool_sizes']['4.0KB (4096 bytes)']);
        $this->assertEquals(1, $stats['pool_sizes']['8.0KB (8192 bytes)']);
        $this->assertEquals(1, $stats['pool_sizes']['16.0KB (16384 bytes)']);
    }

    /**
     * Test pools_by_capacity enhanced format
     */
    public function testPoolsByCapacityFormat(): void
    {
        // Create and return buffers
        $buffer1KB = JsonBufferPool::getBuffer(1024);
        $buffer4KB = JsonBufferPool::getBuffer(4096);
        JsonBufferPool::returnBuffer($buffer1KB);
        JsonBufferPool::returnBuffer($buffer4KB);

        $stats = JsonBufferPool::getStatistics();
        $poolsByCapacity = $stats['pools_by_capacity'];

        $this->assertIsArray($poolsByCapacity);
        $this->assertCount(2, $poolsByCapacity);

        // Check first pool (should be sorted by capacity)
        $firstPool = $poolsByCapacity[0];
        $this->assertArrayHasKey('key', $firstPool);
        $this->assertArrayHasKey('capacity_bytes', $firstPool);
        $this->assertArrayHasKey('capacity_formatted', $firstPool);
        $this->assertArrayHasKey('buffers_available', $firstPool);

        // Verify it's the 1KB pool (smallest)
        $this->assertEquals(1024, $firstPool['capacity_bytes']);
        $this->assertEquals('1.0KB (1024 bytes)', $firstPool['capacity_formatted']);
        $this->assertEquals(1, $firstPool['buffers_available']);
        $this->assertEquals('buffer_1024', $firstPool['key']);

        // Check second pool (4KB)
        $secondPool = $poolsByCapacity[1];
        $this->assertEquals(4096, $secondPool['capacity_bytes']);
        $this->assertEquals('4.0KB (4096 bytes)', $secondPool['capacity_formatted']);
        $this->assertEquals(1, $secondPool['buffers_available']);
        $this->assertEquals('buffer_4096', $secondPool['key']);
    }

    /**
     * Test capacity formatting for different sizes
     */
    public function testCapacityFormatting(): void
    {
        // Use reflection to test private formatCapacity method
        $reflection = new \ReflectionClass(JsonBufferPool::class);
        $method = $reflection->getMethod('formatCapacity');
        $method->setAccessible(true);

        // Test bytes
        $this->assertEquals('512 bytes', $method->invoke(null, 512));
        $this->assertEquals('1023 bytes', $method->invoke(null, 1023));

        // Test KB
        $this->assertEquals('1.0KB (1024 bytes)', $method->invoke(null, 1024));
        $this->assertEquals('4.0KB (4096 bytes)', $method->invoke(null, 4096));
        $this->assertEquals('16.0KB (16384 bytes)', $method->invoke(null, 16384));

        // Test MB
        $this->assertEquals('1.0MB (1048576 bytes)', $method->invoke(null, 1048576));
        $this->assertEquals('2.5MB (2621440 bytes)', $method->invoke(null, 2621440));
    }

    /**
     * Test backward compatibility with legacy pool_sizes format
     */
    public function testBackwardCompatibility(): void
    {
        // Create buffer and return to pool
        $buffer = JsonBufferPool::getBuffer(2048);
        JsonBufferPool::returnBuffer($buffer);

        $stats = JsonBufferPool::getStatistics();

        // Legacy pool_sizes should still exist and be usable
        $this->assertArrayHasKey('pool_sizes', $stats);
        $this->assertIsArray($stats['pool_sizes']);

        // Should have readable format but maintain structure
        $poolSizes = $stats['pool_sizes'];
        $this->assertNotEmpty($poolSizes);

        // The value should still be the count of buffers
        foreach ($poolSizes as $key => $count) {
            $this->assertIsString($key);
            $this->assertIsInt($count);
            $this->assertGreaterThanOrEqual(0, $count);
        }
    }

    /**
     * Test statistics with multiple buffers in same pool
     */
    public function testMultipleBuffersInSamePool(): void
    {
        // Create multiple buffers of same size
        $buffer1 = JsonBufferPool::getBuffer(1024);
        $buffer2 = JsonBufferPool::getBuffer(1024);
        $buffer3 = JsonBufferPool::getBuffer(1024);

        // Return all to pool
        JsonBufferPool::returnBuffer($buffer1);
        JsonBufferPool::returnBuffer($buffer2);
        JsonBufferPool::returnBuffer($buffer3);

        $stats = JsonBufferPool::getStatistics();

        // Should have 3 buffers in the 1KB pool
        $this->assertEquals(3, $stats['pool_sizes']['1.0KB (1024 bytes)']);
        $this->assertEquals(3, $stats['total_buffers_pooled']);
        $this->assertEquals(1, $stats['active_pool_count']); // Only one pool type

        // Check pools_by_capacity format
        $poolsByCapacity = $stats['pools_by_capacity'];
        $this->assertCount(1, $poolsByCapacity);
        $this->assertEquals(3, $poolsByCapacity[0]['buffers_available']);
    }

    /**
     * Test statistics with no pools
     */
    public function testStatisticsWithNoPools(): void
    {
        $stats = JsonBufferPool::getStatistics();

        $this->assertEquals(0, $stats['total_buffers_pooled']);
        $this->assertEquals(0, $stats['active_pool_count']);
        $this->assertEmpty($stats['pool_sizes']);
        $this->assertEmpty($stats['pools_by_capacity']);
        $this->assertEquals(0, $stats['total_operations']);
        $this->assertEquals(0.0, $stats['reuse_rate']);
    }

    /**
     * Test statistics consistency between formats
     */
    public function testStatisticsConsistency(): void
    {
        // Create various sized buffers
        $buffers = [
            JsonBufferPool::getBuffer(512),
            JsonBufferPool::getBuffer(1024),
            JsonBufferPool::getBuffer(1024), // Duplicate size
            JsonBufferPool::getBuffer(4096),
        ];

        foreach ($buffers as $buffer) {
            JsonBufferPool::returnBuffer($buffer);
        }

        $stats = JsonBufferPool::getStatistics();

        // Count buffers in both formats should match
        $totalFromPoolSizes = array_sum($stats['pool_sizes']);
        $totalFromPoolsByCapacity = array_sum(array_column($stats['pools_by_capacity'], 'buffers_available'));

        $this->assertEquals($totalFromPoolSizes, $totalFromPoolsByCapacity);
        $this->assertEquals($stats['total_buffers_pooled'], $totalFromPoolSizes);

        // Number of pools should match
        $this->assertEquals(count($stats['pool_sizes']), count($stats['pools_by_capacity']));
        $this->assertEquals($stats['active_pool_count'], count($stats['pools_by_capacity']));
    }
}