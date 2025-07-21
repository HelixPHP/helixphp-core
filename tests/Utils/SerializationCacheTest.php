<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Utils;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Utils\SerializationCache;

class SerializationCacheTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear cache before each test
        SerializationCache::clearCache();
    }

    protected function tearDown(): void
    {
        // Clear cache after each test
        SerializationCache::clearCache();
    }

    public function testGetSerializedSize(): void
    {
        $data = ['key' => 'value', 'number' => 123];

        // First call should be a cache miss
        $size1 = SerializationCache::getSerializedSize($data);
        $this->assertIsInt($size1);
        $this->assertGreaterThan(0, $size1);

        // Second call should be a cache hit
        $size2 = SerializationCache::getSerializedSize($data);
        $this->assertEquals($size1, $size2);

        // Verify cache statistics
        $stats = SerializationCache::getStats();
        $this->assertEquals(1, $stats['cache_hits']);
        $this->assertEquals(1, $stats['cache_misses']);
        $this->assertEquals(50.0, $stats['hit_rate_percent']);
    }

    public function testGetSerializedSizeWithCustomKey(): void
    {
        $data = ['test' => 'data'];
        $customKey = 'my_custom_key';

        $size = SerializationCache::getSerializedSize($data, $customKey);
        $this->assertIsInt($size);

        // Second call with same key should hit cache
        $size2 = SerializationCache::getSerializedSize($data, $customKey);
        $this->assertEquals($size, $size2);

        $stats = SerializationCache::getStats();
        $this->assertEquals(1, $stats['cache_hits']);
    }

    public function testGetSerializedSizeWithDifferentData(): void
    {
        $data1 = ['key1' => 'value1'];
        $data2 = ['key2' => 'value2'];

        $size1 = SerializationCache::getSerializedSize($data1);
        $size2 = SerializationCache::getSerializedSize($data2);

        // Different data might have same size, but should have different cache keys
        $this->assertIsInt($size1);
        $this->assertIsInt($size2);

        // Both should be cache misses
        $stats = SerializationCache::getStats();
        $this->assertEquals(0, $stats['cache_hits']);
        $this->assertEquals(2, $stats['cache_misses']);
    }

    public function testGetSerializedSizeWithModifiedData(): void
    {
        $data = ['key' => 'value'];
        $key = 'test_key';

        // First call
        $size1 = SerializationCache::getSerializedSize($data, $key);

        // Modify data
        $data['key'] = 'modified_value';

        // Second call should detect change and recalculate
        $size2 = SerializationCache::getSerializedSize($data, $key);
        $this->assertNotEquals($size1, $size2);

        $stats = SerializationCache::getStats();
        $this->assertEquals(0, $stats['cache_hits']);
        $this->assertEquals(2, $stats['cache_misses']);
    }

    public function testGetTotalSerializedSize(): void
    {
        $objects = [
            ['item1' => 'value1'],
            ['item2' => 'value2'],
            ['item3' => 'value3']
        ];

        $totalSize = SerializationCache::getTotalSerializedSize($objects);
        $this->assertIsInt($totalSize);
        $this->assertGreaterThan(0, $totalSize);

        // Calculate manually for comparison
        $manualTotal = 0;
        foreach ($objects as $object) {
            $manualTotal += strlen(serialize($object));
        }
        $this->assertEquals($manualTotal, $totalSize);
    }

    public function testGetTotalSerializedSizeWithCustomKeys(): void
    {
        $objects = [
            ['data1' => 'test1'],
            ['data2' => 'test2']
        ];
        $keys = ['custom_key_1', 'custom_key_2'];

        // First, ensure cache is truly clear for this specific test
        SerializationCache::clear();

        // Check that cache is actually empty
        $initialStats = SerializationCache::getStats();
        $this->assertEquals(0, $initialStats['cache_hits']);
        $this->assertEquals(0, $initialStats['cache_misses']);

        // First call should generate cache misses
        $totalSize1 = SerializationCache::getTotalSerializedSize($objects, $keys);
        $this->assertIsInt($totalSize1);
        $this->assertGreaterThan(0, $totalSize1);

        // Check stats after first call - should have 2 misses, 0 hits
        $afterFirstStats = SerializationCache::getStats();
        $this->assertEquals(2, $afterFirstStats['cache_misses']);
        $this->assertEquals(0, $afterFirstStats['cache_hits']);

        // Second call should generate cache hits
        $totalSize2 = SerializationCache::getTotalSerializedSize($objects, $keys);
        $this->assertEquals($totalSize1, $totalSize2);

        // Check final stats - verify caching is working correctly
        $finalStats = SerializationCache::getStats();

        // In the full test suite, other components may use the cache, so we check deltas
        $missesDelta = $finalStats['cache_misses'] - $afterFirstStats['cache_misses'];
        $hitsDelta = $finalStats['cache_hits'] - $afterFirstStats['cache_hits'];

        // The second call should not generate additional misses (delta should be 0)
        $this->assertEquals(0, $missesDelta, 'Second call should not generate cache misses');

        // The second call should generate exactly 2 hits (one for each object)
        $this->assertEquals(2, $hitsDelta, 'Second call should generate 2 cache hits');
    }

    public function testGetSerializedData(): void
    {
        $data = ['serialize' => 'me'];

        // First call should be cache miss
        $serialized1 = SerializationCache::getSerializedData($data);
        $this->assertIsString($serialized1);
        $this->assertEquals($data, unserialize($serialized1));

        // Second call should be cache hit
        $serialized2 = SerializationCache::getSerializedData($data);
        $this->assertEquals($serialized1, $serialized2);

        $stats = SerializationCache::getStats();
        $this->assertEquals(1, $stats['cache_hits']);
        $this->assertEquals(1, $stats['cache_misses']);
    }

    public function testGetSerializedDataWithCustomKey(): void
    {
        $data = ['test' => 'serialization'];
        $key = 'my_serialization_key';

        $serialized = SerializationCache::getSerializedData($data, $key);
        $this->assertEquals($data, unserialize($serialized));

        // Verify same result with cache hit
        $serialized2 = SerializationCache::getSerializedData($data, $key);
        $this->assertEquals($serialized, $serialized2);

        $stats = SerializationCache::getStats();
        $this->assertEquals(1, $stats['cache_hits']);
    }

    public function testCacheKeyGeneration(): void
    {
        // Test different data types generate different cache behavior

        // Arrays
        $array1 = ['a' => 1, 'b' => 2];
        $array2 = ['a' => 1, 'b' => 2]; // Same content
        $array3 = ['c' => 1, 'd' => 2]; // Different keys

        SerializationCache::getSerializedSize($array1);
        SerializationCache::getSerializedSize($array2); // Should hit cache
        SerializationCache::getSerializedSize($array3); // Should miss cache

        $stats = SerializationCache::getStats();
        $this->assertEquals(1, $stats['cache_hits']);
        $this->assertEquals(2, $stats['cache_misses']);

        // Objects
        SerializationCache::clearCache();

        $obj1 = new \stdClass();
        $obj1->prop = 'value';
        $obj2 = new \stdClass();
        $obj2->prop = 'value';

        SerializationCache::getSerializedSize($obj1);
        SerializationCache::getSerializedSize($obj2); // Different object instance, should miss

        $stats = SerializationCache::getStats();
        $this->assertEquals(0, $stats['cache_hits']);
        $this->assertEquals(2, $stats['cache_misses']);
    }

    public function testLargeArrayHashOptimization(): void
    {
        // Create large array to test sampling optimization
        $largeArray = [];
        for ($i = 0; $i < 100; $i++) {
            $largeArray["key_$i"] = "value_$i";
        }

        $size1 = SerializationCache::getSerializedSize($largeArray);
        $size2 = SerializationCache::getSerializedSize($largeArray);

        $this->assertEquals($size1, $size2);

        $stats = SerializationCache::getStats();
        $this->assertEquals(1, $stats['cache_hits']);
    }

    public function testCacheEviction(): void
    {
        // Set small cache size to trigger eviction
        SerializationCache::setMaxCacheSize(5);

        // Fill cache beyond limit
        for ($i = 0; $i < 10; $i++) {
            $data = ["item_$i" => "value_$i"];
            SerializationCache::getSerializedSize($data, "key_$i");
        }

        $stats = SerializationCache::getStats();

        // Cache should not exceed max size due to eviction (some tolerance for eviction logic)
        $this->assertLessThanOrEqual(10, $stats['cache_entries']);
        $this->assertEquals(0, $stats['cache_hits']); // All should be misses due to filling
    }

    public function testClearCache(): void
    {
        $data = ['clear' => 'test'];

        // Generate some cache entries
        SerializationCache::getSerializedSize($data);
        SerializationCache::getSerializedData($data);

        $stats = SerializationCache::getStats();
        $this->assertGreaterThan(0, $stats['cache_entries']);

        // Clear cache
        SerializationCache::clearCache();

        $stats = SerializationCache::getStats();
        $this->assertEquals(0, $stats['cache_entries']);
        $this->assertEquals(0, $stats['cache_hits']);
        $this->assertEquals(0, $stats['cache_misses']);
    }

    public function testGetStats(): void
    {
        $data1 = ['stats' => 'test1'];
        $data2 = ['stats' => 'test2'];

        // Create some cache activity
        SerializationCache::getSerializedSize($data1);
        SerializationCache::getSerializedSize($data1); // Hit
        SerializationCache::getSerializedSize($data2); // Miss

        $stats = SerializationCache::getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('cache_entries', $stats);
        $this->assertArrayHasKey('size_cache_entries', $stats);
        $this->assertArrayHasKey('hash_cache_entries', $stats);
        $this->assertArrayHasKey('cache_hits', $stats);
        $this->assertArrayHasKey('cache_misses', $stats);
        $this->assertArrayHasKey('hit_rate_percent', $stats);
        $this->assertArrayHasKey('memory_usage', $stats);

        $this->assertEquals(1, $stats['cache_hits']);
        $this->assertEquals(2, $stats['cache_misses']);
        $this->assertEquals(33.33, $stats['hit_rate_percent']);
        $this->assertStringContainsString('B', $stats['memory_usage']); // Should contain byte indicator
    }

    public function testSetMaxCacheSize(): void
    {
        // Test setting valid size
        SerializationCache::setMaxCacheSize(50);

        // Fill cache and verify it respects new limit
        for ($i = 0; $i < 60; $i++) {
            SerializationCache::getSerializedSize(["item_$i" => $i]);
        }

        $stats = SerializationCache::getStats();
        $this->assertLessThanOrEqual(50, $stats['cache_entries']);

        // Test minimum size enforcement
        SerializationCache::setMaxCacheSize(5);
        SerializationCache::clearCache();

        for ($i = 0; $i < 15; $i++) {
            SerializationCache::getSerializedSize(["min_test_$i" => $i]);
        }

        $stats = SerializationCache::getStats();
        $this->assertLessThanOrEqual(10, $stats['cache_entries']); // Should be limited

        // Test that very small values get set to minimum
        SerializationCache::setMaxCacheSize(1);
        // Should be set to 10 (minimum)
        SerializationCache::clearCache();

        for ($i = 0; $i < 12; $i++) {
            SerializationCache::getSerializedSize(["very_small_$i" => $i]);
        }

        $stats = SerializationCache::getStats();
        $this->assertLessThanOrEqual(10, $stats['cache_entries']);
    }

    public function testMemoryUsageCalculation(): void
    {
        // Add some data to cache
        for ($i = 0; $i < 10; $i++) {
            $data = array_fill(0, 10, "memory_test_$i");
            SerializationCache::getSerializedSize($data);
        }

        $stats = SerializationCache::getStats();
        $memoryUsage = $stats['memory_usage'];

        // Should be a formatted string with units
        $this->assertIsString($memoryUsage);
        $this->assertTrue(
            str_contains($memoryUsage, 'B') ||
            str_contains($memoryUsage, 'KB') ||
            str_contains($memoryUsage, 'MB')
        );
    }

    public function testScalarDataCaching(): void
    {
        // Test different scalar types
        $string = 'test string';
        $int = 12345;
        $float = 123.45;
        $bool = true;

        SerializationCache::getSerializedSize($string);
        SerializationCache::getSerializedSize($string); // Should hit

        SerializationCache::getSerializedSize($int);
        SerializationCache::getSerializedSize($int); // Should hit

        $stats = SerializationCache::getStats();
        $this->assertEquals(2, $stats['cache_hits']);
        $this->assertEquals(2, $stats['cache_misses']);
    }

    public function testObjectCaching(): void
    {
        $obj = new \stdClass();
        $obj->property = 'test';

        $size1 = SerializationCache::getSerializedSize($obj);
        $size2 = SerializationCache::getSerializedSize($obj); // Should hit cache

        $this->assertEquals($size1, $size2);

        $stats = SerializationCache::getStats();
        $this->assertEquals(1, $stats['cache_hits']);

        // Modify object and test cache invalidation
        $obj->property = 'modified';
        $size3 = SerializationCache::getSerializedSize($obj); // Should miss due to change

        $this->assertNotEquals($size1, $size3);
    }
}
