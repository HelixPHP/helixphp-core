<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Json\Pool;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Json\Pool\JsonBufferPool;
use ReflectionClass;

/**
 * Test JsonBufferPool configuration merge behavior
 */
class JsonBufferPoolConfigMergeTest extends TestCase
{
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        // Clear pools and reset configuration before each test
        JsonBufferPool::clearPools();
        JsonBufferPool::resetConfiguration();

        $this->reflection = new ReflectionClass(JsonBufferPool::class);
    }

    protected function tearDown(): void
    {
        // Clear pools and reset configuration after each test
        JsonBufferPool::clearPools();
        JsonBufferPool::resetConfiguration();
    }

    private function getConfig(): array
    {
        $configProperty = $this->reflection->getProperty('config');
        $configProperty->setAccessible(true);
        return $configProperty->getValue();
    }

    /**
     * Test basic configuration update
     */
    public function testBasicConfigurationUpdate(): void
    {
        // Update only max_pool_size
        JsonBufferPool::configure(['max_pool_size' => 100]);

        $config = $this->getConfig();

        $this->assertEquals(100, $config['max_pool_size']);
        $this->assertEquals(4096, $config['default_capacity']); // Should remain unchanged
        $this->assertIsArray($config['size_categories']); // Should remain unchanged
    }

    /**
     * Test partial size_categories update preserves existing categories
     */
    public function testPartialSizeCategoriesUpdate(): void
    {
        // First, configure with some custom categories
        JsonBufferPool::configure(
            [
                'size_categories' => [
                    'tiny' => 256,
                    'small' => 1024,
                    'medium' => 4096,
                    'large' => 16384,
                    'custom' => 8192
                ]
            ]
        );

        $config = $this->getConfig();
        $this->assertEquals(256, $config['size_categories']['tiny']);
        $this->assertEquals(8192, $config['size_categories']['custom']);

        // Now update only some categories
        JsonBufferPool::configure(
            [
                'size_categories' => [
                    'medium' => 8192,    // Change existing
                    'xlarge' => 65536    // Add new
                ]
            ]
        );

        $config = $this->getConfig();

        // Should preserve existing categories
        $this->assertEquals(256, $config['size_categories']['tiny']);
        $this->assertEquals(1024, $config['size_categories']['small']);
        $this->assertEquals(16384, $config['size_categories']['large']);
        $this->assertEquals(8192, $config['size_categories']['custom']);

        // Should update changed category
        $this->assertEquals(8192, $config['size_categories']['medium']);

        // Should add new category
        $this->assertEquals(65536, $config['size_categories']['xlarge']);
    }

    /**
     * Test complete size_categories replacement
     */
    public function testCompleteSizeCategoriesReplacement(): void
    {
        // Start with default categories
        $originalConfig = $this->getConfig();
        $this->assertCount(4, $originalConfig['size_categories']);

        // Replace with completely new set
        JsonBufferPool::configure(
            [
                'size_categories' => [
                    'micro' => 128,
                    'mini' => 512
                ]
            ]
        );

        $config = $this->getConfig();

        // Should have both old and new categories merged
        $this->assertArrayHasKey('small', $config['size_categories']); // Original
        $this->assertArrayHasKey('medium', $config['size_categories']); // Original
        $this->assertEquals(128, $config['size_categories']['micro']); // New
        $this->assertEquals(512, $config['size_categories']['mini']); // New

        // Total should be original + new categories
        $this->assertGreaterThanOrEqual(6, count($config['size_categories']));
    }

    /**
     * Test mixed configuration update
     */
    public function testMixedConfigurationUpdate(): void
    {
        // Configure initial state
        JsonBufferPool::configure(
            [
                'max_pool_size' => 50,
                'size_categories' => [
                    'custom1' => 2048,
                    'custom2' => 8192
                ]
            ]
        );

        // Update mix of scalar and array values
        JsonBufferPool::configure(
            [
                'max_pool_size' => 150,         // Scalar update
                'default_capacity' => 16384,    // New scalar
                'size_categories' => [
                    'custom2' => 12288,         // Update existing category
                    'custom3' => 32768          // Add new category
                ]
            ]
        );

        $config = $this->getConfig();

        // Check scalar values
        $this->assertEquals(150, $config['max_pool_size']);
        $this->assertEquals(16384, $config['default_capacity']);

        // Check size_categories merge
        $this->assertEquals(2048, $config['size_categories']['custom1']);   // Preserved
        $this->assertEquals(12288, $config['size_categories']['custom2']);  // Updated
        $this->assertEquals(32768, $config['size_categories']['custom3']);  // Added

        // Original categories should still exist
        $this->assertArrayHasKey('small', $config['size_categories']);
        $this->assertArrayHasKey('medium', $config['size_categories']);
    }

    /**
     * Test that validation still works with partial updates
     */
    public function testValidationWithPartialUpdates(): void
    {
        // This should work - valid partial update
        JsonBufferPool::configure(
            [
                'size_categories' => [
                    'valid_category' => 2048
                ]
            ]
        );

        $this->assertTrue(true); // No exception thrown

        // This should fail - invalid value in partial update
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Size category 'invalid_category' must have a positive integer capacity");

        JsonBufferPool::configure(
            [
                'size_categories' => [
                    'invalid_category' => -1000
                ]
            ]
        );
    }

    /**
     * Test automatic ordering with partial updates
     */
    public function testAutomaticOrderingWithPartialUpdates(): void
    {
        // Set initial categories
        JsonBufferPool::configure(
            [
                'size_categories' => [
                    'tiny' => 512,
                    'small' => 1024,
                    'large' => 4096
                ]
            ]
        );

        // Add categories in random order - should be automatically sorted
        JsonBufferPool::configure(
            [
                'size_categories' => [
                    'xlarge' => 8192,   // Larger than existing
                    'medium' => 2048,   // Between existing
                    'micro' => 256      // Smaller than existing
                ]
            ]
        );

        $config = $this->getConfig();
        $categories = $config['size_categories'];

        // Verify automatic sorting - values should be in ascending order
        $values = array_values($categories);
        $sortedValues = $values;
        sort($sortedValues);

        $this->assertEquals($sortedValues, $values, 'Categories should be automatically sorted by size');

        // Verify specific ordering
        $expectedOrder = [256, 512, 1024, 2048, 4096, 8192];
        $this->assertEquals($expectedOrder, array_values($categories));
    }

    /**
     * Test empty size_categories update
     */
    public function testEmptySizeCategoriesUpdate(): void
    {
        // Clear existing config first to test empty array validation
        $configProperty = $this->reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $configProperty->setValue(
            [
                'max_pool_size' => 50,
                'default_capacity' => 4096,
                'size_categories' => []  // Start with empty
            ]
        );

        // Should fail validation when trying to configure with empty array
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("'size_categories' cannot be empty");

        JsonBufferPool::configure(
            [
                'size_categories' => []
            ]
        );
    }

    /**
     * Test null safety in size_categories merge
     */
    public function testNullSafetyInMerge(): void
    {
        // Clear config to test null safety
        $configProperty = $this->reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue();
        unset($config['size_categories']);
        $configProperty->setValue($config);

        // This should not crash even if size_categories is missing
        JsonBufferPool::configure(
            [
                'size_categories' => [
                    'new_category' => 1024
                ]
            ]
        );

        $updatedConfig = $this->getConfig();
        $this->assertEquals(1024, $updatedConfig['size_categories']['new_category']);
    }
}
