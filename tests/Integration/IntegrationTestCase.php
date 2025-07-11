<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Performance\HighPerformanceMode;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

/**
 * Base class for integration tests
 *
 * Provides common utilities and setup for integration testing scenarios
 *
 * @group integration
 */
abstract class IntegrationTestCase extends TestCase
{
    protected Application $app;
    protected PerformanceCollector $performance;
    protected array $testConfig = [];
    protected array $performanceMetrics = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeApplication();
        $this->setupTestEnvironment();
        $this->startPerformanceCollection();
    }

    protected function tearDown(): void
    {
        $this->collectPerformanceMetrics();
        $this->cleanupTestEnvironment();
        parent::tearDown();
    }

    /**
     * Initialize application with test configuration
     */
    protected function initializeApplication(): void
    {
        $this->app = new Application();

        // Apply test-specific configuration
        if (!empty($this->testConfig)) {
            $this->applyTestConfiguration($this->testConfig);
        }
    }

    /**
     * Setup test environment with clean state
     */
    protected function setupTestEnvironment(): void
    {
        // Reset performance systems
        HighPerformanceMode::disable();
        JsonBufferPool::clearPools();

        // Clear any global state
        $this->clearGlobalState();
    }

    /**
     * Start performance data collection
     */
    protected function startPerformanceCollection(): void
    {
        $this->performance = new PerformanceCollector();
        $this->performance->startCollection();
    }

    /**
     * Collect performance metrics for analysis
     */
    protected function collectPerformanceMetrics(): void
    {
        if (isset($this->performance)) {
            $this->performanceMetrics = $this->performance->stopCollection();
        }
    }

    /**
     * Cleanup test environment
     */
    protected function cleanupTestEnvironment(): void
    {
        // Disable performance features
        HighPerformanceMode::disable();
        JsonBufferPool::clearPools();

        // Force garbage collection
        gc_collect_cycles();
    }

    /**
     * Simulate HTTP request to application
     */
    protected function simulateRequest(
        string $method,
        string $path,
        array $data = [],
        array $headers = []
    ): TestResponse {
        $client = new TestHttpClient($this->app);
        return $client->request(
            $method,
            $path,
            [
                'data' => $data,
                'headers' => $headers
            ]
        );
    }

    /**
     * Enable high performance mode with specified profile
     */
    protected function enableHighPerformanceMode(string $profile = 'HIGH'): void
    {
        $profileConstant = match ($profile) {
            'HIGH' => HighPerformanceMode::PROFILE_HIGH,
            'EXTREME' => HighPerformanceMode::PROFILE_EXTREME,
            'BALANCED' => HighPerformanceMode::PROFILE_BALANCED ?? 'BALANCED',
            default => HighPerformanceMode::PROFILE_HIGH
        };

        HighPerformanceMode::enable($profileConstant);
    }

    /**
     * Measure execution time of a callback
     */
    protected function measureExecutionTime(callable $callback): float
    {
        $start = microtime(true);
        $callback();
        return (microtime(true) - $start) * 1000; // Convert to milliseconds
    }

    /**
     * Assert performance metrics are within acceptable limits
     */
    protected function assertPerformanceWithinLimits(array $metrics, array $limits): void
    {
        foreach ($limits as $metric => $limit) {
            $this->assertArrayHasKey($metric, $metrics, "Metric '{$metric}' not found in performance data");

            if (isset($limit['max'])) {
                $this->assertLessThanOrEqual(
                    $limit['max'],
                    $metrics[$metric],
                    "Metric '{$metric}' ({$metrics[$metric]}) exceeds maximum limit ({$limit['max']})"
                );
            }

            if (isset($limit['min'])) {
                $this->assertGreaterThanOrEqual(
                    $limit['min'],
                    $metrics[$metric],
                    "Metric '{$metric}' ({$metrics[$metric]}) below minimum limit ({$limit['min']})"
                );
            }
        }
    }

    /**
     * Create test server for advanced testing scenarios
     */
    protected function createTestServer(array $config = []): TestServer
    {
        return new TestServer($this->app, $config);
    }

    /**
     * Generate concurrent requests for load testing
     */
    protected function simulateConcurrentRequests(array $requests): array
    {
        $client = new TestHttpClient($this->app);
        return $client->concurrentRequests($requests);
    }

    /**
     * Apply test configuration to application
     */
    protected function applyTestConfiguration(array $config): void
    {
        // This would integrate with application's configuration system
        // For now, store in test config for manual application
        $this->testConfig = array_merge($this->testConfig, $config);
    }

    /**
     * Clear global state between tests
     */
    protected function clearGlobalState(): void
    {
        // Clear any static variables or global state
        // Reset error handlers if needed
    }

    /**
     * Create large JSON payload for testing
     */
    protected function createLargeJsonPayload(int $elementCount = 100): array
    {
        return array_fill(
            0,
            $elementCount,
            [
                'id' => random_int(1, 10000),
                'name' => 'Test Item ' . uniqid(),
                'description' => str_repeat('This is test data ', 10),
                'metadata' => [
                    'created_at' => date('Y-m-d H:i:s'),
                    'tags' => ['test', 'integration', 'performance'],
                    'stats' => [
                        'views' => random_int(1, 1000),
                        'likes' => random_int(1, 100),
                        'shares' => random_int(1, 50)
                    ]
                ]
            ]
        );
    }

    /**
     * Assert JSON response structure and content
     */
    protected function assertJsonResponseStructure(TestResponse $response, array $expectedStructure): void
    {
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));

        $data = $response->getJsonData();
        $this->assertIsArray($data);

        foreach ($expectedStructure as $key => $type) {
            $this->assertArrayHasKey($key, $data, "Expected key '{$key}' not found in response");

            switch ($type) {
                case 'array':
                    $this->assertIsArray($data[$key], "Expected '{$key}' to be array");
                    break;
                case 'string':
                    $this->assertIsString($data[$key], "Expected '{$key}' to be string");
                    break;
                case 'int':
                    $this->assertIsInt($data[$key], "Expected '{$key}' to be integer");
                    break;
                case 'float':
                    $this->assertIsFloat($data[$key], "Expected '{$key}' to be float");
                    break;
                case 'bool':
                    $this->assertIsBool($data[$key], "Expected '{$key}' to be boolean");
                    break;
            }
        }
    }

    /**
     * Create middleware stack for testing
     */
    protected function createMiddlewareStack(array $middlewares): array
    {
        $stack = [];

        foreach ($middlewares as $middleware) {
            if (is_string($middleware)) {
                // Create middleware by name
                $stack[] = $this->createNamedMiddleware($middleware);
            } elseif (is_callable($middleware)) {
                $stack[] = $middleware;
            } else {
                throw new \InvalidArgumentException('Invalid middleware type');
            }
        }

        return $stack;
    }

    /**
     * Create named middleware for testing
     */
    protected function createNamedMiddleware(string $name): callable
    {
        return match ($name) {
            'logging' => function ($req, $res, $next) {
                // Logging middleware for tests (output suppressed for CI/CD)
                return $next($req, $res);
            },
            'timing' => function ($req, $res, $next) {
                $start = microtime(true);
                $response = $next($req, $res);
                $duration = (microtime(true) - $start) * 1000;
                return $response->header('X-Response-Time', $duration . 'ms');
            },
            'auth' => function ($req, $res, $next) {
                if (!$req->header('Authorization')) {
                    return $res->status(401)->json(['error' => 'Unauthorized']);
                }
                return $next($req, $res);
            },
            default => function ($req, $res, $next) {
                return $next($req, $res);
            }
        };
    }

    /**
     * Assert memory usage is within acceptable limits
     */
    protected function assertMemoryUsageWithinLimits(int $maxMemoryMB = 50): void
    {
        $memoryUsage = memory_get_usage(true) / 1024 / 1024; // Convert to MB
        $this->assertLessThan(
            $maxMemoryMB,
            $memoryUsage,
            "Memory usage ({$memoryUsage}MB) exceeds limit ({$maxMemoryMB}MB)"
        );
    }

    /**
     * Get current performance metrics
     */
    protected function getCurrentPerformanceMetrics(): array
    {
        return $this->performance->getCurrentMetrics();
    }
}
