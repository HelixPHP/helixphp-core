<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Json\Pool;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Json\Pool\JsonBufferPool;
use InvalidArgumentException;

/**
 * Test JsonBufferPool configuration validation
 */
class JsonBufferPoolConfigValidationTest extends TestCase
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
     * Test valid configuration passes
     */
    public function testValidConfigurationPasses(): void
    {
        $validConfig = [
            'max_pool_size' => 100,
            'default_capacity' => 8192,
            'size_categories' => [
                'tiny' => 512,
                'small' => 1024,
                'medium' => 4096,
                'large' => 16384
            ]
        ];

        // Should not throw exception
        JsonBufferPool::configure($validConfig);
        $this->assertTrue(true); // Assertion to confirm test ran
    }

    /**
     * Test max_pool_size validation
     */
    public function testMaxPoolSizeValidation(): void
    {
        // Test negative value
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'max_pool_size' must be a positive integer");
        JsonBufferPool::configure(['max_pool_size' => -1]);
    }

    public function testMaxPoolSizeZeroInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'max_pool_size' must be a positive integer");
        JsonBufferPool::configure(['max_pool_size' => 0]);
    }

    public function testMaxPoolSizeTypeValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'max_pool_size' must be an integer");
        JsonBufferPool::configure(['max_pool_size' => '100']);
    }

    public function testMaxPoolSizeUpperLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'max_pool_size' cannot exceed 1000 for memory safety, got: 1001");
        JsonBufferPool::configure(['max_pool_size' => 1001]);
    }

    /**
     * Test default_capacity validation
     */
    public function testDefaultCapacityValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'default_capacity' must be a positive integer");
        JsonBufferPool::configure(['default_capacity' => -1]);
    }

    public function testDefaultCapacityTypeValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'default_capacity' must be an integer");
        JsonBufferPool::configure(['default_capacity' => 4096.5]);
    }

    public function testDefaultCapacityUpperLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'default_capacity' cannot exceed 1MB (1048576 bytes), got: 1048577");
        JsonBufferPool::configure(['default_capacity' => 1048577]);
    }

    /**
     * Test size_categories validation
     */
    public function testSizeCategoriesTypeValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'size_categories' must be an array");
        JsonBufferPool::configure(['size_categories' => 'invalid']);
    }

    public function testSizeCategoriesEmptyArrayInvalid(): void
    {
        // Reset to empty config first
        JsonBufferPool::resetConfiguration();
        $reflection = new \ReflectionClass(JsonBufferPool::class);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $configProperty->setValue(null, [
            'max_pool_size' => 50,
            'default_capacity' => 4096,
            'size_categories' => []
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'size_categories' cannot be empty");
        JsonBufferPool::configure(['size_categories' => []]);
    }

    public function testSizeCategoriesNameValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Size category names must be non-empty strings");
        JsonBufferPool::configure(
            [
                'size_categories' => [
                    123 => 1024  // Invalid numeric key
                ]
            ]
        );
    }

    public function testSizeCategoriesEmptyNameInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Size category names must be non-empty strings");
        JsonBufferPool::configure(
            [
                'size_categories' => [
                    '' => 1024  // Empty string key
                ]
            ]
        );
    }

    public function testSizeCategoriesCapacityValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Size category 'small' must have an integer capacity");
        JsonBufferPool::configure(
            [
                'size_categories' => [
                    'small' => 'invalid'
                ]
            ]
        );
    }

    public function testSizeCategoriesCapacityZeroInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Size category 'small' must have a positive integer capacity");
        JsonBufferPool::configure(
            [
                'size_categories' => [
                    'small' => 0
                ]
            ]
        );
    }

    public function testSizeCategoriesCapacityUpperLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Size category 'huge' capacity cannot exceed 1MB (1048576 bytes), got: 1048577");
        JsonBufferPool::configure(
            [
                'size_categories' => [
                    'huge' => 1048577
                ]
            ]
        );
    }

    public function testSizeCategoriesAutoSorting(): void
    {
        // Categories should be automatically sorted, so this should NOT throw exception
        JsonBufferPool::configure(
            [
                'size_categories' => [
                    'large' => 16384,
                    'small' => 1024,  // Out of order - will be auto-sorted
                    'medium' => 4096
                ]
            ]
        );

        // Get the configuration to verify it was sorted
        $reflection = new \ReflectionClass(JsonBufferPool::class);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue();

        // Verify that categories are now in ascending order by value
        $values = array_values($config['size_categories']);
        $sortedValues = $values;
        sort($sortedValues);

        $this->assertEquals($sortedValues, $values, 'Categories should be automatically sorted by size');

        // Verify that our specific values are in the right order (there may be other default values)
        $categoryValues = $config['size_categories'];
        $this->assertContains(1024, $categoryValues);
        $this->assertContains(4096, $categoryValues);
        $this->assertContains(16384, $categoryValues);

        // Find positions of our values
        $values = array_values($categoryValues);
        $pos1024 = array_search(1024, $values);
        $pos4096 = array_search(4096, $values);
        $pos16384 = array_search(16384, $values);

        // Verify they are in correct relative order
        $this->assertLessThan($pos4096, $pos1024, '1024 should come before 4096');
        $this->assertLessThan($pos16384, $pos4096, '4096 should come before 16384');
    }

    /**
     * Test unknown configuration keys
     */
    public function testUnknownConfigurationKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown configuration keys: unknown_key, another_unknown");
        JsonBufferPool::configure(
            [
                'max_pool_size' => 50,
                'unknown_key' => 'value',
                'another_unknown' => 123
            ]
        );
    }

    /**
     * Test that valid boundary values work
     */
    public function testValidBoundaryValues(): void
    {
        // Test maximum allowed values
        $maxConfig = [
            'max_pool_size' => 1000,
            'default_capacity' => 1048576,  // 1MB
            'size_categories' => [
                'max' => 1048576  // 1MB
            ]
        ];

        JsonBufferPool::configure($maxConfig);
        $this->assertTrue(true);

        // Test minimum allowed values
        $minConfig = [
            'max_pool_size' => 1,
            'default_capacity' => 1,
            'size_categories' => [
                'min' => 1
            ]
        ];

        JsonBufferPool::configure($minConfig);
        $this->assertTrue(true);
    }

    /**
     * Test partial configuration updates
     */
    public function testPartialConfigurationUpdates(): void
    {
        // Configure only max_pool_size
        JsonBufferPool::configure(['max_pool_size' => 75]);
        $this->assertTrue(true);

        // Configure only default_capacity
        JsonBufferPool::configure(['default_capacity' => 2048]);
        $this->assertTrue(true);

        // Configure only size_categories
        JsonBufferPool::configure(
            [
                'size_categories' => [
                    'custom_small' => 512,
                    'custom_large' => 8192
                ]
            ]
        );
        $this->assertTrue(true);
    }

    /**
     * Test that configuration merges correctly with existing config
     */
    public function testConfigurationMerging(): void
    {
        // Set initial config
        JsonBufferPool::configure(
            [
                'max_pool_size' => 100,
                'default_capacity' => 4096
            ]
        );

        // Update only one value
        JsonBufferPool::configure(['max_pool_size' => 200]);

        // Test that we can still get a buffer (indicating config is valid)
        $buffer = JsonBufferPool::getBuffer();
        $this->assertInstanceOf(\PivotPHP\Core\Json\Pool\JsonBuffer::class, $buffer);
    }
}
