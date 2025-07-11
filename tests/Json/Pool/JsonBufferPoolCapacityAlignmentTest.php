<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Json\Pool;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Json\Pool\JsonBufferPool;
use ReflectionClass;

/**
 * Test JsonBufferPool capacity alignment between pool keys and actual buffers
 */
class JsonBufferPoolCapacityAlignmentTest extends TestCase
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
     * Test that buffer capacity matches normalized pool key capacity
     */
    public function testBufferCapacityMatchesPoolKey(): void
    {
        // Test various capacities that should be normalized
        $testCases = [
            // [requested_capacity, expected_normalized_capacity]
            [1000, 1024],      // 1000 -> 1024 (next power of 2)
            [1024, 1024],      // 1024 -> 1024 (already power of 2)
            [2000, 2048],      // 2000 -> 2048
            [512, 512],        // 512 -> 512 (already power of 2)
            [513, 1024],       // 513 -> 1024
            [100, 128],        // 100 -> 128
            [1, 1],            // 1 -> 1 (minimum)
        ];

        foreach ($testCases as [$requestedCapacity, $expectedCapacity]) {
            // Get buffer from pool
            $buffer = JsonBufferPool::getBuffer($requestedCapacity);

            // Verify that the actual buffer capacity matches the expected normalized capacity
            $this->assertEquals(
                $expectedCapacity,
                $buffer->getCapacity(),
                "Buffer requested with capacity {$requestedCapacity} should have " .
                "normalized capacity {$expectedCapacity}"
            );

            // Return buffer to pool
            JsonBufferPool::returnBuffer($buffer);
        }
    }

    /**
     * Test that returned buffers can be properly reused
     */
    public function testBufferReuseWithNormalizedCapacity(): void
    {
        // Request buffer with non-power-of-2 capacity
        $originalBuffer = JsonBufferPool::getBuffer(1000);
        $expectedCapacity = 1024; // Should be normalized to 1024

        $this->assertEquals($expectedCapacity, $originalBuffer->getCapacity());

        // Return buffer to pool
        JsonBufferPool::returnBuffer($originalBuffer);

        // Request buffer with the same non-power-of-2 capacity
        $reusedBuffer = JsonBufferPool::getBuffer(1000);

        // Should get the same buffer back (reused)
        $this->assertSame($originalBuffer, $reusedBuffer);
        $this->assertEquals($expectedCapacity, $reusedBuffer->getCapacity());

        // Also test requesting with the exact normalized capacity
        JsonBufferPool::returnBuffer($reusedBuffer);
        $exactCapacityBuffer = JsonBufferPool::getBuffer(1024);

        // Should get the same buffer back
        $this->assertSame($originalBuffer, $exactCapacityBuffer);
        $this->assertEquals($expectedCapacity, $exactCapacityBuffer->getCapacity());
    }

    /**
     * Test pool statistics accuracy with normalized capacities
     */
    public function testPoolStatisticsWithNormalizedCapacities(): void
    {
        // Request buffers with various capacities
        $buffer1 = JsonBufferPool::getBuffer(1000); // -> 1024
        $buffer2 = JsonBufferPool::getBuffer(1024); // -> 1024 (same pool)
        $buffer3 = JsonBufferPool::getBuffer(2000); // -> 2048 (different pool)

        // All buffers should have normalized capacities
        $this->assertEquals(1024, $buffer1->getCapacity());
        $this->assertEquals(1024, $buffer2->getCapacity());
        $this->assertEquals(2048, $buffer3->getCapacity());

        $stats = JsonBufferPool::getStatistics();
        $this->assertEquals(3, $stats['detailed_stats']['allocations']);
        $this->assertEquals(0, $stats['detailed_stats']['reuses']); // No reuse yet

        // Return buffers to pool
        JsonBufferPool::returnBuffer($buffer1);
        JsonBufferPool::returnBuffer($buffer2);
        JsonBufferPool::returnBuffer($buffer3);

        // Request again - should reuse
        $reusedBuffer1 = JsonBufferPool::getBuffer(1000); // Should reuse from 1024 pool
        $reusedBuffer2 = JsonBufferPool::getBuffer(2000); // Should reuse from 2048 pool

        $stats = JsonBufferPool::getStatistics();
        $this->assertEquals(3, $stats['detailed_stats']['allocations']); // No new allocations
        $this->assertEquals(2, $stats['detailed_stats']['reuses']); // 2 reuses

        // Verify we got the right buffers back
        $this->assertEquals(1024, $reusedBuffer1->getCapacity());
        $this->assertEquals(2048, $reusedBuffer2->getCapacity());
    }

    /**
     * Test that pool keys are consistent for the same normalized capacity
     */
    public function testPoolKeyConsistency(): void
    {
        $reflection = new ReflectionClass(JsonBufferPool::class);
        $getPoolKeyMethod = $reflection->getMethod('getPoolKey');
        $getPoolKeyMethod->setAccessible(true);

        // Test that different requested capacities that normalize to the same value
        // generate the same pool key
        $key1000 = $getPoolKeyMethod->invoke(null, 1000);
        $key1024 = $getPoolKeyMethod->invoke(null, 1024);
        $key900 = $getPoolKeyMethod->invoke(null, 900);

        // All should normalize to 1024
        $this->assertEquals('buffer_1024', $key1000);
        $this->assertEquals('buffer_1024', $key1024);
        $this->assertEquals('buffer_1024', $key900);

        // Different normalized capacity should have different key
        $key2000 = $getPoolKeyMethod->invoke(null, 2000);
        $this->assertEquals('buffer_2048', $key2000);
        $this->assertNotEquals($key1000, $key2000);
    }

    /**
     * Test edge case with capacity 1 (minimum)
     */
    public function testMinimumCapacityAlignment(): void
    {
        $buffer = JsonBufferPool::getBuffer(1);
        $this->assertEquals(1, $buffer->getCapacity());

        JsonBufferPool::returnBuffer($buffer);

        // Should be able to reuse
        $reusedBuffer = JsonBufferPool::getBuffer(1);
        $this->assertSame($buffer, $reusedBuffer);
    }

    /**
     * Test that buffer creation and return cycle maintains capacity consistency
     */
    public function testCapacityConsistencyThroughCycle(): void
    {
        $originalCapacity = 1500; // Will be normalized to 2048
        $expectedCapacity = 2048;

        // Create and return buffer multiple times
        for ($i = 0; $i < 5; $i++) {
            $buffer = JsonBufferPool::getBuffer($originalCapacity);
            $this->assertEquals(
                $expectedCapacity,
                $buffer->getCapacity(),
                "Iteration {$i}: Buffer capacity should remain consistent"
            );

            JsonBufferPool::returnBuffer($buffer);
        }

        // Final check - should still get normalized capacity
        $finalBuffer = JsonBufferPool::getBuffer($originalCapacity);
        $this->assertEquals($expectedCapacity, $finalBuffer->getCapacity());
    }
}
