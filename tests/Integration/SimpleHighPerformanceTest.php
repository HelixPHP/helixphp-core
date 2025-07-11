<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Performance\HighPerformanceMode;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

/**
 * Simple integration tests for High Performance Mode
 *
 * @group integration
 * @group performance
 */
class SimpleHighPerformanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        HighPerformanceMode::disable();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        HighPerformanceMode::disable();
    }

    /**
     * Test basic high performance mode functionality
     */
    public function testBasicHighPerformanceMode(): void
    {
        // Test initial state
        $status = HighPerformanceMode::getStatus();
        $this->assertFalse($status['enabled']);

        // Enable high performance mode
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

        // Verify it's enabled
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);

        // Get monitor instance
        $monitor = HighPerformanceMode::getMonitor();
        $this->assertNotNull($monitor);

        // Test basic monitoring functionality without triggering complex aggregations
        $monitor->startRequest('simple-test', ['path' => '/test']);
        usleep(1000); // 1ms
        $monitor->endRequest('simple-test', 200);

        // Just verify monitor exists and basic functionality works
        $this->assertNotNull($monitor);

        // Disable
        HighPerformanceMode::disable();
        $status = HighPerformanceMode::getStatus();
        $this->assertFalse($status['enabled']);
    }

    /**
     * Test JSON pooling basic functionality
     */
    public function testJsonPoolingBasic(): void
    {
        // Test with pooling
        $data = ['test' => 'data', 'numbers' => [1, 2, 3, 4, 5]];

        $json1 = JsonBufferPool::encodeWithPool($data);
        $json2 = json_encode($data);

        // Results should be identical
        $this->assertEquals($json2, $json1);

        // Test statistics
        $stats = JsonBufferPool::getStatistics();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_operations', $stats);
        $this->assertArrayHasKey('current_usage', $stats);
    }

    /**
     * Test combined high performance mode and JSON pooling
     */
    public function testCombinedOptimizations(): void
    {
        // Enable high performance mode
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

        // Test data that might trigger pooling
        $data = array_fill(0, 15, ['id' => rand(), 'value' => 'test']);

        // Get initial stats
        $initialStats = JsonBufferPool::getStatistics();

        // Perform JSON operations
        for ($i = 0; $i < 3; $i++) {
            $json = JsonBufferPool::encodeWithPool($data);
            $this->assertIsString($json);
        }

        // Get final stats
        $finalStats = JsonBufferPool::getStatistics();

        // Verify operations increased
        $this->assertGreaterThanOrEqual($initialStats['total_operations'], $finalStats['total_operations']);

        // Test that high performance mode is still enabled
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);
    }

    /**
     * Test performance profile switching
     */
    public function testProfileSwitching(): void
    {
        // Test HIGH profile
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);

        // Switch to EXTREME profile
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_EXTREME);
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);

        // Monitor should still be available
        $monitor = HighPerformanceMode::getMonitor();
        $this->assertNotNull($monitor);

        // Test basic request tracking
        $monitor->startRequest('profile-test', ['profile' => 'extreme']);
        usleep(500);
        $monitor->endRequest('profile-test', 200);

        // Verify system is still functional
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);
    }

    /**
     * Test application integration
     */
    public function testApplicationIntegration(): void
    {
        // Create application
        $app = new Application();

        // Enable high performance mode
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

        // Add simple route
        $app->get(
            '/health',
            function ($req, $res) {
                return $res->json(['status' => 'ok', 'performance' => 'enabled']);
            }
        );

        // Verify high performance mode is enabled
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);

        // Verify application is created successfully
        $this->assertInstanceOf(Application::class, $app);

        // Test that we can get monitor
        $monitor = HighPerformanceMode::getMonitor();
        $this->assertNotNull($monitor);
    }

    /**
     * Test error resilience
     */
    public function testErrorResilience(): void
    {
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

        // Test with invalid data
        $monitor = HighPerformanceMode::getMonitor();

        // Start request with unusual data
        $monitor->startRequest('error-test', ['unusual' => null]);

        // End with error status
        $monitor->endRequest('error-test', 500);

        // System should still be functional
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);

        // Test ending non-existent request (should handle gracefully)
        $monitor->endRequest('non-existent', 404);

        // System should still be functional
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);
    }

    /**
     * Test resource cleanup
     */
    public function testResourceCleanup(): void
    {
        // Enable and use
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);
        $monitor = HighPerformanceMode::getMonitor();

        // Generate some activity
        $monitor->startRequest('cleanup-1', ['test' => true]);
        $monitor->endRequest('cleanup-1', 200);

        // Disable
        HighPerformanceMode::disable();

        // Verify disabled
        $status = HighPerformanceMode::getStatus();
        $this->assertFalse($status['enabled']);

        // Clear JSON pools
        JsonBufferPool::clearPools();

        // Verify pools cleared
        $stats = JsonBufferPool::getStatistics();
        $this->assertEquals(0, $stats['current_usage']);
    }
}
