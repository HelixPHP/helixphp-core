<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Json;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

/**
 * Test that pooling thresholds are centralized and consistent
 */
class JsonPoolingThresholdsTest extends TestCase
{
    protected function setUp(): void
    {
        JsonBufferPool::clearPools();
        JsonBufferPool::resetConfiguration();
    }

    protected function tearDown(): void
    {
        JsonBufferPool::clearPools();
        JsonBufferPool::resetConfiguration();
    }

    /**
     * Test that pooling constants are properly defined
     */
    public function testPoolingConstantsExist(): void
    {
        $this->assertTrue(defined('PivotPHP\Core\Json\Pool\JsonBufferPool::POOLING_ARRAY_THRESHOLD'));
        $this->assertTrue(defined('PivotPHP\Core\Json\Pool\JsonBufferPool::POOLING_OBJECT_THRESHOLD'));
        $this->assertTrue(defined('PivotPHP\Core\Json\Pool\JsonBufferPool::POOLING_STRING_THRESHOLD'));

        // Verify values are reasonable
        $this->assertEquals(10, JsonBufferPool::POOLING_ARRAY_THRESHOLD);
        $this->assertEquals(5, JsonBufferPool::POOLING_OBJECT_THRESHOLD);
        $this->assertEquals(1024, JsonBufferPool::POOLING_STRING_THRESHOLD);
    }

    /**
     * Test that Response uses centralized thresholds
     */
    public function testResponseUsesPoolingThresholds(): void
    {
        $response = new Response();
        $response->setTestMode(true);

        // Test array threshold - just below threshold should not pool
        $smallArray = array_fill(0, JsonBufferPool::POOLING_ARRAY_THRESHOLD - 1, 'item');
        $response->json($smallArray);

        $stats = JsonBufferPool::getStatistics();
        $this->assertEquals(0, $stats['total_operations'], 'Small arrays should not use pooling');

        // Reset
        JsonBufferPool::clearPools();
        $response = new Response();
        $response->setTestMode(true);

        // Test array threshold - at threshold should pool
        $mediumArray = array_fill(0, JsonBufferPool::POOLING_ARRAY_THRESHOLD, 'item');
        $response->json($mediumArray);

        $stats = JsonBufferPool::getStatistics();
        $this->assertEquals(1, $stats['total_operations'], 'Arrays at threshold should use pooling');
    }

    /**
     * Test object pooling threshold consistency
     */
    public function testObjectPoolingThreshold(): void
    {
        $response = new Response();
        $response->setTestMode(true);

        // Create object just below threshold
        $smallObject = new \stdClass();
        for ($i = 0; $i < JsonBufferPool::POOLING_OBJECT_THRESHOLD - 1; $i++) {
            $smallObject->{"prop{$i}"} = "value{$i}";
        }

        $response->json($smallObject);
        $stats = JsonBufferPool::getStatistics();
        $this->assertEquals(0, $stats['total_operations'], 'Small objects should not use pooling');

        // Reset
        JsonBufferPool::clearPools();
        $response = new Response();
        $response->setTestMode(true);

        // Create object at threshold
        $mediumObject = new \stdClass();
        for ($i = 0; $i < JsonBufferPool::POOLING_OBJECT_THRESHOLD; $i++) {
            $mediumObject->{"prop{$i}"} = "value{$i}";
        }

        $response->json($mediumObject);
        $stats = JsonBufferPool::getStatistics();
        $this->assertEquals(1, $stats['total_operations'], 'Objects at threshold should use pooling');
    }

    /**
     * Test string pooling threshold consistency
     */
    public function testStringPoolingThreshold(): void
    {
        $response = new Response();
        $response->setTestMode(true);

        // String just under threshold
        $shortString = str_repeat('x', JsonBufferPool::POOLING_STRING_THRESHOLD);
        $response->json($shortString);

        $stats = JsonBufferPool::getStatistics();
        $this->assertEquals(0, $stats['total_operations'], 'Short strings should not use pooling');

        // Reset
        JsonBufferPool::clearPools();
        $response = new Response();
        $response->setTestMode(true);

        // String over threshold
        $longString = str_repeat('x', JsonBufferPool::POOLING_STRING_THRESHOLD + 1);
        $response->json($longString);

        $stats = JsonBufferPool::getStatistics();
        $this->assertEquals(1, $stats['total_operations'], 'Long strings should use pooling');
    }

    /**
     * Test that thresholds are reasonable for performance
     */
    public function testThresholdsAreReasonable(): void
    {
        // Array threshold should be high enough to avoid pooling small arrays
        $this->assertGreaterThanOrEqual(5, JsonBufferPool::POOLING_ARRAY_THRESHOLD);
        $this->assertLessThanOrEqual(50, JsonBufferPool::POOLING_ARRAY_THRESHOLD);

        // Object threshold should be reasonable for common objects
        $this->assertGreaterThanOrEqual(3, JsonBufferPool::POOLING_OBJECT_THRESHOLD);
        $this->assertLessThanOrEqual(20, JsonBufferPool::POOLING_OBJECT_THRESHOLD);

        // String threshold should be reasonable (around 1KB)
        $this->assertGreaterThanOrEqual(512, JsonBufferPool::POOLING_STRING_THRESHOLD);
        $this->assertLessThanOrEqual(4096, JsonBufferPool::POOLING_STRING_THRESHOLD);
    }

    /**
     * Test consistency between direct pooling and Response pooling
     */
    public function testConsistencyBetweenDirectAndResponsePooling(): void
    {
        $testData = array_fill(0, JsonBufferPool::POOLING_ARRAY_THRESHOLD, 'test');

        // Direct pooling
        JsonBufferPool::clearPools();
        $directResult = JsonBufferPool::encodeWithPool($testData);
        $directStats = JsonBufferPool::getStatistics();

        // Response pooling
        JsonBufferPool::clearPools();
        $response = new Response();
        $response->setTestMode(true);
        $response->json($testData);
        $responseResult = $response->getBodyAsString();
        $responseStats = JsonBufferPool::getStatistics();

        // Results should be identical
        $this->assertEquals($directResult, $responseResult);

        // Both should have used pooling
        $this->assertEquals(1, $directStats['total_operations']);
        $this->assertEquals(1, $responseStats['total_operations']);
    }

    /**
     * Test that updating centralized constants affects both components
     */
    public function testCentralizedConstantsAffectBothComponents(): void
    {
        // This test verifies that the constants are truly centralized
        // by checking that Response uses the same values as JsonBufferPool

        $reflection = new \ReflectionClass('PivotPHP\Core\Http\Response');
        $shouldUsePoolingMethod = $reflection->getMethod('shouldUseJsonPooling');
        $shouldUsePoolingMethod->setAccessible(true);

        $response = new Response();

        // Test array threshold boundary
        $arrayAtThreshold = array_fill(0, JsonBufferPool::POOLING_ARRAY_THRESHOLD, 'item');
        $arrayBelowThreshold = array_fill(0, JsonBufferPool::POOLING_ARRAY_THRESHOLD - 1, 'item');

        $this->assertTrue($shouldUsePoolingMethod->invoke($response, $arrayAtThreshold));
        $this->assertFalse($shouldUsePoolingMethod->invoke($response, $arrayBelowThreshold));

        // Test object threshold boundary
        $objectAtThreshold = new \stdClass();
        for ($i = 0; $i < JsonBufferPool::POOLING_OBJECT_THRESHOLD; $i++) {
            $objectAtThreshold->{"prop{$i}"} = "value{$i}";
        }

        $objectBelowThreshold = new \stdClass();
        for ($i = 0; $i < JsonBufferPool::POOLING_OBJECT_THRESHOLD - 1; $i++) {
            $objectBelowThreshold->{"prop{$i}"} = "value{$i}";
        }

        $this->assertTrue($shouldUsePoolingMethod->invoke($response, $objectAtThreshold));
        $this->assertFalse($shouldUsePoolingMethod->invoke($response, $objectBelowThreshold));

        // Test string threshold boundary
        $stringAtThreshold = str_repeat('x', JsonBufferPool::POOLING_STRING_THRESHOLD + 1);
        $stringBelowThreshold = str_repeat('x', JsonBufferPool::POOLING_STRING_THRESHOLD);

        $this->assertTrue($shouldUsePoolingMethod->invoke($response, $stringAtThreshold));
        $this->assertFalse($shouldUsePoolingMethod->invoke($response, $stringBelowThreshold));
    }
}
