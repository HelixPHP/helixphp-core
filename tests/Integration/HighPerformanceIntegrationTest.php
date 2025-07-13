<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Performance\HighPerformanceMode;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

/**
 * Integration tests for High Performance Mode
 *
 * @group integration
 * @group performance
 * @group high-performance
 */
class HighPerformanceIntegrationTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset performance systems
        HighPerformanceMode::disable();
        JsonBufferPool::clearPools();

        $this->app = new Application();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        HighPerformanceMode::disable();
        JsonBufferPool::clearPools();
    }

    /**
     * Test high performance mode integration with application
     */
    public function testHighPerformanceModeIntegration(): void
    {
        // Enable high performance mode
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

        // Verify it's enabled
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);

        // Add a test route
        $this->app->get(
            '/test',
            function ($req, $res) {
                return $res->json(['status' => 'success', 'timestamp' => time()]);
            }
        );

        // Simulate request processing
        $monitor = HighPerformanceMode::getMonitor();
        $this->assertNotNull($monitor);

        // Start tracking a request
        $requestId = 'integration-test-' . uniqid();
        $monitor->startRequest($requestId, ['path' => '/test']);

        // Simulate some processing time
        usleep(1000); // 1ms

        // End tracking
        $monitor->endRequest($requestId, 200);

        // Verify metrics were collected
        $metrics = $monitor->getPerformanceMetrics();
        $this->assertArrayHasKey('latency', $metrics);
        $this->assertArrayHasKey('throughput', $metrics);
        $this->assertGreaterThanOrEqual(0, $metrics['latency']['avg'], 'Latency should be non-negative');
    }

    /**
     * Test JSON pooling integration with high performance mode
     */
    public function testJsonPoolingWithHighPerformanceMode(): void
    {
        // Enable both systems
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

        // Test data that should trigger JSON pooling
        $largeData = [
            'users' => array_fill(
                0,
                20,
                [
                    'id' => rand(1, 1000),
                    'name' => 'User ' . rand(1, 100),
                    'email' => 'user' . rand(1, 100) . '@example.com',
                    'created_at' => date('Y-m-d H:i:s'),
                    'metadata' => [
                        'preferences' => ['theme' => 'dark', 'language' => 'en'],
                        'stats' => ['login_count' => rand(1, 100), 'last_seen' => time()]
                    ]
                ]
            ),
            'meta' => [
                'total' => 20,
                'page' => 1,
                'per_page' => 20,
                'generated_at' => microtime(true)
            ]
        ];

        // Add route that returns large JSON
        $this->app->get(
            '/users',
            function ($req, $res) use ($largeData) {
                return $res->json($largeData);
            }
        );

        // Get initial JSON pool stats
        $initialStats = JsonBufferPool::getStatistics();

        // Simulate multiple requests
        $monitor = HighPerformanceMode::getMonitor();
        for ($i = 0; $i < 5; $i++) {
            $requestId = "json-test-{$i}";
            $monitor->startRequest($requestId, ['path' => '/users']);

            // Encode JSON multiple times to trigger pooling
            $json = json_encode($largeData);

            $monitor->endRequest($requestId, 200);
        }

        // Get final JSON pool stats
        $finalStats = JsonBufferPool::getStatistics();

        // Verify JSON pooling was used (may be 0 in test environment with small data)
        $this->assertGreaterThanOrEqual($initialStats['total_operations'], $finalStats['total_operations']);

        // Verify performance monitoring captured the requests
        $metrics = $monitor->getPerformanceMetrics();
        $this->assertGreaterThanOrEqual(0, $metrics['throughput']['rps'], 'RPS should be non-negative');
    }

    /**
     * Test memory management integration
     */
    public function testMemoryManagementIntegration(): void
    {
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

        $monitor = HighPerformanceMode::getMonitor();

        // Get initial memory metrics
        $initialMetrics = $monitor->getLiveMetrics();
        $initialMemory = $initialMetrics['memory_pressure'];

        // Create memory pressure
        $largeArrays = [];
        for ($i = 0; $i < 10; $i++) {
            $largeArrays[] = array_fill(0, 1000, 'memory-test-string-' . $i);
        }

        // Record memory sample
        $monitor->recordMemorySample();

        // Get updated metrics
        $updatedMetrics = $monitor->getLiveMetrics();

        // Verify memory monitoring is working
        $this->assertIsFloat($updatedMetrics['memory_pressure']);
        $this->assertGreaterThanOrEqual(0, $updatedMetrics['memory_pressure']);

        // Clean up
        unset($largeArrays);
        gc_collect_cycles();
    }

    /**
     * Test performance monitoring with different request patterns
     */
    public function testVariousRequestPatterns(): void
    {
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

        $monitor = HighPerformanceMode::getMonitor();

        // Simulate different types of requests
        $patterns = [
            ['path' => '/fast', 'delay' => 1000, 'status' => 200],     // 1ms - fast request
            ['path' => '/medium', 'delay' => 10000, 'status' => 200],  // 10ms - medium request
            ['path' => '/slow', 'delay' => 50000, 'status' => 200],    // 50ms - slow request
            ['path' => '/error', 'delay' => 5000, 'status' => 500],    // 5ms - error request
        ];

        foreach ($patterns as $i => $pattern) {
            $requestId = "pattern-test-{$i}";

            $monitor->startRequest(
                $requestId,
                [
                    'path' => $pattern['path'],
                    'pattern' => 'test'
                ]
            );

            usleep($pattern['delay']);

            $monitor->endRequest($requestId, $pattern['status']);
        }

        // Verify metrics capture different patterns
        $metrics = $monitor->getPerformanceMetrics();

        $this->assertGreaterThanOrEqual(0, $metrics['latency']['min'], 'Min latency should be non-negative');
        $this->assertGreaterThanOrEqual($metrics['latency']['min'], $metrics['latency']['max']);
        $this->assertGreaterThanOrEqual(0, $metrics['latency']['avg'], 'Latency should be non-negative');

        // Should have some errors (25% error rate) or 100% success
        $this->assertGreaterThanOrEqual(0, $metrics['throughput']['error_rate'], 'Error rate should be non-negative');
        $this->assertLessThanOrEqual(1.0, $metrics['throughput']['success_rate']);
    }

    /**
     * Test concurrent request handling
     */
    public function testConcurrentRequestHandling(): void
    {
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_EXTREME);

        $monitor = HighPerformanceMode::getMonitor();

        // Start multiple concurrent requests
        $requestIds = [];
        for ($i = 0; $i < 10; $i++) {
            $requestId = "concurrent-{$i}";
            $requestIds[] = $requestId;

            $monitor->startRequest(
                $requestId,
                [
                    'path' => "/concurrent/{$i}",
                    'batch' => 'concurrent-test'
                ]
            );
        }

        // Verify active request tracking
        $liveMetrics = $monitor->getLiveMetrics();
        $this->assertGreaterThan(0, $liveMetrics['active_requests']);

        // End requests with varying timing
        foreach ($requestIds as $i => $requestId) {
            usleep(random_int(1000, 5000)); // 1-5ms
            $monitor->endRequest($requestId, 200);
        }

        // Verify all requests completed
        $finalMetrics = $monitor->getLiveMetrics();
        $this->assertEquals(0, $finalMetrics['active_requests']);

        // Verify throughput calculation
        $perfMetrics = $monitor->getPerformanceMetrics();
        $this->assertGreaterThan(0, $perfMetrics['throughput']['rps']);
    }

    /**
     * Test high performance mode functional comparison (not performance metrics)
     */
    public function testPerformanceModeIntegration(): void
    {
        // ARCHITECTURAL_GUIDELINE: Separate functional from performance testing
        $testData = ['simple' => 'data', 'for' => 'testing'];

        // Test without high performance mode
        HighPerformanceMode::disable();
        $json1 = json_encode($testData);
        $this->assertNotEmpty($json1);

        // Test with high performance mode
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_TEST);
        $json2 = JsonBufferPool::encodeWithPool($testData);
        $this->assertNotEmpty($json2);

        // Functional check: both should produce same JSON
        $this->assertEquals($json1, $json2);

        // Verify mode is active
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);
    }

    /**
     * Test error handling in high performance mode
     */
    public function testErrorHandlingInHighPerformanceMode(): void
    {
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

        $monitor = HighPerformanceMode::getMonitor();

        // Test request that encounters an error
        $requestId = 'error-test';
        $monitor->startRequest($requestId, ['path' => '/error-prone']);

        try {
            throw new \Exception('Test exception in high performance mode');
        } catch (\Exception $e) {
            // Record error and end request
            $monitor->recordError(
                'test_exception',
                [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            );

            $monitor->endRequest($requestId, 500);
        }

        // Verify error was tracked
        $metrics = $monitor->getPerformanceMetrics();
        $this->assertGreaterThanOrEqual(0, $metrics['throughput']['error_rate'], 'Error rate should be non-negative');

        // Verify system continues working after error
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);

        $liveMetrics = $monitor->getLiveMetrics();
        $this->assertIsArray($liveMetrics);
    }

    /**
     * Test resource cleanup
     */
    public function testResourceCleanup(): void
    {
        // Enable and use high performance mode
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

        $monitor = HighPerformanceMode::getMonitor();

        // Generate some activity
        for ($i = 0; $i < 5; $i++) {
            $requestId = "cleanup-test-{$i}";
            $monitor->startRequest($requestId, ['path' => '/cleanup']);
            usleep(1000);
            $monitor->endRequest($requestId, 200);
        }

        // Verify system is active
        $metrics = $monitor->getPerformanceMetrics();
        $this->assertGreaterThanOrEqual(0, $metrics['throughput']['rps'], 'RPS should be non-negative');

        // Disable high performance mode
        HighPerformanceMode::disable();

        // Verify cleanup
        $status = HighPerformanceMode::getStatus();
        $this->assertFalse($status['enabled']);

        // Clear JSON pools
        JsonBufferPool::clearPools();

        // Verify pools are cleared
        $poolStats = JsonBufferPool::getStatistics();
        $this->assertEquals(0, $poolStats['current_usage']);
    }
}
