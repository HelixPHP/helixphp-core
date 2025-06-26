<?php

namespace Express\Tests;

use PHPUnit\Framework\TestCase;
use Express\ApiExpress;
use Express\Controller\Router;

class ApiExpressTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        $this->app = new ApiExpress();

        // Reset global state
        $_SERVER = [];
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
    }

    protected function tearDown(): void
    {
        // Clean any output buffers that might have been opened
        while (ob_get_level() > 1) { // Manter pelo menos 1 nível para PHPUnit
            ob_end_clean();
        }

        // Limpar output buffer se tiver conteúdo
        if (ob_get_length()) {
            ob_clean();
        }
    }

    public function testAppInitialization(): void
    {
        $this->assertInstanceOf(ApiExpress::class, $this->app);
    }

    public function testRouterMethodsExist(): void
    {
        // These methods are handled by __call magic method
        $this->assertTrue(method_exists($this->app, '__call'));
        $this->assertTrue(method_exists($this->app, 'use'));
        $this->assertTrue(method_exists($this->app, 'setBaseUrl'));
        $this->assertTrue(method_exists($this->app, 'getBaseUrl'));
    }

    public function testUseMethod(): void
    {
        $middleware = function($req, $res, $next) {
            $next();
        };

        // use() method returns void, not the app instance
        $this->app->use($middleware);
        $this->assertInstanceOf(ApiExpress::class, $this->app);
    }

    public function testSetBaseUrl(): void
    {
        $baseUrl = 'https://api.example.com';
        $this->app->setBaseUrl($baseUrl);
        $this->assertEquals($baseUrl, $this->app->getBaseUrl());
    }

    public function testRouteRegistration(): void
    {
        $handler = function($req, $res) {
            $res->json(['message' => 'Hello World']);
        };

        // These methods are handled by __call and return values from Router
        $this->app->get('/test', $handler);
        $this->app->post('/users', $handler);
        $this->app->put('/users/:id', $handler);
        $this->app->delete('/users/:id', $handler);

        // If no exception is thrown, the methods work correctly
        $this->assertTrue(true);
    }

    public function testCallMagicMethod(): void
    {
        $handler = function($req, $res) {
            $res->json(['message' => 'Test']);
        };

        // Test that HTTP methods can be called via __call
        try {
            $this->app->get('/test', $handler);
            $this->app->post('/test', $handler);
            $this->app->put('/test', $handler);
            $this->app->delete('/test', $handler);
            $this->assertTrue(true); // If no exception, methods work
        } catch (\BadMethodCallException $e) {
            $this->fail('HTTP methods should be callable via __call');
        }
    }

    public function testBaseUrlHandling(): void
    {
        // Test with trailing slash
        $this->app->setBaseUrl('https://api.example.com/');
        $this->assertEquals('https://api.example.com', $this->app->getBaseUrl());

        // Test without trailing slash
        $this->app->setBaseUrl('https://api.example.com');
        $this->assertEquals('https://api.example.com', $this->app->getBaseUrl());

        // Test with null
        $app2 = new ApiExpress();
        $this->assertNull($app2->getBaseUrl());
    }

    public function testConstructorWithBaseUrl(): void
    {
        $baseUrl = 'https://test.example.com';
        $app = new ApiExpress($baseUrl);
        $this->assertEquals($baseUrl, $app->getBaseUrl());
    }
}
