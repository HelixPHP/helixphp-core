<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Json\Pool;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Json\Pool\JsonBuffer;

/**
 * Test JsonBuffer hybrid string/stream implementation
 */
class JsonBufferStreamTest extends TestCase
{
    /**
     * Test that small buffers use string concatenation
     */
    public function testSmallBufferUsesString(): void
    {
        $buffer = new JsonBuffer(1024); // Below 8KB threshold

        // Add some small data
        $buffer->append('{"test": "value"}');
        $buffer->appendJson(['another' => 'item']);

        $result = $buffer->finalize();

        $this->assertStringContainsString('test', $result);
        $this->assertStringContainsString('another', $result);
    }

    /**
     * Test that large buffers use streams from the start
     */
    public function testLargeBufferUsesStream(): void
    {
        $buffer = new JsonBuffer(16384); // Above 8KB threshold

        // Add data to stream-based buffer
        $largeData = array_fill(0, 100, ['field' => 'value', 'number' => 123]);
        $buffer->appendJson($largeData);

        $result = $buffer->finalize();
        $decoded = json_decode($result, true);

        $this->assertIsArray($decoded);
        $this->assertCount(100, $decoded);
        $this->assertEquals('value', $decoded[0]['field']);
    }

    /**
     * Test migration from string to stream when buffer grows
     */
    public function testStringToStreamMigration(): void
    {
        $buffer = new JsonBuffer(1024); // Start with small buffer

        // Add small amount of data (uses string) - start of valid JSON object
        $buffer->append('{"initial": "data", "items": ');

        // Add large amount of data to trigger migration to stream
        $largeData = array_fill(0, 200, ['field' => 'value' . rand(1000, 9999)]);
        $buffer->appendJson($largeData);

        // Close the JSON object
        $buffer->append('}');

        $result = $buffer->finalize();
        $this->assertStringContainsString('initial', $result);
        $this->assertStringContainsString('field', $result);

        // Verify it's valid JSON
        $decoded = json_decode($result, true);
        $this->assertNotNull($decoded, 'Result should be valid JSON: ' . json_last_error_msg());
        $this->assertEquals('data', $decoded['initial']);
        $this->assertIsArray($decoded['items']);
        $this->assertCount(200, $decoded['items']);
    }

    /**
     * Test buffer reset works with both string and stream modes
     */
    public function testResetWithStreamMigration(): void
    {
        $buffer = new JsonBuffer(1024);

        // First use - small data (string mode)
        $buffer->appendJson(['first' => 'use']);
        $result1 = $buffer->finalize();
        $this->assertStringContainsString('first', $result1);

        // Reset
        $buffer->reset();

        // Second use - large data (should migrate to stream)
        $largeData = array_fill(0, 150, ['iteration' => 2]);
        $buffer->appendJson($largeData);
        $result2 = $buffer->finalize();

        $this->assertStringNotContainsString('first', $result2);
        $this->assertStringContainsString('iteration', $result2);

        $decoded = json_decode($result2, true);
        $this->assertIsArray($decoded);
        $this->assertCount(150, $decoded);
    }

    /**
     * Test multiple append operations work correctly with streams
     */
    public function testMultipleAppendsWithStream(): void
    {
        $buffer = new JsonBuffer(16384); // Force stream mode

        // Multiple append operations
        $buffer->append('{"start": true,');
        $buffer->append('"items": [');

        for ($i = 0; $i < 10; $i++) {
            if ($i > 0) {
                $buffer->append(',');
            }
            $buffer->appendJson(['item' => $i, 'value' => "test{$i}"]);
        }

        $buffer->append(']}');

        $result = $buffer->finalize();
        $decoded = json_decode($result, true);

        $this->assertIsArray($decoded);
        $this->assertTrue($decoded['start']);
        $this->assertIsArray($decoded['items']);
        $this->assertCount(10, $decoded['items']);
        $this->assertEquals(0, $decoded['items'][0]['item']);
        $this->assertEquals(9, $decoded['items'][9]['item']);
    }

    /**
     * Test memory efficiency of stream approach
     */
    public function testStreamMemoryEfficiency(): void
    {
        $memBefore = memory_get_usage();

        // Create a buffer with large capacity
        $buffer = new JsonBuffer(32768);

        // Add significant amount of data
        for ($i = 0; $i < 50; $i++) {
            $chunk = array_fill(0, 20, ['iteration' => $i, 'data' => str_repeat('x', 100)]);
            $buffer->appendJson($chunk);
            if ($i < 49) {
                $buffer->append(',');
            }
        }

        $result = $buffer->finalize();
        $memAfter = memory_get_usage();

        // Verify result is valid and large
        $this->assertGreaterThan(50000, strlen($result)); // Should be large

        // Memory growth should be reasonable (streams are more memory efficient)
        $memoryGrowth = $memAfter - $memBefore;
        $this->assertLessThan(5 * 1024 * 1024, $memoryGrowth); // Less than 5MB growth
    }

    /**
     * Test that finalize can be called multiple times safely
     */
    public function testMultipleFinalizeCalls(): void
    {
        $buffer = new JsonBuffer(16384);
        $buffer->appendJson(['test' => 'data']);

        $result1 = $buffer->finalize();
        $result2 = $buffer->finalize();
        $result3 = $buffer->finalize();

        $this->assertEquals($result1, $result2);
        $this->assertEquals($result2, $result3);
        $this->assertStringContainsString('test', $result1);
    }
}
