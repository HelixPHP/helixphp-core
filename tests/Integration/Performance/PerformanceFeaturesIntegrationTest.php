<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Performance;

use PivotPHP\Core\Tests\Integration\IntegrationTestCase;
use PivotPHP\Core\Performance\HighPerformanceMode;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

/**
 * Performance Features Integration Tests
 *
 * Tests the integration between performance optimization components:
 * - High Performance Mode + JSON Pooling
 * - Memory Management + Object Pooling
 * - Performance Monitoring + Statistics
 * - Resource Management + Cleanup
 *
 * @group integration
 * @group performance
 */
class PerformanceFeaturesIntegrationTest extends IntegrationTestCase
{
    /**
     * Test High Performance Mode and JSON Pooling working together
     */
    public function testHighPerformanceModeWithJsonPooling(): void
    {
        // Get baseline statistics
        $initialJsonStats = JsonBufferPool::getStatistics();

        // Enable High Performance Mode
        $this->enableHighPerformanceMode('HIGH');

        // Verify HP mode is enabled
        $hpStatus = HighPerformanceMode::getStatus();
        $this->assertTrue($hpStatus['enabled']);

        // Create data that should trigger JSON pooling
        $largeData = $this->createLargeJsonPayload(50);

        // Perform JSON operations that should use pooling
        $jsonResults = [];
        for ($i = 0; $i < 5; $i++) {
            $jsonResults[] = JsonBufferPool::encodeWithPool($largeData);
        }

        // Verify all operations succeeded
        $this->assertCount(5, $jsonResults);
        foreach ($jsonResults as $json) {
            $this->assertIsString($json);
            $this->assertNotEmpty($json);

            // Verify JSON is valid
            $decoded = json_decode($json, true);
            $this->assertIsArray($decoded);
            $this->assertCount(50, $decoded);
        }

        // Verify JSON pooling statistics updated
        $finalJsonStats = JsonBufferPool::getStatistics();
        $this->assertGreaterThanOrEqual($initialJsonStats['total_operations'], $finalJsonStats['total_operations']);

        // Verify HP mode is still active
        $hpStatus = HighPerformanceMode::getStatus();
        $this->assertTrue($hpStatus['enabled']);

        // Verify performance metrics are being collected
        $monitor = HighPerformanceMode::getMonitor();
        $this->assertNotNull($monitor);
    }

    /**
     * Test performance monitoring with actual workload
     */
    public function testPerformanceMonitoringIntegration(): void
    {
        // Enable High Performance Mode to get monitoring
        $this->enableHighPerformanceMode('HIGH');

        $monitor = HighPerformanceMode::getMonitor();
        $this->assertNotNull($monitor);

        // Simulate a series of operations with monitoring
        $operationCount = 10;
        for ($i = 0; $i < $operationCount; $i++) {
            $requestId = "integration-test-{$i}";

            // Start monitoring request
            $monitor->startRequest(
                $requestId,
                [
                    'operation' => 'integration_test',
                    'iteration' => $i
                ]
            );

            // Simulate work with JSON operations
            $data = $this->createLargeJsonPayload(20);
            $json = JsonBufferPool::encodeWithPool($data);

            // Add some processing time
            usleep(random_int(1000, 5000)); // 1-5ms

            // End monitoring
            $monitor->endRequest($requestId, 200);
        }

        // Verify monitoring data was collected
        $liveMetrics = $monitor->getLiveMetrics();
        $this->assertIsArray($liveMetrics);
        $this->assertArrayHasKey('memory_pressure', $liveMetrics);
        $this->assertArrayHasKey('current_load', $liveMetrics);
        $this->assertArrayHasKey('active_requests', $liveMetrics);

        // Verify no requests are active after completion
        $this->assertEquals(0, $liveMetrics['active_requests']);

        // Verify performance metrics are reasonable
        $perfMetrics = $monitor->getPerformanceMetrics();
        $this->assertIsArray($perfMetrics);
        $this->assertArrayHasKey('latency', $perfMetrics);
        $this->assertArrayHasKey('throughput', $perfMetrics);
    }

    /**
     * Test profile switching under load
     */
    public function testProfileSwitchingUnderLoad(): void
    {
        // Start with HIGH profile
        $this->enableHighPerformanceMode('HIGH');

        $monitor = HighPerformanceMode::getMonitor();
        $this->assertNotNull($monitor);

        // Generate some load
        $this->generateTestLoad(5, 'HIGH');

        // Switch to EXTREME profile
        $this->enableHighPerformanceMode('EXTREME');

        // Verify switch was successful
        $hpStatus = HighPerformanceMode::getStatus();
        $this->assertTrue($hpStatus['enabled']);

        // Monitor should still be available
        $newMonitor = HighPerformanceMode::getMonitor();
        $this->assertNotNull($newMonitor);

        // Generate load under new profile
        $this->generateTestLoad(5, 'EXTREME');

        // Verify system is still functional
        $finalStatus = HighPerformanceMode::getStatus();
        $this->assertTrue($finalStatus['enabled']);

        // Verify metrics are still being collected
        $metrics = $newMonitor->getLiveMetrics();
        $this->assertIsArray($metrics);
    }

    /**
     * Test memory management integration
     */
    public function testMemoryManagementIntegration(): void
    {
        // Record initial memory state
        $initialMemory = memory_get_usage(true);

        // Enable High Performance Mode
        $this->enableHighPerformanceMode('HIGH');

        // Generate memory pressure with JSON operations
        $largeDataSets = [];
        for ($i = 0; $i < 10; $i++) {
            $data = $this->createLargeJsonPayload(100);
            $largeDataSets[] = $data;

            // Use JSON pooling
            $json = JsonBufferPool::encodeWithPool($data);
            $this->assertIsString($json);
        }

        // Get monitor and check memory metrics
        $monitor = HighPerformanceMode::getMonitor();
        $liveMetrics = $monitor->getLiveMetrics();

        $this->assertArrayHasKey('memory_pressure', $liveMetrics);
        $this->assertIsFloat($liveMetrics['memory_pressure']);
        $this->assertGreaterThanOrEqual(0.0, $liveMetrics['memory_pressure']);

        // Clean up large data sets
        unset($largeDataSets);
        gc_collect_cycles();

        // Verify memory didn't grow excessively
        $finalMemory = memory_get_usage(true);
        $memoryGrowth = ($finalMemory - $initialMemory) / 1024 / 1024; // MB

        // Allow some memory growth but not excessive
        $this->assertLessThan(
            20,
            $memoryGrowth,
            "Memory growth ({$memoryGrowth}MB) should be reasonable"
        );
    }

    /**
     * Test concurrent operations with performance features
     */
    public function testConcurrentOperationsIntegration(): void
    {
        // Enable High Performance Mode
        $this->enableHighPerformanceMode('EXTREME');

        $monitor = HighPerformanceMode::getMonitor();

        // Start multiple concurrent operations
        $requestIds = [];
        for ($i = 0; $i < 20; $i++) {
            $requestId = "concurrent-{$i}";
            $requestIds[] = $requestId;

            $monitor->startRequest(
                $requestId,
                [
                    'type' => 'concurrent',
                    'batch_id' => 'integration_test'
                ]
            );
        }

        // Verify all requests are being tracked
        $liveMetrics = $monitor->getLiveMetrics();
        $this->assertGreaterThan(0, $liveMetrics['active_requests']);

        // Process requests with varying completion times
        foreach ($requestIds as $i => $requestId) {
            // Simulate work with JSON pooling
            $data = $this->createLargeJsonPayload(10 + $i);
            $json = JsonBufferPool::encodeWithPool($data);

            // Add processing time
            usleep(random_int(500, 2000)); // 0.5-2ms

            // Complete request
            $monitor->endRequest($requestId, 200);
        }

        // Verify all requests completed
        $finalMetrics = $monitor->getLiveMetrics();
        $this->assertEquals(0, $finalMetrics['active_requests']);

        // Verify pool statistics show activity
        $jsonStats = JsonBufferPool::getStatistics();
        $this->assertGreaterThan(0, $jsonStats['total_operations']);
    }

    /**
     * Test error scenarios with performance features
     */
    public function testErrorScenariosIntegration(): void
    {
        // Enable High Performance Mode
        $this->enableHighPerformanceMode('HIGH');

        $monitor = HighPerformanceMode::getMonitor();

        // Test error in monitored operation
        $requestId = 'error-test';
        $monitor->startRequest($requestId, ['test' => 'error_scenario']);

        try {
            // Simulate an error during JSON processing
            $invalidData = ['resource' => fopen('php://temp', 'r')]; // Resource can't be JSON encoded
            JsonBufferPool::encodeWithPool($invalidData);

            // If we get here, the operation didn't fail as expected
            $monitor->endRequest($requestId, 200);
        } catch (\Exception $e) {
            // Record the error
            $monitor->recordError(
                'json_encoding_error',
                [
                    'message' => $e->getMessage(),
                    'data_type' => 'invalid_resource'
                ]
            );

            $monitor->endRequest($requestId, 500);
        }

        // Verify system is still functional after error
        $hpStatus = HighPerformanceMode::getStatus();
        $this->assertTrue($hpStatus['enabled']);

        // Verify monitoring is still working
        $liveMetrics = $monitor->getLiveMetrics();
        $this->assertIsArray($liveMetrics);
        $this->assertEquals(0, $liveMetrics['active_requests']);
    }

    /**
     * Test resource cleanup integration
     */
    public function testResourceCleanupIntegration(): void
    {
        // Enable both performance features
        $this->enableHighPerformanceMode('HIGH');

        // Generate significant activity
        $this->generateTestLoad(10, 'cleanup_test');

        // Get statistics before cleanup
        $hpStatus = HighPerformanceMode::getStatus();
        $jsonStats = JsonBufferPool::getStatistics();

        $this->assertTrue($hpStatus['enabled']);

        // Manual cleanup (simulating application shutdown)
        HighPerformanceMode::disable();
        JsonBufferPool::clearPools();

        // Verify cleanup was effective
        $finalHpStatus = HighPerformanceMode::getStatus();
        $finalJsonStats = JsonBufferPool::getStatistics();

        $this->assertFalse($finalHpStatus['enabled']);
        $this->assertEquals(0, $finalJsonStats['current_usage']);

        // Verify resources can be re-enabled
        $this->enableHighPerformanceMode('HIGH');
        $newStatus = HighPerformanceMode::getStatus();
        $this->assertTrue($newStatus['enabled']);
    }

    /**
     * Test performance regression detection
     */
    public function testPerformanceRegressionDetection(): void
    {
        // Enable High Performance Mode
        $this->enableHighPerformanceMode('HIGH');

        // Baseline performance measurement
        $baselineTime = $this->measureExecutionTime(
            function () {
                for ($i = 0; $i < 100; $i++) {
                    $data = ['iteration' => $i, 'data' => str_repeat('x', 100)];
                    JsonBufferPool::encodeWithPool($data);
                }
            }
        );

        // Simulate load and measure again
        $this->generateTestLoad(5, 'regression_test');

        $loadTestTime = $this->measureExecutionTime(
            function () {
                for ($i = 0; $i < 100; $i++) {
                    $data = ['iteration' => $i, 'data' => str_repeat('x', 100)];
                    JsonBufferPool::encodeWithPool($data);
                }
            }
        );

        // Performance should not degrade significantly under load
        $performanceDegradation = ($loadTestTime - $baselineTime) / $baselineTime;

        // Allow for more degradation in test environments (Xdebug, CI, etc.)
        $maxDegradation = extension_loaded('xdebug') ? 5.0 : 3.0; // 500% or 300%

        $this->assertLessThan(
            $maxDegradation,
            $performanceDegradation,
            "Performance degradation ({$performanceDegradation}) should be less than " . (($maxDegradation - 1) * 100) . "%"
        );

        // Verify system metrics are within reasonable bounds
        $monitor = HighPerformanceMode::getMonitor();
        $metrics = $monitor->getLiveMetrics();

        $this->assertLessThan(
            1.0,
            $metrics['memory_pressure'],
            "Memory pressure should be below 100%"
        );
    }

    /**
     * Helper method to generate test load
     */
    private function generateTestLoad(int $operationCount, string $context): void
    {
        $monitor = HighPerformanceMode::getMonitor();

        for ($i = 0; $i < $operationCount; $i++) {
            $requestId = "{$context}-{$i}";

            if ($monitor) {
                $monitor->startRequest($requestId, ['context' => $context]);
            }

            // Generate JSON operations
            $data = $this->createLargeJsonPayload(15 + $i);
            $json = JsonBufferPool::encodeWithPool($data);

            // Add processing time
            usleep(random_int(1000, 3000)); // 1-3ms

            if ($monitor) {
                $monitor->endRequest($requestId, 200);
            }
        }
    }

    /**
     * Test stability under extended load
     */
    public function testStabilityUnderExtendedLoad(): void
    {
        // Enable High Performance Mode
        $this->enableHighPerformanceMode('EXTREME');

        $monitor = HighPerformanceMode::getMonitor();

        // Record initial state
        $initialMemory = memory_get_usage(true);
        $initialJsonStats = JsonBufferPool::getStatistics();

        // Generate extended load
        $totalOperations = 50;
        for ($batch = 0; $batch < 5; $batch++) {
            $this->generateTestLoad(10, "stability-batch-{$batch}");

            // Check system state periodically
            $currentMemory = memory_get_usage(true);
            $memoryGrowth = ($currentMemory - $initialMemory) / 1024 / 1024;

            // Memory shouldn't grow unbounded
            $this->assertLessThan(
                30,
                $memoryGrowth,
                "Memory growth in batch {$batch} should be limited"
            );

            // Force garbage collection between batches
            gc_collect_cycles();
        }

        // Verify final system state
        $finalMemory = memory_get_usage(true);
        $finalJsonStats = JsonBufferPool::getStatistics();
        $finalMetrics = $monitor->getLiveMetrics();

        // No active requests should remain
        $this->assertEquals(0, $finalMetrics['active_requests']);

        // JSON pool should show significant activity
        $this->assertGreaterThan(
            $initialJsonStats['total_operations'],
            $finalJsonStats['total_operations']
        );

        // Memory usage should be reasonable
        $totalMemoryGrowth = ($finalMemory - $initialMemory) / 1024 / 1024;
        $this->assertLessThan(
            25,
            $totalMemoryGrowth,
            "Total memory growth should be under 25MB"
        );

        // System should still be responsive
        $hpStatus = HighPerformanceMode::getStatus();
        $this->assertTrue($hpStatus['enabled']);
    }
}
