<?php

namespace PivotPHP\Core\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Middleware\MiddlewareStack;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Middleware\Core\BaseMiddleware;

class MiddlewareStackTest extends TestCase
{
    public function testBasicMiddlewareStack(): void
    {
        $stack = new MiddlewareStack();

        $this->assertInstanceOf(MiddlewareStack::class, $stack);
    }

    public function testMiddlewareStackAddMiddleware(): void
    {
        $stack = new MiddlewareStack();
        $middleware = new class extends BaseMiddleware {
            public function handle($request, $response, callable $next)
            {
                return $next($request, $response);
            }
        };

        $stack->add($middleware);

        $this->assertCount(1, $stack->getMiddlewares());
    }

    public function testMiddlewareStackHasMiddlewares(): void
    {
        $stack = new MiddlewareStack();

        $this->assertIsArray($stack->getMiddlewares());
        $this->assertCount(0, $stack->getMiddlewares());
    }

    public function testMiddlewareStackMultipleMiddlewares(): void
    {
        $stack = new MiddlewareStack();

        $middleware1 = new class extends BaseMiddleware {
            public function handle($request, $response, callable $next)
            {
                return $next($request, $response);
            }
        };

        $middleware2 = new class extends BaseMiddleware {
            public function handle($request, $response, callable $next)
            {
                return $next($request, $response);
            }
        };

        $stack->add($middleware1);
        $stack->add($middleware2);

        $this->assertCount(2, $stack->getMiddlewares());
    }

    public function testMiddlewareStackEmpty(): void
    {
        $stack = new MiddlewareStack();

        $this->assertTrue($stack->isEmpty());
    }
}
