<?php

namespace Helix\Tests\Core;

use PHPUnit\Framework\TestCase;
use Helix\Http\Psr15\Middleware\CacheMiddleware;
use Helix\Http\Psr7\ServerRequest;
use Helix\Http\Psr7\Response;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class CacheMiddlewareTest extends TestCase
{
    public function testCacheHeadersAreSet(): void
    {
        $middleware = new CacheMiddleware(60);
        $request = new ServerRequest('GET', '/');
        $handler = new class implements RequestHandlerInterface {
            public function handle($request): ResponseInterface
            {
                return new Response();
            }
        };
        $response = $middleware->process($request, $handler);
        $this->assertEquals('public, max-age=60', $response->getHeaderLine('Cache-Control'));
    }
}
