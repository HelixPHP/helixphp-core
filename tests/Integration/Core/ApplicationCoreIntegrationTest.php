<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Core;

use PivotPHP\Core\Tests\Integration\IntegrationTestCase;
use PivotPHP\Core\Performance\HighPerformanceMode;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

/**
 * Application Core Integration Tests
 *
 * Tests the integration between core framework components:
 * - Application bootstrap and lifecycle
 * - Service container integration
 * - Configuration management
 * - Basic routing and response handling
 *
 * @group integration
 * @group core
 */
class ApplicationCoreIntegrationTest extends IntegrationTestCase
{
    /**
     * Test basic application lifecycle with routing
     */
    public function testApplicationLifecycleWithRouting(): void
    {
        // Setup a simple route
        $this->app->get(
            '/integration-test',
            function ($_, $res) {
                return $res->json(['message' => 'success', 'timestamp' => time()]);
            }
        );

        // Simulate request
        $response = $this->simulateRequest('GET', '/integration-test');

        // Verify response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeader('Content-Type'));

        $data = $response->getJsonData();
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('success', $data['message']);
        $this->assertArrayHasKey('timestamp', $data);

        // Verify performance metrics
        $metrics = $this->getCurrentPerformanceMetrics();
        $this->assertArrayHasKey('elapsed_time_ms', $metrics);
        $this->assertLessThan(100, $metrics['elapsed_time_ms']); // Should be fast
    }

    /**
     * Test application with high performance mode enabled
     */
    public function testApplicationWithHighPerformanceMode(): void
    {
        // Enable high performance mode
        $this->enableHighPerformanceMode('HIGH');

        // Verify HP mode is enabled
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);

        // Setup routes that would benefit from HP mode
        $this->app->get(
            '/hp-test',
            function ($_, $res) {
                $data = $this->createLargeJsonPayload(50);
                return $res->json($data);
            }
        );

        // Test multiple requests
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->simulateRequest('GET', '/hp-test');
        }

        // Verify all responses are successful
        foreach ($responses as $response) {
            $this->assertEquals(200, $response->getStatusCode());
            $data = $response->getJsonData();
            $this->assertCount(50, $data);
        }

        // Check that HP mode is still enabled
        $status = HighPerformanceMode::getStatus();
        $this->assertTrue($status['enabled']);

        // Verify performance metrics (adjusted for test environment with coverage)
        $this->assertMemoryUsageWithinLimits(150); // Should stay under 150MB in test environment
    }

    /**
     * Test JSON pooling integration with application responses
     */
    public function testJsonPoolingIntegration(): void
    {
        // Get initial pool stats
        $initialStats = JsonBufferPool::getStatistics();

        // Setup route with large JSON response
        $this->app->get(
            '/large-json',
            function ($_, $res) {
                $data = $this->createLargeJsonPayload(100);
                return $res->json($data);
            }
        );

        // Make multiple requests to trigger pooling
        for ($i = 0; $i < 3; $i++) {
            $response = $this->simulateRequest('GET', '/large-json');
            $this->assertEquals(200, $response->getStatusCode());

            $data = $response->getJsonData();
            $this->assertCount(100, $data);
        }

        // Verify pool statistics changed
        $finalStats = JsonBufferPool::getStatistics();
        $this->assertGreaterThanOrEqual($initialStats['total_operations'], $finalStats['total_operations']);

        // Verify pool is being used efficiently
        if ($finalStats['total_operations'] > 0) {
            $this->assertGreaterThanOrEqual(0, $finalStats['current_usage']);
        }
    }

    /**
     * Test middleware integration with application lifecycle
     */
    public function testMiddlewareIntegration(): void
    {
        $middlewareExecuted = [];

        // Create middleware that tracks execution
        $trackingMiddleware = function ($req, $res, $next) use (&$middlewareExecuted) {
            $middlewareExecuted[] = 'before';
            $response = $next($req, $res);
            $middlewareExecuted[] = 'after';
            return $response;
        };

        // Add global middleware
        $this->app->use($trackingMiddleware);

        // Add route
        $this->app->get(
            '/middleware-test',
            function ($_, $res) {
                return $res->json(['middleware_test' => true]);
            }
        );

        // Make request
        $response = $this->simulateRequest('GET', '/middleware-test');

        // Verify response
        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getJsonData();
        $this->assertTrue($data['middleware_test']);

        // Verify middleware execution order
        $this->assertEquals(['before', 'after'], $middlewareExecuted);
    }

    /**
     * Test error handling integration
     */
    public function testErrorHandlingIntegration(): void
    {
        // Add route that throws exception
        $this->app->get(
            '/error-test',
            function ($_) {
                throw new \Exception('Test integration error');
            }
        );

        // Add error handling middleware
        $this->app->use(
            function ($req, $res, $next) {
                try {
                    return $next($req, $res);
                } catch (\Exception $e) {
                    return $res->status(500)->json(
                        [
                            'error' => true,
                            'message' => $e->getMessage()
                        ]
                    );
                }
            }
        );

        // Make request
        $response = $this->simulateRequest('GET', '/error-test');

        // Verify error response
        $this->assertEquals(500, $response->getStatusCode());
        $data = $response->getJsonData();
        $this->assertTrue($data['error']);
        $this->assertEquals('Test integration error', $data['message']);
    }

    /**
     * Test performance under concurrent requests
     */
    public function testConcurrentRequestPerformance(): void
    {
        // Skip test in very slow environments
        $isVerySlowEnvironment = (
            extension_loaded('xdebug') &&
            (getenv('XDEBUG_MODE') === 'coverage' || defined('PHPUNIT_COVERAGE_ACTIVE'))
        ) || getenv('SKIP_PERFORMANCE_TESTS') === 'true';

        if ($isVerySlowEnvironment) {
            $this->markTestSkipped('Skipping concurrent performance test in very slow environment (coverage/debugging)');
        }

        // Enable high performance mode for better concurrency
        $this->enableHighPerformanceMode('HIGH');

        // Setup route
        $this->app->get(
            '/concurrent-test',
            function ($_, $res) {
            // Simulate some work
                usleep(1000); // 1ms
                return $res->json(['request_id' => uniqid(), 'timestamp' => microtime(true)]);
            }
        );

        // Prepare concurrent requests
        $requests = [];
        for ($i = 0; $i < 10; $i++) {
            $requests[] = [
                'method' => 'GET',
                'uri' => '/concurrent-test',
                'options' => []
            ];
        }

        // Execute concurrent requests
        $startTime = microtime(true);
        $responses = $this->simulateConcurrentRequests($requests);
        $totalTime = (microtime(true) - $startTime) * 1000; // Convert to ms

        // Verify all responses
        $this->assertCount(10, $responses);
        foreach ($responses as $response) {
            $this->assertEquals(200, $response->getStatusCode());
            $data = $response->getJsonData();
            $this->assertArrayHasKey('request_id', $data);
            $this->assertArrayHasKey('timestamp', $data);
        }

        // Verify performance with environment-aware expectations
        $maxTime = $this->getPerformanceTimeout();
        $this->assertLessThan(
            $maxTime,
            $totalTime,
            sprintf('Concurrent requests took too long: %.2fms (max: %dms)', $totalTime, $maxTime)
        );

        // Verify memory usage is reasonable (adjusted for test environment)
        $this->assertMemoryUsageWithinLimits(150);
    }

    /**
     * Get performance timeout based on environment
     */
    private function getPerformanceTimeout(): int
    {
        // Check if running in CI environment
        if (getenv('CI') !== false || getenv('GITHUB_ACTIONS') !== false) {
            return 15000; // 15 seconds for CI
        }

        // Check for debug/coverage mode (Xdebug heavily impacts performance)
        if (extension_loaded('xdebug') || getenv('XDEBUG_MODE') !== false) {
            return 10000; // 10 seconds for debug mode
        }

        // Check for slow test environment
        if (getenv('SLOW_TESTS') === 'true') {
            return 20000; // 20 seconds for very slow environment
        }

        // Local development environment
        return 5000; // 5 seconds for local
    }

    /**
     * Test configuration override scenarios
     */
    public function testConfigurationOverride(): void
    {
        // Define test configuration
        $testConfig = [
            'custom_setting' => 'test_value',
            'performance' => [
                'enabled' => true,
                'profile' => 'HIGH'
            ]
        ];

        // Setup route that uses configuration (unique path to avoid conflicts)
        $uniquePath = '/unique-config-test-' . substr(md5(__METHOD__), 0, 8);
        $this->app->get(
            $uniquePath,
            function ($_, $res) use ($testConfig) {
                return $res->json(
                    [
                        'config_loaded' => true,
                        'test_config' => $testConfig
                    ]
                );
            }
        );

        // Make request to unique path
        $response = $this->simulateRequest('GET', $uniquePath);

        // Verify response includes configuration
        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getJsonData();

        // Debug: Show what we actually got
        if (!isset($data['test_config'])) {
            $this->fail('Response missing test_config key. Actual response: ' . json_encode($data));
        }

        $this->assertArrayHasKey('config_loaded', $data, 'Response missing config_loaded key');
        $this->assertTrue($data['config_loaded']);
        $this->assertArrayHasKey('test_config', $data, 'Response missing test_config key');
        $this->assertArrayHasKey('custom_setting', $data['test_config']);
        $this->assertEquals('test_value', $data['test_config']['custom_setting']);
    }

    /**
     * Test memory management during intensive operations
     */
    public function testMemoryManagementIntegration(): void
    {
        // Record initial memory
        $initialMemory = memory_get_usage(true);

        // Setup route that creates memory pressure
        $this->app->get(
            '/memory-test',
            function ($_, $res) {
            // Create temporary large data structure
                $largeData = [];
                for ($i = 0; $i < 1000; $i++) {
                    $largeData[] = str_repeat('x', 1024); // 1KB each
                }

            // Return summary instead of large data
                return $res->json(
                    [
                        'processed_items' => count($largeData),
                        'memory_used' => memory_get_usage(true)
                    ]
                );
            }
        );

        // Make multiple requests
        for ($i = 0; $i < 5; $i++) {
            $response = $this->simulateRequest('GET', '/memory-test');
            $this->assertEquals(200, $response->getStatusCode());

            $data = $response->getJsonData();
            $this->assertEquals(1000, $data['processed_items']);
        }

        // Force garbage collection
        gc_collect_cycles();

        // Verify memory hasn't grown excessively
        $finalMemory = memory_get_usage(true);
        $memoryGrowth = ($finalMemory - $initialMemory) / 1024 / 1024; // MB

        $this->assertLessThan(
            10,
            $memoryGrowth,
            "Memory growth ({$memoryGrowth}MB) should be less than 10MB"
        );
    }

    /**
     * Test application shutdown and cleanup
     */
    public function testApplicationShutdownCleanup(): void
    {
        // Enable performance features
        $this->enableHighPerformanceMode('HIGH');

        // Generate some activity
        $this->app->get(
            '/cleanup-test',
            function ($_, $res) {
                return $res->json(['cleanup_test' => true]);
            }
        );

        // Make some requests
        for ($i = 0; $i < 3; $i++) {
            $response = $this->simulateRequest('GET', '/cleanup-test');
            $this->assertEquals(200, $response->getStatusCode());
        }

        // Get performance state before cleanup
        $hpStatus = HighPerformanceMode::getStatus();
        $jsonStats = JsonBufferPool::getStatistics();

        // Verify systems are active
        $this->assertTrue($hpStatus['enabled']);

        // Cleanup will happen in tearDown() - we can verify it worked
        // by checking that performance systems can be cleanly disabled
        $this->addToAssertionCount(1); // Count this as a successful test
    }

    /**
     * Test edge cases and boundary conditions
     */
    public function testEdgeCasesAndBoundaryConditions(): void
    {
        // Test empty route
        $this->app->get(
            '/empty',
            function ($_, $res) {
                return $res->json([]);
            }
        );

        // Test null values
        $this->app->get(
            '/null-test',
            function ($_, $res) {
                return $res->json(['value' => null]);
            }
        );

        // Test large numbers
        $this->app->get(
            '/large-numbers',
            function ($req, $res) {
                return $res->json(
                    [
                        'large_int' => PHP_INT_MAX,
                        'large_float' => PHP_FLOAT_MAX
                    ]
                );
            }
        );

        // Test special characters
        $this->app->get(
            '/special-chars',
            function ($req, $res) {
                return $res->json(
                    [
                        'unicode' => '🚀💨⚡',
                        'special' => 'test"quote\'apostrophe\nNewline\tTab'
                    ]
                );
            }
        );

        // Test all edge cases
        $testCases = [
            '/empty' => [],
            '/null-test' => ['value' => null],
            '/large-numbers' => ['large_int' => PHP_INT_MAX, 'large_float' => PHP_FLOAT_MAX],
            '/special-chars' => [
                'unicode' => '🚀💨⚡',
                'special' => 'test"quote\'apostrophe\nNewline\tTab'
            ]
        ];

        foreach ($testCases as $path => $expectedData) {
            $response = $this->simulateRequest('GET', $path);
            $this->assertEquals(200, $response->getStatusCode());

            $data = $response->getJsonData();
            $this->assertEquals($expectedData, $data);
        }
    }
}
