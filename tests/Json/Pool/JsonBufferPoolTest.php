<?php

namespace PivotPHP\Core\Tests\Json\Pool;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Json\Pool\JsonBuffer;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

class JsonBufferPoolTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear pools before each test
        JsonBufferPool::clearPools();
    }

    protected function tearDown(): void
    {
        // Clear pools after each test
        JsonBufferPool::clearPools();
    }

    public function testGetBuffer(): void
    {
        $buffer = JsonBufferPool::getBuffer();

        $this->assertInstanceOf(JsonBuffer::class, $buffer);
        $this->assertEquals(4096, $buffer->getCapacity()); // default capacity
    }

    public function testGetBufferWithCustomCapacity(): void
    {
        $buffer = JsonBufferPool::getBuffer(8192);

        $this->assertEquals(8192, $buffer->getCapacity());
    }

    public function testBufferReuse(): void
    {
        // Clear pools to ensure clean state
        JsonBufferPool::clearPools();

        // Get a buffer
        $buffer1 = JsonBufferPool::getBuffer(1024);
        $buffer1->append('test data');

        // Return it to pool
        JsonBufferPool::returnBuffer($buffer1);

        // Get another buffer - should be the same one, but reset
        $buffer2 = JsonBufferPool::getBuffer(1024);

        $this->assertEquals(0, $buffer2->getSize()); // Should be reset
        $this->assertEquals(1024, $buffer2->getCapacity());

        $stats = JsonBufferPool::getStatistics();

        $this->assertArrayHasKey('detailed_stats', $stats);
        $this->assertArrayHasKey('reuses', $stats['detailed_stats']);
        $this->assertEquals(1, $stats['detailed_stats']['reuses']); // Should have exactly 1 reuse
        $this->assertEquals(50.0, $stats['reuse_rate']); // 1 reuse out of 2 operations = 50%
    }

    public function testEncodeWithPool(): void
    {
        $data = array_fill(0, 55, ['name' => 'test', 'value' => 123]); // Use data that will trigger pooling

        $json = JsonBufferPool::encodeWithPool($data);

        $this->assertStringContainsString('"name":"test"', $json);
        $this->assertStringContainsString('"value":123', $json);

        $stats = JsonBufferPool::getStatistics();
        $this->assertEquals(1, $stats['total_operations']);
    }

    public function testPoolStatistics(): void
    {
        // Perform some operations
        JsonBufferPool::getBuffer(1024);
        JsonBufferPool::getBuffer(2048);
        $buffer = JsonBufferPool::getBuffer(1024);
        JsonBufferPool::returnBuffer($buffer);

        $stats = JsonBufferPool::getStatistics();

        $this->assertArrayHasKey('reuse_rate', $stats);
        $this->assertArrayHasKey('total_operations', $stats);
        $this->assertArrayHasKey('current_usage', $stats);
        $this->assertArrayHasKey('peak_usage', $stats);
        $this->assertArrayHasKey('pool_sizes', $stats);

        $this->assertEquals(3, $stats['total_operations']);
        $this->assertEquals(2, $stats['current_usage']); // 2 buffers still in use
    }

    public function testPoolSizeLimit(): void
    {
        JsonBufferPool::configure(['max_pool_size' => 2]);

        // Create and return more buffers than the pool limit
        $buffers = [];
        for ($i = 0; $i < 5; $i++) {
            $buffers[] = JsonBufferPool::getBuffer(1024);
        }

        // Return all buffers
        foreach ($buffers as $buffer) {
            JsonBufferPool::returnBuffer($buffer);
        }

        $stats = JsonBufferPool::getStatistics();

        // Pool should not exceed the configured limit
        $this->assertLessThanOrEqual(2, array_sum($stats['pool_sizes']));
    }

    public function testOptimalCapacityCalculation(): void
    {
        // Test small data
        $smallData = ['id' => 1];
        $capacity = JsonBufferPool::getOptimalCapacity($smallData);
        $this->assertEquals(1024, $capacity);

        // Test medium data
        $mediumData = array_fill(0, 50, ['id' => 1, 'name' => 'test']);
        $capacity = JsonBufferPool::getOptimalCapacity($mediumData);
        $this->assertEquals(4096, $capacity);

        // Test large data
        $largeData = array_fill(0, 500, ['id' => 1, 'name' => 'test']);
        $capacity = JsonBufferPool::getOptimalCapacity($largeData);
        $this->assertEquals(16384, $capacity);
    }

    public function testJsonSizeEstimation(): void
    {
        // Test with reflection to access private method
        $reflection = new \ReflectionClass(JsonBufferPool::class);
        $method = $reflection->getMethod('estimateJsonSize');
        $method->setAccessible(true);

        // Test string
        $size = $method->invoke(null, 'hello world');
        $this->assertEquals(31, $size); // 11 chars + 20 overhead

        // Test empty array
        $size = $method->invoke(null, []);
        $this->assertEquals(2, $size); // []

        // Test small array
        $size = $method->invoke(null, [1, 2, 3]);
        $this->assertEquals(512, $size);

        // Test boolean
        $size = $method->invoke(null, true);
        $this->assertEquals(10, $size);

        // Test null
        $size = $method->invoke(null, null);
        $this->assertEquals(10, $size);
    }

    public function testPoolConfiguration(): void
    {
        $config = [
            'max_pool_size' => 100,
            'default_capacity' => 8192
        ];

        JsonBufferPool::configure($config);

        $buffer = JsonBufferPool::getBuffer();
        $this->assertEquals(8192, $buffer->getCapacity());
    }

    public function testConcurrentBufferUsage(): void
    {
        $buffers = [];

        // Get multiple buffers simultaneously
        for ($i = 0; $i < 10; $i++) {
            $buffer = JsonBufferPool::getBuffer(1024);
            $buffer->appendJson(['iteration' => $i]);
            $buffers[] = $buffer;
        }

        // Verify each buffer has correct content
        foreach ($buffers as $index => $buffer) {
            $result = $buffer->finalize();
            $this->assertEquals("{\"iteration\":$index}", $result);
        }

        // Return all buffers
        foreach ($buffers as $buffer) {
            JsonBufferPool::returnBuffer($buffer);
        }

        $stats = JsonBufferPool::getStatistics();
        $this->assertEquals(0, $stats['current_usage']);
    }

    public function testReuseRateCalculation(): void
    {
        // Create some buffers and return them
        $buffer1 = JsonBufferPool::getBuffer(1024);
        JsonBufferPool::returnBuffer($buffer1);

        $buffer2 = JsonBufferPool::getBuffer(1024); // Should reuse buffer1
        JsonBufferPool::returnBuffer($buffer2);

        $stats = JsonBufferPool::getStatistics();
        $this->assertEquals(50.0, $stats['reuse_rate']); // 1 reuse out of 2 operations = 50%
    }
}
