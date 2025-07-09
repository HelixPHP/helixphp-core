<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;
use PivotPHP\Core\Http\Pool\DynamicPool;
use PivotPHP\Core\Performance\HighPerformanceMode;
use PivotPHP\Core\Performance\PerformanceMonitor;
use PivotPHP\Core\Memory\MemoryManager;
use PivotPHP\Core\Pool\Distributed\DistributedPoolManager;

/**
 * Integration tests for v1.1.0 components
 */
class V11ComponentsTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app = new Application();
    }

    /**
     * Test high-performance mode integration
     */
    public function testHighPerformanceModeIntegration(): void
    {
        // Enable high-performance mode
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

        // Configure application with performance features
        $this->app->middleware('load-shedder');
        $this->app->middleware('circuit-breaker');

        // Create test route
        $this->app->get(
            '/api/test',
            function (Request $req, Response $res) {
                return $res->json(['status' => 'ok', 'mode' => 'high-performance']);
            }
        );

        // Make requests
        for ($i = 0; $i < 100; $i++) {
            $request = Request::create('/api/test', 'GET');
            $response = $this->app->dispatch($request);

            $this->assertContains($response->getStatusCode(), [200, 503]);
        }

        // Verify configuration was applied
        $config = HighPerformanceMode::getConfiguration();
        $this->assertEquals(HighPerformanceMode::PROFILE_HIGH, $config['profile']);
        $this->assertTrue($config['pool']['enabled']);
        $this->assertTrue($config['monitoring']['enabled']);
    }

    /**
     * Test dynamic pool with overflow strategies
     */
    public function testDynamicPoolWithOverflowStrategies(): void
    {
        $pool = new DynamicPool(
            [
                'initial_size' => 10,
                'max_size' => 50,
                'emergency_limit' => 100,
                'auto_scale' => true,
            ]
        );

        // Borrow objects to trigger scaling
        $borrowed = [];
        for ($i = 0; $i < 60; $i++) {
            $borrowed[] = $pool->borrow(
                'request',
                [
                    'method' => 'GET',
                    'uri' => '/test',
                ]
            );
        }

        $stats = $pool->getStats();

        // Verify pool expanded
        $this->assertGreaterThan(0, $stats['stats']['expanded']);
        $this->assertGreaterThan(50, $stats['scaling_state']['request']['current_size']);

        // Return objects
        foreach ($borrowed as $obj) {
            $pool->return('request', $obj);
        }

        // Wait and check if pool shrinks
        sleep(1);
        $pool->check();

        $newStats = $pool->getStats();
        $this->assertLessThanOrEqual(
            $stats['scaling_state']['request']['current_size'],
            $newStats['scaling_state']['request']['current_size']
        );
    }

    /**
     * Test middleware integration
     */
    public function testMiddlewareIntegration(): void
    {
        // Configure all performance middlewares
        $this->app->middleware(
            'rate-limiter',
            [
                'max_requests' => 100,
                'window' => 60,
            ]
        );

        $this->app->middleware(
            'load-shedder',
            [
                'threshold' => 0.8,
                'strategy' => 'priority',
            ]
        );

        $this->app->middleware(
            'circuit-breaker',
            [
                'failure_threshold' => 5,
                'timeout' => 30,
            ]
        );

        // Create routes
        $this->app->get(
            '/health',
            function ($req, $res) {
                return $res->json(['status' => 'healthy']);
            }
        );

        $this->app->post(
            '/api/data',
            function ($req, $res) {
            // Simulate processing
                usleep(10000); // 10ms
                return $res->json(['processed' => true]);
            }
        );

        // Test health endpoint (should always work)
        $healthRequest = Request::create('/health', 'GET');
        $healthResponse = $this->app->dispatch($healthRequest);
        $this->assertEquals(200, $healthResponse->getStatusCode());

        // Test API endpoint with load
        $results = ['success' => 0, 'rate_limited' => 0, 'shed' => 0];

        for ($i = 0; $i < 150; $i++) {
            $request = Request::create('/api/data', 'POST');
            $request->headers['X-Priority'] = $i % 10 === 0 ? 'high' : 'low';

            $response = $this->app->dispatch($request);

            switch ($response->getStatusCode()) {
                case 200:
                    $results['success']++;
                    break;
                case 429:
                    $results['rate_limited']++;
                    break;
                case 503:
                    $results['shed']++;
                    break;
            }
        }

        // Verify middlewares are working
        $this->assertGreaterThan(0, $results['rate_limited'], 'Rate limiter should trigger');
        $this->assertGreaterThan(50, $results['success'], 'Some requests should succeed');
    }

    /**
     * Test performance monitoring integration
     */
    public function testPerformanceMonitoringIntegration(): void
    {
        $monitor = new PerformanceMonitor(
            [
                'sample_rate' => 1.0, // Sample all requests for testing
            ]
        );

        // Simulate request processing
        for ($i = 0; $i < 50; $i++) {
            $requestId = 'req-' . $i;
            $monitor->startRequest(
                $requestId,
                [
                    'path' => '/api/test',
                    'method' => 'GET',
                ]
            );

            // Simulate random processing time
            usleep(random_int(5000, 20000)); // 5-20ms

            $monitor->endRequest($requestId, random_int(0, 100) < 90 ? 200 : 500);
        }

        // Get metrics
        $metrics = $monitor->getPerformanceMetrics();

        // Verify metrics are collected
        $this->assertArrayHasKey('latency', $metrics);
        $this->assertArrayHasKey('throughput', $metrics);
        $this->assertArrayHasKey('memory', $metrics);

        $this->assertGreaterThan(0, $metrics['latency']['p50']);
        $this->assertGreaterThan(0, $metrics['throughput']['rps']);
        $this->assertGreaterThan(0, $metrics['throughput']['success_rate']);
    }

    /**
     * Test memory manager integration
     */
    public function testMemoryManagerIntegration(): void
    {
        $pool = new DynamicPool();
        $memoryManager = new MemoryManager(
            [
                'gc_strategy' => MemoryManager::STRATEGY_ADAPTIVE,
                'gc_threshold' => 0.7,
            ]
        );

        $memoryManager->setPool($pool);

        // Create memory pressure
        $objects = [];
        for ($i = 0; $i < 1000; $i++) {
            $objects[] = str_repeat('x', 1024); // 1KB each

            if ($i % 100 === 0) {
                $memoryManager->check();
            }
        }

        // Verify memory manager is tracking
        $status = $memoryManager->getStatus();
        $this->assertArrayHasKey('usage', $status);
        $this->assertArrayHasKey('gc', $status);
        $this->assertArrayHasKey('pressure', $status);
    }

    /**
     * Test factory with pooling integration
     */
    public function testFactoryWithPoolingIntegration(): void
    {
        OptimizedHttpFactory::enablePooling();

        // Create many requests
        $requests = [];
        for ($i = 0; $i < 100; $i++) {
            $requests[] = OptimizedHttpFactory::createServerRequest('GET', '/test');
        }

        $poolStats = OptimizedHttpFactory::getPoolStats();
        $this->assertGreaterThan(0, $poolStats['creation_stats']['requests_created']);

        // Return to pool (simulate cleanup)
        $requests = [];
        gc_collect_cycles();

        // Create more requests - should reuse from pool
        for ($i = 0; $i < 50; $i++) {
            $requests[] = OptimizedHttpFactory::createServerRequest('GET', '/test2');
        }

        $newStats = OptimizedHttpFactory::getPoolStats();
        $this->assertGreaterThan($poolStats['usage']['request'], $newStats['efficiency']['request']);
    }

    /**
     * Test distributed pool manager (mock)
     */
    public function testDistributedPoolManagerMock(): void
    {
        $this->markTestSkipped('Distributed pool requires Redis');

        $manager = new DistributedPoolManager(
            [
                'coordination' => 'redis',
                'namespace' => 'test:pools',
            ]
        );

        $localPool = new DynamicPool();
        $manager->setLocalPool($localPool);

        // Test basic operations
        $status = $manager->getStatus();
        $this->assertArrayHasKey('instance_id', $status);
        $this->assertArrayHasKey('is_leader', $status);
        $this->assertArrayHasKey('metrics', $status);
    }

    /**
     * Test end-to-end high-performance scenario
     */
    public function testEndToEndHighPerformanceScenario(): void
    {
        // Enable extreme performance mode
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_EXTREME);

        // Configure application
        $this->app->middleware('load-shedder');
        $this->app->middleware('circuit-breaker');

        // Add routes
        $this->app->get(
            '/api/users/:id',
            function ($req, $res, $id) {
                return $res->json(['id' => $id, 'name' => 'User ' . $id]);
            }
        );

        $this->app->post(
            '/api/process',
            function ($req, $res) {
            // Simulate heavy processing
                usleep(5000);
                return $res->json(['processed' => true]);
            }
        );

        // Run scenario
        $results = [];
        $startTime = microtime(true);

        for ($i = 0; $i < 500; $i++) {
            // Mix of read and write operations
            if ($i % 3 === 0) {
                $request = Request::create('/api/users/' . $i, 'GET');
            } else {
                $request = Request::create('/api/process', 'POST');
            }

            $response = $this->app->dispatch($request);
            $results[] = [
                'status' => $response->getStatusCode(),
                'time' => microtime(true),
            ];
        }

        $duration = microtime(true) - $startTime;
        $throughput = count($results) / $duration;

        // Verify performance
        $this->assertGreaterThan(100, $throughput, 'Should handle >100 req/s');

        // Check monitoring data
        $monitor = HighPerformanceMode::getMonitor();
        $metrics = $monitor->getLiveMetrics();

        $this->assertGreaterThan(0, $metrics['current_load']);
        $this->assertLessThan(1, $metrics['memory_pressure']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up
        HighPerformanceMode::disable();
        OptimizedHttpFactory::disablePooling();
        gc_collect_cycles();
    }
}
