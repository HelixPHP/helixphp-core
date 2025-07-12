<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Cache;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Cache\FileCache;
use PivotPHP\Core\Cache\CacheInterface;

/**
 * Comprehensive test suite for FileCache class
 * 
 * Tests file system operations, cache storage/retrieval, TTL handling,
 * serialization, directory management, and all file cache functionality.
 */
class FileCacheTest extends TestCase
{
    private FileCache $cache;
    private string $tempCacheDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create temporary cache directory
        $this->tempCacheDir = sys_get_temp_dir() . '/pivotphp_cache_test_' . uniqid();
        $this->cache = new FileCache($this->tempCacheDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up cache directory
        if (is_dir($this->tempCacheDir)) {
            $this->removeDirectory($this->tempCacheDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    // =========================================================================
    // INTERFACE COMPLIANCE TESTS
    // =========================================================================

    public function testImplementsCacheInterface(): void
    {
        $this->assertInstanceOf(CacheInterface::class, $this->cache);
    }

    public function testAllInterfaceMethodsExist(): void
    {
        $requiredMethods = ['get', 'set', 'delete', 'clear', 'has'];
        
        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                method_exists($this->cache, $method),
                "Method {$method} must exist"
            );
        }
    }

    // =========================================================================
    // DIRECTORY CREATION TESTS
    // =========================================================================

    public function testDirectoryCreationOnInstantiation(): void
    {
        $newCacheDir = sys_get_temp_dir() . '/test_cache_creation_' . uniqid();
        
        // Directory should not exist yet
        $this->assertFalse(is_dir($newCacheDir));
        
        // Create cache with new directory
        $cache = new FileCache($newCacheDir);
        
        // Directory should now exist
        $this->assertTrue(is_dir($newCacheDir));
        
        // Clean up
        rmdir($newCacheDir);
    }

    public function testDefaultCacheDirectory(): void
    {
        $cache = new FileCache();
        
        // Should use default temp directory
        $defaultDir = sys_get_temp_dir() . '/express-cache';
        $this->assertTrue(is_dir($defaultDir));
        
        // Clean up
        if (is_dir($defaultDir)) {
            $this->removeDirectory($defaultDir);
        }
    }

    public function testDirectoryPermissions(): void
    {
        // Verify directory was created with correct permissions
        $permissions = fileperms($this->tempCacheDir);
        $octal = substr(sprintf('%o', $permissions), -4);
        
        // Should be 755 or similar (depends on umask)
        $this->assertGreaterThanOrEqual(0755, octdec($octal));
    }

    // =========================================================================
    // BASIC CACHE OPERATIONS TESTS
    // =========================================================================

    public function testSetAndGetBasicValue(): void
    {
        $key = 'test_key';
        $value = 'test_value';
        
        $result = $this->cache->set($key, $value);
        $this->assertTrue($result);
        
        $retrieved = $this->cache->get($key);
        $this->assertEquals($value, $retrieved);
    }

    public function testGetNonExistentKey(): void
    {
        $result = $this->cache->get('non_existent_key');
        $this->assertNull($result);
    }

    public function testGetNonExistentKeyWithDefault(): void
    {
        $default = 'default_value';
        $result = $this->cache->get('non_existent_key', $default);
        $this->assertEquals($default, $result);
    }

    public function testSetOverwritesExistingValue(): void
    {
        $key = 'overwrite_test';
        
        $this->cache->set($key, 'original_value');
        $this->assertEquals('original_value', $this->cache->get($key));
        
        $this->cache->set($key, 'new_value');
        $this->assertEquals('new_value', $this->cache->get($key));
    }

    // =========================================================================
    // DATA TYPE SERIALIZATION TESTS
    // =========================================================================

    public function testStringValues(): void
    {
        $key = 'string_test';
        $value = 'This is a test string with special chars: !@#$%^&*()';
        
        $this->cache->set($key, $value);
        $this->assertEquals($value, $this->cache->get($key));
    }

    public function testIntegerValues(): void
    {
        $testCases = [0, 1, -1, 12345, -67890, PHP_INT_MAX, PHP_INT_MIN];
        
        foreach ($testCases as $value) {
            $key = "int_test_{$value}";
            $this->cache->set($key, $value);
            $this->assertSame($value, $this->cache->get($key));
        }
    }

    public function testFloatValues(): void
    {
        $testCases = [0.0, 1.5, -2.7, 3.14159, 1.23e-10, 9.87e20];
        
        foreach ($testCases as $value) {
            $key = "float_test_{$value}";
            $this->cache->set($key, $value);
            $this->assertEquals($value, $this->cache->get($key), '', 0.000001);
        }
    }

    public function testBooleanValues(): void
    {
        $this->cache->set('bool_true', true);
        $this->cache->set('bool_false', false);
        
        $this->assertTrue($this->cache->get('bool_true'));
        $this->assertFalse($this->cache->get('bool_false'));
    }

    public function testNullValue(): void
    {
        $this->cache->set('null_test', null);
        $this->assertNull($this->cache->get('null_test'));
    }

    public function testArrayValues(): void
    {
        $arrays = [
            'simple' => [1, 2, 3],
            'associative' => ['name' => 'John', 'age' => 30],
            'nested' => [
                'level1' => [
                    'level2' => [
                        'level3' => 'deep_value'
                    ]
                ]
            ],
            'mixed' => [
                'string' => 'text',
                'number' => 42,
                'bool' => true,
                'null' => null,
                'array' => [1, 2, 3]
            ]
        ];
        
        foreach ($arrays as $name => $array) {
            $key = "array_test_{$name}";
            $this->cache->set($key, $array);
            $this->assertEquals($array, $this->cache->get($key));
        }
    }

    public function testObjectValues(): void
    {
        $obj = new \stdClass();
        $obj->property1 = 'value1';
        $obj->property2 = 42;
        $obj->property3 = ['nested', 'array'];
        
        $this->cache->set('object_test', $obj);
        $retrieved = $this->cache->get('object_test');
        
        $this->assertInstanceOf(\stdClass::class, $retrieved);
        $this->assertEquals($obj->property1, $retrieved->property1);
        $this->assertEquals($obj->property2, $retrieved->property2);
        $this->assertEquals($obj->property3, $retrieved->property3);
    }

    // =========================================================================
    // TTL (TIME TO LIVE) TESTS
    // =========================================================================

    public function testSetWithoutTTL(): void
    {
        $this->cache->set('no_ttl', 'value');
        
        // Should still be available after some time
        sleep(1);
        $this->assertEquals('value', $this->cache->get('no_ttl'));
    }

    public function testSetWithTTL(): void
    {
        $this->cache->set('with_ttl', 'value', 5); // 5 seconds TTL for more robust testing
        
        // Should be available immediately
        $this->assertEquals('value', $this->cache->get('with_ttl'));
        
        // Should be available after 1 second
        sleep(1);
        $this->assertEquals('value', $this->cache->get('with_ttl'));
    }

    public function testTTLExpiration(): void
    {
        $this->cache->set('expires_fast', 'value', 1); // 1 second TTL
        
        // Should be available immediately
        $this->assertEquals('value', $this->cache->get('expires_fast'));
        
        // Wait for expiration
        sleep(2);
        
        // Should return default value (null)
        $this->assertNull($this->cache->get('expires_fast'));
    }

    public function testTTLExpirationWithDefault(): void
    {
        $this->cache->set('expires_with_default', 'value', 1);
        
        sleep(2); // Wait for expiration
        
        $default = 'default_value';
        $this->assertEquals($default, $this->cache->get('expires_with_default', $default));
    }

    public function testZeroTTL(): void
    {
        $this->cache->set('zero_ttl', 'value', 0);
        
        // Zero TTL should mean no expiration (like null TTL)
        sleep(1);
        $this->assertEquals('value', $this->cache->get('zero_ttl'));
    }

    public function testNegativeTTL(): void
    {
        $this->cache->set('negative_ttl', 'value', -1);
        
        // Negative TTL should cause immediate expiration since time() + (-1) is in the past
        $this->assertNull($this->cache->get('negative_ttl'));
    }

    // =========================================================================
    // FILE OPERATIONS TESTS
    // =========================================================================

    public function testFileCreation(): void
    {
        $key = 'file_creation_test';
        $this->cache->set($key, 'test_value');
        
        // Check that the cache file was created
        $expectedFile = $this->tempCacheDir . '/' . md5($key) . '.cache';
        $this->assertTrue(file_exists($expectedFile));
        $this->assertGreaterThan(0, filesize($expectedFile));
    }

    public function testFileContentFormat(): void
    {
        $key = 'content_format_test';
        $value = 'test_value';
        $ttl = 300;
        
        $this->cache->set($key, $value, $ttl);
        
        $expectedFile = $this->tempCacheDir . '/' . md5($key) . '.cache';
        $fileContents = file_get_contents($expectedFile);
        $data = unserialize($fileContents);
        
        $this->assertIsArray($data);
        $this->assertArrayHasKey('value', $data);
        $this->assertArrayHasKey('expires', $data);
        $this->assertEquals($value, $data['value']);
        $this->assertGreaterThan(time(), $data['expires']);
    }

    public function testCorruptedFileHandling(): void
    {
        $key = 'corrupted_test';
        $file = $this->tempCacheDir . '/' . md5($key) . '.cache';
        
        // Create corrupted file
        file_put_contents($file, 'corrupted_data_not_serialized');
        
        // Should return default and clean up corrupted file
        $this->assertNull($this->cache->get($key));
        $this->assertFalse(file_exists($file));
    }

    public function testIncompleteDataHandling(): void
    {
        $key = 'incomplete_test';
        $file = $this->tempCacheDir . '/' . md5($key) . '.cache';
        
        // Create file with incomplete data structure
        $incompleteData = ['value' => 'test']; // Missing 'expires'
        file_put_contents($file, serialize($incompleteData));
        
        // Should return default and clean up
        $this->assertNull($this->cache->get($key));
        $this->assertFalse(file_exists($file));
    }

    public function testFilePermissions(): void
    {
        $key = 'permissions_test';
        $this->cache->set($key, 'value');
        
        $file = $this->tempCacheDir . '/' . md5($key) . '.cache';
        $this->assertTrue(is_readable($file));
        $this->assertTrue(is_writable($file));
    }

    // =========================================================================
    // DELETE OPERATION TESTS
    // =========================================================================

    public function testDeleteExistingKey(): void
    {
        $key = 'delete_test';
        $this->cache->set($key, 'value');
        
        // Verify it exists
        $this->assertEquals('value', $this->cache->get($key));
        
        // Delete it
        $result = $this->cache->delete($key);
        $this->assertTrue($result);
        
        // Verify it's gone
        $this->assertNull($this->cache->get($key));
    }

    public function testDeleteNonExistentKey(): void
    {
        $result = $this->cache->delete('non_existent_key');
        $this->assertTrue($result); // Should return true even if key doesn't exist
    }

    public function testDeleteRemovesFile(): void
    {
        $key = 'file_delete_test';
        $this->cache->set($key, 'value');
        
        $file = $this->tempCacheDir . '/' . md5($key) . '.cache';
        $this->assertTrue(file_exists($file));
        
        $this->cache->delete($key);
        $this->assertFalse(file_exists($file));
    }

    // =========================================================================
    // HAS OPERATION TESTS
    // =========================================================================

    public function testHasExistingKey(): void
    {
        $key = 'has_existing_test';
        $this->cache->set($key, 'value');
        
        $this->assertTrue($this->cache->has($key));
    }

    public function testHasNonExistentKey(): void
    {
        $this->assertFalse($this->cache->has('non_existent_key'));
    }

    public function testHasExpiredKey(): void
    {
        $key = 'has_expired_test';
        $this->cache->set($key, 'value', 1);
        
        // Should exist initially
        $this->assertTrue($this->cache->has($key));
        
        // Wait for expiration
        sleep(2);
        
        // Should not exist after expiration
        $this->assertFalse($this->cache->has($key));
    }

    public function testHasNullValue(): void
    {
        $key = 'has_null_test';
        $this->cache->set($key, null);
        
        // has() uses get() internally, and null value should still be considered as "has"
        // But since get() returns null for both non-existent and null values,
        // and has() checks if get() !== null, this will return false
        $this->assertFalse($this->cache->has($key));
    }

    // =========================================================================
    // CLEAR OPERATION TESTS
    // =========================================================================

    public function testClearEmptyCache(): void
    {
        $result = $this->cache->clear();
        $this->assertTrue($result);
    }

    public function testClearWithSingleItem(): void
    {
        $this->cache->set('clear_test1', 'value1');
        
        // Verify item exists
        $this->assertEquals('value1', $this->cache->get('clear_test1'));
        
        // Clear cache
        $result = $this->cache->clear();
        $this->assertTrue($result);
        
        // Verify item is gone
        $this->assertNull($this->cache->get('clear_test1'));
    }

    public function testClearWithMultipleItems(): void
    {
        $items = [
            'clear_test1' => 'value1',
            'clear_test2' => 42,
            'clear_test3' => ['array', 'value'],
            'clear_test4' => true,
            'clear_test5' => null,
        ];
        
        // Set all items
        foreach ($items as $key => $value) {
            $this->cache->set($key, $value);
        }
        
        // Verify all items exist (except null which won't show as "existing")
        foreach (['clear_test1', 'clear_test2', 'clear_test3', 'clear_test4'] as $key) {
            $this->assertTrue($this->cache->has($key) || $this->cache->get($key) !== null);
        }
        
        // Clear cache
        $result = $this->cache->clear();
        $this->assertTrue($result);
        
        // Verify all items are gone
        foreach (array_keys($items) as $key) {
            $this->assertNull($this->cache->get($key));
        }
    }

    public function testClearRemovesAllFiles(): void
    {
        // Create multiple cache files
        for ($i = 1; $i <= 5; $i++) {
            $this->cache->set("file_test_{$i}", "value_{$i}");
        }
        
        // Verify files exist
        $files = glob($this->tempCacheDir . '/*.cache');
        $this->assertCount(5, $files);
        
        // Clear cache
        $this->cache->clear();
        
        // Verify no cache files remain
        $files = glob($this->tempCacheDir . '/*.cache');
        $this->assertCount(0, $files);
    }

    // =========================================================================
    // KEY HANDLING TESTS
    // =========================================================================

    public function testSpecialCharactersInKeys(): void
    {
        $specialKeys = [
            'key with spaces',
            'key/with/slashes',
            'key\\with\\backslashes',
            'key:with:colons',
            'key.with.dots',
            'key-with-dashes',
            'key_with_underscores',
            'key@with@symbols',
            'key#with#hash',
            'key%with%percent',
        ];
        
        foreach ($specialKeys as $key) {
            $value = "value_for_{$key}";
            $this->cache->set($key, $value);
            $this->assertEquals($value, $this->cache->get($key));
        }
    }

    public function testUnicodeKeys(): void
    {
        $unicodeKeys = [
            'key_with_émojis_🎉',
            'клюč_на_русском',
            'キー_日本語',
            'مفتاح_عربي',
            'चाबी_हिंदी',
        ];
        
        foreach ($unicodeKeys as $key) {
            $value = "unicode_value_for_{$key}";
            $this->cache->set($key, $value);
            $this->assertEquals($value, $this->cache->get($key));
        }
    }

    public function testLongKeys(): void
    {
        $longKey = str_repeat('very_long_key_', 100); // 1400 characters
        $value = 'value_for_long_key';
        
        $this->cache->set($longKey, $value);
        $this->assertEquals($value, $this->cache->get($longKey));
    }

    public function testKeyHashing(): void
    {
        $key1 = 'test_key_1';
        $key2 = 'test_key_2';
        
        $this->cache->set($key1, 'value1');
        $this->cache->set($key2, 'value2');
        
        // Verify different keys create different files
        $file1 = $this->tempCacheDir . '/' . md5($key1) . '.cache';
        $file2 = $this->tempCacheDir . '/' . md5($key2) . '.cache';
        
        $this->assertTrue(file_exists($file1));
        $this->assertTrue(file_exists($file2));
        $this->assertNotEquals($file1, $file2);
    }

    // =========================================================================
    // LARGE DATA TESTS
    // =========================================================================

    public function testLargeStringValues(): void
    {
        $largeString = str_repeat('This is a large string for testing. ', 10000); // ~370KB
        $key = 'large_string_test';
        
        $result = $this->cache->set($key, $largeString);
        $this->assertTrue($result);
        
        $retrieved = $this->cache->get($key);
        $this->assertEquals($largeString, $retrieved);
    }

    public function testLargeArrayValues(): void
    {
        $largeArray = [];
        for ($i = 0; $i < 10000; $i++) {
            $largeArray[$i] = [
                'id' => $i,
                'name' => "Item {$i}",
                'data' => str_repeat('x', 50),
                'nested' => ['level' => 1, 'items' => range(1, 10)]
            ];
        }
        
        $key = 'large_array_test';
        
        $result = $this->cache->set($key, $largeArray);
        $this->assertTrue($result);
        
        $retrieved = $this->cache->get($key);
        $this->assertEquals($largeArray, $retrieved);
        $this->assertCount(10000, $retrieved);
    }

    // =========================================================================
    // EDGE CASE TESTS
    // =========================================================================

    public function testEmptyStringKey(): void
    {
        // Empty string is a valid key, just gets hashed like any other key
        $result = $this->cache->set('', 'value');
        $this->assertTrue($result);
        
        $retrieved = $this->cache->get('');
        $this->assertEquals('value', $retrieved);
    }

    public function testEmptyStringValue(): void
    {
        $key = 'empty_value_test';
        $this->cache->set($key, '');
        $this->assertEquals('', $this->cache->get($key));
    }

    public function testConcurrentAccess(): void
    {
        $key = 'concurrent_test';
        
        // Simulate concurrent writes (basic test)
        $this->cache->set($key, 'value1');
        $this->cache->set($key, 'value2');
        $this->cache->set($key, 'value3');
        
        // Last write should win
        $this->assertEquals('value3', $this->cache->get($key));
    }

    // =========================================================================
    // PERFORMANCE TESTS
    // =========================================================================

    public function testPerformanceWithManyOperations(): void
    {
        $startTime = microtime(true);
        
        // Perform many operations
        for ($i = 0; $i < 100; $i++) {
            $key = "perf_test_{$i}";
            $value = "value_{$i}";
            
            $this->cache->set($key, $value);
            $this->assertEquals($value, $this->cache->get($key));
            $this->assertTrue($this->cache->has($key));
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // Should complete in reasonable time (less than 1 second for 100 operations)
        $this->assertLessThan(1.0, $duration);
    }

    // =========================================================================
    // INTEGRATION TESTS
    // =========================================================================

    public function testCompleteWorkflow(): void
    {
        // Test a complete cache workflow
        $testData = [
            'user_1' => ['name' => 'John', 'email' => 'john@example.com'],
            'user_2' => ['name' => 'Jane', 'email' => 'jane@example.com'],
            'config' => ['timeout' => 30, 'retries' => 3],
            'temp_data' => 'This will expire soon',
        ];
        
        // 1. Store data with different TTLs
        $this->cache->set('user_1', $testData['user_1']); // No TTL
        $this->cache->set('user_2', $testData['user_2'], 3600); // 1 hour
        $this->cache->set('config', $testData['config'], 7200); // 2 hours
        $this->cache->set('temp_data', $testData['temp_data'], 1); // 1 second
        
        // 2. Verify all data is accessible
        $this->assertEquals($testData['user_1'], $this->cache->get('user_1'));
        $this->assertEquals($testData['user_2'], $this->cache->get('user_2'));
        $this->assertEquals($testData['config'], $this->cache->get('config'));
        $this->assertEquals($testData['temp_data'], $this->cache->get('temp_data'));
        
        // 3. Verify has() works
        $this->assertTrue($this->cache->has('user_1'));
        $this->assertTrue($this->cache->has('user_2'));
        $this->assertTrue($this->cache->has('config'));
        $this->assertTrue($this->cache->has('temp_data'));
        
        // 4. Wait for temp data to expire
        sleep(2);
        $this->assertNull($this->cache->get('temp_data'));
        $this->assertFalse($this->cache->has('temp_data'));
        
        // 5. Other data should still be available
        $this->assertEquals($testData['user_1'], $this->cache->get('user_1'));
        $this->assertEquals($testData['user_2'], $this->cache->get('user_2'));
        $this->assertEquals($testData['config'], $this->cache->get('config'));
        
        // 6. Delete specific item
        $this->cache->delete('user_1');
        $this->assertNull($this->cache->get('user_1'));
        $this->assertFalse($this->cache->has('user_1'));
        
        // 7. Other data should still be available
        $this->assertEquals($testData['user_2'], $this->cache->get('user_2'));
        $this->assertEquals($testData['config'], $this->cache->get('config'));
        
        // 8. Clear all remaining data
        $this->cache->clear();
        $this->assertNull($this->cache->get('user_2'));
        $this->assertNull($this->cache->get('config'));
        $this->assertFalse($this->cache->has('user_2'));
        $this->assertFalse($this->cache->has('config'));
        
        // 9. Verify cache directory is clean
        $files = glob($this->tempCacheDir . '/*.cache');
        $this->assertCount(0, $files);
    }
}