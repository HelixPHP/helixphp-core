<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Http\Optimization;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Optimization\ZeroCopyOptimizer;
use PivotPHP\Core\Http\Optimization\ArrayView;
use PivotPHP\Core\Http\Optimization\StreamView;

/**
 * Comprehensive tests for ZeroCopyOptimizer
 *
 * Tests zero-copy optimization techniques and memory management.
 * Following the "less is more" principle with focused, quality testing.
 */
class ZeroCopyOptimizerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset optimizer state for each test
        ZeroCopyOptimizer::reset();
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        ZeroCopyOptimizer::reset();
        parent::tearDown();
    }

    /**
     * Test string interning functionality
     */
    public function testStringInterning(): void
    {
        $string1 = 'This is a test string for interning';
        $string2 = 'This is a test string for interning';

        // First call should intern the string
        $result1 = ZeroCopyOptimizer::internString($string1);
        $this->assertEquals($string1, $result1);

        // Second call should return the same reference
        $result2 = ZeroCopyOptimizer::internString($string2);
        $this->assertEquals($string1, $result2);

        // Check statistics
        $stats = ZeroCopyOptimizer::getStats();
        $this->assertEquals(1, $stats['detailed_stats']['copies_avoided']);
        $this->assertEquals(1, $stats['interned_strings']);
    }

    /**
     * Test that short strings are not interned
     */
    public function testShortStringNotInterned(): void
    {
        $shortString = 'short';
        $result = ZeroCopyOptimizer::internString($shortString);
        
        $this->assertEquals($shortString, $result);
        
        // Should not be interned
        $stats = ZeroCopyOptimizer::getStats();
        $this->assertEquals(0, $stats['interned_strings']);
    }

    /**
     * Test that very long strings are not interned
     */
    public function testVeryLongStringNotInterned(): void
    {
        $longString = str_repeat('A', 1500); // Over 1000 character limit
        $result = ZeroCopyOptimizer::internString($longString);
        
        $this->assertEquals($longString, $result);
        
        // Should not be interned
        $stats = ZeroCopyOptimizer::getStats();
        $this->assertEquals(0, $stats['interned_strings']);
    }

    /**
     * Test array reference creation and access
     */
    public function testArrayReferenceCreation(): void
    {
        $testArray = ['key1' => 'value1', 'key2' => 'value2'];
        
        // Create reference
        $refId = ZeroCopyOptimizer::createArrayReference($testArray);
        $this->assertIsString($refId);
        $this->assertStringStartsWith('arr_ref_', $refId);

        // Access by reference
        $retrieved = ZeroCopyOptimizer::getArrayByReference($refId);
        $this->assertEquals($testArray, $retrieved);

        // Check statistics
        $stats = ZeroCopyOptimizer::getStats();
        $this->assertEquals(1, $stats['references_active']);
        $this->assertEquals(1, $stats['detailed_stats']['references_created']);
        $this->assertEquals(1, $stats['detailed_stats']['copies_avoided']);
    }

    /**
     * Test array reference with custom ID
     */
    public function testArrayReferenceWithCustomId(): void
    {
        $testArray = ['test' => 'data'];
        $customId = 'custom_array_ref';
        
        $refId = ZeroCopyOptimizer::createArrayReference($testArray, $customId);
        $this->assertEquals($customId, $refId);

        $retrieved = ZeroCopyOptimizer::getArrayByReference($customId);
        $this->assertEquals($testArray, $retrieved);
    }

    /**
     * Test accessing non-existent array reference
     */
    public function testGetNonExistentArrayReference(): void
    {
        $result = ZeroCopyOptimizer::getArrayByReference('nonexistent');
        $this->assertNull($result);
    }

    /**
     * Test buffer pool functionality
     */
    public function testBufferPooling(): void
    {
        $size = 1024;
        
        // Get buffer from pool (should be a miss first time)
        $buffer1 = ZeroCopyOptimizer::getBuffer($size);
        $this->assertEquals($size, strlen($buffer1));
        
        // Return buffer to pool
        ZeroCopyOptimizer::returnBuffer($buffer1, $size);
        
        // Get buffer again (should be a hit)
        $buffer2 = ZeroCopyOptimizer::getBuffer($size);
        $this->assertEquals($size, strlen($buffer2));
        
        // Check statistics
        $stats = ZeroCopyOptimizer::getStats();
        $this->assertEquals(1, $stats['detailed_stats']['pool_hits']);
        $this->assertEquals(1, $stats['detailed_stats']['pool_misses']);
    }

    /**
     * Test copy-on-write wrapper creation
     */
    public function testCOWWrapperCreation(): void
    {
        $testObject = (object) ['prop' => 'value'];
        
        // Create COW wrapper
        $cowId = ZeroCopyOptimizer::createCOWWrapper($testObject);
        $this->assertIsString($cowId);
        $this->assertStringStartsWith('cow_', $cowId);

        // Read access (should return original)
        $readObject = ZeroCopyOptimizer::getCOWObject($cowId, false);
        $this->assertEquals($testObject, $readObject);
        $this->assertSame($testObject, $readObject);

        // Write access (should create copy)
        $writeObject = ZeroCopyOptimizer::getCOWObject($cowId, true);
        $this->assertEquals($testObject, $writeObject);
        $this->assertNotSame($testObject, $writeObject);

        // Check statistics
        $stats = ZeroCopyOptimizer::getStats();
        $this->assertEquals(1, $stats['references_active']);
        $this->assertEquals(1, $stats['detailed_stats']['references_created']);
        $this->assertEquals(1, $stats['detailed_stats']['copies_avoided']);
    }

    /**
     * Test COW wrapper with custom ID
     */
    public function testCOWWrapperWithCustomId(): void
    {
        $testObject = (object) ['data' => 'test'];
        $customId = 'custom_cow_wrapper';
        
        $cowId = ZeroCopyOptimizer::createCOWWrapper($testObject, $customId);
        $this->assertEquals($customId, $cowId);

        $retrieved = ZeroCopyOptimizer::getCOWObject($customId, false);
        $this->assertEquals($testObject, $retrieved);
    }

    /**
     * Test accessing non-existent COW object
     */
    public function testGetNonExistentCOWObject(): void
    {
        $result = ZeroCopyOptimizer::getCOWObject('nonexistent', false);
        $this->assertNull($result);
    }

    /**
     * Test array view creation
     */
    public function testArrayViewCreation(): void
    {
        $sourceArray = ['a', 'b', 'c', 'd', 'e'];
        
        // Create array view
        $view = ZeroCopyOptimizer::createArrayView($sourceArray, 1, 3);
        $this->assertInstanceOf(ArrayView::class, $view);
        
        // ArrayView should be tested separately, but we verify it's created
        $this->assertTrue(true);
    }

    /**
     * Test stream view creation
     */
    public function testStreamViewCreation(): void
    {
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_stream_view');
        file_put_contents($tempFile, 'Test content for stream view');
        
        try {
            // Create stream view
            $view = ZeroCopyOptimizer::createStreamView($tempFile, 5, 7);
            $this->assertInstanceOf(StreamView::class, $view);
            
            // StreamView should be tested separately, but we verify it's created
            $this->assertTrue(true);
        } finally {
            // Clean up
            unlink($tempFile);
        }
    }

    /**
     * Test efficient string concatenation
     */
    public function testEfficientConcat(): void
    {
        $strings = ['Hello', ' ', 'World', '!'];
        
        $result = ZeroCopyOptimizer::efficientConcat($strings);
        $this->assertEquals('Hello World!', $result);
        
        // Check memory savings
        $stats = ZeroCopyOptimizer::getStats();
        $this->assertGreaterThan(0, $stats['detailed_stats']['memory_saved']);
    }

    /**
     * Test efficient concat with empty array
     */
    public function testEfficientConcatWithEmptyArray(): void
    {
        $result = ZeroCopyOptimizer::efficientConcat([]);
        $this->assertEquals('', $result);
    }

    /**
     * Test efficient concat with empty strings
     */
    public function testEfficientConcatWithEmptyStrings(): void
    {
        $strings = ['', 'test', '', 'data', ''];
        
        $result = ZeroCopyOptimizer::efficientConcat($strings);
        $this->assertEquals('testdata', $result);
    }

    /**
     * Test stream JSON encoding
     */
    public function testStreamJsonEncode(): void
    {
        $data = ['key' => 'value', 'number' => 42, 'bool' => true];
        
        // Create stream
        $stream = fopen('php://memory', 'r+');
        
        // Encode to stream
        $bytesWritten = ZeroCopyOptimizer::streamJsonEncode($data, $stream);
        
        // Read result
        rewind($stream);
        $jsonString = stream_get_contents($stream);
        fclose($stream);
        
        $this->assertGreaterThan(0, $bytesWritten);
        $this->assertIsString($jsonString);
        
        // Should be valid JSON (though format might differ from json_encode)
        $this->assertNotEmpty($jsonString);
    }

    /**
     * Test stream JSON encoding with array
     */
    public function testStreamJsonEncodeWithArray(): void
    {
        $data = [1, 2, 3, 'test'];
        
        $stream = fopen('php://memory', 'r+');
        $bytesWritten = ZeroCopyOptimizer::streamJsonEncode($data, $stream);
        
        rewind($stream);
        $jsonString = stream_get_contents($stream);
        fclose($stream);
        
        $this->assertGreaterThan(0, $bytesWritten);
        $this->assertStringContainsString('[', $jsonString);
        $this->assertStringContainsString(']', $jsonString);
    }

    /**
     * Test stream JSON encoding with object
     */
    public function testStreamJsonEncodeWithObject(): void
    {
        $data = (object) ['prop1' => 'value1', 'prop2' => 42];
        
        $stream = fopen('php://memory', 'r+');
        $bytesWritten = ZeroCopyOptimizer::streamJsonEncode($data, $stream);
        
        rewind($stream);
        $jsonString = stream_get_contents($stream);
        fclose($stream);
        
        $this->assertGreaterThan(0, $bytesWritten);
        $this->assertStringContainsString('{', $jsonString);
        $this->assertStringContainsString('}', $jsonString);
    }

    /**
     * Test reference cleanup
     */
    public function testReferenceCleanup(): void
    {
        $testArray = ['test' => 'data'];
        $testObject = (object) ['prop' => 'value'];
        
        // Create some references
        ZeroCopyOptimizer::createArrayReference($testArray);
        ZeroCopyOptimizer::createCOWWrapper($testObject);
        
        // Check initial state
        $stats = ZeroCopyOptimizer::getStats();
        $this->assertEquals(2, $stats['references_active']);
        
        // Clean up references
        $cleaned = ZeroCopyOptimizer::cleanupReferences();
        $this->assertArrayHasKey('references_cleaned', $cleaned);
        $this->assertArrayHasKey('memory_freed', $cleaned);
    }

    /**
     * Test statistics collection
     */
    public function testStatisticsCollection(): void
    {
        $stats = ZeroCopyOptimizer::getStats();
        
        $this->assertArrayHasKey('copies_avoided', $stats);
        $this->assertArrayHasKey('memory_saved', $stats);
        $this->assertArrayHasKey('references_active', $stats);
        $this->assertArrayHasKey('interned_strings', $stats);
        $this->assertArrayHasKey('buffer_pools', $stats);
        $this->assertArrayHasKey('memory_pools', $stats);
        $this->assertArrayHasKey('pool_efficiency', $stats);
        $this->assertArrayHasKey('detailed_stats', $stats);
        
        $this->assertEquals(0, $stats['copies_avoided']);
        $this->assertEquals(0, $stats['references_active']);
        $this->assertEquals(0, $stats['interned_strings']);
        $this->assertEquals(0.0, $stats['pool_efficiency']);
    }

    /**
     * Test pool efficiency calculation
     */
    public function testPoolEfficiencyCalculation(): void
    {
        $size = 512;
        
        // Generate some pool activity
        $buffer1 = ZeroCopyOptimizer::getBuffer($size); // Miss
        ZeroCopyOptimizer::returnBuffer($buffer1, $size);
        
        $buffer2 = ZeroCopyOptimizer::getBuffer($size); // Hit
        ZeroCopyOptimizer::returnBuffer($buffer2, $size);
        
        $buffer3 = ZeroCopyOptimizer::getBuffer($size); // Hit
        
        $stats = ZeroCopyOptimizer::getStats();
        $this->assertGreaterThan(60.0, $stats['pool_efficiency']); // Should be around 66.7%
        $this->assertLessThan(70.0, $stats['pool_efficiency']);
    }

    /**
     * Test reset functionality
     */
    public function testReset(): void
    {
        $testArray = ['test' => 'data'];
        $testString = 'This is a test string for interning';
        
        // Create some state
        ZeroCopyOptimizer::createArrayReference($testArray);
        ZeroCopyOptimizer::internString($testString);
        ZeroCopyOptimizer::getBuffer(1024);
        
        // Verify state exists
        $stats = ZeroCopyOptimizer::getStats();
        $this->assertGreaterThan(0, $stats['references_active']);
        
        // Reset
        ZeroCopyOptimizer::reset();
        
        // Verify state is cleared
        $stats = ZeroCopyOptimizer::getStats();
        $this->assertEquals(0, $stats['references_active']);
        $this->assertEquals(0, $stats['interned_strings']);
        $this->assertEquals(0, $stats['copies_avoided']);
        $this->assertEquals(0, $stats['detailed_stats']['pool_hits']);
        $this->assertEquals(0, $stats['detailed_stats']['pool_misses']);
    }

    /**
     * Test memory usage with large data
     */
    public function testMemoryUsageWithLargeData(): void
    {
        $largeArray = range(1, 10000);
        
        // Create multiple references to the same data
        $ref1 = ZeroCopyOptimizer::createArrayReference($largeArray);
        $ref2 = ZeroCopyOptimizer::createArrayReference($largeArray);
        
        // Access references
        $retrieved1 = ZeroCopyOptimizer::getArrayByReference($ref1);
        $retrieved2 = ZeroCopyOptimizer::getArrayByReference($ref2);
        
        $this->assertEquals($largeArray, $retrieved1);
        $this->assertEquals($largeArray, $retrieved2);
        
        // Check that references are working
        $stats = ZeroCopyOptimizer::getStats();
        $this->assertEquals(2, $stats['references_active']);
        $this->assertEquals(2, $stats['detailed_stats']['copies_avoided']);
    }

    /**
     * Test performance with repeated operations
     */
    public function testPerformanceWithRepeatedOperations(): void
    {
        $testString = 'This is a string that will be interned multiple times';
        
        $startTime = microtime(true);
        
        // Perform multiple interning operations
        for ($i = 0; $i < 100; $i++) {
            ZeroCopyOptimizer::internString($testString);
        }
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $this->assertLessThan(50, $duration); // Should be fast (less than 50ms)
        
        // Check that copies were avoided
        $stats = ZeroCopyOptimizer::getStats();
        $this->assertEquals(99, $stats['detailed_stats']['copies_avoided']); // First one is not a copy avoided
    }

    /**
     * Test edge cases and error conditions
     */
    public function testEdgeCases(): void
    {
        // Test with null/empty inputs
        $result = ZeroCopyOptimizer::efficientConcat([]);
        $this->assertEquals('', $result);
        
        // Test with very small buffer
        $smallBuffer = ZeroCopyOptimizer::getBuffer(1);
        $this->assertEquals(1, strlen($smallBuffer));
        
        // Test accessing wrong type of reference
        $testArray = ['test'];
        $refId = ZeroCopyOptimizer::createArrayReference($testArray);
        $cowResult = ZeroCopyOptimizer::getCOWObject($refId, false);
        $this->assertNull($cowResult);
    }
}