<?php

namespace Express\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Express\Controller\Router;

class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset router state between tests
        $reflection = new \ReflectionClass(Router::class);
        
        $routesProperty = $reflection->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routesProperty->setValue([]);
        
        $prevPathProperty = $reflection->getProperty('prev_path');
        $prevPathProperty->setAccessible(true);
        $prevPathProperty->setValue('');
        
        $currentGroupPrefixProperty = $reflection->getProperty('current_group_prefix');
        $currentGroupPrefixProperty->setAccessible(true);
        $currentGroupPrefixProperty->setValue('');
    }

    public function testAddHttpMethod(): void
    {
        // Test adding a custom HTTP method
        Router::addHttpMethod('PATCH');
        
        $reflection = new \ReflectionClass(Router::class);
        $methodsProperty = $reflection->getProperty('httpMethodsAccepted');
        $methodsProperty->setAccessible(true);
        $methods = $methodsProperty->getValue();
        
        $this->assertContains('PATCH', $methods);
        
        // Test adding duplicate method (should not duplicate)
        $initialCount = count($methods);
        Router::addHttpMethod('PATCH');
        $methodsAfter = $methodsProperty->getValue();
        $this->assertEquals($initialCount, count($methodsAfter));
    }

    public function testAddHttpMethodCaseInsensitive(): void
    {
        Router::addHttpMethod('custom');
        
        $reflection = new \ReflectionClass(Router::class);
        $methodsProperty = $reflection->getProperty('httpMethodsAccepted');
        $methodsProperty->setAccessible(true);
        $methods = $methodsProperty->getValue();
        
        $this->assertContains('CUSTOM', $methods);
        $this->assertNotContains('custom', $methods);
    }

    public function testRegisterRoute(): void
    {
        $handler = function($req, $res) {
            return 'test';
        };
        
        Router::register('GET', '/test', $handler);
        
        $reflection = new \ReflectionClass(Router::class);
        $routesProperty = $reflection->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routes = $routesProperty->getValue();
        
        $this->assertCount(1, $routes);
        $this->assertEquals('GET', $routes[0]['method']);
        $this->assertEquals('/test', $routes[0]['path']);
        $this->assertEquals($handler, $routes[0]['handler']);
    }

    public function testRegisterRouteWithMiddleware(): void
    {
        $handler = function($req, $res) {
            return 'test';
        };
        
        $middleware = function($req, $res, $next) {
            $next();
        };
        
        Router::register('POST', '/users', $handler, [$middleware]);
        
        $reflection = new \ReflectionClass(Router::class);
        $routesProperty = $reflection->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routes = $routesProperty->getValue();
        
        $this->assertCount(1, $routes);
        $this->assertEquals('POST', $routes[0]['method']);
        $this->assertEquals('/users', $routes[0]['path']);
        $this->assertEquals($handler, $routes[0]['handler']);
        $this->assertCount(1, $routes[0]['middlewares']);
        $this->assertEquals($middleware, $routes[0]['middlewares'][0]);
    }

    public function testRouteGroup(): void
    {
        $groupHandler = function() {
            Router::register('GET', '/users', function($req, $res) {
                return 'users';
            });
            Router::register('POST', '/posts', function($req, $res) {
                return 'posts';
            });
        };
        
        Router::group('/api', $groupHandler);
        
        $reflection = new \ReflectionClass(Router::class);
        $routesProperty = $reflection->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routes = $routesProperty->getValue();
        
        $this->assertCount(2, $routes);
        $this->assertEquals('/api/users', $routes[0]['path']);
        $this->assertEquals('/api/posts', $routes[1]['path']);
    }

    public function testRouteGroupWithMiddleware(): void
    {
        $groupMiddleware = function($req, $res, $next) {
            $next();
        };
        
        $groupHandler = function() {
            Router::register('GET', '/test', function($req, $res) {
                return 'test';
            });
        };
        
        Router::group('/api', $groupHandler, [$groupMiddleware]);
        
        $reflection = new \ReflectionClass(Router::class);
        $routesProperty = $reflection->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routes = $routesProperty->getValue();
        
        $this->assertCount(1, $routes);
        $this->assertEquals('/api/test', $routes[0]['path']);
        $this->assertContains($groupMiddleware, $routes[0]['middlewares']);
    }

    public function testNestedGroups(): void
    {
        Router::group('/api', function() {
            Router::group('/v1', function() {
                Router::register('GET', '/users', function($req, $res) {
                    return 'users';
                });
            });
        });
        
        $reflection = new \ReflectionClass(Router::class);
        $routesProperty = $reflection->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routes = $routesProperty->getValue();
        
        $this->assertCount(1, $routes);
        $this->assertEquals('/api/v1/users', $routes[0]['path']);
    }

    public function testFindRoute(): void
    {
        $handler = function($req, $res) {
            return 'found';
        };
        
        Router::register('GET', '/users/:id', $handler);
        
        $result = Router::find('GET', '/users/123');
        
        $this->assertNotNull($result);
        $this->assertEquals($handler, $result['handler']);
        $this->assertEquals('/users/:id', $result['path']);
    }

    public function testFindRouteNotFound(): void
    {
        $result = Router::find('GET', '/nonexistent');
        
        $this->assertNull($result);
    }

    public function testRouteWithParameters(): void
    {
        $handler = function($req, $res) {
            return 'user';
        };
        
        Router::register('GET', '/users/:id/posts/:postId', $handler);
        
        $result = Router::find('GET', '/users/123/posts/456');
        
        $this->assertNotNull($result);
        $this->assertEquals($handler, $result['handler']);
    }

    public function testRouteWithOptionalParameters(): void
    {
        $handler = function($req, $res) {
            return 'search';
        };
        
        Router::register('GET', '/search/:term?', $handler);
        
        // Test with parameter
        $result1 = Router::find('GET', '/search/php');
        $this->assertNotNull($result1);
        
        // Test without parameter
        $result2 = Router::find('GET', '/search');
        $this->assertNotNull($result2);
    }

    public function testRouteMethodCaseSensitivity(): void
    {
        $handler = function($req, $res) {
            return 'test';
        };
        
        Router::register('GET', '/test', $handler);
        
        // Should find with exact case
        $result1 = Router::find('GET', '/test');
        $this->assertNotNull($result1);
        
        // Should find with different case
        $result2 = Router::find('get', '/test');
        $this->assertNotNull($result2);
    }

    public function testGetAllRoutes(): void
    {
        Router::register('GET', '/route1', function() {});
        Router::register('POST', '/route2', function() {});
        Router::register('PUT', '/route3', function() {});
        
        $routes = Router::getAllRoutes();
        
        $this->assertCount(3, $routes);
        $this->assertEquals('GET', $routes[0]['method']);
        $this->assertEquals('POST', $routes[1]['method']);
        $this->assertEquals('PUT', $routes[2]['method']);
    }

    public function testWildcardRoutes(): void
    {
        $handler = function($req, $res) {
            return 'wildcard';
        };
        
        Router::register('GET', '/api/*', $handler);
        
        $result1 = Router::find('GET', '/api/users');
        $this->assertNotNull($result1);
        
        $result2 = Router::find('GET', '/api/posts/123');
        $this->assertNotNull($result2);
        
        $result3 = Router::find('GET', '/other/path');
        $this->assertNull($result3);
    }

    public function testRouteWithQueryParameters(): void
    {
        $handler = function($req, $res) {
            return 'query';
        };
        
        Router::register('GET', '/search', $handler);
        
        // Should match regardless of query parameters
        $result = Router::find('GET', '/search?q=test&page=1');
        $this->assertNotNull($result);
    }

    public function testEmptyPath(): void
    {
        $handler = function($req, $res) {
            return 'root';
        };
        
        Router::register('GET', '/', $handler);
        
        $result = Router::find('GET', '/');
        $this->assertNotNull($result);
    }

    public function testMultipleMiddlewaresInGroup(): void
    {
        $middleware1 = function($req, $res, $next) { $next(); };
        $middleware2 = function($req, $res, $next) { $next(); };
        
        Router::group('/api', function() {
            Router::register('GET', '/test', function($req, $res) {
                return 'test';
            });
        }, [$middleware1, $middleware2]);
        
        $reflection = new \ReflectionClass(Router::class);
        $routesProperty = $reflection->getProperty('routes');
        $routesProperty->setAccessible(true);
        $routes = $routesProperty->getValue();
        
        $this->assertCount(1, $routes);
        $this->assertCount(2, $routes[0]['middlewares']);
        $this->assertContains($middleware1, $routes[0]['middlewares']);
        $this->assertContains($middleware2, $routes[0]['middlewares']);
    }
}
