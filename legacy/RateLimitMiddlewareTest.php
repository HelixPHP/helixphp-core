<?php

namespace PivotPHP\Core\Tests\Core;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Middleware\Core\RateLimitMiddleware;

class RateLimitMiddlewareTest extends TestCase
{
    public function testMiddlewareCreation(): void
    {
        $middleware = new RateLimitMiddleware([
            'max' => 100,
            'window' => 60
        ]);

        $this->assertInstanceOf(RateLimitMiddleware::class, $middleware);
    }

    public function testDefaultOptions(): void
    {
        $middleware = new RateLimitMiddleware();

        $this->assertInstanceOf(RateLimitMiddleware::class, $middleware);
    }

    public function testInvokeMethod(): void
    {
        $middleware = new RateLimitMiddleware([
            'max' => 5,
            'window' => 60
        ]);

        $this->assertTrue(method_exists($middleware, '__invoke'));
    }
}
