<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Json\Pool;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Json\Pool\JsonBufferPool;
use ReflectionClass;

/**
 * Test JsonBufferPool size estimation with named constants
 */
class JsonSizeEstimationTest extends TestCase
{
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(JsonBufferPool::class);
    }

    private function callEstimateJsonSize(mixed $data): int
    {
        $method = $this->reflection->getMethod('estimateJsonSize');
        $method->setAccessible(true);
        return $method->invokeArgs(null, [$data]);
    }

    /**
     * Test string size estimation
     */
    public function testStringEstimation(): void
    {
        $shortString = 'hello';
        $longString = str_repeat('a', 100);

        $shortEstimate = $this->callEstimateJsonSize($shortString);
        $longEstimate = $this->callEstimateJsonSize($longString);

        // Should be string length + STRING_OVERHEAD (20)
        $this->assertEquals(strlen($shortString) + JsonBufferPool::STRING_OVERHEAD, $shortEstimate);
        $this->assertEquals(strlen($longString) + JsonBufferPool::STRING_OVERHEAD, $longEstimate);

        // Longer strings should have larger estimates
        $this->assertGreaterThan($shortEstimate, $longEstimate);
    }

    /**
     * Test array size estimation thresholds
     */
    public function testArrayEstimationThresholds(): void
    {
        $emptyArray = [];
        $smallArray = array_fill(0, 5, 'item');      // < 10 items
        $mediumArray = array_fill(0, 50, 'item');    // < 100 items
        $largeArray = array_fill(0, 500, 'item');    // < 1000 items
        $xlargeArray = array_fill(0, 2000, 'item');  // >= 1000 items

        $emptyEstimate = $this->callEstimateJsonSize($emptyArray);
        $smallEstimate = $this->callEstimateJsonSize($smallArray);
        $mediumEstimate = $this->callEstimateJsonSize($mediumArray);
        $largeEstimate = $this->callEstimateJsonSize($largeArray);
        $xlargeEstimate = $this->callEstimateJsonSize($xlargeArray);

        // Empty array should be smallest (2 bytes for [])
        $this->assertEquals(JsonBufferPool::EMPTY_ARRAY_SIZE, $emptyEstimate);

        // Each category should be larger than the previous
        $this->assertGreaterThan($emptyEstimate, $smallEstimate);
        $this->assertGreaterThan($smallEstimate, $mediumEstimate);
        $this->assertGreaterThan($mediumEstimate, $largeEstimate);
        $this->assertGreaterThan($largeEstimate, $xlargeEstimate);

        // Verify expected sizes based on constants
        $this->assertEquals(JsonBufferPool::SMALL_ARRAY_SIZE, $smallEstimate);
        $this->assertEquals(JsonBufferPool::MEDIUM_ARRAY_SIZE, $mediumEstimate);
        $this->assertEquals(JsonBufferPool::LARGE_ARRAY_SIZE, $largeEstimate);
        $this->assertEquals(JsonBufferPool::XLARGE_ARRAY_SIZE, $xlargeEstimate);
    }

    /**
     * Test object size estimation
     */
    public function testObjectEstimation(): void
    {
        $emptyObject = new \stdClass();
        $smallObject = (object)['name' => 'test', 'value' => 42];
        $largeObject = (object)array_fill_keys(range('a', 'j'), 'value'); // 10 properties

        $emptyEstimate = $this->callEstimateJsonSize($emptyObject);
        $smallEstimate = $this->callEstimateJsonSize($smallObject);
        $largeEstimate = $this->callEstimateJsonSize($largeObject);

        // Empty object should be base size (100)
        $this->assertEquals(JsonBufferPool::OBJECT_BASE_SIZE, $emptyEstimate);

        // Objects with properties should be larger
        $this->assertGreaterThan($emptyEstimate, $smallEstimate);
        $this->assertGreaterThan($smallEstimate, $largeEstimate);

        // Should follow formula: property_count * OBJECT_PROPERTY_OVERHEAD + OBJECT_BASE_SIZE
        $this->assertEquals(
            2 * JsonBufferPool::OBJECT_PROPERTY_OVERHEAD + JsonBufferPool::OBJECT_BASE_SIZE,
            $smallEstimate
        );  // 2 properties
        $this->assertEquals(
            10 * JsonBufferPool::OBJECT_PROPERTY_OVERHEAD + JsonBufferPool::OBJECT_BASE_SIZE,
            $largeEstimate
        ); // 10 properties
    }

    /**
     * Test primitive type estimations
     */
    public function testPrimitiveEstimations(): void
    {
        $boolean = true;
        $null = null;
        $integer = 42;
        $float = 3.14;

        $booleanEstimate = $this->callEstimateJsonSize($boolean);
        $nullEstimate = $this->callEstimateJsonSize($null);
        $integerEstimate = $this->callEstimateJsonSize($integer);
        $floatEstimate = $this->callEstimateJsonSize($float);

        // Boolean and null should be same size (10)
        $this->assertEquals(JsonBufferPool::BOOLEAN_OR_NULL_SIZE, $booleanEstimate);
        $this->assertEquals(JsonBufferPool::BOOLEAN_OR_NULL_SIZE, $nullEstimate);

        // Numeric values should be same size (20)
        $this->assertEquals(JsonBufferPool::NUMERIC_SIZE, $integerEstimate);
        $this->assertEquals(JsonBufferPool::NUMERIC_SIZE, $floatEstimate);
    }

    /**
     * Test default estimation fallback
     */
    public function testDefaultEstimation(): void
    {
        // Create a resource (which doesn't match any specific type)
        $resource = fopen('php://memory', 'r+');
        $estimate = $this->callEstimateJsonSize($resource);
        fclose($resource);

        // Should return default estimate (100)
        $this->assertEquals(JsonBufferPool::DEFAULT_ESTIMATE, $estimate);
    }

    /**
     * Test optimal capacity calculation
     */
    public function testOptimalCapacityCalculation(): void
    {
        $smallData = ['test' => 'value'];
        $largeData = array_fill(0, 2000, ['field' => 'value']);

        $smallCapacity = JsonBufferPool::getOptimalCapacity($smallData);
        $largeCapacity = JsonBufferPool::getOptimalCapacity($largeData);

        // Small data should fit in standard categories (1024, 4096, 16384, 65536)
        $this->assertContains($smallCapacity, [1024, 4096, 16384, 65536]);

        // Large data should get calculated capacity
        $this->assertGreaterThanOrEqual(JsonBufferPool::MIN_LARGE_BUFFER_SIZE, $largeCapacity);
        $this->assertGreaterThan($smallCapacity, $largeCapacity);
    }

    /**
     * Test that constants are properly defined and reasonable
     */
    public function testConstantsAreReasonable(): void
    {
        // Test that size constants are in ascending order
        $this->assertLessThan(JsonBufferPool::SMALL_ARRAY_SIZE, JsonBufferPool::EMPTY_ARRAY_SIZE); // EMPTY < SMALL
        $this->assertLessThan(JsonBufferPool::MEDIUM_ARRAY_SIZE, JsonBufferPool::SMALL_ARRAY_SIZE); // SMALL < MEDIUM
        $this->assertLessThan(JsonBufferPool::LARGE_ARRAY_SIZE, JsonBufferPool::MEDIUM_ARRAY_SIZE); // MEDIUM < LARGE
        $this->assertLessThan(JsonBufferPool::XLARGE_ARRAY_SIZE, JsonBufferPool::LARGE_ARRAY_SIZE); // LARGE < XLARGE

        // Test threshold constants are in ascending order
        $this->assertLessThan(
            JsonBufferPool::MEDIUM_ARRAY_THRESHOLD,
            JsonBufferPool::SMALL_ARRAY_THRESHOLD
        ); // SMALL < MEDIUM threshold
        $this->assertLessThan(
            JsonBufferPool::LARGE_ARRAY_THRESHOLD,
            JsonBufferPool::MEDIUM_ARRAY_THRESHOLD
        ); // MEDIUM < LARGE threshold

        // Test overhead constants are reasonable
        $this->assertGreaterThan(0, JsonBufferPool::STRING_OVERHEAD);        // STRING_OVERHEAD > 0
        $this->assertGreaterThan(0, JsonBufferPool::OBJECT_PROPERTY_OVERHEAD);        // OBJECT_PROPERTY_OVERHEAD > 0
        $this->assertGreaterThan(0, JsonBufferPool::OBJECT_BASE_SIZE);       // OBJECT_BASE_SIZE > 0
    }

    /**
     * Test realistic data size estimations
     */
    public function testRealisticDataEstimations(): void
    {
        // Typical API response data
        $apiResponse = [
            'status' => 'success',
            'data' => [
                'users' => array_fill(
                    0,
                    50,
                    [
                        'id' => rand(1, 1000),
                        'name' => 'User Name',
                        'email' => 'user@example.com'
                    ]
                )
            ],
            'meta' => [
                'total' => 50,
                'page' => 1,
                'per_page' => 50
            ]
        ];

        $estimate = $this->callEstimateJsonSize($apiResponse);
        $capacity = JsonBufferPool::getOptimalCapacity($apiResponse);

        // Should estimate reasonable size for this data structure (it's an array)
        $this->assertGreaterThan(100, $estimate);
        $this->assertGreaterThan($estimate, $capacity);

        // Actual JSON should be reasonably close to estimate
        $actualJson = json_encode($apiResponse);
        $actualSize = strlen($actualJson);

        // Estimate should be within reasonable range of actual size
        $this->assertGreaterThan($actualSize * 0.1, $estimate); // At least 10% of actual
        $this->assertLessThan($actualSize * 10, $estimate);     // At most 10x actual
    }
}
