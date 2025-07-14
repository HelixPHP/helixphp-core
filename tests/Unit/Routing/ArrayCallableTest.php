<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Routing\Router;
use PivotPHP\Core\Tests\Unit\Routing\TestController;

/**
 * Tests for array callable routing functionality
 */
class ArrayCallableTest extends TestCase
{
    private TestController $controller;

    protected function setUp(): void
    {
        Router::clear();
        $this->controller = new TestController();
    }

    protected function tearDown(): void
    {
        Router::clear();
    }

    /**
     * @test
     */
    public function testArrayCallableWithInstanceMethod(): void
    {
        Router::get('/test', [$this->controller, 'index']);

        $route = Router::identify('GET', '/test');

        $this->assertNotNull($route);
        $this->assertEquals('/test', $route['path']);
        $this->assertIsCallable($route['handler']);

        // Verify the handler is the correct array callable
        $this->assertIsArray($route['handler']);
        $this->assertCount(2, $route['handler']);
        $this->assertSame($this->controller, $route['handler'][0]);
        $this->assertEquals('index', $route['handler'][1]);
    }

    /**
     * @test
     */
    public function testArrayCallableWithStaticMethod(): void
    {
        Router::get('/static', [TestController::class, 'staticMethod']);

        $route = Router::identify('GET', '/static');

        $this->assertNotNull($route);
        $this->assertEquals('/static', $route['path']);
        $this->assertIsCallable($route['handler']);

        // Verify the handler is the correct array callable
        $this->assertIsArray($route['handler']);
        $this->assertCount(2, $route['handler']);
        $this->assertEquals(TestController::class, $route['handler'][0]);
        $this->assertEquals('staticMethod', $route['handler'][1]);
    }

    /**
     * @test
     */
    public function testArrayCallableWithParameters(): void
    {
        Router::get('/users/:userId/posts/:postId', [$this->controller, 'withParameters']);

        $route = Router::identify('GET', '/users/123/posts/456');

        $this->assertNotNull($route);
        $this->assertEquals('/users/:userId/posts/:postId', $route['path']);
        $this->assertIsCallable($route['handler']);

        // Verify parameters are extracted
        $this->assertArrayHasKey('matched_params', $route);
        $this->assertEquals('123', $route['matched_params']['userId']);
        $this->assertEquals('456', $route['matched_params']['postId']);
    }

    /**
     * @test
     */
    public function testHealthCheckRoute(): void
    {
        // This is the specific case mentioned by the user
        Router::get('/health', [$this->controller, 'healthCheck']);

        $route = Router::identify('GET', '/health');

        $this->assertNotNull($route);
        $this->assertEquals('/health', $route['path']);
        $this->assertIsCallable($route['handler']);

        // Verify it's the correct method
        $this->assertIsArray($route['handler']);
        $this->assertEquals('healthCheck', $route['handler'][1]);
    }

    /**
     * @test
     */
    public function testMultipleArrayCallables(): void
    {
        Router::get('/method1', [$this->controller, 'index']);
        Router::get('/method2', [$this->controller, 'healthCheck']);
        Router::get('/static', [TestController::class, 'staticMethod']);

        $route1 = Router::identify('GET', '/method1');
        $route2 = Router::identify('GET', '/method2');
        $route3 = Router::identify('GET', '/static');

        $this->assertNotNull($route1);
        $this->assertNotNull($route2);
        $this->assertNotNull($route3);

        $this->assertEquals('index', $route1['handler'][1]);
        $this->assertEquals('healthCheck', $route2['handler'][1]);
        $this->assertEquals('staticMethod', $route3['handler'][1]);
    }

    /**
     * @test
     */
    public function testArrayCallableInGroup(): void
    {
        Router::group(
            '/api/v1',
            function () {
                Router::get('/api/v1/health', [$this->controller, 'healthCheck']);
                Router::get('/api/v1/users/:id', [$this->controller, 'show']);
            }
        );

        $healthRoute = Router::identify('GET', '/api/v1/health');
        $userRoute = Router::identify('GET', '/api/v1/users/123');

        $this->assertNotNull($healthRoute);
        $this->assertNotNull($userRoute);

        $this->assertEquals('/api/v1/health', $healthRoute['path']);
        $this->assertEquals('/api/v1/users/:id', $userRoute['path']);

        $this->assertIsCallable($healthRoute['handler']);
        $this->assertIsCallable($userRoute['handler']);
    }

    /**
     * @test
     */
    public function testMixedCallableTypes(): void
    {
        // Mix different callable types
        Router::get(
            '/closure',
            function ($req, $res) {
                return 'closure';
            }
        );

        Router::get('/array', [$this->controller, 'index']);

        Router::get('/static', [TestController::class, 'staticMethod']);

        $closureRoute = Router::identify('GET', '/closure');
        $arrayRoute = Router::identify('GET', '/array');
        $staticRoute = Router::identify('GET', '/static');

        $this->assertNotNull($closureRoute);
        $this->assertNotNull($arrayRoute);
        $this->assertNotNull($staticRoute);

        // All should be callable
        $this->assertIsCallable($closureRoute['handler']);
        $this->assertIsCallable($arrayRoute['handler']);
        $this->assertIsCallable($staticRoute['handler']);

        // But different types
        $this->assertInstanceOf(\Closure::class, $closureRoute['handler']);
        $this->assertIsArray($arrayRoute['handler']);
        $this->assertIsArray($staticRoute['handler']);
    }

    /**
     * @test
     */
    public function testInvalidArrayCallable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Route handler validation failed: Method');

        // Try to register an invalid callable
        Router::get('/invalid', [$this->controller, 'nonExistentMethod']);
    }

    /**
     * @test
     */
    public function testInvalidStaticCallable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Route handler validation failed: Static method');

        // Try to register an invalid static callable
        Router::get('/invalid', [TestController::class, 'nonExistentStaticMethod']);
    }

    /**
     * @test
     */
    public function testArrayCallableWithMiddleware(): void
    {
        $middlewareCalled = false;
        $middleware = function ($req, $res, $next) use (&$middlewareCalled) {
            $middlewareCalled = true;
            return $next($req, $res);
        };

        Router::get('/with-middleware', [$this->controller, 'index'], [], $middleware);

        $route = Router::identify('GET', '/with-middleware');

        $this->assertNotNull($route);
        $this->assertIsCallable($route['handler']);
        $this->assertArrayHasKey('middlewares', $route);
        $this->assertCount(1, $route['middlewares']);
        $this->assertIsCallable($route['middlewares'][0]);
    }

    /**
     * @test
     */
    public function testRouterAddMethodDirectly(): void
    {
        // Test the Router::add method directly
        Router::add('GET', '/direct', [$this->controller, 'index']);

        $route = Router::identify('GET', '/direct');

        $this->assertNotNull($route);
        $this->assertEquals('/direct', $route['path']);
        $this->assertEquals('GET', $route['method']);
        $this->assertIsCallable($route['handler']);
        $this->assertIsArray($route['handler']);
    }

    /**
     * @test
     */
    public function testCallableValidation(): void
    {
        // These should all work
        $validCallables = [
            [$this->controller, 'index'],
            [TestController::class, 'staticMethod'],
            function () {
                return 'test';
            },
            'trim' // Built-in function
        ];

        foreach ($validCallables as $i => $callable) {
            $this->assertTrue(is_callable($callable), "Callable {$i} should be valid");
        }

        // These should fail
        $invalidCallables = [
            [$this->controller, 'nonExistent'],
            [TestController::class, 'nonExistentStatic'],
            ['NonExistentClass', 'method'],
            'nonExistentFunction'
        ];

        foreach ($invalidCallables as $i => $callable) {
            $this->assertFalse(is_callable($callable), "Callable {$i} should be invalid");
        }
    }

    /**
     * @test
     */
    public function testRouteRegistrationPerformance(): void
    {
        $start = microtime(true);

        // Register 100 routes with array callables
        for ($i = 0; $i < 100; $i++) {
            Router::get("/test{$i}", [$this->controller, 'index']);
        }

        $end = microtime(true);
        $duration = ($end - $start) * 1000; // Convert to milliseconds

        // Should be very fast (less than 50ms even on slow systems)
        $this->assertLessThan(50, $duration, "Route registration took too long: {$duration}ms");

        // Verify all routes were registered
        $this->assertCount(100, Router::getRoutes());
    }
}
