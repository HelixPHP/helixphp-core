<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Performance;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Performance\HighPerformanceMode;
use PivotPHP\Core\Performance\PerformanceMonitor;

/**
 * Comprehensive tests for HighPerformanceMode
 *
 * @group performance
 * @group high-performance
 */
class HighPerformanceModeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure clean state
        HighPerformanceMode::disable();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up after tests
        HighPerformanceMode::disable();
    }

    /**
     * Test enabling high performance mode with different profiles
     */
    public function testEnableHighPerformanceMode(): void
    {
        // Test enabling with HIGH profile
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);

        // Reset and test EXTREME profile
        HighPerformanceMode::disable();
        $status = HighPerformanceMode::getStatus();
        $this->assertFalse($status['enabled']);

        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_EXTREME);
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);
    }

    /**
     * Test disabling high performance mode
     */
    public function testDisableHighPerformanceMode(): void
    {
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);

        HighPerformanceMode::disable();
        $status = HighPerformanceMode::getStatus();
        $this->assertFalse($status['enabled']);
    }

    /**
     * Test profile constants exist
     */
    public function testProfileConstants(): void
    {
        $this->assertTrue(defined('PivotPHP\Core\Performance\HighPerformanceMode::PROFILE_HIGH'));
        $this->assertTrue(defined('PivotPHP\Core\Performance\HighPerformanceMode::PROFILE_EXTREME'));

        $this->assertIsString(HighPerformanceMode::PROFILE_HIGH);
        $this->assertIsString(HighPerformanceMode::PROFILE_EXTREME);
        $this->assertNotEquals(HighPerformanceMode::PROFILE_HIGH, HighPerformanceMode::PROFILE_EXTREME);
    }

    /**
     * Test getting performance monitor
     */
    public function testGetMonitor(): void
    {
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

        $monitor = HighPerformanceMode::getMonitor();
        $this->assertInstanceOf(PerformanceMonitor::class, $monitor);

        // Should return same instance on subsequent calls
        $monitor2 = HighPerformanceMode::getMonitor();
        $this->assertSame($monitor, $monitor2);
    }

    /**
     * Test monitor integration when enabled
     */
    public function testMonitorIntegrationWhenEnabled(): void
    {
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

        $monitor = HighPerformanceMode::getMonitor();

        // Test that monitor can track requests
        $monitor->startRequest('test-request', ['path' => '/test']);
        usleep(10000); // 10ms
        $monitor->endRequest('test-request', 200);

        $metrics = $monitor->getPerformanceMetrics();
        $this->assertArrayHasKey('latency', $metrics);
        $this->assertArrayHasKey('throughput', $metrics);
    }

    /**
     * Test monitor when disabled
     */
    public function testMonitorWhenDisabled(): void
    {
        HighPerformanceMode::disable();

        // May return null when disabled
        $monitor = HighPerformanceMode::getMonitor();
        $this->assertTrue($monitor === null || $monitor instanceof PerformanceMonitor);
    }

    /**
     * Test high performance mode with HIGH profile configuration
     */
    public function testHighProfileConfiguration(): void
    {
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);

        $monitor = HighPerformanceMode::getMonitor();
        $liveMetrics = $monitor->getLiveMetrics();

        $this->assertIsArray($liveMetrics);
        $this->assertArrayHasKey('memory_pressure', $liveMetrics);
        $this->assertArrayHasKey('current_load', $liveMetrics);
    }

    /**
     * Test high performance mode with EXTREME profile configuration
     */
    public function testExtremeProfileConfiguration(): void
    {
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_EXTREME);

        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);

        $monitor = HighPerformanceMode::getMonitor();
        $liveMetrics = $monitor->getLiveMetrics();

        $this->assertIsArray($liveMetrics);
        $this->assertArrayHasKey('memory_pressure', $liveMetrics);
        $this->assertArrayHasKey('current_load', $liveMetrics);
    }

    /**
     * Test state persistence across calls
     */
    public function testStatePersistence(): void
    {
        // Initially disabled
        $status = HighPerformanceMode::getStatus();
        $this->assertFalse($status['enabled']);

        // Enable and verify persistence
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']); // Should still be true

        // Monitor should be consistent
        $monitor1 = HighPerformanceMode::getMonitor();
        $monitor2 = HighPerformanceMode::getMonitor();
        $this->assertSame($monitor1, $monitor2);

        // Disable and verify
        HighPerformanceMode::disable();
        $status = HighPerformanceMode::getStatus();
        $this->assertFalse($status['enabled']);
        $status = HighPerformanceMode::getStatus();
        $this->assertFalse($status['enabled']); // Should still be false
    }

    /**
     * Test multiple enable calls with same profile
     */
    public function testMultipleEnableCallsSameProfile(): void
    {
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);

        // Enable again with same profile
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);

        // Monitor should remain consistent
        $monitor = HighPerformanceMode::getMonitor();
        $this->assertInstanceOf(PerformanceMonitor::class, $monitor);
    }

    /**
     * Test profile switching
     */
    public function testProfileSwitching(): void
    {
        // Start with HIGH profile
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);
        $monitor1 = HighPerformanceMode::getMonitor(); // Initialize monitor

        // Switch to EXTREME profile
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_EXTREME);
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);
        $monitor2 = HighPerformanceMode::getMonitor();

        // Should still get a monitor (may be same or different instance)
        $this->assertInstanceOf(PerformanceMonitor::class, $monitor2);
    }

    /**
     * Test performance impact measurement
     */
    public function testPerformanceImpactMeasurement(): void
    {
        // Measure performance without high performance mode
        $start = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            // Some work
            str_repeat('x', 100); // Memory allocation test
        }
        $baselineTime = microtime(true) - $start;

        // Enable high performance mode and measure again
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

        $start = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            // Same work
            str_repeat('x', 100); // Memory allocation test
        }
        $optimizedTime = microtime(true) - $start;

        // Performance should not be significantly degraded
        // (allowing for measurement variance across PHP versions)
        $this->assertLessThan(
            $baselineTime * 5, // More tolerant for CI and different PHP versions
            $optimizedTime,
            'High performance mode should not significantly degrade performance'
        );
    }

    /**
     * Test memory usage monitoring
     */
    public function testMemoryUsageMonitoring(): void
    {
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

        $monitor = HighPerformanceMode::getMonitor();
        $beforeMetrics = $monitor->getLiveMetrics();

        // Create some memory pressure
        $largeArray = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeArray[] = str_repeat('x', 1024);
        }

        $afterMetrics = $monitor->getLiveMetrics();

        // Memory pressure should be trackable
        $this->assertArrayHasKey('memory_pressure', $beforeMetrics);
        $this->assertArrayHasKey('memory_pressure', $afterMetrics);
        $this->assertIsFloat($beforeMetrics['memory_pressure']);
        $this->assertIsFloat($afterMetrics['memory_pressure']);

        // Clean up
        unset($largeArray);
    }

    /**
     * Test concurrent access to high performance mode
     */
    public function testConcurrentAccess(): void
    {
        // Simulate concurrent access patterns
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

        $monitors = [];
        for ($i = 0; $i < 10; $i++) {
            $monitors[] = HighPerformanceMode::getMonitor();
        }

        // All should be the same instance
        $firstMonitor = $monitors[0];
        foreach ($monitors as $monitor) {
            $this->assertSame($firstMonitor, $monitor);
        }

        // All should report consistent state
        foreach ($monitors as $monitor) {
            $metrics = $monitor->getLiveMetrics();
            $this->assertIsArray($metrics);
        }
    }

    /**
     * Test error handling in high performance mode
     */
    public function testErrorHandlingInHighPerformanceMode(): void
    {
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

        $monitor = HighPerformanceMode::getMonitor();

        // Test that monitor handles errors gracefully
        $monitor->startRequest('error-test', ['path' => '/error']);

        try {
            throw new \Exception('Test exception');
        } catch (\Exception) { // Ignore exception for test
            // Monitor should handle this gracefully
            $monitor->endRequest('error-test', 500);
        }

        $metrics = $monitor->getPerformanceMetrics();
        $this->assertIsArray($metrics);
    }

    /**
     * Test integration with garbage collection
     */
    public function testGarbageCollectionIntegration(): void
    {
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_EXTREME);

        $monitor = HighPerformanceMode::getMonitor();
        $beforeGc = $monitor->getLiveMetrics();

        // Force garbage collection
        gc_collect_cycles();

        $afterGc = $monitor->getLiveMetrics();

        // Monitor should continue working after GC
        $this->assertIsArray($beforeGc);
        $this->assertIsArray($afterGc);
    }

    /**
     * Test resource cleanup on disable
     */
    public function testResourceCleanupOnDisable(): void
    {
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
        $monitor = HighPerformanceMode::getMonitor();

        // Generate some activity
        $monitor->startRequest('cleanup-test', ['path' => '/test']);
        $monitor->endRequest('cleanup-test', 200);

        // Disable should clean up resources
        HighPerformanceMode::disable();
        $status = HighPerformanceMode::getStatus();
        $this->assertFalse($status['enabled']);

        // Should still be able to get a monitor instance (may be different or null when disabled)
        $newMonitor = HighPerformanceMode::getMonitor();
        $this->assertTrue($newMonitor === null || $newMonitor instanceof PerformanceMonitor);
    }
}
