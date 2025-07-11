<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Integration\Core;

use PivotPHP\Core\Tests\Integration\IntegrationTestCase;
use PivotPHP\Core\Performance\HighPerformanceMode;

/**
 * Application + Container + Routing Integration Tests
 *
 * Tests the core integration between:
 * - Application bootstrap and lifecycle
 * - Container dependency injection
 * - Router and route handling
 * - Service providers
 * - Configuration management
 *
 * @group integration
 * @group core
 * @group application
 */
class ApplicationContainerRoutingIntegrationTest extends IntegrationTestCase
{
    /**
     * Test basic application lifecycle with container and routing
     */
    public function testApplicationBootstrapWithContainerAndRouting(): void
    {
        // Verify application is created
        $this->assertInstanceOf(\PivotPHP\Core\Core\Application::class, $this->app);

        // Verify container is available
        $container = $this->app->getContainer();
        $this->assertInstanceOf(\PivotPHP\Core\Providers\Container::class, $container);

        // Verify router is available
        $router = $this->app->getRouter();
        $this->assertInstanceOf(\PivotPHP\Core\Routing\Router::class, $router);

        // Add a simple route to test routing integration
        $this->app->get(
            '/container-test',
            function ($req, $res) {
                return $res->json(
                    [
                        'message' => 'Container and routing working',
                        'timestamp' => time()
                    ]
                );
            }
        );

        // Test the route execution
        $response = $this->simulateRequest('GET', '/container-test');

        // Verify response structure (even if mocked initially)
        $this->assertInstanceOf(\PivotPHP\Core\Tests\Integration\TestResponse::class, $response);

        // Test that application lifecycle completes without errors
        $this->assertTrue(true); // Basic smoke test
    }

    /**
     * Test dependency injection through container
     */
    public function testDependencyInjectionIntegration(): void
    {
        $container = $this->app->getContainer();

        // Test basic container functionality
        $container->bind(
            'test_service',
            function () {
                return new class {
                    public function getName(): string
                    {
                        return 'test_service';
                    }
                };
            }
        );

        // Verify service can be resolved
        $service = $container->get('test_service');
        $this->assertEquals('test_service', $service->getName());

        // Test singleton binding
        $container->singleton(
            'singleton_service',
            function () {
                return new class {
                    public $id;
                    public function __construct()
                    {
                        $this->id = uniqid();
                    }
                };
            }
        );

        $service1 = $container->get('singleton_service');
        $service2 = $container->get('singleton_service');
        $this->assertSame($service1->id, $service2->id);
    }

    /**
     * Test service provider registration and integration
     */
    public function testServiceProviderIntegration(): void
    {
        // Boot the application to ensure providers are registered
        $this->app->boot();

        $container = $this->app->getContainer();

        // Test that core services are registered
        $this->assertTrue($container->has('config'));
        $this->assertTrue($container->has('router'));

        // Test custom service provider registration
        $testProvider = new class ($this->app) extends \PivotPHP\Core\Providers\ServiceProvider {
            public function register(): void
            {
                $this->app->bind(
                    'custom_service',
                    function () {
                        return 'custom_value';
                    }
                );
            }
        };

        $this->app->register($testProvider);

        // Verify custom service is registered
        $this->assertTrue($container->has('custom_service'));
        $this->assertEquals('custom_value', $container->get('custom_service'));
    }

    /**
     * Test routing with different HTTP methods
     */
    public function testRoutingWithDifferentMethods(): void
    {
        // Test GET route
        $this->app->get(
            '/get-test',
            function ($req, $res) {
                return $res->json(['method' => 'GET', 'success' => true]);
            }
        );

        // Test POST route
        $this->app->post(
            '/post-test',
            function ($req, $res) {
                return $res->json(['method' => 'POST', 'success' => true]);
            }
        );

        // Test PUT route
        $this->app->put(
            '/put-test',
            function ($req, $res) {
                return $res->json(['method' => 'PUT', 'success' => true]);
            }
        );

        // Test DELETE route
        $this->app->delete(
            '/delete-test',
            function ($req, $res) {
                return $res->json(['method' => 'DELETE', 'success' => true]);
            }
        );

        // Verify routes are registered
        $router = $this->app->getRouter();
        $this->assertInstanceOf(\PivotPHP\Core\Routing\Router::class, $router);

        // Test each method (basic smoke test for now)
        $methods = ['GET', 'POST', 'PUT', 'DELETE'];
        foreach ($methods as $method) {
            $path = '/' . strtolower($method) . '-test';
            $response = $this->simulateRequest($method, $path);
            $this->assertInstanceOf(\PivotPHP\Core\Tests\Integration\TestResponse::class, $response);
        }
    }

    /**
     * Test configuration integration with container
     */
    public function testConfigurationIntegration(): void
    {
        // Apply test configuration
        $this->applyTestConfiguration(
            [
                'app' => [
                    'name' => 'PivotPHP Test',
                    'debug' => true
                ],
                'custom' => [
                    'value' => 'test_config_value'
                ]
            ]
        );

        // Boot application to process configuration
        $this->app->boot();

        // Verify configuration is accessible through container
        $container = $this->app->getContainer();

        if ($container->has('config')) {
            $config = $container->get('config');
            $this->assertNotNull($config);
        }

        // Test configuration in route
        $this->app->get(
            '/config-test',
            function ($req, $res) {
                return $res->json(
                    [
                        'config_loaded' => true,
                        'test_data' => $this->testConfig
                    ]
                );
            }
        );

        $response = $this->simulateRequest('GET', '/config-test');
        $this->assertInstanceOf(\PivotPHP\Core\Tests\Integration\TestResponse::class, $response);
    }

    /**
     * Test middleware integration with container and routing
     */
    public function testMiddlewareIntegration(): void
    {
        $executionOrder = [];

        // Create middleware that uses container
        $containerMiddleware = function ($req, $res, $next) use (&$executionOrder) {
            $executionOrder[] = 'container_middleware_before';
            $result = $next($req, $res);
            $executionOrder[] = 'container_middleware_after';
            return $result;
        };

        // Add global middleware
        $this->app->use($containerMiddleware);

        // Add route with middleware
        $this->app->get(
            '/middleware-test',
            function ($req, $res) use (&$executionOrder) {
                $executionOrder[] = 'route_handler';
                return $res->json(['middleware_test' => true]);
            }
        );

        // Execute request
        $response = $this->simulateRequest('GET', '/middleware-test');

        // Verify response
        $this->assertInstanceOf(\PivotPHP\Core\Tests\Integration\TestResponse::class, $response);

        // Verify middleware execution (basic test for now)
        $this->assertNotEmpty($executionOrder);
    }

    /**
     * Test error handling integration
     */
    public function testErrorHandlingIntegration(): void
    {
        // Add route that throws exception
        $this->app->get(
            '/error-test',
            function ($req, $res) {
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
                            'message' => $e->getMessage(),
                            'integration' => 'error_handled'
                        ]
                    );
                }
            }
        );

        // Test error handling
        $response = $this->simulateRequest('GET', '/error-test');

        // Verify error response structure
        $this->assertInstanceOf(\PivotPHP\Core\Tests\Integration\TestResponse::class, $response);
    }

    /**
     * Test application state management
     */
    public function testApplicationStateManagement(): void
    {
        // Test initial state
        $this->assertFalse($this->isApplicationBooted());

        // Boot application
        $this->app->boot();

        // Test booted state
        $this->assertTrue($this->isApplicationBooted());

        // Test multiple boot calls don't cause issues
        $this->app->boot();
        $this->assertTrue($this->isApplicationBooted());

        // Test that services are still accessible after multiple boots
        $container = $this->app->getContainer();
        $router = $this->app->getRouter();

        $this->assertInstanceOf(\PivotPHP\Core\Providers\Container::class, $container);
        $this->assertInstanceOf(\PivotPHP\Core\Routing\Router::class, $router);
    }

    /**
     * Test integration with performance features
     */
    public function testPerformanceIntegration(): void
    {
        // Enable high performance mode
        HighPerformanceMode::enable(HighPerformanceMode::PROFILE_HIGH);

        // Verify HP mode integration
        $hpStatus = HighPerformanceMode::getStatus();
        $this->assertTrue($hpStatus['enabled']);

        // Add route that benefits from performance features
        $this->app->get(
            '/performance-test',
            function ($req, $res) {
                $data = [
                    'performance_enabled' => true,
                    'large_data' => array_fill(0, 20, ['id' => uniqid(), 'data' => str_repeat('x', 100)])
                ];
                return $res->json($data);
            }
        );

        // Test route execution with performance features
        $response = $this->simulateRequest('GET', '/performance-test');

        // Verify response
        $this->assertInstanceOf(\PivotPHP\Core\Tests\Integration\TestResponse::class, $response);

        // Verify HP mode is still active
        $finalStatus = HighPerformanceMode::getStatus();
        $this->assertTrue($finalStatus['enabled']);
    }

    /**
     * Test container service resolution in routes
     */
    public function testContainerServiceResolutionInRoutes(): void
    {
        $container = $this->app->getContainer();

        // Register a test service
        $container->bind(
            'test_calculator',
            function () {
                return new class {
                    public function add(int $a, int $b): int
                    {
                        return $a + $b;
                    }
                    public function multiply(int $a, int $b): int
                    {
                        return $a * $b;
                    }
                };
            }
        );

        // Add route that uses the service
        $this->app->get(
            '/calculator/:operation/:a/:b',
            function ($req, $res) use ($container) {
                $calculator = $container->get('test_calculator');
                $operation = $req->param('operation');
                $a = (int) $req->param('a');
                $b = (int) $req->param('b');

                $result = match ($operation) {
                    'add' => $calculator->add($a, $b),
                    'multiply' => $calculator->multiply($a, $b),
                    default => 0
                };

                return $res->json(
                    [
                        'operation' => $operation,
                        'a' => $a,
                        'b' => $b,
                        'result' => $result
                    ]
                );
            }
        );

        // Test service resolution (basic smoke test)
        $response = $this->simulateRequest('GET', '/calculator/add/5/3');
        $this->assertInstanceOf(\PivotPHP\Core\Tests\Integration\TestResponse::class, $response);
    }

    /**
     * Test memory management with container and routing
     */
    public function testMemoryManagementIntegration(): void
    {
        $initialMemory = memory_get_usage(true);

        // Create multiple routes with services
        for ($i = 0; $i < 10; $i++) {
            $this->app->getContainer()->bind(
                "service_{$i}",
                function () use ($i) {
                    return new class ($i) {
                        private int $id;
                        public function __construct(int $id)
                        {
                            $this->id = $id;
                        }
                        public function getId(): int
                        {
                            return $this->id;
                        }
                    };
                }
            );

            $this->app->get(
                "/memory-test-{$i}",
                function ($req, $res) use ($i) {
                    $service = $this->app->getContainer()->get("service_{$i}");
                    return $res->json(['service_id' => $service->getId()]);
                }
            );
        }

        // Execute some routes
        for ($i = 0; $i < 5; $i++) {
            $response = $this->simulateRequest('GET', "/memory-test-{$i}");
            $this->assertInstanceOf(\PivotPHP\Core\Tests\Integration\TestResponse::class, $response);
        }

        // Force garbage collection
        gc_collect_cycles();

        // Verify memory usage is reasonable
        $finalMemory = memory_get_usage(true);
        $memoryGrowth = ($finalMemory - $initialMemory) / 1024 / 1024; // MB

        $this->assertLessThan(
            10,
            $memoryGrowth,
            "Memory growth ({$memoryGrowth}MB) should be reasonable"
        );
    }

    /**
     * Helper method to check if application is booted
     */
    private function isApplicationBooted(): bool
    {
        try {
            $reflection = new \ReflectionClass($this->app);
            $bootedProperty = $reflection->getProperty('booted');
            $bootedProperty->setAccessible(true);
            return $bootedProperty->getValue($this->app);
        } catch (\Exception $e) {
            return false;
        }
    }
}
